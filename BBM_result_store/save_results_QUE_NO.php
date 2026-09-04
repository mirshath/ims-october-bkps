<?php

include '../database/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['studentResults'])) {
        $selectedValue = $data['selectedValue'];
        $matchedColumn = $data['matchedColumn'];
        $ModuleGPA     = $data['moduleGPA_Q_NO'];

        // Get year_id and semester_id from the request
        $yearId = isset($data['year_id']) ? intval($data['year_id']) : null;
        $semesterId = isset($data['semester_id']) ? intval($data['semester_id']) : null;

        $examinerColumn = ($matchedColumn === 'examinor_1') ? 'examiner1_marks' : 'examiner2_marks';
        $marksColumn = ($matchedColumn === 'examinor_1') ? '100marksEx1' : '100marksEx2';
        $finalColumn = ($matchedColumn === 'examinor_1') ? 'final_result' : 'final_result_ex2';

        $insertedData = [];
        $updatedData = [];
        $errorData = [];

        // Start transaction for data integrity
        $conn->begin_transaction();

        try {
            foreach ($data['studentResults'] as $studentResult) {
                $studentCode = $studentResult['studentCode'];
                $studentRegistrationID = $studentResult['studentId'];
                $converted = floatval($studentResult['converted']);
                $result100 = floatval($studentResult['result']);
                $questionNo = 'fullmarks';
                $que_no = 'WITH_QUE_NO';

                $marksValue = $result100;

                // Store old value for calculating difference
                $oldValue = 0;

                // 1. Check if the record exists in bbm_result_details and get old value
                $checkQuery = "SELECT id, $examinerColumn FROM bbm_result_details WHERE student_id = ? AND program_id = ? AND batch_id = ? AND module_id = ? AND year_id = ? AND semester_id = ? AND main_comp_id = ? AND question_no = ?";
                if ($checkStmt = $conn->prepare($checkQuery)) {
                    $checkStmt->bind_param("iiiiiiis", $studentCode, $data['programmeId'], $data['batchId'], $data['moduleId'], $yearId, $semesterId, $data['mainComponentId'], $questionNo);
                    $checkStmt->execute();
                    $result = $checkStmt->get_result();

                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $oldValue = floatval($row[$examinerColumn]);
                        $recordId = $row['id'];

                        // UPDATE existing result_details
                        $updateQuery = "UPDATE bbm_result_details 
                                       SET $examinerColumn = ?, 
                                           $marksColumn = ?, 
                                           status = 'Pending' 
                                       WHERE id = ?";

                        if ($updateStmt = $conn->prepare($updateQuery)) {
                            $updateStmt->bind_param(
                                "ddi",
                                $converted,
                                $marksValue,
                                $recordId
                            );
                            
                            if ($updateStmt->execute()) {
                                $updatedData[] = [
                                    'studentCode' => $studentCode,
                                    'table' => 'bbm_result_details',
                                    'action' => 'updated',
                                    'id' => $recordId
                                ];
                            } else {
                                throw new Exception("Failed to update bbm_result_details: " . $conn->error);
                            }
                            $updateStmt->close();
                        } else {
                            throw new Exception("Failed to prepare update statement for bbm_result_details: " . $conn->error);
                        }
                    } else {
                        // INSERT new result_details
                        $insertQuery = "INSERT INTO bbm_result_details 
                            (student_id, program_id, batch_id, module_id, year_id, semester_id, main_comp_id, with_Ques, question_no, $examinerColumn, $marksColumn, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";

                        if ($insertStmt = $conn->prepare($insertQuery)) {
                            $insertStmt->bind_param(
                                "iiiiiiissdd",
                                $studentCode,
                                $data['programmeId'],
                                $data['batchId'],
                                $data['moduleId'],
                                $yearId,
                                $semesterId,
                                $data['mainComponentId'],
                                $selectedValue,
                                $questionNo,
                                $converted,
                                $marksValue
                            );
                            
                            if ($insertStmt->execute()) {
                                $insertedData[] = [
                                    'studentCode' => $studentCode,
                                    'table' => 'bbm_result_details',
                                    'action' => 'inserted',
                                    'id' => $conn->insert_id
                                ];
                            } else {
                                throw new Exception("Failed to insert into bbm_result_details: " . $conn->error);
                            }
                            $insertStmt->close();
                        } else {
                            throw new Exception("Failed to prepare insert statement for bbm_result_details: " . $conn->error);
                        }
                    }

                    $checkStmt->close();
                } else {
                    throw new Exception("Failed to prepare check statement for bbm_result_details: " . $conn->error);
                }

                // Calculate the difference between new and old values
                $valueDifference = $converted - $oldValue;

                // 2. INSERT or UPDATE final results
                $studentRegCode = $studentCode; // Or use different reg code
                $subComponentId = 0;

                // Store old final result values
                $oldFinalValue = 0;

                // Check if final result row already exists and get old values
                $checkFinalQuery = "SELECT id, $finalColumn FROM bbm_final_results_tbl WHERE student_id = ? AND program_id = ? AND batch_id = ? AND module_id = ? AND year_id = ? AND semester_id = ? AND main_comp_id = ? AND que_no = ?";
                if ($checkFinalStmt = $conn->prepare($checkFinalQuery)) {
                    $checkFinalStmt->bind_param(
                        "iiiiiiis",
                        $studentCode,
                        $data['programmeId'],
                        $data['batchId'],
                        $data['moduleId'],
                        $yearId,
                        $semesterId,
                        $data['mainComponentId'],
                        $que_no
                    );
                    $checkFinalStmt->execute();
                    $finalResult = $checkFinalStmt->get_result();

                    if ($finalResult->num_rows > 0) {
                        $finalRow = $finalResult->fetch_assoc();
                        $oldFinalValue = floatval($finalRow[$finalColumn]);
                        $finalRecordId = $finalRow['id'];

                        // Update final_results_tbl
                        $updateFinalQuery = "UPDATE bbm_final_results_tbl 
                                            SET $finalColumn = ? 
                                            WHERE id = ?";

                        if ($updateFinalStmt = $conn->prepare($updateFinalQuery)) {
                            $updateFinalStmt->bind_param(
                                "di",
                                $converted,
                                $finalRecordId
                            );
                            
                            if ($updateFinalStmt->execute()) {
                                $updatedData[] = [
                                    'studentCode' => $studentCode,
                                    'table' => 'bbm_final_results_tbl',
                                    'action' => 'updated',
                                    'id' => $finalRecordId
                                ];
                            } else {
                                throw new Exception("Failed to update bbm_final_results_tbl: " . $conn->error);
                            }
                            $updateFinalStmt->close();
                        } else {
                            throw new Exception("Failed to prepare update statement for bbm_final_results_tbl: " . $conn->error);
                        }
                    } else {
                        // Insert into final_results_tbl
                        $finalResultValue = ($matchedColumn === 'examinor_1') ? $converted : null;
                        $finalResultEx2Value = ($matchedColumn === 'examinor_2') ? $converted : null;

                        $insertFinalQuery = "INSERT INTO bbm_final_results_tbl 
                            (student_id, student_reg_code, program_id, batch_id, module_id, year_id, semester_id, main_comp_id, sub_comp_id, final_result, final_result_ex2, que_no)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                        if ($insertFinalStmt = $conn->prepare($insertFinalQuery)) {
                            $insertFinalStmt->bind_param(
                                "isiiiiiiidds",
                                $studentCode,
                                $studentRegistrationID,
                                $data['programmeId'],
                                $data['batchId'],
                                $data['moduleId'],
                                $yearId,
                                $semesterId,
                                $data['mainComponentId'],
                                $subComponentId,
                                $finalResultValue,
                                $finalResultEx2Value,
                                $que_no
                            );
                            
                            if ($insertFinalStmt->execute()) {
                                $insertedData[] = [
                                    'studentCode' => $studentCode,
                                    'table' => 'bbm_final_results_tbl',
                                    'action' => 'inserted',
                                    'id' => $conn->insert_id
                                ];
                            } else {
                                throw new Exception("Failed to insert into bbm_final_results_tbl: " . $conn->error);
                            }
                            $insertFinalStmt->close();
                        } else {
                            throw new Exception("Failed to prepare insert statement for bbm_final_results_tbl: " . $conn->error);
                        }
                    }

                    // Calculate the difference for final results
                    $finalDifference = $converted - $oldFinalValue;

                    // Now handle the total_mod_rslt table
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
                    
                    if ($checkTotalStmt = $conn->prepare($checkTotalQuery)) {
                        $checkTotalStmt->bind_param(
                            "iiiiii",
                            $studentCode,
                            $data['programmeId'],
                            $data['batchId'],
                            $data['moduleId'],
                            $yearId,
                            $semesterId
                        );
                        $checkTotalStmt->execute();
                        $checkTotalResult = $checkTotalStmt->get_result();

                        // Convert studentRegCode to integer if needed
                        $studentRegInt = intval($studentRegCode);

                        if ($checkTotalResult->num_rows > 0) {
                            // Record exists, fetch current values
                            $totalRow = $checkTotalResult->fetch_assoc();
                            $totalRecordId = $totalRow['id'];
                            $currentEx1Total = floatval($totalRow['ex1_total_rslt']);
                            $currentEx2Total = floatval($totalRow['ex2_total_rslt']);

                            // Determine which value to update based on matched column
                            if ($matchedColumn === 'examinor_1') {
                                // For examiner 1, directly set the new value instead of adding difference
                                $newEx1Total = $converted;
                                $newEx2Total = $currentEx2Total; // Keep the same
                            } else {
                                // For examiner 2, directly set the new value instead of adding difference
                                $newEx2Total = $converted;
                                $newEx1Total = $currentEx1Total; // Keep the same
                            }

                            // Calculate the average total
                            $avgTotal = ($newEx1Total + $newEx2Total) / 2;

                            // Determine grade and grade value
                            list($grade, $gradeValue) = calculateGradeAndValue($avgTotal);

                            // Calculate CGP (Credit Grade Point)
                            $cgp = $gradeValue * $ModuleGPA;

                            if ($matchedColumn === 'examinor_1') {
                                $updateTotalQuery = "
                                    UPDATE total_mod_rslt 
                                    SET ex1_total_rslt = ?,
                                        total = ?,
                                        grades = ?,
                                        GV = ?,
                                        CGP = ?
                                    WHERE id = ?
                                ";
                                
                                if ($updateTotalStmt = $conn->prepare($updateTotalQuery)) {
                                    $updateTotalStmt->bind_param(
                                        "ddsddi",
                                        $newEx1Total,
                                        $avgTotal,
                                        $grade,
                                        $gradeValue,
                                        $cgp,
                                        $totalRecordId
                                    );
                                    
                                    if ($updateTotalStmt->execute()) {
                                        $updatedData[] = [
                                            'studentCode' => $studentCode,
                                            'table' => 'total_mod_rslt',
                                            'action' => 'updated ex1',
                                            'id' => $totalRecordId
                                        ];
                                    } else {
                                        throw new Exception("Failed to update total_mod_rslt for ex1: " . $conn->error);
                                    }
                                    $updateTotalStmt->close();
                                } else {
                                    throw new Exception("Failed to prepare update statement for total_mod_rslt ex1: " . $conn->error);
                                }
                            } else {
                                $updateTotalQuery = "
                                    UPDATE total_mod_rslt 
                                    SET ex2_total_rslt = ?,
                                        total = ?,
                                        grades = ?,
                                        GV = ?,
                                        CGP = ?
                                    WHERE id = ?
                                ";
                                
                                if ($updateTotalStmt = $conn->prepare($updateTotalQuery)) {
                                    $updateTotalStmt->bind_param(
                                        "ddsddi",
                                        $newEx2Total,
                                        $avgTotal,
                                        $grade,
                                        $gradeValue,
                                        $cgp,
                                        $totalRecordId
                                    );
                                    
                                    if ($updateTotalStmt->execute()) {
                                        $updatedData[] = [
                                            'studentCode' => $studentCode,
                                            'table' => 'total_mod_rslt',
                                            'action' => 'updated ex2',
                                            'id' => $totalRecordId
                                        ];
                                    } else {
                                        throw new Exception("Failed to update total_mod_rslt for ex2: " . $conn->error);
                                    }
                                    $updateTotalStmt->close();
                                } else {
                                    throw new Exception("Failed to prepare update statement for total_mod_rslt ex2: " . $conn->error);
                                }
                            }
                        } else {
                            // No record exists, insert a new one
                            // Initialize values based on matched column
                            $ex1Total = ($matchedColumn === 'examinor_1') ? $converted : 0;
                            $ex2Total = ($matchedColumn === 'examinor_2') ? $converted : 0;

                            // Calculate the average total
                            $avgTotal = ($ex1Total + $ex2Total) / 2;

                            // Determine grade and grade value
                            list($grade, $gradeValue) = calculateGradeAndValue($avgTotal);

                            // Calculate CGP (Credit Grade Point)
                            $cgp = $gradeValue * $ModuleGPA;

                            $insertTotalQuery = "
                                INSERT INTO total_mod_rslt
                                    (std_id, std_reg_no, prog_id, batch_id, module_id, year_id, semester_id, module_gpa_value, ex1_total_rslt, ex2_total_rslt, total, grades, GV, CGP)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ";

                            if ($insertTotalStmt = $conn->prepare($insertTotalQuery)) {
                                $insertTotalStmt->bind_param(
                                    "isiiiiiddddsdd",
                                    $studentCode,
                                    $studentRegistrationID,
                                    $data['programmeId'],
                                    $data['batchId'],
                                    $data['moduleId'],
                                    $yearId,
                                    $semesterId,
                                    $ModuleGPA,
                                    $ex1Total,
                                    $ex2Total,
                                    $avgTotal,
                                    $grade,
                                    $gradeValue,
                                    $cgp
                                );
                                
                                if ($insertTotalStmt->execute()) {
                                    $insertedData[] = [
                                        'studentCode' => $studentCode,
                                        'table' => 'total_mod_rslt',
                                        'action' => 'inserted',
                                        'id' => $conn->insert_id
                                    ];
                                } else {
                                    throw new Exception("Failed to insert into total_mod_rslt: " . $conn->error);
                                }
                                $insertTotalStmt->close();
                            } else {
                                throw new Exception("Failed to prepare insert statement for total_mod_rslt: " . $conn->error);
                            }
                        }
                        $checkTotalStmt->close();
                    } else {
                        throw new Exception("Failed to prepare check statement for total_mod_rslt: " . $conn->error);
                    }

                    // 3. Update GPA_calculate_tbl
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
                    
                    if ($sumGpaStmt = $conn->prepare($sumGpaQuery)) {
                        $sumGpaStmt->bind_param(
                            "iiiii",
                            $studentCode,
                            $data['programmeId'],
                            $data['batchId'],
                            $yearId,
                            $semesterId
                        );
                        $sumGpaStmt->execute();
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
                        
                        if ($checkGpaStmt = $conn->prepare($checkGpaQuery)) {
                            $checkGpaStmt->bind_param(
                                "iiiii",
                                $studentCode,
                                $data['programmeId'],
                                $data['batchId'],
                                $yearId,
                                $semesterId
                            );
                            $checkGpaStmt->execute();
                            $checkGpaResult = $checkGpaStmt->get_result();

                            if ($checkGpaResult->num_rows > 0) {
                                // Update existing record
                                $gpaRow = $checkGpaResult->fetch_assoc();
                                $gpaRecordId = $gpaRow['id'];
                                
                                $updateGpaQuery = "
                                    UPDATE GPA_calculate_tbl
                                    SET 
                                        total_credit_value = ?,
                                        total_CGP_value = ?,
                                        final_GPA_value = ?
                                    WHERE id = ?
                                ";
                                
                                if ($updateGpaStmt = $conn->prepare($updateGpaQuery)) {
                                    $updateGpaStmt->bind_param(
                                        "dddi",
                                        $totalCreditValue,
                                        $totalCGPValue,
                                        $finalGPAValue,
                                        $gpaRecordId
                                    );
                                    
                                    if ($updateGpaStmt->execute()) {
                                        $updatedData[] = [
                                            'studentCode' => $studentCode,
                                            'table' => 'GPA_calculate_tbl',
                                            'action' => 'updated',
                                            'id' => $gpaRecordId
                                        ];
                                    } else {
                                        throw new Exception("Failed to update GPA_calculate_tbl: " . $conn->error);
                                    }
                                    $updateGpaStmt->close();
                                } else {
                                    throw new Exception("Failed to prepare update statement for GPA_calculate_tbl: " . $conn->error);
                                }
                            } else {
                                // Insert new record
                                $insertGpaQuery = "
                                    INSERT INTO GPA_calculate_tbl
                                        (std_id, std_reg_no, prog_id, batch_id, year_id, semester_id, total_credit_value, total_CGP_value, final_GPA_value)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                                ";
                                
                                if ($insertGpaStmt = $conn->prepare($insertGpaQuery)) {
                                    $insertGpaStmt->bind_param(
                                        "isiiiiddd",
                                        $studentCode,
                                        $studentRegistrationID,
                                        $data['programmeId'],
                                        $data['batchId'],
                                        $yearId,
                                        $semesterId,
                                        $totalCreditValue,
                                        $totalCGPValue,
                                        $finalGPAValue
                                    );
                                    
                                    if ($insertGpaStmt->execute()) {
                                        $insertedData[] = [
                                            'studentCode' => $studentCode,
                                            'table' => 'GPA_calculate_tbl',
                                            'action' => 'inserted',
                                            'id' => $conn->insert_id
                                        ];
                                    } else {
                                        throw new Exception("Failed to insert into GPA_calculate_tbl: " . $conn->error);
                                    }
                                    $insertGpaStmt->close();
                                } else {
                                    throw new Exception("Failed to prepare insert statement for GPA_calculate_tbl: " . $conn->error);
                                }
                            }
                            $checkGpaStmt->close();
                        } else {
                            throw new Exception("Failed to prepare check statement for GPA_calculate_tbl: " . $conn->error);
                        }
                    } else {
                        throw new Exception("Failed to prepare sum statement for GPA calculation: " . $conn->error);
                    }
                    
                    $checkFinalStmt->close();
                } else {
                    throw new Exception("Failed to prepare check statement for bbm_final_results_tbl: " . $conn->error);
                }
            }

            // Commit the transaction if everything went well
            $conn->commit();

            // Final response
            echo json_encode([
                'status' => 'success',
                'insertedData' => $insertedData,
                'updatedData' => $updatedData
            ]);
            
        } catch (Exception $e) {
            // Rollback the transaction if an error occurred
            $conn->rollback();
            
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'errorData' => $errorData
            ]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No student results received']);
    }
} else {
    echo json_encode(['error' => 'Invalid request or content type']);
}

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
