<?php
include('database/connection.php');
/** @var mysqli $conn */

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $programme_id = $_GET['programme_id'] ?? '';
    $batch_id = $_GET['batch_id'] ?? '';
    $module_id = $_GET['module_id'] ?? '';
    $lecturer_id = $_GET['lecturer_id'] ?? '';

    $program_name = '';
    $batch_name = '';
    $module_name = '';
    $lecturer_name = '';
    $filled_count = 0;
    $rating_categories = [];
    $comments_list = [];

    $link_ids = [];
    $link_sql = "SELECT id FROM feedback_links 
                 WHERE programme_id = ? AND batch_id = ? AND module_id = ? AND lecturer_id = ? AND active = 1";
    $link_stmt = $conn->prepare($link_sql);
    $link_stmt->bind_param("ssss", $programme_id, $batch_id, $module_id, $lecturer_id);
    $link_stmt->execute();
    $link_result = $link_stmt->get_result();
    while ($row = $link_result->fetch_assoc()) {
        $link_ids[] = (int)$row['id'];
    }
    $link_stmt->close();

    if (!empty($link_ids)) {
        $link_placeholders = implode(',', array_fill(0, count($link_ids), '?'));
        $submission_ids = [];

        $sub_sql = "SELECT id, program_name, module_name, lecturer_name
                    FROM feedback_submissions 
                    WHERE link_id IN ($link_placeholders)
                    ORDER BY submitted_at DESC";
        $sub_stmt = $conn->prepare($sub_sql);
        $types = str_repeat('i', count($link_ids));
        $sub_stmt->bind_param($types, ...$link_ids);
        $sub_stmt->execute();
        $sub_result = $sub_stmt->get_result();
        $first_row = true;
        while ($row = $sub_result->fetch_assoc()) {
            $submission_ids[] = (int)$row['id'];
            if ($first_row) {
                $program_name = $row['program_name'];
                $module_name = $row['module_name'];
                $lecturer_name = $row['lecturer_name'];
                $first_row = false;
            }
        }
        $sub_stmt->close();

        $filled_count = count($submission_ids);

        if (!empty($submission_ids)) {
            $batch_sql = "SELECT batch_name FROM batch_table WHERE id = ? LIMIT 1";
            $batch_stmt = $conn->prepare($batch_sql);
            $batch_id_int = (int)$batch_id;
            $batch_stmt->bind_param("i", $batch_id_int);
            $batch_stmt->execute();
            $batch_result = $batch_stmt->get_result();
            if ($brow = $batch_result->fetch_assoc()) {
                $batch_name = $brow['batch_name'];
            }
            $batch_stmt->close();

            $sub_placeholders = implode(',', array_fill(0, count($submission_ids), '?'));
            $answers_sql = "SELECT a.field_label, a.field_type, a.answer_value
                            FROM feedback_submission_answers a
                            WHERE a.submission_id IN ($sub_placeholders)
                            ORDER BY a.field_label, a.id";
            $ans_stmt = $conn->prepare($answers_sql);
            $ans_types = str_repeat('i', count($submission_ids));
            $ans_stmt->bind_param($ans_types, ...$submission_ids);
            $ans_stmt->execute();
            $ans_result = $ans_stmt->get_result();

            while ($arow = $ans_result->fetch_assoc()) {
                $label = $arow['field_label'];
                $type = $arow['field_type'];
                $value = $arow['answer_value'];

                if ($type === 'rating') {
                    if (!isset($rating_categories[$label])) {
                        $rating_categories[$label] = [
                            'Excellent' => 0,
                            'Good' => 0,
                            'Average' => 0,
                            'Poor' => 0
                        ];
                    }
                    if (isset($rating_categories[$label][$value])) {
                        $rating_categories[$label][$value]++;
                    }
                } elseif ($type === 'comment') {
                    if (!empty(trim($value))) {
                        $comments_list[] = $value;
                    }
                }
            }
            $ans_stmt->close();
        }
    }

    $safe_lecturer = str_replace(' ', '_', $lecturer_name);
    $filename = "Final-Year-Report-{$safe_lecturer}-" . date('Y-m-d') . ".csv";

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    fputcsv($output, ['Lecturer Name', $lecturer_name]);
    fputcsv($output, ['Programme', $program_name]);
    fputcsv($output, ['Batch', $batch_name]);
    fputcsv($output, ['Module', $module_name]);
    fputcsv($output, ['Total Filled Students', $filled_count]);
    fputcsv($output, []);

    fputcsv($output, ['Category', 'Excellent', 'Good', 'Average', 'Poor']);
    foreach ($rating_categories as $cat_label => $counts) {
        fputcsv($output, [
            $cat_label,
            $counts['Excellent'],
            $counts['Good'],
            $counts['Average'],
            $counts['Poor']
        ]);
    }
    fputcsv($output, []);

    fputcsv($output, ['Comments']);
    foreach ($comments_list as $comment) {
        fputcsv($output, [$comment]);
    }

    fclose($output);
}
