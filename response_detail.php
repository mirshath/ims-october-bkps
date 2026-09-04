<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

// require_once 'PermissionChecking.php';

$response_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conn->prepare("SELECT r.*, f.title AS form_title, f.id AS form_id FROM form_responses r JOIN forms f ON f.id = r.form_id WHERE r.id = ?");
$stmt->bind_param("i", $response_id);
$stmt->execute();
$response = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$response) {
    echo "<div class='container mt-5'><h4>Response not found.</h4></div>";
    exit();
}

$a_stmt = $conn->prepare("
    SELECT q.question_text, q.question_image, q.question_type, q.order_index,
           a.answer_text, a.answer_type
    FROM form_questions q
    LEFT JOIN response_answers a ON a.question_id = q.id AND a.response_id = ?
    WHERE q.form_id = ?
    ORDER BY q.order_index ASC
");
$a_stmt->bind_param("ii", $response_id, $response['form_id']);
$a_stmt->execute();
$answers = $a_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$a_stmt->close();
?>

<div id="wrapper">

    <?php include("nav.php"); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <?php include("includes/topnav.php"); ?>

            <div class="container">

                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">
                        Response Detail — <?= htmlspecialchars($response['form_title']) ?>
                    </h4>
                    <a href="view_responses.php?form_id=<?= (int)$response['form_id'] ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Responses
                    </a>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($response['respondent_email'] ?? '—') ?></p>
                        <p class="mb-0"><strong>Submitted:</strong> <?= htmlspecialchars($response['submitted_at']) ?></p>
                    </div>
                </div>

                <?php foreach ($answers as $a): ?>
                <div class="card mb-2">
                    <div class="card-body">
                        <label class="font-weight-bold mb-1"><?= htmlspecialchars($a['question_text']) ?></label>

                        <?php if (!empty($a['question_image'])): ?>
                            <div class="mb-2">
                                <img src="bms-form/<?= htmlspecialchars($a['question_image']) ?>" style="max-height:120px; border-radius:4px;">
                            </div>
                        <?php endif; ?>

                        <?php if ($a['answer_text'] === null): ?>
                            <p class="mb-0"><span class="text-muted">No answer</span></p>

                        <?php elseif ($a['answer_type'] === 'file'): ?>
                            <?php
                                $fileUrl = 'bms-form/' . $a['answer_text'];
                                $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $a['answer_text']);
                            ?>
                            <?php if ($isImage): ?>
                                <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank" rel="noopener">
                                    <img src="<?= htmlspecialchars($fileUrl) ?>" style="max-height:200px; border-radius:4px;" alt="Uploaded image">
                                </a>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank" rel="noopener">
                                    <i class="fas fa-paperclip"></i> View attached file
                                </a>
                            <?php endif; ?>

                        <?php else: ?>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($a['answer_text'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>
</div>

<?php $conn->close(); ?>
