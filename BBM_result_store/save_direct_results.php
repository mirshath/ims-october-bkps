<?php
include '../database/connection.php';
header('Content-Type: application/json');

// Enable error logging for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log the raw input
$rawInput = file_get_contents('php://input');
error_log("Raw input: " . $rawInput);

// Only handle JSON POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Decode incoming JSON
$data = json_decode($rawInput, true);
error_log("Decoded data: " . print_r($data, true));

if (!isset($data['studentResults']) || !is_array($data['studentResults'])) {
    error_log("Invalid or missing studentResults");
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing studentResults']);
    exit;
}

// Log key parameters
error_log("moduleGPA_Q_Direct: " . (isset($data['moduleGPA_Q_Direct']) ? $data['moduleGPA_Q_Direct'] : 'not set'));
error_log("year_id: " . (isset($data['year_id']) ? $data['year_id'] : 'not set'));
error_log("semester_id: " . (isset($data['semester_id']) ? $data['semester_id'] : 'not set'));
error_log("Number of student results: " . count($data['studentResults']));

$insertedData = [];
$conn->begin_transaction();

try {
    // Get year_id and semester_id from the request
    $yearId = isset($data['year_id']) ? intval($data['year_id']) : null;
    $semesterId = isset($data['semester_id']) ? intval($data['semester_id']) : null;

    error_log("Using year_id: $yearId, semester_id: $semesterId");

    foreach ($data['studentResults'] as $index => $student) {
        error_log("Processing student " . ($index + 1) . ": " . print_r($student, true));

        // 1. Extract and sanitize inputs
        $studentId        = intval($student['student_code']);
        $studentRegId     = $conn->real_escape_string($student['student_registration_id']);
        $programId        = intval($student['program_id']);
        $batchId          = intval($student['batch_id']);
        $moduleId         = intval($student['module_id']);
        $mainComponentId  = intval($student['main_component_id']);
        $subComponentId   = intval($student['sub_component_id']);
        $studentMarks     = floatval($student['result']);       // student's scored marks
        $fullMarks        = floatval($student['final_result']); // full marks for the sub-component
        $questionNo       = 'direct_fullmarks';
        $ModuleGPA        = $data['moduleGPA_Q_Direct'];

        // 2. Check if record exists in bbm_direct_result
        $check = $conn->prepare(
            "SELECT id, final_marks
               FROM bbm_direct_result
              WHERE student_id=?
                AND program_id=?
                AND batch_id=?
                AND module_id=?
                AND year_id=?
                AND semester_id=?
                AND main_comp_id=?
                AND sub_component_id=?
                AND question_no=?"
        );
        $check->bind_param(
            "iiiiiiiis",
            $studentId,
            $programId,
            $batchId,
            $moduleId,
            $yearId,
            $semesterId,
            $mainComponentId,
            $subComponentId,
            $questionNo
        );
        $check->execute();
        $result = $check->get_result();

        // Store the old value before updating (for calculating the difference)
        $oldValue = 0;
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $oldValue = floatval($row['final_marks']);
        }
        $check->close();

        // 3a. UPDATE existing bbm_direct_result
        if ($result->num_rows > 0) {
            $upd = $conn->prepare(
                "UPDATE bbm_direct_result
                    SET examiner1_marks = ?,
                        100marksEx1      = ?,
                        examiner2_marks = ?,
                        100marksEx2      = ?,
                        final_marks      = ?,
                        status           = 'Pending'
                  WHERE student_id=?
                    AND program_id=?
                    AND batch_id=?
                    AND module_id=?
                    AND year_id=?
                    AND semester_id=?
                    AND main_comp_id=?
                    AND sub_component_id=?
                    AND question_no=?"
            );
            $upd->bind_param(
                "dsdsdiiiiiiiis",
                $fullMarks,
                $studentMarks,
                $fullMarks,
                $studentMarks,
                $fullMarks,
                $studentId,
                $programId,
                $batchId,
                $moduleId,
                $yearId,
                $semesterId,
                $mainComponentId,
                $subComponentId,
                $questionNo
            );
            $upd->execute();
            if ($upd->error) {
                error_log("SQL Error in UPDATE bbm_direct_result: " . $upd->error);
                throw new Exception("SQL Error in UPDATE bbm_direct_result: " . $upd->error);
            }
            $upd->close();

            $insertedData[] = ['student_id' => $studentId, 'status' => 'updated'];
        } else {
            // 3b. INSERT new into bbm_direct_result
            $ins = $conn->prepare(
                "INSERT INTO bbm_direct_result
                    (student_id, std_reg_id, program_id, batch_id, module_id, year_id, semester_id,
                     main_comp_id, sub_component_id, with_Ques, question_no,
                     examiner1_marks, 100marksEx1, examiner2_marks, 100marksEx2, final_marks, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '', ?, ?, ?, ?, ?, ?, 'Pending')"
            );
            $ins->bind_param(
                "isiiiiiiisdsdsd",
                $studentId,
                $studentRegId,
                $programId,
                $batchId,
                $moduleId,
                $yearId,
                $semesterId,
                $mainComponentId,
                $subComponentId,
                $questionNo,
                $fullMarks,
                $studentMarks,
                $fullMarks,
                $studentMarks,
                $fullMarks
            );
            $ins->execute();
            if ($ins->error) {
                error_log("SQL Error in INSERT bbm_direct_result: " . $ins->error);
                throw new Exception("SQL Error in INSERT bbm_direct_result: " . $ins->error);
            }
            $ins->close();

            $insertedData[] = ['student_id' => $studentId, 'status' => 'inserted'];
        }

        // 4. Re-compute the total of final_marks across all sub-components
        $sumStmt = $conn->prepare(
            "SELECT COALESCE(SUM(final_marks),0)
               FROM bbm_direct_result
              WHERE student_id=?
                AND program_id=?
                AND batch_id=?
                AND module_id=?
                AND year_id=?
                AND semester_id=?
                AND main_comp_id=?
                 "
        );
        $sumStmt->bind_param(
            "iiiiiii",
            $studentId,
            $programId,
            $batchId,
            $moduleId,
            $yearId,
            $semesterId,
            $mainComponentId
        );
        $sumStmt->execute();
        if ($sumStmt->error) {
            error_log("SQL Error in SUM query: " . $sumStmt->error);
            throw new Exception("SQL Error in SUM query: " . $sumStmt->error);
        }
        $sumStmt->bind_result($newTotal);
        $sumStmt->fetch();
        $sumStmt->close();

        // 5. Upsert into bbm_final_results_tbl
        $finalCheck = $conn->prepare(
            "SELECT id
               FROM bbm_final_results_tbl
              WHERE student_id=?
                AND program_id=?
                AND batch_id=?
                AND module_id=?
                AND year_id=?
                AND semester_id=?
                AND main_comp_id=?
                AND que_no=?
                 "
        );
        $finalCheck->bind_param(
            "iiiiiiis",
            $studentId,
            $programId,
            $batchId,
            $moduleId,
            $yearId,
            $semesterId,
            $mainComponentId,
            $questionNo
        );
        $finalCheck->execute();
        if ($finalCheck->error) {
            error_log("SQL Error in SELECT bbm_final_results_tbl: " . $finalCheck->error);
            throw new Exception("SQL Error in SELECT bbm_final_results_tbl: " . $finalCheck->error);
        }
        $finalCheck->store_result();

        if ($finalCheck->num_rows > 0) {
            // Update existing final_result
            $finalUpd = $conn->prepare(
                "UPDATE bbm_final_results_tbl
                    SET final_result = ?, 
                        final_result_ex2 = ?
                  WHERE student_id=?
                    AND program_id=?
                    AND batch_id=?
                    AND module_id=?
                    AND year_id=?
                    AND semester_id=?
                    AND main_comp_id=?
                    AND que_no=?
                    "
            );
            $finalUpd->bind_param(
                "ddiiiiiiis",
                $newTotal,
                $newTotal,
                $studentId,
                $programId,
                $batchId,
                $moduleId,
                $yearId,
                $semesterId,
                $mainComponentId,
                $questionNo
            );
            $finalUpd->execute();
            if ($finalUpd->error) {
                error_log("SQL Error in UPDATE bbm_final_results_tbl: " . $finalUpd->error);
                throw new Exception("SQL Error in UPDATE bbm_final_results_tbl: " . $finalUpd->error);
            }
            $finalUpd->close();
        } else {
            // Insert new final_result
            $finalIns = $conn->prepare(
                "INSERT INTO bbm_final_results_tbl
                    (student_id, student_reg_code, program_id, batch_id, module_id, year_id, semester_id, main_comp_id, sub_comp_id, final_result, final_result_ex2, que_no)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $finalIns->bind_param(
                "isiiiiiiidds",
                $studentId,
                $studentRegId,
                $programId,
                $batchId,
                $moduleId,
                $yearId,
                $semesterId,
                $mainComponentId,
                $subComponentId,
                $newTotal,
                $newTotal,
                $questionNo
            );
            $finalIns->execute();
            if ($finalIns->error) {
                error_log("SQL Error in INSERT bbm_final_results_tbl: " . $finalIns->error);
                throw new Exception("SQL Error in INSERT bbm_final_results_tbl: " . $finalIns->error);
            }
            $finalIns->close();
        }
        $finalCheck->close();

        // 6. Handle the total_mod_rslt table
        // Calculate the difference between new and old values
        $valueDifference = $fullMarks - $oldValue;

        // First check if a record already exists and get current values
        $checkTotalQuery = "
            SELECT id, ex1_total_rslt, ex2_total_rslt 
            FROM total_mod_rslt 
            WHERE std_id = ? 
            AND prog_id = ? 
            AND batch_id = ? 
            AND module_id = ?
            AND year_id = ?
            AND semester_id = ?
        ";
        $checkTotalStmt = $conn->prepare($checkTotalQuery);
        $checkTotalStmt->bind_param(
            "iiiiii",
            $studentId,
            $programId,
            $batchId,
            $moduleId,
            $yearId,
            $semesterId
        );
        $checkTotalStmt->execute();
        if ($checkTotalStmt->error) {
            error_log("SQL Error in SELECT total_mod_rslt: " . $checkTotalStmt->error);
            throw new Exception("SQL Error in SELECT total_mod_rslt: " . $checkTotalStmt->error);
        }
        $totalResult = $checkTotalStmt->get_result();

        // Convert studentRegId to integer if needed
        $studentRegInt = intval($studentRegId);

        if ($totalResult->num_rows > 0) {
            // Record exists, fetch current values
            $totalRow = $totalResult->fetch_assoc();
            $currentEx1Total = floatval($totalRow['ex1_total_rslt']);
            $currentEx2Total = floatval($totalRow['ex2_total_rslt']);

            // Add only the difference to existing values
            $newEx1Total = $currentEx1Total + $valueDifference;
            $newEx2Total = $currentEx2Total + $valueDifference;

            // Calculate the average total
            $avgTotal = ($newEx1Total + $newEx2Total) / 2;

            // Determine grade and grade value
            list($grade, $gradeValue) = calculateGradeAndValue($avgTotal);

            // Calculate CGP (Credit Grade Point)
            $cgp = $gradeValue * $ModuleGPA;

            // Update with correctly summed values
            $updateTotalQuery = "
                UPDATE total_mod_rslt 
                SET ex1_total_rslt = ?, 
                    ex2_total_rslt = ?,
                    total = ?,
                    grades = ?,
                    GV = ?,
                    CGP = ?
                WHERE std_id = ? 
                AND prog_id = ? 
                AND batch_id = ? 
                AND module_id = ?
                AND year_id = ?
                AND semester_id = ?
            ";
            $updateTotalStmt = $conn->prepare($updateTotalQuery);
            $updateTotalStmt->bind_param(
                "dddsddiiiiii",
                $newEx1Total,
                $newEx2Total,
                $avgTotal,
                $grade,
                $gradeValue,
                $cgp,
                $studentId,
                $programId,
                $batchId,
                $moduleId,
                $yearId,
                $semesterId
            );
            $updateTotalStmt->execute();
            if ($updateTotalStmt->error) {
                error_log("SQL Error in UPDATE total_mod_rslt: " . $updateTotalStmt->error);
                throw new Exception("SQL Error in UPDATE total_mod_rslt: " . $updateTotalStmt->error);
            }
            $updateTotalStmt->close();
        } else {
            // No record exists, insert a new one
            // Calculate the average total
            $avgTotal = $fullMarks; // Both ex1 and ex2 are the same (fullMarks)

            // Determine grade and grade value
            list($grade, $gradeValue) = calculateGradeAndValue($avgTotal);

            // Calculate CGP (Credit Grade Point)
            $cgp = $gradeValue * $ModuleGPA;

            $insertTotalQuery = "
                INSERT INTO total_mod_rslt
                    (std_id, std_reg_no, prog_id, batch_id, module_id, year_id, semester_id, module_gpa_value, ex1_total_rslt, ex2_total_rslt, total, grades, GV, CGP)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $insertTotalStmt = $conn->prepare($insertTotalQuery);
            $insertTotalStmt->bind_param(
                "isiiiiiddddsdd",
                $studentId,
                $studentRegId,
                $programId,
                $batchId,
                $moduleId,
                $yearId,
                $semesterId,
                $ModuleGPA,
                $fullMarks,  // For new records, just use the current value
                $fullMarks,
                $avgTotal,
                $grade,
                $gradeValue,
                $cgp
            );
            $insertTotalStmt->execute();
            if ($insertTotalStmt->error) {
                error_log("SQL Error in INSERT total_mod_rslt: " . $insertTotalStmt->error);
                throw new Exception("SQL Error in INSERT total_mod_rslt: " . $insertTotalStmt->error);
            }
            $insertTotalStmt->close();
        }

        $checkTotalStmt->close();

        // 7. Update GPA_calculate_tbl
        // First, calculate the sums from total_mod_rslt
        $sumGpaQuery = "
            SELECT 
                COALESCE(SUM(module_gpa_value), 0) as total_credit,
                COALESCE(SUM(CGP), 0) as total_cgp
            FROM 
                total_mod_rslt
            WHERE 
                std_id = ? 
                AND prog_id = ? 
                AND batch_id = ? 
                AND year_id = ? 
                AND semester_id = ?
            ";
        $sumGpaStmt = $conn->prepare($sumGpaQuery);
        $sumGpaStmt->bind_param(
            "iiiii",
            $studentId,
            $programId,
            $batchId,
            $yearId,
            $semesterId
        );
        $sumGpaStmt->execute();
        if ($sumGpaStmt->error) {
            error_log("SQL Error in SUM GPA query: " . $sumGpaStmt->error);
            throw new Exception("SQL Error in SUM GPA query: " . $sumGpaStmt->error);
        }
        $sumGpaResult = $sumGpaStmt->get_result();
        $sumRow = $sumGpaResult->fetch_assoc();
        $totalCreditValue = floatval($sumRow['total_credit']);
        $totalCGPValue = floatval($sumRow['total_cgp']);
        $sumGpaStmt->close();

        // Calculate final GPA value (avoid division by zero)
        $finalGPAValue = ($totalCreditValue > 0) ? ($totalCGPValue / $totalCreditValue) : 0;

        // Check if record exists in GPA_calculate_tbl
        $checkGpaQuery = "
        SELECT id
        FROM GPA_calculate_tbl
        WHERE 
            std_id = ? 
            AND prog_id = ? 
            AND batch_id = ? 
            AND year_id = ? 
            AND semester_id = ?
        ";
        $checkGpaStmt = $conn->prepare($checkGpaQuery);
        $checkGpaStmt->bind_param(
            "iiiii",
            $studentId,
            $programId,
            $batchId,
            $yearId,
            $semesterId
        );
        $checkGpaStmt->execute();
        if ($checkGpaStmt->error) {
            error_log("SQL Error in SELECT GPA_calculate_tbl: " . $checkGpaStmt->error);
            throw new Exception("SQL Error in SELECT GPA_calculate_tbl: " . $checkGpaStmt->error);
        }
        $checkGpaResult = $checkGpaStmt->get_result();

        if ($checkGpaResult->num_rows > 0) {
            // Update existing record
            $updateGpaQuery = "
        UPDATE GPA_calculate_tbl
        SET 
            total_credit_value = ?,
            total_CGP_value = ?,
            final_GPA_value = ?
        WHERE 
            std_id = ? 
            AND prog_id = ? 
            AND batch_id = ? 
            AND year_id = ? 
            AND semester_id = ?
    ";
            $updateGpaStmt = $conn->prepare($updateGpaQuery);
            $updateGpaStmt->bind_param(
                "dddiiiii",
                $totalCreditValue,
                $totalCGPValue,
                $finalGPAValue,
                $studentId,
                $programId,
                $batchId,
                $yearId,
                $semesterId
            );
            $updateGpaStmt->execute();
            if ($updateGpaStmt->error) {
                error_log("SQL Error in UPDATE GPA_calculate_tbl: " . $updateGpaStmt->error);
                throw new Exception("SQL Error in UPDATE GPA_calculate_tbl: " . $updateGpaStmt->error);
            }
            $updateGpaStmt->close();
        } else {
            // Insert new record
            $insertGpaQuery = "
            INSERT INTO GPA_calculate_tbl
                (std_id, std_reg_no, prog_id, batch_id, year_id, semester_id, total_credit_value, total_CGP_value, final_GPA_value)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
            $insertGpaStmt = $conn->prepare($insertGpaQuery);
            $insertGpaStmt->bind_param(
                "isiiiiddd",
                $studentId,
                $studentRegId,
                $programId,
                $batchId,
                $yearId,
                $semesterId,
                $totalCreditValue,
                $totalCGPValue,
                $finalGPAValue
            );
            $insertGpaStmt->execute();
            if ($insertGpaStmt->error) {
                error_log("SQL Error in INSERT GPA_calculate_tbl: " . $insertGpaStmt->error);
                throw new Exception("SQL Error in INSERT GPA_calculate_tbl: " . $insertGpaStmt->error);
            }
            $insertGpaStmt->close();
        }

        $checkGpaStmt->close();
        // -- 
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['status' => 'success', 'insertedData' => $insertedData]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Exception: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

exit;

/**
 * Calculate grade and grade value based on total marks
 * 
 * @param float $total The total marks
 * @return array Array containing [grade, gradeValue]
 */
function calculateGradeAndValue($total)
{
    // Determine grade based on total
    if ($total >= 85) {
        $grade = 'A+';
    } elseif ($total >= 70) {
        $grade = 'A';
    } elseif ($total >= 65) {
        $grade = 'A-';
    } elseif ($total >= 60) {
        $grade = 'B+';
    } elseif ($total >= 55) {
        $grade = 'B';
    } elseif ($total >= 50) {
        $grade = 'B-';
    } elseif ($total >= 45) {
        $grade = 'C+';
    } elseif ($total >= 40) {
        $grade = 'C';
    } elseif ($total >= 35) {
        $grade = 'C-';
    } elseif ($total >= 30) {
        $grade = 'D+';
    } elseif ($total >= 25) {
        $grade = 'D';
    } else {
        $grade = 'F';
    }

    // Determine grade value based on total
    if ($total >= 84.5) {
        $gradeValue = 4;
    } elseif ($total >= 69.5) {
        $gradeValue = 4;
    } elseif ($total >= 64.5) {
        $gradeValue = 3.7;
    } elseif ($total >= 59.5) {
        $gradeValue = 3.3;
    } elseif ($total >= 54.5) {
        $gradeValue = 3;
    } elseif ($total >= 49.5) {
        $gradeValue = 2.7;
    } elseif ($total >= 44.5) {
        $gradeValue = 2.3;
    } elseif ($total >= 39.5) {
        $gradeValue = 2;
    } elseif ($total >= 34.5) {
        $gradeValue = 1.7;
    } elseif ($total >= 29.5) {
        $gradeValue = 1.3;
    } elseif ($total >= 24.5) {
        $gradeValue = 1.3;
    } else {
        $gradeValue = 0;
    }

    return [$grade, $gradeValue];
}
