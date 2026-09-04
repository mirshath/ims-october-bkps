<?php
session_start();
include("./database/connection.php");

// Alert message display
if (isset($_SESSION['message'])) {
    $alertClass = ($_SESSION['message_type'] == 'success') ? 'alert-success' : 'alert-danger';
?>
    <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php
    // Clear the message after displaying
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the student code from the form
    $student_code = $_POST['student_code'];

    // First check if student documents already exist
    $check_sql = "SELECT * FROM student_documents WHERE student_code = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $student_code);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $exists = $result->num_rows > 0;
    $check_stmt->close();

    // Process files and store paths as before
    $files = [
        'registration_receipt',
        'cv',
        'nic_passport',
        'education_qualification_1',
        'education_qualification_2',
        'education_qualification_3',
        'education_qualification_4',
        'experience_1',
        'experience_2',
        'experience_3',
        'experience_4',
        'photo',
        'other'
    ];

    // Prepare an array to store file paths
    $file_paths = [];

    // Loop through all files and upload them
    foreach ($files as $file) {
        if (isset($_FILES[$file]) && $_FILES[$file]['error'] == 0) {
            // Generate a unique file name
            $file_name = $student_code . '_' . $file . '_' . basename($_FILES[$file]['name']);
            $file_path = 'uploads/' . $file_name;

            // Validate file type (example: allowing only PDF, DOCX, JPEG)
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
            $fileType = $_FILES[$file]['type'];

            if (!in_array($fileType, $allowedTypes)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid file type for ' . $file]);
                exit();
            }

            // Move uploaded file
            if (move_uploaded_file($_FILES[$file]['tmp_name'], $file_path)) {
                $file_paths[$file] = $file_path;
            } else {
                $file_paths[$file] = NULL;
            }
        } else {
            // If no new file uploaded, keep existing file path if updating
            if ($exists) {
                $file_paths[$file] = NULL; // Will be ignored in UPDATE query
            } else {
                $file_paths[$file] = NULL;
            }
        }
    }

    // Prepare SQL query based on whether document exists
    if ($exists) {
        // UPDATE query
        $sql = "UPDATE student_documents SET 
                registration_receipt = COALESCE(?, registration_receipt),
                cv = COALESCE(?, cv),
                nic_passport = COALESCE(?, nic_passport),
                education_qualification_1 = COALESCE(?, education_qualification_1),
                education_qualification_2 = COALESCE(?, education_qualification_2),
                education_qualification_3 = COALESCE(?, education_qualification_3),
                education_qualification_4 = COALESCE(?, education_qualification_4),
                experience_1 = COALESCE(?, experience_1),
                experience_2 = COALESCE(?, experience_2),
                experience_3 = COALESCE(?, experience_3),
                experience_4 = COALESCE(?, experience_4),
                photo = COALESCE(?, photo),
                other = COALESCE(?, other)
                WHERE student_code = ?";
    } else {
        // INSERT query
        $sql = "INSERT INTO student_documents 
                (student_code, registration_receipt, cv, nic_passport, education_qualification_1, 
                education_qualification_2, education_qualification_3, education_qualification_4, 
                experience_1, experience_2, experience_3, experience_4, photo, other) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    }

    // Prepare and bind parameters for SQL query
    if ($stmt = $conn->prepare($sql)) {
        $params = [
            $file_paths['registration_receipt'],
            $file_paths['cv'],
            $file_paths['nic_passport'],
            $file_paths['education_qualification_1'],
            $file_paths['education_qualification_2'],
            $file_paths['education_qualification_3'],
            $file_paths['education_qualification_4'],
            $file_paths['experience_1'],
            $file_paths['experience_2'],
            $file_paths['experience_3'],
            $file_paths['experience_4'],
            $file_paths['photo'],
            $file_paths['other'],
            $student_code
        ];

        if ($exists) {
            $stmt->bind_param(str_repeat('s', count($params) - 1) . 's', ...$params);
        } else {
            $stmt->bind_param('s' . str_repeat('s', count($params) - 1), ...$params);
        }

        // Execute query
        if ($stmt->execute()) {
            // Set success message in session
            $_SESSION['message'] = "Documents " . ($exists ? "updated" : "uploaded") . " successfully!";
            $_SESSION['message_type'] = "success";

            // Send JSON response instead of redirecting
            echo json_encode([
                'status' => 'success',
                'message' => "Documents " . ($exists ? "updated" : "uploaded") . " successfully!"
            ]);
            exit();
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => "Error " . ($exists ? "updating" : "uploading") . " documents: " . $stmt->error
            ]);
            exit();
        }

        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => "Error preparing the SQL statement: " . $conn->error]);
        exit();
    }
}
?>

