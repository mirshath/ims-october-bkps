<?php
session_start();
// here is the code for the function that is supposed to be called by the button click event: 

include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}


// ---------------------------- allowed Redirections ---------------------------------------------------------------- 
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// -------------------------------------------------------------------------------------------- 


// Initialize variables
$is_update_flag = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['is_update']) && $_POST['is_update'] === '1') {
    $is_update_flag = true;
    $_POST['update'] = true;
    unset($_POST['save']);
}
$module_code = $module_name = $university_id = $programme_id = $assessment_components = "";
$pass_mark = $type = $lecturers = $institution = $year_id = $semester_id = $main_component = $sub_components = "";
$module_gpa = $examinor_1 = $examinor_2 = ""; // Initialize new fields
$id = 0;
$update = $is_update_flag;

// Fetch universities for the dropdown
$universities_result = mysqli_query($conn, "SELECT * FROM universities");
$universities = [];
while ($row = mysqli_fetch_assoc($universities_result)) {
    $universities[] = $row;
}

// Fetch programs for dropdown
$sql = "SELECT * FROM program_table";
$result = mysqli_query($conn, $sql);
$programsOptions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $programsOptions[] = $row;
}

// Fetch years for dropdown
$years_result = mysqli_query($conn, "SELECT * FROM year_table");
$years = [];
while ($row = mysqli_fetch_assoc($years_result)) {
    $years[] = $row;
}

// Fetch semesters for dropdown
$semesters_result = mysqli_query($conn, "SELECT * FROM semester_table");
$semesters = [];
while ($row = mysqli_fetch_assoc($semesters_result)) {
    $semesters[] = $row;
}

// Fetch assignment components for the dropdown
$components_result = mysqli_query($conn, "SELECT * FROM assignment_components");
$assignment_components = [];
while ($row = mysqli_fetch_assoc($components_result)) {
    $assignment_components[] = $row;
}

// --------------------- NEW INSERT AND UPDATE -------------------------------------------

