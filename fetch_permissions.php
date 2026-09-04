<?php
include("database/connection.php");

// $userId = $_POST['user_id'] ?? null;
// $existing_permissions = [];

// if ($userId) {
//     $perm_query = "SELECT nav_items, sub_list_value FROM user_permission WHERE user_id = '$userId'";
//     $perm_result = mysqli_query($conn, $perm_query);
//     while ($perm = mysqli_fetch_assoc($perm_result)) {
//         $existing_permissions[$perm['nav_items']][] = $perm['sub_list_value'];
//     }
// }

// $nav_query = "SELECT * FROM nav_collections";
// $nav_result = mysqli_query($conn, $nav_query);

// while ($nav = mysqli_fetch_assoc($nav_result)) {
//     echo "<div class='col-md-4 mb-4'>";
//     echo "<div class='card'>";
//     echo "<div class='card-header'>";
//     echo "<h5 class='card-title'>{$nav['main_list']}</h5>";
//     echo "</div>";
//     // echo "<div class='card-body'>";
//     echo "<div class='card-body' style='font-size: 12px;'>";
//     $sub_lists = explode(',', $nav['sub_lists']);
//     foreach ($sub_lists as $index => $item) {
//         $item = trim($item);
//         $checked = (isset($existing_permissions[$nav['id']]) && in_array($item, $existing_permissions[$nav['id']])) ? "checked" : "";
//         $inputId = "perm_{$nav['id']}_{$index}";

//         echo "<div class='form-group mb-3 d-flex align-items-center'>";
//         echo "<label class='switch me-2'>";
//         echo "<input type='checkbox' id='$inputId' name='permissions[{$nav['id']}][]' value='$item' $checked>";
//         echo "<span class='slider'></span>";
//         echo "</label>";
//         echo "<label for='$inputId' class='mb-0'>$item</label>";
//         echo "</div>";      
//     }
//     echo "</div>";
//     echo "</div>";
//     echo "</div>";
// }
// 



// ----------------- new changed  02.07 .2025 --------------------- 

$userId = $_POST['user_id'] ?? null;
$existing_permissions = [];

if ($userId) {
    $perm_query = "SELECT nav_items, sub_list_value FROM user_permission WHERE user_id = '$userId'";
    $perm_result = mysqli_query($conn, $perm_query);
    while ($perm = mysqli_fetch_assoc($perm_result)) {
        $existing_permissions[$perm['nav_items']][] = $perm['sub_list_value'];
    }
}

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

        // Split item at dash
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



