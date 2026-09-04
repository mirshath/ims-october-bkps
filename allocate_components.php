<?php
session_start();
include("database/connection.php");

if (!isset( $_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}
// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Fetch values from the form
    $moduleId = isset($_POST['module_id']) ? intval($_POST['module_id']) : null; // Handle module_id
    $mainComponentId = isset($_POST['main_component']) ? intval($_POST['main_component']) : null;
    $hasSubComponent = isset($_POST['has_sub_component']) ? $_POST['has_sub_component'] : null;
    $subComponentIds = isset($_POST['sub_component']) ? $_POST['sub_component'] : null; // This will be an array of subcomponent IDs

    // Validate required fields
    if (!$moduleId) {
        echo "<script>alert('Module is required.'); window.location.href = 'assign_components_allocations';</script>";
        exit;
    }

    if (!$mainComponentId) {
        echo "<script>alert('Main component is required.'); window.location.href = 'assign_components_allocations';</script>";
        exit;
    }

    // If "No" for subcomponent, set sub_component_id to NULL
    if ($hasSubComponent === "no") {
        $subComponentIds = [];
    }

    // Prepare the insert query for main component allocation
    try {
        // Insert into allocated_components table for the main component
        $stmt = $conn->prepare("INSERT INTO allocated_components (module_id, main_component_id, sub_component_id, created_at) VALUES (?, ?, ?, NOW())");

        // Loop through the subcomponent array (if any) and insert each one
        if (!empty($subComponentIds)) {
            foreach ($subComponentIds as $subComponentId) {
                // Ensure subComponentId is an integer and assign it to a variable
                $subComponentIdValue = intval($subComponentId);

                $stmt->bind_param("iii", $moduleId, $mainComponentId, $subComponentIdValue); // Bind subcomponentId
                if (!$stmt->execute()) {
                    throw new Exception("Error executing query: " . $stmt->error);
                }
            }
        } else {
            // Handle case when there are no subcomponents selected
            // You need to ensure that $subComponentId is a variable, not a direct assignment
            $subComponentId = null; // Set subcomponent to NULL explicitly
            $stmt->bind_param("iii", $moduleId, $mainComponentId, $subComponentId); // Bind the NULL value
            if (!$stmt->execute()) {
                throw new Exception("Error executing query: " . $stmt->error);
            }
        }

        // Show success message and redirect
        echo "<script>
            alert('Allocation successfully updated.');
            window.location.href = 'assign_components_allocations';
            </script>";
        exit;
    } catch (Exception $e) {
        echo "<script>
            alert('An error occurred: " . $e->getMessage() . "');
            window.location.href = 'assign_components_allocations';
            </script>";
        exit;
    }
}