if (isset($_POST['save'])) {
    // Get form data
    $module_code = $_POST['module_code'];
    $module_name = $_POST['module_name'];
    $university_id = $_POST['university'];
    $programme_id = $_POST['programme'];
    $year_id = $_POST['year'];
    $semester_id = $_POST['semester'];
    $pass_mark = $_POST['pass_mark'];
    $type = $_POST['type'];
    // Check if enable_lecturers is checked, otherwise set to empty
    $lecturers = isset($_POST['enable_lecturers']) ? (isset($_POST['lecturers']) ? $_POST['lecturers'] : '') : '';
    // Check if enable_institution is checked, otherwise set to empty
    $institution = isset($_POST['enable_institution']) ? (isset($_POST['institution']) ? $_POST['institution'] : '') : '';
    $module_gpa = $_POST['module_gpa'];  // New field for module GPA
    $examinor_1 = $_POST['examinor_1'];  // New field for Examinor 1
    $examinor_2 = $_POST['examinor_2'];  // New field for Examinor 2

    // Validate module code and module name
    if (empty($module_code) || empty($module_name)) {
        die("Module code and name are required.");
    }

    // Validate university and programme selection
    if (empty($university_id) || empty($programme_id)) {
        die("University and Programme are required.");
    }

    // Check if the module code already exists
    $check_stmt = $conn->prepare("SELECT module_code FROM modules WHERE module_code = ?");
    $check_stmt->bind_param("s", $module_code);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $check_stmt->close();
        // Module code exists, prompt for update
        // Build inputs array in PHP first
        $inputs = [
            ['name' => 'update', 'value' => '1'],
            ['name' => 'module_code', 'value' => $module_code],
            ['name' => 'module_name', 'value' => $module_name],
            ['name' => 'university', 'value' => $university_id],
            ['name' => 'programme', 'value' => $programme_id],
            ['name' => 'year', 'value' => $year_id],
            ['name' => 'semester', 'value' => $semester_id],
            ['name' => 'pass_mark', 'value' => $pass_mark],
            ['name' => 'type', 'value' => $type],
            ['name' => 'lecturers', 'value' => $lecturers],
            ['name' => 'institution', 'value' => $institution],
            ['name' => 'module_gpa', 'value' => $module_gpa],
            ['name' => 'examinor_1', 'value' => $examinor_1],
            ['name' => 'examinor_2', 'value' => $examinor_2]
        ];
        // Add enable checkboxes if present
        if (isset($_POST['enable_lecturers'])) {
            $inputs[] = ['name' => 'enable_lecturers', 'value' => '1'];
        }
        if (isset($_POST['enable_institution'])) {
            $inputs[] = ['name' => 'enable_institution', 'value' => '1'];
        }
        // Encode to JSON
        $inputs_json = json_encode($inputs);

        echo '<script>
                if (confirm("This module code already exists. Do you want to update it?")) {
                    // Create a hidden form to submit the update
                    var form = document.createElement("form");
                    form.method = "POST";
                    form.action = "create_module.php"; // Submit to the same file

                    // Create hidden inputs for the update
                    var inputs = ' . $inputs_json . ';

                    inputs.forEach(function(input) {
                        var hiddenField = document.createElement("input");
                        hiddenField.type = "hidden";
                        hiddenField.name = input.name;
                        hiddenField.value = input.value;
                        form.appendChild(hiddenField);
                    });

                    document.body.appendChild(form);
                    form.submit(); // Submit the form
                } else {
                    alert("Module not updated.");
                }
              </script>';
    } else {
        $check_stmt->close();
        // Insert the module data using prepared statement
        $insert_stmt = $conn->prepare("INSERT INTO modules (module_code, module_name, university_id, programme_id, year_id, semester_id, pass_mark, type, lecturers, institution, module_GPA, examinor_1, examinor_2) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Handle potential nulls for numeric fields
        $y_id = empty($year_id) ? NULL : (int)$year_id;
        $s_id = empty($semester_id) ? NULL : (int)$semester_id;
        $m_gpa = empty($module_gpa) ? NULL : (int)$module_gpa;
        $uni_id = (int)$university_id;
        $prog_id = (int)$programme_id;

        $insert_stmt->bind_param("ssiiiisssisss", $module_code, $module_name, $uni_id, $prog_id, $y_id, $s_id, $pass_mark, $type, $lecturers, $institution, $m_gpa, $examinor_1, $examinor_2);

        if ($insert_stmt->execute() !== TRUE) {
            $_SESSION['message'] = "Error adding module: " . $insert_stmt->error;
            $insert_stmt->close();
            echo '<script>window.location.href = "create_module";</script>';
        } else {
            $insert_stmt->close();
            $_SESSION['message'] = "Module added successfully!";
            echo '<script>window.location.href = "create_module";</script>';
        }
    }
}

// ------------------------------------------------------------------------------------------------------------

