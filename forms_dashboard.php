<?php
session_start();

include("database/connection.php");
include("includes/header.php");

// ============================================================
// 1. CHECK LOGIN
// ============================================================

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}


// ============================================================
// 2. GET SESSION DETAILS
// ============================================================

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$role    = $_SESSION['role'] ?? '';


// ============================================================
// 3. HANDLE DELETE REQUEST
// ============================================================
// Only super_admin can delete forms.
// ============================================================

if (isset($_GET['delete'])) {

    // Security check
    if ($role !== 'super_admin') {
        echo '<script>
                alert("You do not have permission to delete forms.");
                window.location.href = "forms_dashboard.php";
              </script>';
        exit();
    }

    $del_id = (int)$_GET['delete'];

    if ($del_id > 0) {

        $stmt = $conn->prepare("DELETE FROM forms WHERE id = ?");

        if ($stmt) {

            $stmt->bind_param("i", $del_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    echo '<script>
            window.location.href = "forms_dashboard.php";
          </script>';
    exit();
}


// ============================================================
// 4. FETCH FORMS
// ============================================================

$forms = [];


// ------------------------------------------------------------
// SUPER ADMIN
// ------------------------------------------------------------
// Super admin can see ALL forms.
// ------------------------------------------------------------

if ($role === 'super_admin') {

    $stmt = $conn->prepare("
        SELECT
            f.id,
            f.program_code,
            p.program_name,
            f.title,
            f.description,
            f.status,
            f.accepting_responses,
            f.created_at
        FROM forms f

        LEFT JOIN program_table p
            ON p.program_code = f.program_code

        ORDER BY f.created_at DESC
    ");

    if ($stmt) {

        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $forms[] = $row;
        }

        $stmt->close();
    }
}


// ------------------------------------------------------------
// NORMAL USER
// ------------------------------------------------------------
// User can see only forms belonging to programs allocated
// to the logged-in user in program_allocation_user.
// ------------------------------------------------------------

else {

    if ($user_id > 0) {

        $stmt = $conn->prepare("
            SELECT
                f.id,
                f.program_code,
                p.program_name,
                f.title,
                f.description,
                f.status,
                f.accepting_responses,
                f.created_at

            FROM forms f

            INNER JOIN program_table p
                ON p.program_code = f.program_code

            WHERE EXISTS (

                SELECT 1

                FROM program_allocation_user pau

                WHERE pau.program_code = f.program_code

                AND pau.user_id = ?

            )

            ORDER BY f.created_at DESC
        ");

        if ($stmt) {

            $stmt->bind_param("i", $user_id);

            $stmt->execute();

            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $forms[] = $row;
            }

            $stmt->close();
        }
    }
}

?>

<!-- ============================================================
     DATATABLE
============================================================= -->

<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

<link rel="stylesheet"
    href="./vendor/datatables/dataTables.bootstrap4.min.css">

<script src="js/demo/datatables-demo.js"></script>


<!-- ============================================================
     PAGE
============================================================= -->

<div id="wrapper">

    <?php include("nav.php"); ?>


    <div id="content-wrapper" class="d-flex flex-column">

        <div id="content">

            <?php include("includes/topnav.php"); ?>


            <div class="container">


                <!-- ==================================================
                     PAGE HEADER
                =================================================== -->

                <div class="d-sm-flex align-items-center justify-content-between mb-4">

                    <h4 class="h4 mb-0 text-gray-800">
                        Form Builder — My Forms
                    </h4>


                    <a href="create_form.php"
                        class="btn btn-primary btn-sm">

                        <i class="fas fa-plus"></i>
                        Create New Form

                    </a>

                </div>


                <!-- ==================================================
                     FORMS CARD
                =================================================== -->

                <div class="card" style="font-size: 12px;">


                    <!-- CARD HEADER -->

                    <div class="card-header d-flex align-items-center"
                        style="height: 60px;">

                        <span
                            class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 30px; height: 30px;">

                            <i class="fas fa-list"></i>

                        </span>

                        &nbsp;&nbsp;&nbsp;&nbsp;

                        <h6 class="mb-0">
                            All Forms
                        </h6>

                    </div>


                    <!-- CARD BODY -->

                    <div class="card-body">


                        <div class="table-responsive">

                            <table id="formsTable"
                                class="table table-striped">

                                <thead>

                                    <tr>

                                        <th>
                                            Program Name
                                        </th>

                                        <th>
                                            Title
                                        </th>

                                        <th>
                                            Description
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                        <th>
                                            Accepting Responses
                                        </th>

                                        <th>
                                            Created At
                                        </th>

                                        <th>
                                            Actions
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                    <?php if (!empty($forms)): ?>


                                        <?php foreach ($forms as $form): ?>

                                            <tr>


                                                <!-- PROGRAM NAME -->

                                                <td>

                                                    <?php if (!empty($form['program_name'])): ?>

                                                        <?= htmlspecialchars(
                                                            $form['program_name'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                        <small class="text-muted d-block">

                                                            Code:
                                                            <?= (int)$form['program_code'] ?>

                                                        </small>

                                                    <?php else: ?>

                                                        <span class="text-muted">
                                                            Program not found
                                                        </span>

                                                    <?php endif; ?>

                                                </td>


                                                <!-- TITLE -->

                                                <td>

                                                    <?= htmlspecialchars(
                                                        $form['title'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>

                                                </td>


                                                <!-- DESCRIPTION -->

                                                <td>

                                                    <?= htmlspecialchars(
                                                        mb_strimwidth(
                                                            $form['description'] ?? '',
                                                            0,
                                                            60,
                                                            '...'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>

                                                </td>
                                                <!-- STATUS -->
                                                <td>

                                                    <?php

                                                    $badgeClass = 'warning';

                                                    if ($form['status'] === 'published') {

                                                        $badgeClass = 'success';
                                                    } elseif ($form['status'] === 'closed') {

                                                        $badgeClass = 'secondary';
                                                    }

                                                    ?>

                                                    <span class="badge badge-<?= $badgeClass ?>">

                                                        <?= htmlspecialchars(
                                                            ucfirst($form['status']),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                    </span>

                                                </td>
                                                <!-- ACCEPTING RESPONSES -->
                                                <td >

                                                    <?php if ($form['accepting_responses']): ?>

                                                        <span class="text-success">
                                                            Yes
                                                        </span>

                                                    <?php else: ?>

                                                        <span class="text-danger">
                                                            No
                                                        </span>

                                                    <?php endif; ?>

                                                </td>
                                                <!-- CREATED AT -->
                                                <td>

                                                    <?= htmlspecialchars(
                                                        $form['created_at'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>

                                                </td>
                                                <!-- ACTIONS -->
                                                <td>

                                                    <!-- ==========================================
                                                         OPEN FORM
                                                    =========================================== -->

                                                    <?php

                                                    $form_slug = strtolower(
                                                        trim(
                                                            preg_replace(
                                                                '/[^A-Za-z0-9]+/',
                                                                '-',
                                                                $form['title']
                                                            ),
                                                            '-'
                                                        )
                                                    );

                                                    ?>


                                                    <a
                                                        href="bms-form/<?= (int)$form['id'] ?>/<?= htmlspecialchars(
                                                                                                    $form_slug,
                                                                                                    ENT_QUOTES,
                                                                                                    'UTF-8'
                                                                                                ) ?>"
                                                        target="_blank"
                                                        class="btn btn-info btn-sm"
                                                        title="Open Form">

                                                        <i class="fas fa-eye"></i>

                                                    </a>


                                                    <!-- ==========================================
                                                         VIEW RESPONSES
                                                    =========================================== -->

                                                    <a
                                                        href="view_responses.php?form_id=<?= (int)$form['id'] ?>&title=<?= urlencode($form['title']) ?>"
                                                        class="btn btn-success btn-sm"
                                                        title="Responses">

                                                        <i class="fas fa-chart-bar"></i>

                                                    </a>


                                                    <!-- ==========================================
                                                         SUPER ADMIN ONLY
                                                         EDIT + DELETE
                                                    =========================================== -->

                                                    <?php if ($role === 'super_admin'): ?>


                                                        <!-- EDIT -->

                                                        <a
                                                            href="create_form.php?id=<?= (int)$form['id'] ?>"
                                                            class="btn btn-primary btn-sm"
                                                            title="Edit">

                                                            <i class="fas fa-edit"></i>

                                                        </a>


                                                        <!-- DELETE -->

                                                        <a
                                                            href="forms_dashboard.php?delete=<?= (int)$form['id'] ?>"
                                                            class="btn btn-danger btn-sm"
                                                            title="Delete"
                                                            onclick="return confirm('Delete this form and all its responses?');">

                                                            <i class="fas fa-trash"></i>

                                                        </a>


                                                    <?php endif; ?>


                                                </td>

                                            </tr>

                                        <?php endforeach; ?>


                                    <?php endif; ?>


                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- ============================================================
     DATATABLE INITIALIZATION
============================================================= -->

<script>
    $(document).ready(function() {

        $('#formsTable').DataTable({

            paging: true,

            searching: true,

            ordering: true,

            pageLength: 10

        });

    });
</script>


<?php

$conn->close();

?>