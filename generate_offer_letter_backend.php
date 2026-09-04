<?php
// generate_offer_letter_backend.php
session_start();
include("database/connection.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
$batch_id = isset($_POST['batch_id']) ? intval($_POST['batch_id']) : 0;

if ($student_id <= 0 || $batch_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student or batch ID']);
    exit();
}

// 1. Fetch Student Details
$stmt = $conn->prepare("SELECT * FROM students_temporary_registration WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();
$stmt->close();

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit();
}

// 2. Fetch Batch Details (Payment info, dates)
$stmt = $conn->prepare("
    SELECT p.*, b.batch_name, b.batch_intake, b.attendance, b.awarded_by, b.qualification_level, b.intake_no,
           pr.duration, pr.course_fee_lkr as p_fee_lkr, pr.course_fee_gbp as p_fee_gbp, pr.course_fee_usd as p_fee_usd, pr.course_fee_euro as p_fee_euro
    FROM batch_table b
    LEFT JOIN payment_batch_allocation p ON b.id = p.batch_id 
    LEFT JOIN program_table pr ON b.programme = pr.program_code
    WHERE b.id = ? 
    LIMIT 1
");
$stmt->bind_param("i", $batch_id);
$stmt->execute();
$res = $stmt->get_result();
$batch = $res->fetch_assoc();
$stmt->close();

// 3. Prepare Data for Python
$temp_id = $student['temp_id'];
$program_name = $student['program']; // "Graduate Diploma in Management" etc.
$batch_name = $batch['batch_name'] ?? 'Unknown Batch';

// Dates
$register_date = isset($batch['register_date']) ? $batch['register_date'] : date('Y-m-d');
$start_date_str = date('F Y', strtotime($register_date));
$end_date_str = date('F Y', strtotime($register_date . ' +1 year'));
$deadline_str = date('d F Y', strtotime('+1 month'));

// Fee String Construction using program_table fees
$fee_str = 'LKR ' . (isset($batch['p_fee_lkr']) ? number_format($batch['p_fee_lkr']) : '0');
if (isset($batch['p_fee_gbp']) && floatval($batch['p_fee_gbp']) > 0) {
    $fee_str .= ' + GBP ' . number_format($batch['p_fee_gbp']);
} elseif (isset($batch['p_fee_usd']) && floatval($batch['p_fee_usd']) > 0) {
    $fee_str .= ' + USD ' . number_format($batch['p_fee_usd']);
} elseif (isset($batch['p_fee_euro']) && floatval($batch['p_fee_euro']) > 0) {
    $fee_str .= ' + EURO ' . number_format($batch['p_fee_euro']);
}

$pdfData = [
    'fullname' => $student['fullname'],
    'firstname' => $student['firstname'],
    'address' => $student['permanent_address'],
    'program' => $program_name,
    'duration' => $batch['duration'] ?? '-',
    'start_date' => $start_date_str,
    'end_date' => $end_date_str,
    'fee_string' => $fee_str,
    'deadline' => $deadline_str,
    'batch_intake' => $batch['intake_no'] ?? '-',
    'attendance' => $batch['attendance'] ?? '-',
    'awarded_by' => $batch['awarded_by'] ?? '-',
    'qualification_level' => $batch['qualification_level'] ?? '-'
];

// 4. Define Output Path
// Pattern: application_pdfs/{CleanProgram}/{CleanBatch}/{CleanTempID}/OfferLetter.pdf
function sanitize($str)
{
    return preg_replace('/[^A-Za-z0-9_\-]/', '', $str);
}

$cleanProgram = sanitize($program_name);
$cleanBatch = sanitize($batch_name);
$cleanTempId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $temp_id);

// Changed from uploaded_documents to application_pdfs as requested
$outputDir = __DIR__ . "/application_pdfs/{$cleanProgram}/{$cleanBatch}/{$cleanTempId}";

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// Final PDF path
$nicVal = trim($student['nic'] ?? '');
$passportVal = trim($student['passport'] ?? '');
$identVal = !empty($nicVal) ? $nicVal : $passportVal;
$cleanIdentVal = preg_replace('/[^A-Za-z0-9_\-]/', '_', $identVal);

$pdfFilename = "Offer_Letter_{$cleanTempId}_{$student_id}" . (!empty($cleanIdentVal) ? "_{$cleanIdentVal}" : "") . ".pdf";
$pdfPath = $outputDir . "/" . $pdfFilename;

$pdfData['output_pdf_path'] = $pdfPath;

// 5. Save JSON and Run Python
$jsonPath = sys_get_temp_dir() . "/offer_gen_{$student_id}_" . time() . ".json";
file_put_contents($jsonPath, json_encode($pdfData));

$scriptPath = __DIR__ . "/generate_offer_letter.py";
$cmd = "python \"{$scriptPath}\" \"{$jsonPath}\" 2>&1";
$output = shell_exec($cmd);

// Cleanup
if (file_exists($jsonPath)) {
    unlink($jsonPath);
}

// 6. Return Result
if (file_exists($pdfPath)) {
    // Return the web-accessible path
    $webPath = "application_pdfs/{$cleanProgram}/{$cleanBatch}/{$cleanTempId}/{$pdfFilename}";
    echo json_encode(['success' => true, 'pdf_url' => $webPath, 'message' => 'Offer Letter generated successfully']);
} else {
    error_log("Offer Letter Gen Failed: $output");
    echo json_encode(['success' => false, 'message' => 'Failed to generate PDF', 'debug' => $output]);
}
?>