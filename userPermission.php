<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}



// ---------------------------- allowed Redirections --------------------------- 
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// ----------------------------------------------------------------------------- 


$user_id = null;
$existing_permissions = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['user']) && !empty($_POST['user'])) {
        $user_id = $_POST['user']; // Store user ID
    }

    if (isset($_POST['submit']) && $user_id) {
        $check_user_query = "SELECT id FROM admin WHERE id = '$user_id'";
        $check_user_result = mysqli_query($conn, $check_user_query);
        if (mysqli_num_rows($check_user_result) > 0) {

            // Clear existing permissions for the user
            $delete_permissions_query = "DELETE FROM user_permission WHERE user_id = '$user_id'";
            mysqli_query($conn, $delete_permissions_query);

            // Save new permissions 
            if (isset($_POST['permissions']) && !empty($_POST['permissions'])) {
                foreach ($_POST['permissions'] as $nav_id => $sub_lists) {
                    foreach ($sub_lists as $sub_list) {
                        $sub_list_value = mysqli_real_escape_string($conn, $sub_list);
                        $insert_query = "INSERT INTO user_permission (user_id, nav_items, sub_list_value) 
                                         VALUES ('$user_id', '$nav_id', '$sub_list_value')";
                        mysqli_query($conn, $insert_query);
                    }
                }
                // echo "<script>alert('Permissions saved successfully!');</script>";
                $_SESSION['message'] = "Permissions saved successfully!";

                echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
            } else {
                // echo "<script>alert('No permissions selected.');</script>";
                $_SESSION['error'] = "No permissions selected.";
                echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
            }
        } else {
            echo "<script>alert('User not found.');</script>";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        }
    }
}

// Fetch existing permissions when a user is selected
if ($user_id) {
    $perm_query = "SELECT nav_items, sub_list_value FROM user_permission WHERE user_id = '$user_id'";
    $perm_result = mysqli_query($conn, $perm_query);
    while ($perm = mysqli_fetch_assoc($perm_result)) {
        $existing_permissions[$perm['nav_items']][] = $perm['sub_list_value'];
    }
}
?>

