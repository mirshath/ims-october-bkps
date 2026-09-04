<?php
include('database/connection.php');
/** @var mysqli $conn */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programme_id = $_POST['programme_id'] ?? '';
    $batch_id = $_POST['batch_id'] ?? '';
    $module_id = $_POST['module_id'] ?? '';
    $lecturer_id = $_POST['lecturer_id'] ?? '';

    $response = [
        'filled_count' => 0,
        'program_name' => '',
        'batch_name' => '',
        'module_name' => '',
        'lecturer_name' => '',
        'categories' => [],
        'comments_list' => [],
        'overall_totals' => [
            'Excellent' => 0,
            'Good' => 0,
            'Average' => 0,
            'Poor' => 0
        ],
        'submissions' => []
    ];

    $link_ids = [];
    $link_sql = "SELECT id, programme_id, batch_id, module_id, lecturer_id 
                 FROM feedback_links 
                 WHERE programme_id = ? AND batch_id = ? AND module_id = ? AND lecturer_id = ? AND active = 1";
    $link_stmt = $conn->prepare($link_sql);
    $link_stmt->bind_param("ssss", $programme_id, $batch_id, $module_id, $lecturer_id);
    $link_stmt->execute();
    $link_result = $link_stmt->get_result();
    while ($row = $link_result->fetch_assoc()) {
        $link_ids[] = (int)$row['id'];
    }
    $link_stmt->close();

    if (empty($link_ids)) {
        echo json_encode($response);
        exit;
    }

    $link_placeholders = implode(',', array_fill(0, count($link_ids), '?'));

    $submission_ids = [];
    $submissions_map = [];
    $sub_sql = "SELECT id, program_id, batch_id, module_id, lecturer_id, 
                       program_name, module_name, lecturer_name, submitted_at
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
        $sid = $row['id'];
        $submission_ids[] = $sid;
        $submissions_map[$sid] = [
            'submission_id' => $sid,
            'submitted_at' => $row['submitted_at'],
            'answers' => []
        ];
        if ($first_row) {
            $response['program_name'] = $row['program_name'];
            $response['module_name'] = $row['module_name'];
            $response['lecturer_name'] = $row['lecturer_name'];
            $first_row = false;
        }
    }
    $sub_stmt->close();

    $response['filled_count'] = count($submission_ids);

    if (!empty($submission_ids)) {
        $batch_sql = "SELECT batch_name FROM batch_table WHERE id = ? LIMIT 1";
        $batch_stmt = $conn->prepare($batch_sql);
        $batch_id_int = (int)$batch_id;
        $batch_stmt->bind_param("i", $batch_id_int);
        $batch_stmt->execute();
        $batch_result = $batch_stmt->get_result();
        if ($brow = $batch_result->fetch_assoc()) {
            $response['batch_name'] = $brow['batch_name'];
        }
        $batch_stmt->close();
    }

    if (empty($submission_ids)) {
        echo json_encode($response);
        exit;
    }

    $sub_placeholders = implode(',', array_fill(0, count($submission_ids), '?'));

    $answers_sql = "SELECT a.submission_id, a.field_label, a.field_type, a.answer_value
                    FROM feedback_submission_answers a
                    WHERE a.submission_id IN ($sub_placeholders)
                    ORDER BY a.submission_id, a.field_label, a.id";
    $ans_stmt = $conn->prepare($answers_sql);
    $ans_types = str_repeat('i', count($submission_ids));
    $ans_stmt->bind_param($ans_types, ...$submission_ids);
    $ans_stmt->execute();
    $ans_result = $ans_stmt->get_result();

    $rating_categories = [];
    $comments_list = [];

    while ($arow = $ans_result->fetch_assoc()) {
        $sid = $arow['submission_id'];
        $label = $arow['field_label'];
        $type = $arow['field_type'];
        $value = $arow['answer_value'];

        if (isset($submissions_map[$sid])) {
            $submissions_map[$sid]['answers'][] = [
                'field_label' => $label,
                'field_type' => $type,
                'answer_value' => $value
            ];
        }

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
                $response['overall_totals'][$value]++;
            }
        } elseif ($type === 'comment') {
            if (!empty(trim($value))) {
                $comments_list[] = $value;
            }
        }
    }
    $ans_stmt->close();

    $response['categories'] = $rating_categories;
    $response['comments_list'] = $comments_list;
    $response['submissions'] = array_values($submissions_map);

    $cat_keys = array_keys($rating_categories);
    if (count($cat_keys) > 0) {
        $last_label = end($cat_keys);
        if (!isset($rating_categories['Overall'])) {
            $response['overall_category_label'] = $last_label;
        }
    }

    echo json_encode($response);
}
?>
