<?php
session_start();
include("database/connection.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

// 1. Unify Parameter Handling (accepts both form_id and id)
$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'summary'; // summary | question | individual
$response_idx = isset($_GET['resp_idx']) ? intval($_GET['resp_idx']) : 0;
$question_id = isset($_GET['qid']) ? intval($_GET['qid']) : 0;

// =========================================================================
// CSV EXPORT FUNCTIONALITY (WITH COUNTS & STATS SUMMARY)
// =========================================================================
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $form_id > 0) {
    // Fetch form metadata
    $stmt = $conn->prepare("SELECT title FROM forms WHERE id = ?");
    $stmt->bind_param("i", $form_id);
    $stmt->execute();
    $form_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $filename = "Responses_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $form_data['title'] ?? 'Form') . "_" . date('Y-m-d') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // --- SECTION 1: FORM SUMMARY & COUNTS ---
    fputcsv($output, ['FORM RESPONSE SUMMARY & COUNTS']);
    fputcsv($output, ['Form Title:', $form_data['title'] ?? '']);
    fputcsv($output, ['Export Date:', date('Y-m-d H:i:s')]);

    // Count Total Responses
    $tot_stmt = $conn->prepare("SELECT COUNT(*) as total FROM form_responses WHERE form_id = ?");
    $tot_stmt->bind_param("i", $form_id);
    $tot_stmt->execute();
    $total_resp_count = $tot_stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $tot_stmt->close();

    fputcsv($output, ['Total Submissions:', $total_resp_count]);
    fputcsv($output, []); // Empty row divider

    // Fetch Questions for Counts Breakdown
    $q_stmt = $conn->prepare("SELECT id, question_text, question_type FROM form_questions WHERE form_id = ? ORDER BY order_index ASC, id ASC");
    $q_stmt->bind_param("i", $form_id);
    $q_stmt->execute();
    $questions_list = $q_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $q_stmt->close();

    fputcsv($output, ['--- QUESTION BREAKDOWN & RESPONSE COUNTS ---']);
    foreach ($questions_list as $q) {
        fputcsv($output, ['Question:', $q['question_text']]);

        $g_stmt = $conn->prepare("SELECT answer_text, answer_type, COUNT(*) as count FROM response_answers WHERE question_id = ? GROUP BY answer_text, answer_type ORDER BY count DESC");
        $g_stmt->bind_param("i", $q['id']);
        $g_stmt->execute();
        $grouped_answers = $g_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $g_stmt->close();

        fputcsv($output, ['Answer Choice / Input', 'Response Count']);
        if (empty($grouped_answers)) {
            fputcsv($output, ['(No responses)', 0]);
        } else {
            foreach ($grouped_answers as $ga) {
                $ans_label = ($ga['answer_type'] === 'file') ? '[File Uploaded]' : ($ga['answer_text'] ?: '— Empty —');
                fputcsv($output, [$ans_label, $ga['count']]);
            }
        }
        fputcsv($output, []); // Empty row separator between questions
    }

    // --- SECTION 2: DETAILED INDIVIDUAL RESPONSES TABLE ---
    fputcsv($output, ['--- DETAILED RESPONSES TABLE ---']);
    $headers = ['#', 'Respondent Email', 'Submitted At'];
    $q_ids = [];
    foreach ($questions_list as $q) {
        $headers[] = $q['question_text'];
        $q_ids[] = $q['id'];
    }
    fputcsv($output, $headers);

    $r_stmt = $conn->prepare("SELECT id, respondent_email, submitted_at FROM form_responses WHERE form_id = ? ORDER BY submitted_at DESC");
    $r_stmt->bind_param("i", $form_id);
    $r_stmt->execute();
    $responses_list = $r_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $r_stmt->close();

    foreach ($responses_list as $index => $resp) {
        $row = [
            $index + 1,
            $resp['respondent_email'] ?? 'Anonymous',
            $resp['submitted_at']
        ];

        foreach ($q_ids as $qid) {
            $ans_stmt = $conn->prepare("SELECT answer_text FROM response_answers WHERE response_id = ? AND question_id = ?");
            $ans_stmt->bind_param("ii", $resp['id'], $qid);
            $ans_stmt->execute();
            $ans_res = $ans_stmt->get_result()->fetch_assoc();
            $ans_stmt->close();

            $row[] = $ans_res['answer_text'] ?? '';
        }

        fputcsv($output, $row);
    }

    fclose($output);
    $conn->close();
    exit();
}

include("includes/header.php");

// 2. Fetch form metadata
$stmt = $conn->prepare("SELECT * FROM forms WHERE id = ?");
$stmt->bind_param("i", $form_id);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$form) {
    echo "<div class='container mt-5'><h4>Form not found.</h4></div>";
    include("includes/footer.php");
    exit();
}

// 3. Fetch all form questions
$q_stmt = $conn->prepare("SELECT * FROM form_questions WHERE form_id = ? ORDER BY order_index ASC, id ASC");
$q_stmt->bind_param("i", $form_id);
$q_stmt->execute();
$questions = $q_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$q_stmt->close();