// Handle the update if the form was submitted
if (isset($_POST['update'])) {
    // Get form data for update
    $module_code = $_POST['module_code'];
    $module_name = $_POST['module_name'];
    $university_id = $_POST['university'];
    $programme_id = $_POST['programme'];
    $year_id = $_POST['year'];
    $semester_id = $_POST['semester'];
    $pass_mark = $_POST['pass_mark'];
    $type = $_POST['type'];
    // Check if enable_lecturers is checked, otherwise set to empty
    $lecturers = isset($_POST['enable_lecturers']) ? (isset($_POST['lecturers']) ? $_POST['lecturers'] : '') : '';
    // Check if enable_institution is checked, otherwise set to empty
    $institution = isset($_POST['enable_institution']) ? (isset($_POST['institution']) ? $_POST['institution'] : '') : '';
    $module_gpa = $_POST['module_gpa'];
    $examinor_1 = $_POST['examinor_1'];
    $examinor_2 = $_POST['examinor_2'];

    // Check if the programme_id exists in program_table using prepared statement
    $prog_stmt = $conn->prepare("SELECT program_code FROM program_table WHERE program_code = ?");
    $prog_stmt->bind_param("i", $programme_id);
    $prog_stmt->execute();
    $prog_result = $prog_stmt->get_result();

    if ($prog_result->num_rows == 0) {
        $prog_stmt->close();
        die("Error: The selected programme does not exist.");
    }
    $prog_stmt->close();

    // Proceed with the update using prepared statement
    $update_stmt = $conn->prepare("UPDATE modules SET module_name = ?, university_id = ?, programme_id = ?, year_id = ?, semester_id = ?, pass_mark = ?, type = ?, lecturers = ?, institution = ?, module_GPA = ?, examinor_1 = ?, examinor_2 = ? WHERE module_code = ?");
    
    // Handle potential nulls for numeric fields
    $y_id = empty($year_id) ? NULL : (int)$year_id;
    $s_id = empty($semester_id) ? NULL : (int)$semester_id;
    $m_gpa = empty($module_gpa) ? NULL : (int)$module_gpa;
    $uni_id = (int)$university_id;
        $prog_id = (int)$programme_id;

        $update_stmt->bind_param("siiiisssissss", $module_name, $uni_id, $prog_id, $y_id, $s_id, $pass_mark, $type, $lecturers, $institution, $m_gpa, $examinor_1, $examinor_2, $module_code);

        if ($update_stmt->execute() === TRUE) {
        $update_stmt->close();
        $_SESSION['message'] = "Module updated successfully!";
        echo '<script>window.location.href = "create_module";</script>';
    } else {
        $_SESSION['message'] = "Error updating module: " . $update_stmt->error;
        $update_stmt->close();
        echo '<script>window.location.href = "create_module";</script>';
    }
}

