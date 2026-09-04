<?php
// search_students.php
include("database/connection.php");

// Retrieve the search query from the AJAX request and sanitize it
$query = mysqli_real_escape_string($conn, $_POST['query']);

$sql = "SELECT * FROM students WHERE first_name LIKE '%$query%' OR last_name LIKE '%$query%' OR student_code LIKE '%$query%' OR nic LIKE '%$query%'";
$result = mysqli_query($conn, $sql);

$results = '';

// Check if any results were returned
// if (mysqli_num_rows($result) > 0) {
//     // Loop through the query results and create a dropdown item for each student
//     while ($row = mysqli_fetch_assoc($result)) {
//         // Concatenate the first and last names for display
//         $full_name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);

//         // Create a dropdown item with the student code as a data attribute
//         $results .= "<a href='#' class='dropdown-item' data-id='{$row['student_code']}'>{$full_name} ({$row['nic']})</a>";
//     }
// } else {
//     $results = "<a href='#' class='dropdown-item'>No results found</a>";
// }



if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Convert qualifications to comma-separated string if stored as CSV
        $qualifications = htmlspecialchars($row['qualifications']);

        // Include **all relevant fields as data attributes**
        $results .= "<a href='#' class='dropdown-item student-item' 
            data-student_code='{$row['student_code']}'
            data-title='{$row['title']}'
            data-first_name='{$row['first_name']}'
            data-last_name='{$row['last_name']}'
            data-certificate_name='{$row['certificate_name']}'
            data-preferred_name='{$row['preferred_name']}'
            data-dob='{$row['date_of_birth']}'
            data-nationality='{$row['nationality']}'
            data-permanent_address='{$row['permanent_address']}'
            data-current_address='{$row['current_address']}'
            data-mobile='{$row['mobile']}'
            data-telephone='{$row['telephone']}'
            data-emergency_contact_name='{$row['emergency_contact_name']}'
            data-emergency_contact_number='{$row['emergency_contact_number']}'
            data-english_ability='{$row['english_ability']}'
            data-minimum_entry_qualification='{$row['minimum_entry_qualification']}'
            data-nic='{$row['nic']}'
            data-passport='{$row['passport']}'
            data-personal_email='{$row['personal_email']}'
            data-bms_email='{$row['bms_email']}'
            data-occupation='{$row['occupation']}'
            data-organization='{$row['organization']}'
            data-previous_organization='{$row['previous_organization']}'
            data-qualifications='{$qualifications}'
            data-active='{$row['active']}'
        >{$row['first_name']} {$row['last_name']} ({$row['nic']})</a>";
    }
} else {
    $results = "<a href='#' class='dropdown-item'>No results found</a>";
}

echo $results;

mysqli_close($conn);
