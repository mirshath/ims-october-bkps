<?php
session_start();
ob_start(); // Start output buffering

include("database/connection.php");
include("includes/header.php");

$session_role = $_SESSION['role'];
$disabled_m_d = ($session_role == 'manager' || $session_role == 'data_enter') ? 'disabled' : '';

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

// Permission Checking
require_once 'PermissionChecking.php';
?>

<!-- Page Wrapper -->
<div id="wrapper">
    <?php include("nav.php"); ?>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
        <!-- Main Content -->
        <div id="content">
            <!-- Topbar -->
            <?php include("includes/topnav.php"); ?>
            <!-- End of Topbar -->

            <!-- Begin Page Content -->
            <div class="container mt-4">

                <h2 class="text-center">🔔 All Notifications</h2>

                <!-- Bulk Action Controls (above table) -->
                 <!-- Bulk Action Controls (above table) -->
                <div class="d-flex justify-content-end mb-2 align-items-center">
                    <div>
                        <input type="checkbox" id="selectAll"> <label for="selectAll">Select All</label>
                        <button id="markSelectedBtn" class="btn btn-success btn-sm ms-2">Mark Selected as Read</button>
                    </div>
                </div>


                <div class="card">
                    <div class="card-body">

                        <!-- Notifications Table -->
                        <table id="notificationsTable" class="display table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Action</th>
                                    <th>Table Name</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Checkbox</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded dynamically -->
                            </tbody>
                        </table>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Footer Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
    function loadNotifications() {
        fetch("get_all_notifications.php")
            .then(response => response.json())
            .then(data => {
                let tbody = "";
                if (data.length === 0) {
                    tbody = `<tr><td colspan="7" class="text-center">No notifications yet.</td></tr>`;
                } else {
                    data.forEach(n => {
                        let statusBadge = (n.status === "unread") ?
                            '<span class="badge bg-primary">Unread</span>' :
                            '<span class="badge bg-success">Read</span>';

                        let checkbox = (n.status === "unread") ?
                            `<input type="checkbox" class="notif-checkbox" value="${n.id}">` :
                            '';

                        tbody += `
                        <tr>
                            <td>${n.id}</td>
                            <td>${n.action.toUpperCase()}</td>
                            <td>${n.table_name}</td>
                            <td>${n.message}</td>
                            <td>${statusBadge}</td>
                            <td>${n.created_at}</td>
                            <td>${checkbox}</td>
                        </tr>
                    `;
                    });
                }
                $('#notificationsTable tbody').html(tbody);

                if ($.fn.DataTable.isDataTable('#notificationsTable')) {
                    $('#notificationsTable').DataTable().destroy();
                }

                $('#notificationsTable').DataTable({
                    "order": [
                        [5, "desc"]
                    ],
                    "pageLength": 1000
                });
            })
            .catch(error => console.error("Error fetching notifications:", error));
    }

    // Individual mark as read
    function markAsRead(id) {
        fetch("mark_as_read.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "id=" + id
            })
            .then(res => res.text())
            .then(data => {
                if (data === "success") {
                    loadNotifications();
                }
            });
    }

    // Select All toggle
    $(document).on("change", "#selectAll", function() {
        const isChecked = $(this).is(":checked");
        $(".notif-checkbox").prop("checked", isChecked);
    });

    // Bulk Mark as Read
    $(document).on("click", "#markSelectedBtn", function() {
        const selected = $(".notif-checkbox:checked")
            .map(function() {
                return this.value;
            })
            .get();

        if (selected.length === 0) {
            alert("No notifications selected.");
            return;
        }

        if (!confirm("Mark selected notifications as read?")) return;

        fetch("mark_as_read_bulk.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    ids: selected
                })
            })
            .then(res => res.text())
            .then(data => {
                if (data === "success") {
                    loadNotifications();
                    setInterval(loadNotifications, 2000);
                    location.reload();
                } else {
                    alert("Error updating notifications.");
                }
            });
    });

    // Load immediately & refresh every 2s
    loadNotifications();
    // setInterval(loadNotifications, 2000);
</script>

</body>

</html>