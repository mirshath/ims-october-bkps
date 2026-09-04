<?php
include('database/connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $programme_id = $_GET['programme_id'] ?? '';
    $batch_id = $_GET['batch_id'] ?? '';
    $module_id = $_GET['module_id'] ?? '';
    $lecturer_id = $_GET['lecturer_id'] ?? '';

    // Fetch main data
    $sql = "SELECT 
                MAX(f.program_name) as program_name,
                MAX(b.batch_name) as batch_name,
                MAX(f.module_name) as module_name,
                MAX(f.lecturer_name) as lecturer_name,
                COUNT(f.id) as total_filled_students,
                SUM(CASE WHEN presentation = 'Excellent' THEN 1 ELSE 0 END) as excellent_presentation,
                SUM(CASE WHEN presentation = 'Good' THEN 1 ELSE 0 END) as good_presentation,
                SUM(CASE WHEN presentation = 'Average' THEN 1 ELSE 0 END) as average_presentation,
                SUM(CASE WHEN presentation = 'Poor' THEN 1 ELSE 0 END) as poor_presentation,
                SUM(CASE WHEN preparation = 'Excellent' THEN 1 ELSE 0 END) as excellent_preparation,
                SUM(CASE WHEN preparation = 'Good' THEN 1 ELSE 0 END) as good_preparation,
                SUM(CASE WHEN preparation = 'Average' THEN 1 ELSE 0 END) as average_preparation,
                SUM(CASE WHEN preparation = 'Poor' THEN 1 ELSE 0 END) as poor_preparation,
                SUM(CASE WHEN syllabus_coverage = 'Excellent' THEN 1 ELSE 0 END) as excellent_syllabus_coverage,
                SUM(CASE WHEN syllabus_coverage = 'Good' THEN 1 ELSE 0 END) as good_syllabus_coverage,
                SUM(CASE WHEN syllabus_coverage = 'Average' THEN 1 ELSE 0 END) as average_syllabus_coverage,
                SUM(CASE WHEN syllabus_coverage = 'Poor' THEN 1 ELSE 0 END) as poor_syllabus_coverage,
                SUM(CASE WHEN knowledge_subject = 'Excellent' THEN 1 ELSE 0 END) as excellent_knowledge_subject,
                SUM(CASE WHEN knowledge_subject = 'Good' THEN 1 ELSE 0 END) as good_knowledge_subject,
                SUM(CASE WHEN knowledge_subject = 'Average' THEN 1 ELSE 0 END) as average_knowledge_subject,
                SUM(CASE WHEN knowledge_subject = 'Poor' THEN 1 ELSE 0 END) as poor_knowledge_subject,
                SUM(CASE WHEN question_discussion = 'Excellent' THEN 1 ELSE 0 END) as excellent_question_discussion,
                SUM(CASE WHEN question_discussion = 'Good' THEN 1 ELSE 0 END) as good_question_discussion,
                SUM(CASE WHEN question_discussion = 'Average' THEN 1 ELSE 0 END) as average_question_discussion,
                SUM(CASE WHEN question_discussion = 'Poor' THEN 1 ELSE 0 END) as poor_question_discussion,
                SUM(CASE WHEN interaction_students = 'Excellent' THEN 1 ELSE 0 END) as excellent_interaction_students,
                SUM(CASE WHEN interaction_students = 'Good' THEN 1 ELSE 0 END) as good_interaction_students,
                SUM(CASE WHEN interaction_students = 'Average' THEN 1 ELSE 0 END) as average_interaction_students,
                SUM(CASE WHEN interaction_students = 'Poor' THEN 1 ELSE 0 END) as poor_interaction_students,
                SUM(CASE WHEN punctuality = 'Excellent' THEN 1 ELSE 0 END) as excellent_punctuality,
                SUM(CASE WHEN punctuality = 'Good' THEN 1 ELSE 0 END) as good_punctuality,
                SUM(CASE WHEN punctuality = 'Average' THEN 1 ELSE 0 END) as average_punctuality,
                SUM(CASE WHEN punctuality = 'Poor' THEN 1 ELSE 0 END) as poor_punctuality,
                SUM(CASE WHEN overall = 'Excellent' THEN 1 ELSE 0 END) as excellent_overall,
                SUM(CASE WHEN overall = 'Good' THEN 1 ELSE 0 END) as good_overall,
                SUM(CASE WHEN overall = 'Average' THEN 1 ELSE 0 END) as average_overall,
                SUM(CASE WHEN overall = 'Poor' THEN 1 ELSE 0 END) as poor_overall
            FROM feedback f
            LEFT JOIN batch_table b ON f.batch_id = b.id
            WHERE f.program_id = ? AND f.batch_id = ? AND f.module_id = ? AND f.lecturer_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siis", $programme_id, $batch_id, $module_id, $lecturer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    // Fetch comments separately
    $comments_sql = "SELECT comments FROM feedback WHERE program_id = ? AND batch_id = ? AND module_id = ? AND lecturer_id = ? AND comments != ''";
    $comments_stmt = $conn->prepare($comments_sql);
    $comments_stmt->bind_param("siis", $programme_id, $batch_id, $module_id, $lecturer_id);
    $comments_stmt->execute();
    $comments_result = $comments_stmt->get_result();
    $comments = [];
    while ($row = $comments_result->fetch_assoc()) {
        $comments[] = $row['comments'];
    }

    $lecturer_name = str_replace(' ', '_', $data['lecturer_name']);
    $filename = "Feedback-Report-{$lecturer_name}-" . date('Y-m-d') . ".csv";

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Header
    fputcsv($output, ['Lecturer Name', $data['lecturer_name']]);
    fputcsv($output, ['Programme', $data['program_name']]);
    fputcsv($output, ['Batch', $data['batch_name']]);
    fputcsv($output, ['Module', $data['module_name']]);
    fputcsv($output, ['Total Filled Students', $data['total_filled_students']]);
    fputcsv($output, []); // Spacer

    // Breakdown
    fputcsv($output, ['Category', 'Excellent', 'Good', 'Average', 'Poor']);
    $categories = [
        'presentation', 'preparation', 'syllabus_coverage', 'knowledge_subject', 
        'question_discussion', 'interaction_students', 'punctuality', 'overall'
    ];
    foreach ($categories as $cat) {
        fputcsv($output, [
            ucfirst(str_replace('_', ' ', $cat)),
            $data["excellent_{$cat}"],
            $data["good_{$cat}"],
            $data["average_{$cat}"],
            $data["poor_{$cat}"]
        ]);
    }
    fputcsv($output, []); // Spacer

    // Comments
    fputcsv($output, ['Comments']);
    foreach ($comments as $comment) {
        fputcsv($output, [$comment]);
    }

    fclose($output);
}
?>