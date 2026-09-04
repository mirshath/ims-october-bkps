<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($student_id == 0) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Invalid Student ID</div></div>';
    include("includes/footer.php");
    exit();
}

// 1. Fetch Main Registration Data
$stmt = $conn->prepare("SELECT * FROM students_temporary_registration WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$reg_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reg_data) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Student not found</div></div>';
    include("includes/footer.php");
    exit();
}

$temp_id = $reg_data['temp_id'];

// 2. Fetch Document Data
// Using student_auto_id which links to registration id
$stmt = $conn->prepare("SELECT * FROM students_temporary_document WHERE student_auto_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$doc_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If not found by ID, try by temp_id (fallback)
if (!$doc_data) {
    $stmt = $conn->prepare("SELECT * FROM students_temporary_document WHERE temp_id = ?");
    $stmt->bind_param("s", $temp_id);
    $stmt->execute();
    $doc_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// 3. Fetch Academic Qualifications
$acad_data = [];
$stmt = $conn->prepare("SELECT * FROM student_academic_qualifications WHERE registration_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $acad_data[] = $row;
}
$stmt->close();


// Helper to build file path for display
function getDocumentUrl($filename, $program, $batch, $temp_id)
{
    if (empty($filename))
        return null;

    // Clean folder names: Remove matches similar to backend logic but seemingly stricter based on user feedback (no spaces)
    // User indicates structure like: uploaded_documents\GraduateDiplomainManagementLevel6\Batch85\BMS996633V\filename.pdf

    $program_clean = preg_replace('/[^A-Za-z0-9]/', '', $program);
    $batch_clean = preg_replace('/[^A-Za-z0-9]/', '', $batch);
    $temp_id_clean = preg_replace('/[^A-Za-z0-9_\-]/', '_', $temp_id);

    return "uploaded_documents/" . $program_clean . "/" . $batch_clean . "/" . $temp_id_clean . "/" . $filename;
}

// Helper to find generated application/offer letter PDFs
function getGeneratedFileUrl($prefix, $program, $batch, $temp_id)
{
    $program_clean = preg_replace('/[^A-Za-z0-9_\-]/', '', $program);
    $batch_clean = preg_replace('/[^A-Za-z0-9_\-]/', '', $batch);
    $temp_id_clean = preg_replace('/[^A-Za-z0-9_\-]/', '_', $temp_id);

    $dir = "application_pdfs/" . $program_clean . "/" . $batch_clean . "/" . $temp_id_clean;
    $abs_dir = __DIR__ . "/" . $dir;

    if (!is_dir($abs_dir))
        return null;

    $files = glob($abs_dir . "/" . $prefix . "*.pdf");
    if ($files && count($files) > 0) {
        // Return the latest file if multiple exist
        $latest_file = basename(end($files));
        return $dir . "/" . $latest_file;
    }

    return null;
}
?>

<div id="wrapper">
    <?php include("nav.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>

            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Student Details</h1>
                    <a href="get_data_from_std.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
                    </a>
                </div>

                <div class="row">
                    <!-- Personal Info -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Personal Information</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $photo_url = getDocumentUrl($doc_data['doc0'] ?? '', $reg_data['program'], $reg_data['batch'], $reg_data['temp_id']);
                                if ($photo_url):
                                    ?>
                                    <div class="text-center mb-4">
                                        <img src="<?= $photo_url ?>" alt="Student Photo"
                                            class="img-thumbnail rounded-circle"
                                            style="width: 150px; height: 150px; object-fit: cover;">
                                    </div>
                                <?php endif; ?>
                                <table class="table table-bordered table-striped">
                                    <tbody>
                                        <tr>
                                            <th width="35%">Title</th>
                                            <td><?= htmlspecialchars($reg_data['title']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Full Name</th>
                                            <td><?= htmlspecialchars($reg_data['fullname']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Name on Certificate</th>
                                            <td><?= htmlspecialchars($reg_data['certificate_name']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Date of Birth</th>
                                            <td><?= htmlspecialchars($reg_data['dob']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Gender</th>
                                            <td><?= htmlspecialchars($reg_data['gender']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Nationality</th>
                                            <td><?= htmlspecialchars($reg_data['nationality']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>NIC</th>
                                            <td><?= htmlspecialchars($reg_data['nic']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Passport</th>
                                            <td><?= htmlspecialchars($reg_data['passport']) ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Contact Details</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-striped">
                                    <tbody>
                                        <tr>
                                            <th width="35%">Mobile</th>
                                            <td><?= htmlspecialchars($reg_data['mobile']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td><?= htmlspecialchars($reg_data['email']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Home Number</th>
                                            <td><?= htmlspecialchars($reg_data['home_number']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Office Number</th>
                                            <td><?= htmlspecialchars($reg_data['office_number']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Emergency Contact</th>
                                            <td><?= htmlspecialchars($reg_data['emergency_contact']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Address (Permanent)</th>
                                            <td><?= htmlspecialchars($reg_data['permanent_address']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Address (Current)</th>
                                            <td><?= htmlspecialchars($reg_data['current_address']) ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Program & Documents -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Program Information</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-striped">
                                    <tbody>
                                        <tr>
                                            <th width="35%">Program</th>
                                            <td><?= htmlspecialchars($reg_data['program']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Batch</th>
                                            <td><?= htmlspecialchars($reg_data['batch']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Registration ID (TEMP)</th>
                                            <td><?= htmlspecialchars($reg_data['temp_id']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Date Applied</th>
                                            <td><?= htmlspecialchars($reg_data['created_at']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Approval Status</th>
                                            <td>
                                                <?php if ($reg_data['approved'] == '1'): ?>
                                                    <span class="badge badge-success">Approved</span>
                                                <?php elseif ($reg_data['approved'] == '2'): ?>
                                                    <span class="badge badge-danger">Rejected</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php if ($reg_data['approved'] == '1' && !empty($reg_data['approved_by'])): ?>
                                            <tr>
                                                <th>Approved By</th>
                                                <td>
                                                    <?= htmlspecialchars($reg_data['approved_by']) ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Uploaded Documents</h6>
                                <a href="download_all_student_docs.php?id=<?= $student_id ?>"
                                    class="btn btn-sm btn-success shadow-sm">
                                    <i class="fas fa-file-archive fa-sm text-white-50"></i> Download All (.zip)
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table  table-striped">
                                        <thead>
                                            <tr>
                                                <th>Document Type</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $docs = [
                                                'doc0' => 'Passport Photo',
                                                'doc1' => 'NIC/Passport Scanned Data',
                                                'doc2' => 'Passport',
                                                'doc3' => 'O/L Certificate',
                                                'doc4' => 'A/L Certificate'
                                            ];

                                            foreach ($docs as $key => $label) {
                                                $filename = $doc_data[$key] ?? null;
                                                $url = getDocumentUrl($filename, $reg_data['program'], $reg_data['batch'], $reg_data['temp_id']);

                                                echo "<tr>";
                                                echo "<td>$label</td>";
                                                if ($filename) {
                                                    echo "<td><span class='badge badge-success'>Uploaded</span></td>";
                                                    echo "<td><a href='$url' target='_blank' class='btn btn-primary btn-sm'><i class='fas fa-download'></i> View</a></td>";
                                                } else {
                                                    echo "<td><span class='badge badge-secondary'>Pending</span></td>";
                                                    echo "<td>-</td>";
                                                }
                                                echo "</tr>";
                                            }

                                            // Added Generated Documents
                                            $generatedDocs = [
                                                'Application_' => 'Application PDF',
                                                'Offer_Letter_' => 'Offer Letter'
                                            ];

                                            foreach ($generatedDocs as $prefix => $label) {
                                                $url = getGeneratedFileUrl($prefix, $reg_data['program'], $reg_data['batch'], $reg_data['temp_id']);

                                                echo "<tr>";
                                                echo "<td><strong>$label</strong></td>";
                                                if ($url) {
                                                    echo "<td><span class='badge badge-info'>Generated</span></td>";
                                                    echo "<td><a href='$url' target='_blank' class='btn btn-success btn-sm'><i class='fas fa-file-pdf'></i> View PDF</a></td>";
                                                } else {
                                                    echo "<td><span class='badge badge-light'>Not Generated</span></td>";
                                                    echo "<td>-</td>";
                                                }
                                                echo "</tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>

                <!-- Academic Qualifications -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Academic Qualifications</h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($acad_data) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Qualification</th>
                                            <th>Institution</th>
                                            <th>Year</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($acad_data as $acad): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($acad['qualification']) ?></td>
                                                <td><?= htmlspecialchars($acad['institution']) ?></td>
                                                <td><?= htmlspecialchars($acad['year']) ?></td>
                                                <td><?= htmlspecialchars($acad['notes']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No academic qualifications recorded.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>
</body>

</html>