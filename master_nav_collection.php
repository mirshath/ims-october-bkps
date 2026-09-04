<?php
session_start();

include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}



require_once 'PermissionChecking.php';
/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

$message = "";
$messageType = "success";

/**
 * Escape output safely.
 */
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Normalize comma-separated sub lists.
 */
function normalizeSubLists($value)
{
    $value = trim((string)$value);

    if ($value === '') {
        return '';
    }

    $parts = preg_split('/\s*,\s*/', $value);

    $parts = array_map('trim', $parts);

    $parts = array_filter($parts, function ($item) {
        return $item !== '';
    });

    return implode(',', $parts);
}

/**
 * Return database error safely.
 */
function dbErrorMessage($stmt)
{
    return "Database operation failed: " . e($stmt->error);
}


/*
|--------------------------------------------------------------------------
| CREATE / UPDATE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = isset($_POST['action'])
        ? trim($_POST['action'])
        : '';


    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    if ($action === 'create') {

        $mainList = isset($_POST['main_list'])
            ? trim($_POST['main_list'])
            : '';

        $subLists = isset($_POST['sub_lists'])
            ? normalizeSubLists($_POST['sub_lists'])
            : '';


        if ($mainList === '') {

            $message = "Main List is required.";
            $messageType = "danger";

        } else {

            $stmt = $conn->prepare(
                "INSERT INTO nav_collections
                (main_list, sub_lists)
                VALUES (?, ?)"
            );


            if (!$stmt) {

                $message = "Unable to prepare INSERT: " . e($conn->error);
                $messageType = "danger";

            } else {

                /*
                 * main_list = string
                 * sub_lists = string
                 */
                $stmt->bind_param(
                    "ss",
                    $mainList,
                    $subLists
                );


                if ($stmt->execute()) {

                    $message = "Navigation collection added successfully.";
                    $messageType = "success";

                } else {

                    $message = dbErrorMessage($stmt);
                    $messageType = "danger";
                }


                $stmt->close();
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    if ($action === 'update') {

        $id = isset($_POST['id'])
            ? (int)$_POST['id']
            : 0;

        $mainList = isset($_POST['main_list'])
            ? trim($_POST['main_list'])
            : '';

        $subLists = isset($_POST['sub_lists'])
            ? normalizeSubLists($_POST['sub_lists'])
            : '';


        if ($id <= 0) {

            $message = "Invalid record ID.";
            $messageType = "danger";

        } elseif ($mainList === '') {

            $message = "Main List is required.";
            $messageType = "danger";

        } else {

            $stmt = $conn->prepare(
                "UPDATE nav_collections
                 SET main_list = ?,
                     sub_lists = ?
                 WHERE id = ?"
            );


            if (!$stmt) {

                $message = "Unable to prepare UPDATE: " . e($conn->error);
                $messageType = "danger";

            } else {

                /*
                 * main_list = string
                 * sub_lists = string
                 * id = integer
                 */
                $stmt->bind_param(
                    "ssi",
                    $mainList,
                    $subLists,
                    $id
                );


                if ($stmt->execute()) {

                    $message = "Navigation collection updated successfully.";
                    $messageType = "success";

                } else {

                    $message = dbErrorMessage($stmt);
                    $messageType = "danger";
                }


                $stmt->close();
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_GET['delete'])
) {

    $deleteId = (int)$_GET['delete'];


    if ($deleteId > 0) {

        $stmt = $conn->prepare(
            "DELETE FROM nav_collections
             WHERE id = ?"
        );


        if (!$stmt) {

            $message = "Unable to prepare DELETE: " . e($conn->error);
            $messageType = "danger";

        } else {

            /*
             * id = integer
             */
            $stmt->bind_param(
                "i",
                $deleteId
            );


            if ($stmt->execute()) {

                if ($stmt->affected_rows > 0) {

                    $message = "Navigation collection deleted successfully.";
                    $messageType = "success";

                } else {

                    $message = "Record not found.";
                    $messageType = "warning";
                }

            } else {

                $message = dbErrorMessage($stmt);
                $messageType = "danger";
            }


            $stmt->close();
        }

    } else {

        $message = "Invalid record ID.";
        $messageType = "danger";
    }
}


/*
|--------------------------------------------------------------------------
| EDIT RECORD
|--------------------------------------------------------------------------
*/

$editRecord = null;


if (isset($_GET['edit'])) {

    $editId = (int)$_GET['edit'];


    if ($editId > 0) {

        $stmt = $conn->prepare(
            "SELECT
                id,
                main_list,
                sub_lists
             FROM nav_collections
             WHERE id = ?"
        );


        if ($stmt) {

            /*
             * id = integer
             */
            $stmt->bind_param(
                "i",
                $editId
            );


            $stmt->execute();


            $result = $stmt->get_result();

            $editRecord = $result->fetch_assoc();


            $stmt->close();


            if (!$editRecord) {

                $message = "The selected record was not found.";
                $messageType = "warning";
            }

        } else {

            $message = "Unable to prepare SELECT: " . e($conn->error);
            $messageType = "danger";
        }
    }
}


/*
|--------------------------------------------------------------------------
| READ / GET ALL RECORDS
|--------------------------------------------------------------------------
*/

$rows = [];


$result = $conn->query(
    "SELECT
        id,
        main_list,
        sub_lists
     FROM nav_collections
     ORDER BY id ASC"
);


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $rows[] = $row;
    }


    $result->free();

} else {

    $message = "Unable to load navigation collections: "
        . e($conn->error);

    $messageType = "danger";
}