// Fetch all records
$result = $conn->query("SELECT m.*, p.program_name, y.year_name, s.semester_name
            FROM modules m
            LEFT JOIN program_table p ON m.programme_id = p.program_code
            LEFT JOIN year_table y ON m.year_id = y.id
            LEFT JOIN semester_table s ON m.semester_id = s.id");
?>

<!-- Page Wrapper -->
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
                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Module Managment</h4>
                </div>

                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span
                                    class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                                    style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Add Modules</h6>
                            </div>

                            <div class="card-body">

                                <form action="" method="post" class="mb-3" id="editForm">
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                                    <input type="hidden" id="is_update" name="is_update" value="0">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="module_code">Module Code:<span style="color: red; font-weight: bold;">*</span> </label></div>
                                                    <div class="col">
                                                        <input type="text" class="form-control" placeholder="Module Code"
                                                            id="module_code" name="module_code"
                                                            value="<?php echo htmlspecialchars($module_code); ?>" <?php echo $update ? 'readonly' : ''; ?> required>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="module_name">Module Name: <span style="color: red; font-weight: bold;">*</span></label></div>
                                                    <div class="col">
                                                        <input type="text" class="form-control" placeholder="Module Name"
                                                            id="module_name" name="module_name"
                                                            value="<?php echo htmlspecialchars($module_name); ?>" required>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ---------- changes 19.03.2025 ---------------------------------  -->
                                            <!-- Module GPA Input -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <label for="module_gpa">Module Credit value:</label>
                                                    </div>
                                                    <div class="col">
                                                        <input type="number" step="0.01" class="form-control" placeholder="Module GPA"
                                                            id="module_gpa" name="module_gpa"
                                                            value="<?php echo htmlspecialchars($module_gpa); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- -------------------------------------------  -->

                                            <!-- University Dropdown -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="university">University: <span style="color: red; font-weight: bold;">*</span></label></div>
                                                    <div class="col">
                                                        <select class="form-control select2" id="university" name="university" required>
                                                            <option value="">Select University</option>
                                                            <?php foreach ($universities as $uni): ?>
                                                                <option value="<?php echo htmlspecialchars($uni['id']); ?>" <?php echo $uni['id'] == $university_id ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($uni['university_name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Programme Dropdown -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <label for="programme">Programme:<span style="color: red; font-weight: bold;">*</span></label>
                                                    </div>
                                                    <div class="col">
                                                        <select class="form-control select2" id="programme" name="programme" required>
                                                            <option value="">Select Programme</option>
                                                            <!-- Programme options will be populated dynamically -->
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Year Selection -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="year">Year:</label></div>
                                                    <div class="col">
                                                        <select class="form-control select2" id="year" name="year" required>
                                                            <option value="">Select Year</option>
                                                            <!-- Years will be dynamically loaded here -->
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Semester Selection -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="semester">Semester:</label></div>
                                                    <div class="col">
                                                        <select class="form-control select2" id="semester" name="semester" required>
                                                            <option value="">Select Semester</option>
                                                            <!-- Semesters will be dynamically loaded here -->
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="pass_mark">Pass Mark:<span style="color: red; font-weight: bold;">*</span></label></div>
                                                    <div class="col">
                                                        <input type="text" placeholder="Pass Mark" class="form-control"
                                                            id="pass_mark" name="pass_mark"
                                                            value="<?php echo htmlspecialchars($pass_mark); ?>" required>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Type Selection -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label>Type:<span style="color: red; font-weight: bold;">*</span></label></div>
                                                    <div class="col">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="type"
                                                                id="type_compulsory" value="Compulsory" <?php echo $type == 'Compulsory' ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="type_compulsory">
                                                                Compulsory
                                                            </label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="type"
                                                                id="type_elective" value="Elective" <?php echo $type == 'Elective' ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="type_elective">
                                                                Elective
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Lecturer Section -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="lecturers"><input type="checkbox"
                                                                id="enable_lecturers" name="enable_lecturers" <?php echo !empty($lecturers) ? 'checked' : ''; ?>> Lecturer/s:</label>
                                                    </div>
                                                    <div class="col">
                                                        <div id="lecturers_container" <?php echo empty($lecturers) ? 'style="display:none;"' : ''; ?>>
                                                            <!-- Checkboxes will be populated here dynamically -->
                                                        </div>
                                                        <input type="hidden" id="lecturers" name="lecturers" value="<?php echo htmlspecialchars($lecturers); ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Institution Section -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> 
                                                        <label for="enable_institution">
                                                            <input type="checkbox" id="enable_institution" name="enable_institution" <?php echo !empty($institution) ? 'checked' : ''; ?>> 
                                                            Institution:
                                                        </label>
                                                    </div>
                                                    <div class="col">
                                                        <input type="text" placeholder="institution" class="form-control"
                                                            id="institution_input" name="institution"
                                                            value="<?php echo htmlspecialchars($institution); ?>" <?php echo empty($institution) ? 'disabled' : ''; ?>>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ------------- update section 19.03.2025 -------------------------------- -->

                                            <?php
                                            // Fetching data for Examinor dropdowns
                                            $sql = "SELECT id, username FROM admin";
                                            $result = $conn->query($sql);

                                            // Create an array to store results
                                            $examinors = [];
                                            while ($row = $result->fetch_assoc()) {
                                                $examinors[] = $row;
                                            }

                                            ?>
                                            <!-- Semester Selection -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="examinor_1">Examinor 1</label></div>
                                                    <div class="col">
                                                        <select class="form-control select2" id="examinor_1" name="examinor_1">
                                                            <option value="">Select Examinor 1</option>
                                                            <?php foreach ($examinors as $examinor): ?>
                                                                <option value="<?php echo $examinor['id']; ?>" <?php echo $examinor['id'] == $examinor_1 ? 'selected' : ''; ?>>
                                                                    <?php echo $examinor['username']; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Semester Selection -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="examinor_2">Examinor 2</label></div>
                                                    <div class="col">
                                                        <select class="form-control select2" id="examinor_2" name="examinor_2">
                                                            <option value="">Select Examinor 2</option>
                                                            <?php foreach ($examinors as $examinor): ?>
                                                                <option value="<?php echo $examinor['id']; ?>" <?php echo $examinor['id'] == $examinor_2 ? 'selected' : ''; ?>>
                                                                    <?php echo $examinor['username']; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- -----------------------------------------------------------------------  -->

                                            <!-- Save/Update Button -->
                                            <div class="text-right">
                                                <button type="submit" class="btn btn-primary btn-sm" id="formSubmitBtn" name="save">
                                                    <?php echo $update ? 'Update' : 'Save'; ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modules Table -->
                <div class="module-details">

                    <div class="table-responsive">
                        <div class="card-header d-flex align-items-center" style="height: 60px;">
                            <span
                                class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                                style="width: 30px; height: 30px;">
                                <i class="fas fa-list"></i>
                            </span> &nbsp;&nbsp;&nbsp;&nbsp;
                            <h6 class="mb-0 me-2">Module Details</h6>
                        </div>
                        <br>
                        <table id="modulesTable" class="table table-striped table-bordered" style="font-size: 14px;">
                            <thead>
                                <tr>
                                    <th style="width: 5px;">#</th>
                                    <th style="width: 5px;">Module Code</th>
                                    <th>Module Name</th>
                                    <th class="hide-column">University</th>
                                    <th>Programme</th>
                                    <th>Year</th>
                                    <th>Semester</th>
                                    <th>Pass Mark</th>
                                    <th>Type</th>
                                    <th>Lecturers</th>
                                    <!-- <th>Institution</th> -->
                                    <th></th>Assessment Components</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                include("database/connection.php"); // Include DB connection

                                // Fetch modules and their respective components and sub-components
                                $sql = "SELECT 
                            m.module_code, 
                            m.module_name, 
                            p.program_name, 
                            y.year_name, 
                            se.semester_name, 
                            u.university_name, 
                            m.pass_mark, 
                            m.type, 
                            m.lecturers,
                            m.institution,
                            a.as_main_component_name, a.main_component_percent,
                            s.sub_component_name, s.sub_component_percent
                            FROM modules m
                            LEFT JOIN allocated_components ac ON m.id = ac.module_id
                            LEFT JOIN assignment_components a ON ac.main_component_id = a.id
                            LEFT JOIN sub_assign_components s ON ac.sub_component_id = s.id
                            LEFT JOIN program_table p ON m.programme_id = p.program_code 
                            LEFT JOIN year_table y ON m.year_id = y.id 
                            LEFT JOIN semester_table se ON m.semester_id = se.id 
                            LEFT JOIN universities u ON m.university_id = u.id
                            ORDER BY m.module_code, a.as_main_component_name, s.sub_component_name";

                                $result = $conn->query($sql);

                                // Initialize an array to store module data by module_code
                                $modules_data = [];
                                $index = 1; // Initialize index counter

                                if ($result->num_rows > 0) {
                                    // Loop through rows and store module data by module_code
                                    while ($row = $result->fetch_assoc()) {
                                        $modules_data[$row['module_code']][] = $row;
                                    }

                                    // Loop through each module's data and render it
                                    foreach ($modules_data as $module_code => $module_rows) {
                                        $module_data = $module_rows[0]; // Get the first row for common data (like module code, name, etc.)

                                        // Initialize an array to store the main components and their sub-components
                                        $components = [];

                                        foreach ($module_rows as $row) {
                                            $main_component = $row['as_main_component_name'] . " " . $row['main_component_percent'];
                                            $sub_component = $row['sub_component_name'] . " " . $row['sub_component_percent'] ?? '';

                                            // Add sub-component under the main component
                                            if (!isset($components[$main_component])) {
                                                $components[$main_component] = [];
                                            }
                                            $components[$main_component][] = $sub_component;
                                        }

                                        // Prepare the components and sub-components for display in a single row
                                        $formatted_components = [];
                                        foreach ($components as $main_component => $sub_components) {
                                            $sub_component_list = implode('<br> ', $sub_components); // Combine sub-components
                                            $formatted_components[] = "<b>" . $main_component . "</b>: <br> " . $sub_component_list . "<br><br>";
                                        }

                                        // Combine all main components and sub-components into one string
                                        $formatted_components_str = implode('', $formatted_components);

                                        // Render the row for this module
                                        //   <td>" . htmlspecialchars($module_data['institution'] ?? 'N/A') . "</td>
                                        echo "<tr>
                                        <td>" . $index++ . "</td>
                                        <td>" . htmlspecialchars($module_data['module_code']) . "</td>
                                        <td>" . htmlspecialchars($module_data['module_name']) . "</td>
                                        <td class='hide-column'>" . htmlspecialchars($module_data['university_name'] ?? 'N/A') . "</td>
                                            <td>" . htmlspecialchars($module_data['program_name'] ?? 'N/A') . "</td>
                                            <td>" . htmlspecialchars($module_data['year_name'] ?? 'N/A') . "</td>
                                            <td>" . htmlspecialchars($module_data['semester_name'] ?? 'N/A') . "</td>
                                            <td>" . htmlspecialchars($module_data['pass_mark'] ?? 'N/A') . "</td>
                                            <td>" . htmlspecialchars($module_data['type'] ?? 'N/A') . "</td>
                                            <td>" . htmlspecialchars($module_data['lecturers'] ?? 'N/A') . "</td>
                                          
                                            <td>" . ($formatted_components_str ? $formatted_components_str : 'No Components') . "</td>
                                            <td>
                                                <button class='btn btn-sm btn-primary' 
                                                    data-module-code='" . htmlspecialchars($module_data['module_code']) . "' 
                                                    onclick='showModuleCode(this)'>Edit</button>
                                            </td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='13'>No modules found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <script>
                        $(document).ready(function() {
                            $('#modulesTable').DataTable({
                                "paging": true, // Enable pagination
                                "searching": true, // Enable searching
                                "ordering": true, // Enable sorting
                                "info": true, // Show table info
                                "lengthMenu": [5, 10, 25, 50], // Set entries per page
                                "pageLength": 10, // Default number of entries
                                "responsive": true // Make it responsive
                            });
                        });
                    </script>
                </div>

            </div>
            <!-- ----------------------------------------------------------  -->
            <script>
                function showModuleCode(button) {
                    var moduleCode = button.getAttribute("data-module-code");

                    // Show an alert with module_code
                    // alert("Module Code: " + moduleCode);
                    console.log("Module Code: " + moduleCode);

                    // Fetch details using AJAX
                    fetch("module_creating/get_module_details.php?module_code=" + moduleCode)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                console.error("Error:", data.error);
                                alert("Error: " + data.error);
                                return;
                            }

                            // Populate the form fields
                            $('#module_code').val(data.module_code).prop('readonly', true);
                            $('#module_name').val(data.module_name);
                            $('#university').val(data.university_id).trigger('change.select2');
                            $('#module_gpa').val(data.module_GPA || '');
                            $('#examinor_1').val(data.examinor_1).trigger('change.select2');
                            $('#examinor_2').val(data.examinor_2).trigger('change.select2');

                            // Populate the programme dropdown based on the university
                            window.selectedProgramme = data.programme_id;
                            populateProgrammeDropdown(data.university_id, data.programme_id); // Pass the selected programme ID

                            $('#year').val(data.year_id).trigger('change.select2');
                            $('#semester').val(data.semester_id).trigger('change.select2');
                            $('#pass_mark').val(data.pass_mark);
                            if (data.type === "Compulsory") {
                                $('#type_compulsory').prop('checked', true);
                            } else if (data.type === "Elective") {
                                $('#type_elective').prop('checked', true);
                            }
                            $('#lecturers').val(data.lecturers || '');
                            $('#enable_lecturers').prop('checked', !!data.lecturers);
                            if (data.lecturers) {
                                $('#lecturers_container').show();
                            }
                            $('#institution_input').val(data.institution || '');
                            $('#institution_input').prop('disabled', !data.institution);
                            $('#enable_institution').prop('checked', !!data.institution);

                            document.getElementById("is_update").value = "1";
                            const submitBtn = document.getElementById("formSubmitBtn");
                            if (submitBtn) {
                                submitBtn.textContent = "Update";
                            }

                            // After programme dropdown is populated, fetch and render lecturers
                            setTimeout(function() {
                                if ($('#enable_lecturers').is(':checked')) {
                                    fetchAndRenderLecturers($('#programme').val(), data.lecturers || '');
                                }
                            }, 500);

                            // Show the form if it's hidden
                            document.getElementById("editForm").style.display = "block";
                        })
                        .catch(error => console.error("Error fetching module details:", error));
                }
            </script>
            <!-- ----------------------------------------------------------  -->

        </div>
        <!-- End of Main Content -->
    </div>
    <!-- End of Content Wrapper -->
</div>
<!-- End of Page Wrapper -->

<script>
    $(document).ready(function() {
        // PHP values passed to JavaScript
        var selectedYear = '<?php echo $year_id; ?>';
        var selectedSemester = '<?php echo $semester_id; ?>';
        var selectedProgramme = '<?php echo $programme_id; ?>'; // If needed for programme

        // Fetch years from year_table
        $.ajax({
            type: 'POST',
            url: 'module_creating/get_years.php',
            dataType: 'json',
            success: function(data) {
                $('#year').html('<option value="">Select Year</option>'); // Reset Year dropdown
                $.each(data, function(key, value) {
                    var isSelected = (value.id == selectedYear) ? 'selected' : '';
                    $('#year').append('<option value="' + value.id + '" ' + isSelected + '>' + value.year_name + '</option>');
                });
                $('#year').trigger('change.select2');
            },
            error: function(xhr, status, error) {
                console.error("Error fetching years: " + status + " " + error);
            }
        });

        // Fetch semesters from semester_table
        $.ajax({
            type: 'POST',
            url: 'module_creating/get_semesters.php',
            dataType: 'json',
            success: function(data) {
                $('#semester').html('<option value="">Select Semester</option>'); // Reset Semester dropdown
                $.each(data, function(key, value) {
                    var isSelected = (value.id == selectedSemester) ? 'selected' : '';
                    $('#semester').append('<option value="' + value.id + '" ' + isSelected + '>' + value.semester_name + '</option>');
                });
                $('#semester').trigger('change.select2');
            },
            error: function(xhr, status, error) {
                console.error("Error fetching semesters: " + status + " " + error);
            }
        });

        // Optionally, populate the Programme dropdown dynamically if needed
    });
</script>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script>
    $(document).ready(function() {
        window.selectedProgramme = '<?php echo $programme_id; ?>';

        $('#university').on('change', function() {
            var universityID = $(this).val();
            if (universityID) {
                $.ajax({
                    type: 'POST',
                    url: 'module_creating/get_programs.php', // Ensure this path is correct
                    data: {
                        university_id: universityID
                    },
                    dataType: 'json',
                    success: function(data) {
                        $('#programme').html('<option value="">Select Programme</option>'); // Reset the dropdown
                        $.each(data, function(key, value) {
                            var isSelected = value.program_code == window.selectedProgramme ? 'selected' : '';
                            $('#programme').append('<option value="' + value.program_code + '" ' + isSelected + '>' + value.program_name + '</option>');
                        });
                        $('#programme').trigger('change.select2');
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error: " + status + error);
                    }
                });
            } else {
                $('#programme').html('<option value="">Select Programme</option>');
            }
        });

        $('#programme').on('change', function() {
            window.selectedProgramme = $(this).val();
            // If enable_lecturers is checked, fetch and render lecturers
            if ($('#enable_lecturers').is(':checked')) {
                fetchAndRenderLecturers($(this).val());
            } else {
                $('#lecturers_container').empty();
                $('#lecturers').val('');
            }
        });

        // Trigger change event to load programs if university is already selected (for edit mode)
        <?php if ($update && !empty($university_id)): ?>
            $('#university').trigger('change');
        <?php endif; ?>
    });
</script>

<!-- this is for the check box when clik  -->
<script>
    $(document).ready(function() {
        $('#enable_lecturers').change(function() {
            if (this.checked) {
                $('#lecturers_container').show();
                // If programme is already selected, fetch lecturers
                var programmeId = $('#programme').val();
                if (programmeId) {
                    fetchAndRenderLecturers(programmeId);
                }
            } else {
                $('#lecturers_container').hide();
                $('#lecturers').val(''); // Clear hidden field
            }
        });

        $('#enable_institution').change(function() {
                    if (this.checked) {
                        $('#institution_input').prop('disabled', false);
                    } else {
                        $('#institution_input').prop('disabled', true);
                        $('#institution_input').val('');
                    }
                });

        // Function to fetch and render lecturers
        function fetchAndRenderLecturers(programmeId, existingLecturers = '') {
            $.ajax({
                type: 'POST',
                url: 'module_creating/get_lecturers.php',
                data: {
                    programme_id: programmeId
                },
                dataType: 'json',
                success: function(data) {
                    var container = $('#lecturers_container');
                    container.empty();

                    if (data.length === 0) {
                        container.html('<p class="text-muted">No lecturers found for this programme</p>');
                        return;
                    }

                    // Parse existing lecturers into array
                    var existingArray = existingLecturers.split(',').map(function(item) {
                        return item.trim();
                    });

                    data.forEach(function(lecturer) {
                        var displayName = lecturer.lecturer_name;
                        if (lecturer.full_name) {
                            displayName += ' (' + lecturer.full_name + ')';
                        }
                        var isChecked = existingArray.includes(lecturer.lecturer_name) ? 'checked' : '';
                        var checkboxHtml = '<div class="form-check">';
                        checkboxHtml += '<input class="form-check-input lecturer-checkbox" type="checkbox" value="' + lecturer.lecturer_name + '" id="lecturer_' + lecturer.id + '" ' + isChecked + '>';
                        checkboxHtml += '<label class="form-check-label" for="lecturer_' + lecturer.id + '">' + displayName + '</label>';
                        checkboxHtml += '</div>';
                        container.append(checkboxHtml);
                    });

                    // Attach event listeners to checkboxes
                    $('.lecturer-checkbox').on('change', updateLecturersHiddenField);
                    updateLecturersHiddenField();
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: " + status + error);
                }
            });
        }

        // Function to update hidden field with selected lecturers
        function updateLecturersHiddenField() {
            var selected = [];
            $('.lecturer-checkbox:checked').each(function() {
                selected.push($(this).val());
            });
            $('#lecturers').val(selected.join(','));
        }

        // Make fetchAndRenderLecturers available globally
        window.fetchAndRenderLecturers = fetchAndRenderLecturers;
    });
</script>

<!-- Page level plugins -->
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

<link rel="stylesheet" href="./vendor/datatables/dataTables.bootstrap4.min.css">
<!-- Page level custom scripts -->
<script src="js/demo/datatables-demo.js"></script>

<style>
    .hide-column {
        display: none;
        /* Hides the column */
    }

    .table-responsive {
        overflow-x: auto;
        /* Enables horizontal scrolling */
    }
</style>
</body>
<!-- At the bottom of your file, replace the Select2 initialization script with this improved version -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2 with proper width and dropdown parent
        $('.select2').select2({
            width: '100%',
            dropdownParent: $('body')
        });

        // Fix for modal dialogs if you have any
        $('.modal').on('shown.bs.modal', function() {
            $(this).find('.select2').select2({
                width: '100%',
                dropdownParent: $(this)
            });
        });
    });
</script>

</html>

<script>
    function populateProgrammeDropdown(universityID, selectedProgrammeID) {
        $.ajax({
            type: 'POST',
            url: 'module_creating/get_programs.php', // Ensure this path is correct
            data: {
                university_id: universityID
            },
            dataType: 'json',
            success: function(data) {
                $('#programme').html('<option value="">Select Programme</option>'); // Reset the dropdown
                $.each(data, function(key, value) {
                    // Only add the option if it matches the selectedProgrammeID
                    $('#programme').append('<option value="' + value.program_code + '"' + (value.program_code == selectedProgrammeID ? ' selected' : '') + '>' + value.program_name + '</option>');
                });
                $('#programme').trigger('change.select2');
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: " + status + error);
            }
        });
    }
</script>