<?php
session_start();
$Session_username = $_SESSION['username'];
// Database connection
include("database/connection.php");

// Check if a file has been uploaded
if (isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];

    // Capture program, batch, and university_id from the form
    $program = isset($_POST['program']) ? $_POST['program'] : '';        // Program code
    $batch = isset($_POST['batch']) ? $_POST['batch'] : '';              // Batch ID
    $university_id = isset($_POST['university_id']) ? $_POST['university_id'] : ''; // University ID

    // Open the CSV file for reading
    if (($handle = fopen($file, 'r')) !== FALSE) {
        // Skip the first row if it contains headers
        fgetcsv($handle);

        // Read each row from the CSV
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            // Insert into students table
            $stmt1 = $conn->prepare("INSERT INTO students (
                student_code, 
                title, 
                first_name, 
                last_name, 
                certificate_name, 
                preferred_name, 
                date_of_birth, 
                nationality, 
                permanent_address, 
                current_address, 
                mobile, 
                telephone, 
                emergency_contact_name, 
                emergency_contact_number, 
                english_ability, 
                minimum_entry_qualification, 
                nic, 
                passport, 
                personal_email, 
                bms_email, 
                occupation, 
                organization, 
                previous_organization, 
                qualifications, 
                active,
                entered_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)");

            // Bind parameters according to your CSV structure
            $stmt1->bind_param(
                "isssssssssssssssssssssssss",
                $data[0],  // student_code (int)
                $data[1],  // title (enum)
                $data[2],  // first_name (varchar)
                $data[3],  // last_name (varchar)
                $data[4],  // certificate_name (varchar)
                $data[5],  // preferred_name (varchar)
                $data[6],  // date_of_birth (date)
                $data[7],  // nationality (varchar)
                $data[8],  // permanent_address (text)
                $data[9],  // current_address (text)
                $data[10], // mobile (varchar)
                $data[11], // telephone (varchar)
                $data[12], // emergency_contact_name (varchar)
                $data[13], // emergency_contact_number (varchar)
                $data[14], // english_ability (tinyint)
                $data[15], // minimum_entry_qualification (tinyint)
                $data[16], // nic (varchar)
                $data[17], // passport (varchar)
                $data[18], // personal_email (varchar)
                $data[19], // bms_email (varchar)
                $data[20], // occupation (varchar)
                $data[21], // organization (varchar)
                $data[22], // previous_organization (varchar)
                $data[23], // qualifications (varchar or text)
                $data[24], // active (tinyint)
                $Session_username
            );

            if (!$stmt1->execute()) {
                echo "Error executing statement for students table: " . $stmt1->error . "<br>";
                continue; // Skip to the next row on error
            }

            // Get the last inserted student ID
            $student_id = $conn->insert_id;

            // Now, insert into allocate_programme table
            $registration_number = $data[25]; // Assuming the registration number is in the 26th column of the CSV
            $elective_subs = $data[26];       // Assuming elective subjects are in the 27th column of the CSV
            $compulsory_sub = $data[27];      // Assuming compulsory subjects are in the 28th column of the CSV

            if (!empty($program) && !empty($batch) || !empty($university_id)) {
                $stmt2 = $conn->prepare("INSERT INTO allocate_programme (
                    student_code, 
                    student_registration_id, 
                    programme_code, 
                    batch_id, 
                    university_id, 
                    elective_subs, 
                    compulsory_sub,
                    entered_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?,?)");

                $stmt2->bind_param(
                    "isiiisss",
                    $student_id,           // student_code (int)
                    $registration_number,  // student_registration_id (varchar)
                    $program,              // programme_code (varchar)
                    $batch,                // batch_id (varchar)
                    $university_id,        // university_id (varchar)
                    $elective_subs,        // elective subjects (varchar or text)
                    $compulsory_sub,        // compulsory subjects (varchar or text)
                    $Session_username
                );

                if (!$stmt2->execute()) {
                    echo "Error executing statement for allocate_programme table: " . $stmt2->error . "<br>";
                    continue; // Skip to the next row on error
                }

                // Close the second statement
                $stmt2->close();
            }

            // Close the first statement
            $stmt1->close();
        }

        // Close the file
        fclose($handle);
        // echo alert 
        echo "<script>alert('Student Data Imported Successfully');</script>";
        echo '<script>window.location.href = "uploadStudents";</script>';
        // echo "Data uploaded successfully!";
        // header("Location: index.php");
        exit; // Important to prevent further code execution after redirect
    } else {
        echo "Error opening the file.";
    }
} else {
    echo "No file uploaded.";
}

// Close the database connection
$conn->close();