?>

<!--
|--------------------------------------------------------------------------
| Page Wrapper
|--------------------------------------------------------------------------
-->

<div id="wrapper">


    <!-- Sidebar -->
    <?php include("nav.php"); ?>


    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">


        <!-- Main Content -->
        <div id="content">


            <!-- Topbar -->
            <?php include("includes/topnav.php"); ?>


            <!-- Begin Page Content -->
            <div class="p-3">


                <!-- Page Title -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">

                    <h4 class="h4 mb-0 text-gray-800">
                        Master Navigation Collection
                    </h4>

                </div>


                <!--
                |--------------------------------------------------------------------------
                | Success / Error Message
                |--------------------------------------------------------------------------
                -->

                <?php if ($message !== ''): ?>

                    <div
                        class="alert alert-<?php echo e($messageType); ?> alert-dismissible fade show"
                        role="alert"
                    >

                        <?php echo $message; ?>


                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"
                            aria-label="Close"
                        ></button>

                    </div>


                    <?php if ($messageType === 'success'): ?>

                        <script>
                            setTimeout(function () {

                                window.location.href =
                                    'master_nav_collection.php';

                            }, 1500);
                        </script>

                    <?php endif; ?>

                <?php endif; ?>


                <!--
                |--------------------------------------------------------------------------
                | Add / Edit Form
                |--------------------------------------------------------------------------
                -->

                <div class="row mb-4">

                    <div class="col-md-12">

                        <div class="card shadow-sm">


                            <!-- Card Header -->
                            <div
                                class="card-header d-flex align-items-center"
                                style="height: 60px;"
                            >

                                <span
                                    class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                                    style="width: 30px; height: 30px;"
                                >

                                    <i
                                        class="fas <?php
                                        echo $editRecord
                                            ? 'fa-edit'
                                            : 'fa-plus-circle';
                                        ?>"
                                    ></i>

                                </span>


                                &nbsp;&nbsp;&nbsp;&nbsp;


                                <h6 class="mb-0 me-2">

                                    <?php

                                    echo $editRecord
                                        ? 'Edit Navigation Collection'
                                        : 'Add Navigation Collection';

                                    ?>

                                </h6>

                            </div>


                            <!-- Card Body -->
                            <div class="card-body">


                                <form
                                    method="POST"
                                    action=""
                                    autocomplete="off"
                                >


                                    <!-- Action -->
                                    <input
                                        type="hidden"
                                        name="action"
                                        value="<?php
                                        echo $editRecord
                                            ? 'update'
                                            : 'create';
                                        ?>"
                                    >


                                    <!-- ID for Update -->
                                    <?php if ($editRecord): ?>

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?php
                                            echo (int)$editRecord['id'];
                                            ?>"
                                        >

                                    <?php endif; ?>


                                    <div class="row">


                                        <!-- Main List -->
                                        <div class="col-md-4 mb-3">

                                            <label
                                                for="main_list"
                                                class="form-label"
                                            >

                                                Main List

                                                <span class="text-danger">
                                                    *
                                                </span>

                                            </label>


                                            <input
                                                type="text"
                                                class="form-control"
                                                id="main_list"
                                                name="main_list"
                                                maxlength="250"
                                                required
                                                value="<?php
                                                echo e(
                                                    $editRecord['main_list']
                                                    ?? ''
                                                );
                                                ?>"
                                                placeholder="Example: File"
                                            >

                                        </div>


                                        <!-- Sub Lists -->
                                        <div class="col-md-8 mb-3">

                                            <label
                                                for="sub_lists"
                                                class="form-label"
                                            >

                                                Sub Lists

                                            </label>


                                            <textarea
                                                class="form-control"
                                                id="sub_lists"
                                                name="sub_lists"
                                                rows="14"
                                                placeholder="Example: Lead Types - create_lead,DBTablesCount - db_tables_count"
                                            ><?php

                                            echo e(
                                                $editRecord['sub_lists']
                                                ?? ''
                                            );

                                            ?></textarea>


                                            <small class="text-muted">

                                                Store sub-items using the
                                                existing format:

                                                <strong>
                                                    Display Name - page_name
                                                </strong>

                                                separated by commas.

                                            </small>

                                        </div>

                                    </div>


                                    <!-- Buttons -->
                                    <div class="d-flex gap-2">


                                        <button
                                            type="submit"
                                            class="btn btn-primary"
                                        >

                                            <i
                                                class="fas <?php
                                                echo $editRecord
                                                    ? 'fa-save'
                                                    : 'fa-plus-circle';
                                                ?> me-1"
                                            ></i>


                                            <?php

                                            echo $editRecord
                                                ? 'Update'
                                                : 'Add';

                                            ?>

                                        </button>


                                        <?php if ($editRecord): ?>

                                            <a
                                                href="master_nav_collection.php"
                                                class="btn btn-secondary"
                                            >

                                                <i
                                                    class="fas fa-times me-1"
                                                ></i>

                                                Cancel

                                            </a>

                                        <?php else: ?>

                                            <button
                                                type="reset"
                                                class="btn btn-secondary"
                                            >

                                                <i
                                                    class="fas fa-undo me-1"
                                                ></i>

                                                Clear

                                            </button>

                                        <?php endif; ?>


                                    </div>


                                </form>


                            </div>

                        </div>

                    </div>

                </div>


                <!--
                |--------------------------------------------------------------------------
                | Data Table
                |--------------------------------------------------------------------------
                -->

                <div class="row mb-5">

                    <div class="col-md-12">

                        <div class="card shadow-sm">


                            <!-- Card Header -->
                            <div
                                class="card-header d-flex align-items-center"
                                style="height: 60px;"
                            >

                                <span
                                    class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                                    style="width: 30px; height: 30px;"
                                >

                                    <i class="fas fa-list"></i>

                                </span>


                                &nbsp;&nbsp;&nbsp;&nbsp;


                                <h6 class="mb-0 me-2">
                                    Navigation Collections
                                </h6>

                            </div>


                            <!-- Card Body -->
                            <div class="card-body">


                                <div class="table-responsive mb-4">


                                    <table
                                        id="navCollectionsTable"
                                        class="table table-striped table-bordered align-middle"
                                        style="width: 100%; font-size: 11px;"
                                    >


                                        <thead class="table-dark">

                                            <tr>

                                                <th style="width: 70px;">
                                                    ID
                                                </th>

                                                <th style="width: 180px;">
                                                    Main List
                                                </th>

                                                <th>
                                                    Sub Lists
                                                </th>

                                                <th style="width: 150px;">
                                                    Actions
                                                </th>

                                            </tr>

                                        </thead>


                                        <tbody>


                                            <?php if (count($rows) > 0): ?>


                                                <?php foreach ($rows as $row): ?>


                                                    <tr>


                                                        <!-- ID -->
                                                        <td>

                                                            <?php
                                                            echo (int)$row['id'];
                                                            ?>

                                                        </td>


                                                        <!-- Main List -->
                                                        <td>

                                                            <strong>
                                                                <?php
                                                                echo e(
                                                                    $row['main_list']
                                                                );
                                                                ?>
                                                            </strong>

                                                        </td>


                                                        <!-- Sub Lists -->
                                                        <td style="font-size: 13px;">


                                                            <?php

                                                            $subItems = preg_split(
                                                                '/\s*,\s*/',
                                                                trim(
                                                                    $row['sub_lists']
                                                                )
                                                            );


                                                            $subItems = array_filter(
                                                                $subItems,
                                                                function ($item) {

                                                                    return trim(
                                                                        $item
                                                                    ) !== '';

                                                                }
                                                            );

                                                            ?>


                                                            <?php if (count($subItems) > 0): ?>


                                                                <div
                                                                    class="d-flex flex-wrap gap-1"
                                                                >


                                                                    <?php foreach (
                                                                        $subItems
                                                                        as $subItem
                                                                    ): ?>


                                                                        <span
                                                                            class="badge bg-light text-dark border"
                                                                        >

                                                                            <?php
                                                                            echo e(
                                                                                trim(
                                                                                    $subItem
                                                                                )
                                                                            );
                                                                            ?>

                                                                        </span>


                                                                    <?php endforeach; ?>


                                                                </div>


                                                            <?php else: ?>


                                                                <span class="text-muted">
                                                                    No sub lists
                                                                </span>


                                                            <?php endif; ?>


                                                        </td>


                                                        <!-- Actions -->
                                                        <td>


                                                            <div
                                                                class="btn-group btn-group-sm"
                                                                role="group"
                                                            >


                                                                <!-- Edit -->
                                                                <a
                                                                    href="?edit=<?php
                                                                    echo (int)$row['id'];
                                                                    ?>"
                                                                    class="btn btn-warning"
                                                                    title="Edit"
                                                                >

                                                                    <i
                                                                        class="fas fa-edit"
                                                                    ></i>

                                                                </a>


                                                                <!-- Delete -->
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-danger delete-btn"
                                                                    data-id="<?php
                                                                    echo (int)$row['id'];
                                                                    ?>"
                                                                    data-name="<?php
                                                                    echo e(
                                                                        $row['main_list']
                                                                    );
                                                                    ?>"
                                                                    title="Delete"
                                                                >

                                                                    <i
                                                                        class="fas fa-trash"
                                                                    ></i>

                                                                </button>


                                                            </div>


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

    </div>