<!-- Page Wrapper -->
<div id="wrapper">
    <?php include("nav.php"); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>

            <div class="p-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">User Permission Management</h4>
                </div>

                <form method="POST" action="" id="permissionForm">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header d-flex align-items-center" style="height: 60px;">
                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                        <i class="fas fa-user"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">Select User</h6>
                                </div>
                                <div class="card-body">
                                    <select id="user" name="user" class="select2" style="width: 100%;" required>
                                        <option value="" selected>Select a user</option>
                                        <?php
                                        $query = "SELECT id, username FROM admin";
                                        $result = mysqli_query($conn, $query);
                                        while ($user = mysqli_fetch_assoc($result)) {
                                            $selected = ($user_id == $user['id']) ? '' : '';
                                            echo "<option value='{$user['id']}' $selected>{$user['username']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nav Collection -->
                    <div class="row mb-5">
                        <div class="col-md-12">

                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                                <label class="form-check-label" for="selectAll">Select All</label>
                            </div>
                            <!-- <div class="card-body">
                                <div class="row" id="navPermissions">
                                    <?php
                                    $nav_query = "SELECT * FROM nav_collections";
                                    $nav_result = mysqli_query($conn, $nav_query);
                                    while ($nav = mysqli_fetch_assoc($nav_result)) {
                                        echo "<div class='col-md-4 mb-4'>";
                                        echo "<div class='card'>";
                                        echo "<div class='card-header'>";
                                        echo "<h5 class='card-title'>{$nav['main_list']}</h5>";
                                        echo "</div>";
                                        echo "<div class='card-body' style='font-size: 12px;'>";
                                        $sub_lists = explode(',', $nav['sub_lists']);
                                        foreach ($sub_lists as $index => $item) {
                                            $item = trim($item);
                                            $checked = (isset($existing_permissions[$nav['id']]) && in_array($item, $existing_permissions[$nav['id']])) ? "checked" : "";
                                            $inputId = "perm_{$nav['id']}_{$index}";

                                            echo "<div class='form-group mb-3 d-flex align-items-center'>";
                                            echo "<label class='switch me-2'>";
                                            echo "<input type='checkbox' id='$inputId' name='permissions[{$nav['id']}][]' value='$item' $checked>";
                                            echo "<span class='slider'></span>";
                                            echo "</label>";
                                            echo "<label for='$inputId' class='mb-0'>$item</label>";
                                            echo "</div>";
                                        }
                                        echo "</div>";
                                        echo "</div>";
                                        echo "</div>";
                                    }
                                    ?>
                                </div>


                                <style>
                                    /* The switch - the box around the slider */
                                    .switch {
                                        font-size: 14px;
                                        position: relative;
                                        display: inline-block;
                                        width: 2.5em;
                                        height: 1.4em;
                                    }

                                    /* Hide default checkbox */
                                    .switch input {
                                        opacity: 0;
                                        width: 0;
                                        height: 0;
                                    }

                                    /* Slider track */
                                    .slider {
                                        position: absolute;
                                        cursor: pointer;
                                        inset: 0;
                                        background: #9fccfa;
                                        border-radius: 34px;
                                        transition: all 0.3s ease;
                                    }

                                    /* Slider knob */
                                    .slider:before {
                                        position: absolute;
                                        content: "";
                                        height: 1.2em;
                                        width: 1.2em;
                                        left: 0.1em;
                                        bottom: 0.1em;
                                        background-color: white;
                                        border-radius: 50%;
                                        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
                                        transition: all 0.3s ease;
                                    }

                                    .switch input:checked+.slider {
                                        background: #0974f1;
                                    }

                                    .switch input:checked+.slider:before {
                                        transform: translateX(1.1em);
                                    }
                                </style>
                            </div> -->


                            <!-- --------------  -->
                            <div class="card-body">
                                <div class="row" id="navPermissions">
                                    <?php
                                    $nav_query = "SELECT * FROM nav_collections";
                                    $nav_result = mysqli_query($conn, $nav_query);
                                    while ($nav = mysqli_fetch_assoc($nav_result)) {
                                        echo "<div class='col-md-4 mb-4'>";
                                        echo "<div class='card'>";
                                        echo "<div class='card-header'>";
                                        echo "<h5 class='card-title'>{$nav['main_list']}</h5>";
                                        echo "</div>";
                                        echo "<div class='card-body' style='font-size: 12px;'>";
                                        $sub_lists = explode(',', $nav['sub_lists']);
                                        foreach ($sub_lists as $index => $item) {
                                            $item = trim($item);
                                            $checked = (isset($existing_permissions[$nav['id']]) && in_array($item, $existing_permissions[$nav['id']])) ? "checked" : "";
                                            $inputId = "perm_{$nav['id']}_{$index}";

                                            $parts = explode('-', $item, 2);
                                            $beforeDash = trim($parts[0]);
                                            $afterDash = isset($parts[1]) ? "<span class='text-muted' style='display: none;'> - " . trim($parts[1]) . "</span>" : "";

                                            echo "<div class='form-group mb-3 d-flex align-items-center'>";
                                            echo "<label class='switch me-2'>";
                                            echo "<input type='checkbox' id='$inputId' name='permissions[{$nav['id']}][]' value='$item' $checked>";
                                            echo "<span class='slider'></span>";
                                            echo "</label>";
                                            echo "<label for='$inputId' class='mb-0'>{$beforeDash}{$afterDash}</label>";
                                            echo "</div>";
                                        }
                                        echo "</div>";
                                        echo "</div>";
                                        echo "</div>";
                                    }
                                    ?>
                                </div>

                                <style>
                                    /* The switch - the box around the slider */
                                    .switch {
                                        font-size: 14px;
                                        position: relative;
                                        display: inline-block;
                                        width: 2.5em;
                                        height: 1.4em;
                                    }

                                    /* Hide default checkbox */
                                    .switch input {
                                        opacity: 0;
                                        width: 0;
                                        height: 0;
                                    }

                                    /* Slider track */
                                    .slider {
                                        position: absolute;
                                        cursor: pointer;
                                        inset: 0;
                                        background: #9fccfa;
                                        border-radius: 34px;
                                        transition: all 0.3s ease;
                                    }

                                    /* Slider knob */
                                    .slider:before {
                                        position: absolute;
                                        content: "";
                                        height: 1.2em;
                                        width: 1.2em;
                                        left: 0.1em;
                                        bottom: 0.1em;
                                        background-color: white;
                                        border-radius: 50%;
                                        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
                                        transition: all 0.3s ease;
                                    }

                                    .switch input:checked+.slider {
                                        background: #0974f1;
                                    }

                                    .mb-3 {
                                        margin-bottom: 0.4rem !important;
                                    }

                                    .switch input:checked+.slider:before {
                                        transform: translateX(1.1em);
                                    }

                                    .text-muted {
                                        color: #6c757d;
                                        font-size: 90%;
                                    }
                                </style>
                            </div>

                            <!-- --------------  -->
                        </div>
                    </div>


                    <!-- Submit Button -->
                    <!-- <button type="submit" name="submit" class="btn btn-primary">Save Permissions</button> -->
                    <!-- <div class="d-flex justify-content-end align-items-start mb-3">
                        <button type="submit" name="submit" class="btn btn-primary">Save Permissions</button>
                    </div> -->
                    <div style="position: relative;">
                        <button type="submit" name="submit"
                            class="btn btn-primary"
                            style="position: fixed; top: 80px; right: 30px;">
                            Save Permissions
                        </button>

                    </div>


                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('#user').select2({
            placeholder: "Select a user"
        });


        // Select/Deselect all checkboxes when "Select All" checkbox is clicked
        // $('#selectAll').change(function() {
        //     $('.form-check-input').prop('checked', $(this).prop('checked'));
        // });
        // Select/Deselect all checkboxes inside navPermissions
        $('#selectAll').change(function() {
            $('#navPermissions input[type="checkbox"]').prop('checked', $(this).prop('checked'));
        });


        // Fetch permissions when user is selected
        $('#user').change(function() {
            var userId = $(this).val();
            if (userId) {
                $.ajax({
                    url: 'fetch_permissions.php',
                    type: 'POST',
                    data: {
                        user_id: userId
                    },
                    success: function(response) {
                        $('#navPermissions').html(response);
                    }
                });
            } else {
                $('#navPermissions').html('');
            }
        });
    });
</script>
</body>

</html>