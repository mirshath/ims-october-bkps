<?php
include 'database/connection.php';

$programme = $_POST['programme'] ?? '';

if ($programme) {
    $stmt = $conn->prepare("SELECT * FROM induction_students WHERE programme=? AND email_sent=0 ORDER BY id DESC");
    $stmt->bind_param("s", $programme);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM induction_students WHERE email_sent=0 ORDER BY id DESC");
}
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<!-- DataTables Responsive (optional) -->
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<table id="studentsDataTable" class="table table-striped table-bordered" style="width:100%">
    <thead>
        <tr>
            <th>No</th>
            <th>Ref No</th>
            <th>Name</th>
            <th>NIC</th>
            <th>Email</th>
            <th>Programme</th>
            <th>Contact</th>
            <th>Gender</th>
            <th>Paid</th>
            <th>Email Sent</th>
            <th>Email Sent Time</th>
            <th>Email Sent By</th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1;
        while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['ref_no']) ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['nic']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['programme']) ?></td>
                <td><?= htmlspecialchars($row['contact_no']) ?></td>
                <td><?= htmlspecialchars($row['gender']) ?></td>
                <td><?= htmlspecialchars($row['paid']) ?></td>
                <td><?= $row['email_sent'] ? '✅ Yes' : '❌ No' ?></td>
                <td><?= htmlspecialchars($row['email_sent_time']) ?></td>
                <td><?= htmlspecialchars($row['email_sent_by']) ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- jQuery (required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<!-- DataTables Responsive JS (optional) -->
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script>
    $(document).ready(function() {
        $('#studentsDataTable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "pageLength": 250,
            "order": [
                [0, "asc"]
            ]
        });
    });
</script>