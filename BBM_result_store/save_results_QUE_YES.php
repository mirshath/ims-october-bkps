<?php
// save_results_QUE_YES.php
include '../database/connection.php';
// Only accept JSON POST requests
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_SERVER['CONTENT_TYPE']) &&
    $_SERVER['CONTENT_TYPE'] === 'application/json'
) {
    $jsonData = file_get_contents('php://input');
    $data     = json_decode($jsonData, true);

    if (!is_array($data)) {
        echo json_encode(['error' => 'Invalid data received']);
        exit;
    }

    // Extract parameters
    $selectedValue          = $data['selectedValue'];    // e.g. 'yes'
    $programmeId            = $data['programmeId'];
    $batchId                = $data['batchId'];
    $moduleId               = $data['moduleId'];
    $mainComponentId        = $data['mainComponentId'];
    $matchedColumn          = $data['matchedColumn'];    // 'examinor_1' or 'examinor_2'
    $students               = $data['students'];         // array of student records
    $main_comp_percent      = $data['mainComponentPercent'];
    $main_comp_percent      = (int)$main_comp_percent;
    $ModuleGPA              = $data['moduleGPA'];


    // Get year_id and semester_id from the request
    $yearId = isset($data['year_id']) ? intval($data['year_id']) : null;
    $semesterId = isset($data['semester_id']) ? intval($data['semester_id']) : null;

    // Determine which detail‑column and which final‑column to use
    if ($matchedColumn === 'examinor_1') {
        $detailCol = 'examiner1_marks';
        $finalCol  = 'final_result';
    } else {
        $detailCol = 'examiner2_marks';
        $finalCol  = 'final_result_ex2';
    }

    $insertedData = [];
    $updatedData  = [];

    foreach ($students as $student) {
        $studentCode = $student['studentCode'];
        $studentRegId = isset($student['studentRegistrationId']) ? $student['studentRegistrationId'] : '';

        // 1) Insert or update each question's marks
        $bbmResults = [
            $student['bbm_result_1'] ?? null,
            $student['bbm_result_2'] ?? null,
            $student['bbm_result_3'] ?? null,
            $student['bbm_result_4'] ?? null,
            $student['bbm_result_5'] ?? null,
            $student['bbm_result_6'] ?? null,
            $student['bbm_result_7'] ?? null,
            $student['bbm_result_8'] ?? null,
        ];

        foreach ($bbmResults as $index => $mark) {
            if ($mark === null) {
                continue;
            }
            $questionNo = 'Q' . ($index + 1);

            // Check if detail row exists and get old value
            $checkQuery = "
                SELECT id, {$detailCol}
                  FROM bbm_result_details
                 WHERE student_id   = ?
                   AND program_id   = ?
                   AND batch_id     = ?
                   AND module_id    = ?
                   AND year_id      = ?
                   AND semester_id  = ?
                   AND main_comp_id = ?
                   AND question_no  = ?
            ";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param(
                "iiiiiiis",
                $studentCode,
                $programmeId,
                $batchId,
                $moduleId,
                $yearId,
                $semesterId,
                $mainComponentId,
                $questionNo
            );
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            $oldMark = 0;
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $oldMark = floatval($row[$detailCol]);

                // Update existing detail row
                $updateQuery = "
                    UPDATE bbm_result_details
                       SET {$detailCol} = ?
                     WHERE student_id   = ?
                       AND program_id   = ?
                       AND batch_id     = ?
                       AND module_id    = ?
                       AND year_id      = ?
                       AND semester_id  = ?
                       AND main_comp_id = ?
                       AND question_no  = ?
                ";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param(
                    "diiiiiiis",
                    $mark,
                    $studentCode,
                    $programmeId,
                    $batchId,
                    $moduleId,
                    $yearId,
                    $semesterId,
                    $mainComponentId,
                    $questionNo
                );
                $updateStmt->execute();
                $updateStmt->close();

                $updatedData[] = [
                    'studentCode' => $studentCode,
                    'questionNo'  => $questionNo,
                    'newMark'     => $mark,
                ];
            } else {
                // Insert new detail row
                $insertQuery = "
                    INSERT INTO bbm_result_details
                        (student_id, program_id, batch_id, module_id, year_id, semester_id,
                         main_comp_id, with_Ques, question_no,
                         {$detailCol}, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
                ";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->bind_param(
                    "iiiiiiissd",
                    $studentCode,
                    $programmeId,
                    $batchId,
                    $moduleId,
                    $yearId,
                    $semesterId,
                    $mainComponentId,
                    $selectedValue,
                    $questionNo,
                    $mark
                );
                $insertStmt->execute();
                $insertStmt->close();

                $insertedData[] = [
                    'studentCode' => $studentCode,
                    'questionNo'  => $questionNo,
                    'mark'        => $mark,
                ];
            }

            $checkStmt->close();
        }

        // 2) Compute the total for this student+component
        $sumQuery = "
            SELECT COALESCE(SUM({$detailCol}), 0) AS total
              FROM bbm_result_details
             WHERE student_id   = ?
               AND program_id   = ?
               AND batch_id     = ?
               AND module_id    = ?
               AND year_id      = ?
               AND semester_id  = ?
               AND main_comp_id = ?
        ";
        $sumStmt = $conn->prepare($sumQuery);
        $sumStmt->bind_param(
            "iiiiiii",
            $studentCode,
            $programmeId,
            $batchId,
            $moduleId,
            $yearId,
            $semesterId,
            $mainComponentId
        );
        $sumStmt->execute();
        $sumStmt->bind_result($totalMarks);
        $sumStmt->fetch();
        $totalMarks = ($totalMarks / 100) * $main_comp_percent;
        $sumStmt->close();

        // 3) Upsert the sum into bbm_final_results_tbl
        //    (use sub_comp_id = 0 and que_no = 'TOTAL')
        $subCompId = 0;
        $queNo     = 'With_QUES_YES';

        // Get old final result value
        $oldFinalValue = 0;
        $checkFinalQuery = "
            SELECT id, {$finalCol}
              FROM bbm_final_results_tbl
             WHERE student_id   = ?
               AND program_id   = ?
               AND batch_id     = ?
               AND module_id    = ?
               AND year_id      = ?
               AND semester_id  = ?
               AND main_comp_id = ?
        ";
        $chkFinalStmt = $conn->prepare($checkFinalQuery);
        $chkFinalStmt->bind_param(
            "iiiiiii",
            $studentCode,
            $programmeId,
            $batchId,
            $moduleId,
            $yearId,
            $semesterId,
            $mainComponentId
        );
        $chkFinalStmt->execute();
        $finalResult = $chkFinalStmt->get_result();

        if ($finalResult->num_rows > 0) {
            $finalRow = $finalResult->fetch_assoc();
            $oldFinalValue = floatval($finalRow[$finalCol]);

            // Update existing final row
            $updateFinalQuery = "
                UPDATE bbm_final_results_tbl
                   SET {$finalCol} = ?
                 WHERE student_id   = ?
                   AND program_id   = ?
                   AND batch_id     = ?
                   AND module_id    = ?
                   AND year_id      = ?
                   AND semester_id  = ?
                   AND main_comp_id = ?
            ";
            $updFinalStmt = $conn->prepare($updateFinalQuery);
            $updFinalStmt->bind_param(
                "diiiiiii",
                $totalMarks,
                $studentCode,
                $programmeId,
                $batchId,
                $moduleId,
                $yearId,
                $semesterId,
                $mainComponentId
            );
            $updFinalStmt->execute();
            $updFinalStmt->close();
        } else {
            // Insert new final row
            $insertFinalQuery = "
                INSERT INTO bbm_final_results_tbl
                    (student_id,student_reg_code, program_id, batch_id, module_id, year_id, semester_id,
                     main_comp_id, sub_comp_id, {$finalCol}, que_no)
                VALUES (?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $insFinalStmt = $conn->prepare($insertFinalQuery);
            $insFinalStmt->bind_param(
                "isiiiiiiids",
                $studentCode,
                $studentRegId,
                $programmeId,
                $batchId,
                $moduleId,
                $yearId,
                $semesterId,
                $mainComponentId,
                $subCompId,
                $totalMarks,
                $queNo
            );
            $insFinalStmt->execute();
            $insFinalStmt->close();
        }

        $chkFinalStmt->close();

        // Calculate the difference between new and old final values
        $finalDifference = $totalMarks - $oldFinalValue;

        // 4) Insert or update the total_mod_rslt table
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
            $studentCode,
            $programmeId,
            $batchId,
            $moduleId,
            $yearId,
            $semesterId
        );
        $checkTotalStmt->execute();
        $totalResult = $checkTotalStmt->get_result();

        // Convert studentRegId to integer if needed
        $studentRegIdInt = intval($studentRegId);

        if ($totalResult->num_rows > 0) {
            // Record exists, fetch current values
            $totalRow = $totalResult->fetch_assoc();
            $currentEx1Total = floatval($totalRow['ex1_total_rslt']);
            $currentEx2Total = floatval($totalRow['ex2_total_rslt']);

            // Update based on which examiner is submitting
            if ($matchedColumn === 'examinor_1') {
                // Add only the difference to existing value for examiner 1
                $newEx1Total = $currentEx1Total + $finalDifference;
                $newEx2Total = $currentEx2Total; // Keep the same

                // Calculate the average total
                $avgTotal = ($newEx1Total + $newEx2Total) / 2;

                // Determine grade and grade value
                list($grade, $gradeValue) = calculateGradeAndValue($avgTotal);

                // Calculate CGP (Credit Grade Point)
                $cgp = $gradeValue * $ModuleGPA;

                $updateTotalQuery = "
                    UPDATE total_mod_rslt 
                    SET ex1_total_rslt = ?,
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
                    "ddsddiiiiii",
                    $newEx1Total,
                    $avgTotal,
                    $grade,
                    $gradeValue,
                    $cgp,
                    $studentCode,
                    $programmeId,
                    $batchId,
                    $moduleId,
                    $yearId,
                    $semesterId
                );
            } else {
                // Add only the difference to existing value for examiner 2
                $newEx2Total = $currentEx2Total + $finalDifference;
                $newEx1Total = $currentEx1Total; // Keep the same

                // Calculate the average total
                $avgTotal = ($newEx1Total + $newEx2Total) / 2;

                // Determine grade and grade value
                list($grade, $gradeValue) = calculateGradeAndValue($avgTotal);

                // Calculate CGP (Credit Grade Point)
                $cgp = $gradeValue * $ModuleGPA;

                $updateTotalQuery = "
                    UPDATE total_mod_rslt 
                    SET ex2_total_rslt = ?,
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
                    "ddsddiiiiii",
                    $newEx2Total,
                    $avgTotal,
                    $grade,
                    $gradeValue,
                    $cgp,
                    $studentCode,
                    $programmeId,
                    $batchId,
                    $moduleId,
                    $yearId,
                    $semesterId
                );
            }
            $updateTotalStmt->execute();
            $updateTotalStmt->close();
        } else {
            // Insert new record
            // Initialize both examiner results to 0
            $ex1Result = 0;
            $ex2Result = 0;

            // Set the appropriate examiner's result
            if ($matchedColumn === 'examinor_1') {
                $ex1Result = $totalMarks;
            } else {
                $ex2Result = $totalMarks;
            }

            // Calculate the average total
            $avgTotal = ($ex1Result + $ex2Result) / 2;

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
                $studentCode,
                $studentRegId,
                $programmeId,
                $batchId,
                $moduleId,
                $yearId,
                $semesterId,
                $ModuleGPA,
                $ex1Result,
                $ex2Result,
                $avgTotal,
                $grade,
                $gradeValue,
                $cgp
            );
            $insertTotalStmt->execute();
            $insertTotalStmt->close();
        }

        $checkTotalStmt->close();
        
        // 5. Update GPA_calculate_tbl
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
            $studentCode,
            $programmeId,
            $batchId,
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
        $checkGpaStmt = $conn->prepare($checkGpaQuery);
        $checkGpaStmt->bind_param(
            "iiiii",
            $studentCode,
            $programmeId,
            $batchId,
            $yearId,
            $semesterId
        );
        $checkGpaStmt->execute();
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
                $studentCode,
                $programmeId,
                $batchId,
                $yearId,
                $semesterId
            );
            $updateGpaStmt->execute();
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
                $studentCode,
                $studentRegId,
                $programmeId,
                $batchId,
                $yearId,
                $semesterId,
                $totalCreditValue,
                $totalCGPValue,
                $finalGPAValue
            );
            $insertGpaStmt->execute();
            $insertGpaStmt->close();
        }

        $checkGpaStmt->close();
    }

    $conn->close();

    // Return result
    echo json_encode([
        'status'       => 'success',
        'insertedData' => $insertedData,
        'updatedData'  => $updatedData
    ]);
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
    } elseif ($total >= 50) { // 50- 54
        $grade = 'B-';
    } elseif ($total >= 45) { // 45 - 49
        $grade = 'C+';
    } elseif ($total >= 40) { //40-44
        $grade = 'C';
    } elseif ($total >= 35) {  // 35 - 39
        $grade = 'C-';
    } elseif ($total >= 30) { // 30 - 34
        $grade = 'D+';
    } elseif ($total >= 25) {  // 25 - 29 
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