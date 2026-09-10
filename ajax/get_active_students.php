<?php
require __DIR__ . '/_bootstrap.php';
// $conn is now a verified, connected mysqli instance, and $_SESSION['username'] is confirmed set.

/*
 * One row per student with an active allocate_programme record.
 * GROUP_CONCAT pulls together every registration id the student has across
 * their active allocation(s), so search-by-registration-id still works even
 * if a student has more than one active row.
 */
$sql = "SELECT s.student_code, s.first_name, s.last_name, s.preferred_name, s.nic,
               GROUP_CONCAT(DISTINCT NULLIF(ap.student_registration_id, '') SEPARATOR ', ') AS reg_ids,
               GROUP_CONCAT(DISTINCT NULLIF(ap.new_student_registration_id, '') SEPARATOR ', ') AS new_reg_ids
        FROM allocate_programme ap
        INNER JOIN students s ON s.student_code = ap.student_code
        WHERE ap.status = 'active'
        GROUP BY s.student_code, s.first_name, s.last_name, s.preferred_name, s.nic
        ORDER BY s.first_name, s.last_name";

$result = $conn->query($sql);

if ($result === false) {
    ajax_fail('Query failed: ' . $conn->error);
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $label = trim($row['first_name'] . ' ' . $row['last_name']);
    if (!empty($row['preferred_name']) && $row['preferred_name'] !== $label) {
        $label .= ' (' . $row['preferred_name'] . ')';
    }
    $label .= ' - #' . $row['student_code'];

    // Everything typeable that should be searchable against this student,
    // even though only $label is shown in the dropdown.
    $searchBlob = implode(' ', array_filter([
        $label,
        $row['nic'],
        $row['reg_ids'],
        $row['new_reg_ids']
    ]));

    $data[] = [
        'id'     => $row['student_code'],
        'text'   => $label,
        'nic'    => $row['nic'],
        'reg'    => $row['reg_ids'],
        'newReg' => $row['new_reg_ids'],
        'search' => $searchBlob
    ];
}

if (ob_get_length()) {
    ob_clean();
}
echo json_encode(['success' => true, 'data' => $data]);