</div>


<!--
|--------------------------------------------------------------------------
| Delete Confirmation Modal
|--------------------------------------------------------------------------
-->

<div
    class="modal fade"
    id="deleteModal"
    tabindex="-1"
    aria-labelledby="deleteModalLabel"
    aria-hidden="true"
>


    <div class="modal-dialog modal-dialog-centered">


        <div class="modal-content">


            <div class="modal-header">


                <h5
                    class="modal-title"
                    id="deleteModalLabel"
                >
                    Confirm Delete
                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>


            </div>


            <div class="modal-body">

                Are you sure you want to delete

                <strong id="deleteRecordName"></strong>?

            </div>


            <div class="modal-footer">


                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >

                    Cancel

                </button>


                <a
                    href="#"
                    id="confirmDeleteButton"
                    class="btn btn-danger"
                >

                    <i class="fas fa-trash me-1"></i>

                    Delete

                </a>


            </div>


        </div>

    </div>

</div>


<!--
|--------------------------------------------------------------------------
| JavaScript / DataTables
|--------------------------------------------------------------------------
-->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>

<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>


<!-- Select2 CSS -->
<link
    href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css"
    rel="stylesheet"
/>


<!-- DataTables CSS -->
<link
    href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css"
    rel="stylesheet"
/>


<script>