// 4. Fetch all submitted responses
$r_stmt = $conn->prepare("SELECT * FROM form_responses WHERE form_id = ? ORDER BY submitted_at DESC");
$r_stmt->bind_param("i", $form_id);
$r_stmt->execute();
$all_responses = $r_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$r_stmt->close();

$total_responses = count($all_responses);

// Handle bounds for Individual view
if ($tab === 'individual' && $total_responses > 0) {
    if ($response_idx < 0) $response_idx = 0;
    if ($response_idx >= $total_responses) $response_idx = $total_responses - 1;
    $current_response = $all_responses[$response_idx];
}

// Handle default selection for Question view
if ($tab === 'question' && !empty($questions)) {
    if ($question_id === 0) {
        $question_id = $questions[0]['id'];
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .gf-container { max-width: 850px; margin: 0 auto; }
    .gf-card { background: #fff; border-radius: 8px; border: 1px solid #e3e6f0; margin-bottom: 20px; padding: 24px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 129, 0.05); }
    .gf-header-card { border-top: 6px solid #4e73df; }
    .nav-tabs .nav-link { color: #5a5c69; font-weight: 600; border: none; border-bottom: 3px solid transparent; }
    .nav-tabs .nav-link.active { color: #4e73df; border-bottom: 3px solid #4e73df; background: transparent; }
    .chart-box { max-width: 450px; margin: 0 auto; }
</style>

<div id="wrapper">

    <?php include("nav.php"); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <?php include("includes/topnav.php"); ?>

            <div class="container-fluid gf-container">

                <!-- Header Card & Navigation -->
                <div class="gf-card gf-header-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h3 class="h3 text-gray-800 font-weight-bold mb-1"><?= htmlspecialchars($form['title']) ?></h3>
                            <a href="forms_dashboard.php" class="text-secondary small">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-primary px-3 py-2 mr-2" style="font-size: 0.9rem;"><?= $total_responses ?> responses</span>
                            <a href="?form_id=<?= $form_id ?>&export=csv" class="btn btn-success btn-sm">
                                <i class="fas fa-file-excel"></i> Export CSV
                            </a>
                        </div>
                    </div>

                    <ul class="nav nav-tabs nav-fill border-0">
                        <li class="nav-item">
                            <a class="nav-link <?= $tab === 'summary' ? 'active' : '' ?>" href="?form_id=<?= $form_id ?>&id=<?= $form_id ?>&tab=summary">Summary</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $tab === 'question' ? 'active' : '' ?>" href="?form_id=<?= $form_id ?>&id=<?= $form_id ?>&tab=question">Question</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $tab === 'individual' ? 'active' : '' ?>" href="?form_id=<?= $form_id ?>&id=<?= $form_id ?>&tab=individual">Individual</a>
                        </li>
                    </ul>
                </div>

                <?php if ($total_responses === 0): ?>
                    <div class="gf-card text-center text-muted py-5">
                        <i class="fas fa-inbox fa-3x mb-3 text-gray-400"></i>
                        <p class="mb-0">Waiting for responses...</p>
                    </div>
                <?php else: ?>

                    <!-- ================= TAB 1: SUMMARY ================= -->
                    <?php if ($tab === 'summary'): ?>
                        <?php foreach ($questions as $q): ?>
                            <div class="gf-card">
                                <h5 class="font-weight-bold text-gray-800 mb-3"><?= htmlspecialchars($q['question_text']) ?></h5>
                                <?php
                                $q_id_val = intval($q['id']);
                                $ans_stmt = $conn->prepare("SELECT answer_text, answer_type FROM response_answers WHERE question_id = ?");
                                $ans_stmt->bind_param("i", $q_id_val);
                                $ans_stmt->execute();
                                $answers_raw = $ans_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                $ans_stmt->close();

                                $answers = [];
                                foreach ($answers_raw as $ar) {
                                    if ($ar['answer_type'] === 'file') {
                                        $answers[] = '[File Uploaded]';
                                    } else if (!empty($ar['answer_text'])) {
                                        $split_answers = array_map('trim', explode(',', $ar['answer_text']));
                                        foreach ($split_answers as $sa) {
                                            if ($sa !== '') $answers[] = $sa;
                                        }
                                    }
                                }

                                if (in_array($q['question_type'], ['multiple_choice', 'checkbox', 'dropdown', 'linear_scale'])):
                                    $counts = array_count_values($answers);
                                    $labels = array_keys($counts);
                                    $data = array_values($counts);
                                ?>
                                    <div class="chart-box">
                                        <canvas id="chart_<?= $q_id_val ?>"></canvas>
                                    </div>
                                    <script>
                                        new Chart(document.getElementById('chart_<?= $q_id_val ?>'), {
                                            type: '<?= $q['question_type'] === 'checkbox' ? 'bar' : 'pie' ?>',
                                            data: {
                                                labels: <?= json_encode($labels) ?>,
                                                datasets: [{
                                                    data: <?= json_encode($data) ?>,
                                                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796']
                                                }]
                                            },
                                            options: { responsive: true }
                                        });
                                    </script>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php if (empty($answers)): ?>
                                            <div class="text-muted small">No answers provided</div>
                                        <?php else: ?>
                                            <?php foreach (array_slice($answers, 0, 15) as $ans): ?>
                                                <div class="list-group-item bg-light rounded mb-2 border-0">
                                                    <?= htmlspecialchars($ans) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                    <!-- ================= TAB 2: QUESTION WISE ================= -->
                    <?php elseif ($tab === 'question'): ?>
                        <div class="gf-card">
                            <form method="GET" action="">
                                <input type="hidden" name="form_id" value="<?= $form_id ?>">
                                <input type="hidden" name="id" value="<?= $form_id ?>">
                                <input type="hidden" name="tab" value="question">
                                
                                <label class="form-label font-weight-bold text-gray-700">Select Question:</label>
                                <select name="qid" class="form-control custom-select" onchange="this.form.submit()">
                                    <?php foreach ($questions as $q): ?>
                                        <option value="<?= $q['id'] ?>" <?= $q['id'] == $question_id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($q['question_text']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>

                        <div class="gf-card">
                            <?php
                            $q_id_val = intval($question_id);
                            $g_stmt = $conn->prepare("SELECT answer_text, answer_type, COUNT(*) as count FROM response_answers WHERE question_id = ? GROUP BY answer_text, answer_type");
                            $g_stmt->bind_param("i", $q_id_val);
                            $g_stmt->execute();
                            $grouped_answers = $g_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            $g_stmt->close();
                            ?>
                            <h6 class="text-muted mb-3"><?= count($grouped_answers) ?> unique response groups</h6>
                            <div class="list-group">
                                <?php foreach ($grouped_answers as $ga): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <?php if ($ga['answer_type'] === 'file'): ?>
                                                <a href="bms-form/<?= htmlspecialchars($ga['answer_text']) ?>" target="_blank">
                                                    <i class="fas fa-paperclip"></i> View Attached File
                                                </a>
                                            <?php else: ?>
                                                <?= htmlspecialchars($ga['answer_text'] ?: '— No answer —') ?>
                                            <?php endif; ?>
                                        </span>
                                        <span class="badge badge-secondary badge-pill"><?= $ga['count'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    <!-- ================= TAB 3: INDIVIDUAL ================= -->
                    <?php elseif ($tab === 'individual'): ?>
                        <div class="gf-card d-flex justify-content-between align-items-center">
                            <div>
                                <a href="?form_id=<?= $form_id ?>&id=<?= $form_id ?>&tab=individual&resp_idx=<?= $response_idx - 1 ?>" 
                                   class="btn btn-outline-secondary btn-sm <?= $response_idx <= 0 ? 'disabled' : '' ?>">
                                   <i class="fas fa-chevron-left"></i> Previous
                                </a>
                                <span class="mx-3 font-weight-bold text-gray-800"><?= $response_idx + 1 ?> of <?= $total_responses ?></span>
                                <a href="?form_id=<?= $form_id ?>&id=<?= $form_id ?>&tab=individual&resp_idx=<?= $response_idx + 1 ?>" 
                                   class="btn btn-outline-secondary btn-sm <?= $response_idx >= $total_responses - 1 ? 'disabled' : '' ?>">
                                   Next <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                            <div class="text-right">
                                <span class="text-muted small d-block">Respondent: <?= htmlspecialchars($current_response['respondent_email'] ?? 'Anonymous') ?></span>
                                <span class="text-muted small d-block">Submitted: <?= $current_response['submitted_at'] ?></span>
                            </div>
                        </div>

                        <?php
                        $current_resp_id = intval($current_response['id']);
                        $ind_stmt = $conn->prepare("SELECT q.question_text, q.question_type, a.answer_text, a.answer_type 
                                                    FROM form_questions q 
                                                    LEFT JOIN response_answers a ON q.id = a.question_id AND a.response_id = ? 
                                                    WHERE q.form_id = ? 
                                                    ORDER BY q.order_index ASC");
                        $ind_stmt->bind_param("ii", $current_resp_id, $form_id);
                        $ind_stmt->execute();
                        $ind_answers = $ind_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        $ind_stmt->close();
                        ?>

                        <?php foreach ($ind_answers as $ia): ?>
                            <div class="gf-card">
                                <label class="font-weight-bold text-gray-700"><?= htmlspecialchars($ia['question_text']) ?></label>
                                <div class="p-2 bg-light rounded mt-1 border">
                                    <?php if ($ia['answer_type'] === 'file' && !empty($ia['answer_text'])): ?>
                                        <a href="bms-form/<?= htmlspecialchars($ia['answer_text']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download"></i> View Uploaded File
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($ia['answer_text'] ?? '— No response —') ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    <?php endif; ?>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
include("includes/footer.php"); 
?>