<?php
include("database/connection.php");
include("includes/header.php");

$query = "SELECT scm.*, pt.program_name, bt.batch_name, mt.module_name
          FROM special_class_messages scm
          JOIN program_table pt ON scm.program_code = pt.program_code
          JOIN batch_table bt ON scm.batch_id = bt.id
          JOIN module_table mt ON scm.module_id = mt.id
          ORDER BY scm.class_date DESC, scm.class_time DESC";

$result = mysqli_query($conn, $query);
?>

<div class="container mt-5">
    <h4 class="mb-4">All Special Classes</h4>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Programme</th>
                <th>Batch</th>
                <th>Module</th>
                <th>Date</th>
                <th>Time</th>
                <th>Subject</th>
                <th>Link</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['program_name']) ?></td>
                    <td><?= htmlspecialchars($row['batch_name']) ?></td>
                    <td><?= htmlspecialchars($row['module_name']) ?></td>
                    <td><?= htmlspecialchars($row['class_date']) ?></td>
                    <td><?= htmlspecialchars($row['class_time']) ?></td>
                    <td><?= htmlspecialchars($row['mail_subject']) ?></td>
                    <td><a href="<?= htmlspecialchars($row['link']) ?>" target="_blank">Link</a></td>
                    <td><?= $row['description'] ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