$(document).ready(function () {


    /*
    |--------------------------------------------------------------------------
    | DataTable
    |--------------------------------------------------------------------------
    */

    $('#navCollectionsTable').DataTable({

        pageLength: 10,

        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "All"]
        ],

        order: [
            [0, 'asc']
        ],

        columnDefs: [

            {
                orderable: false,
                targets: [2, 3]
            }

        ]

    });


    /*
    |--------------------------------------------------------------------------
    | Delete Confirmation
    |--------------------------------------------------------------------------
    */

    $('.delete-btn').on('click', function () {


        const id = $(this).data('id');

        const name = $(this).data('name');


        $('#deleteRecordName').text(name);


        $('#confirmDeleteButton').attr(
            'href',
            'master_nav_collection.php?delete='
            + encodeURIComponent(id)
        );


        const modalElement =
            document.getElementById('deleteModal');


        /*
        |--------------------------------------------------------------------------
        | Bootstrap 5 Modal
        |--------------------------------------------------------------------------
        */

        if (
            typeof bootstrap !== 'undefined'
            && bootstrap.Modal
        ) {

            bootstrap.Modal
                .getOrCreateInstance(modalElement)
                .show();

        } else {

            /*
            |--------------------------------------------------------------------------
            | Fallback
            |--------------------------------------------------------------------------
            */

            if (
                confirm(
                    'Are you sure you want to delete "'
                    + name
                    + '"?'
                )
            ) {

                window.location.href =
                    'master_nav_collection.php?delete='
                    + encodeURIComponent(id);

            }

        }

    });


});

</script>


</body>
</html>