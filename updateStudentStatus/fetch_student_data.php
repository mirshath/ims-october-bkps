fecth_student_data 


<?php
include("../database/connection.php");

if (isset($_POST['program_id']) && isset($_POST['batch_id'])) {
    $program_id = $_POST['program_id'];
    $batch_id = $_POST['batch_id'];

    $query = "
        SELECT allocate_programme.*, 
               program_table.*, 
               batch_table.*, 
               students.* 
        FROM allocate_programme
        JOIN program_table ON allocate_programme.programme_code  = program_table.program_code 
        JOIN batch_table ON allocate_programme.batch_id  = batch_table.id 
        JOIN students ON allocate_programme.student_code  = students.student_code 
        WHERE allocate_programme.programme_code  = '$program_id' 
          AND allocate_programme.batch_id  = '$batch_id'";

    $result = mysqli_query($conn, $query);


    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>
                <td>{$row['program_name']}</td>
                <td>{$row['batch_name']}</td>
                <td style='width: 10px;'>{$row['student_code']}</td>
                <td>{$row['student_registration_id']}</td>
                <td>{$row['first_name']} {$row['last_name']}</td>
                <td>{$row['student_status']}</td>
                <td>
                    <select name='student_status' class='form-control'>
                        <option value='active' " . ($row['student_status'] === 'Active' ? 'selected' : '') . ">Active</option>
                        <option value='drop' " . ($row['student_status'] === 'Drop' ? 'selected' : '') . ">Drop</option>
                        <option value='transferred' " . ($row['student_status'] === 'Transferred' ? 'selected' : '') . ">Transferred</option>
                        <option value='completed' " . ($row['student_status'] === 'Completed' ? 'selected' : '') . ">Completed</option>
                    </select>
                </td>
                <td>
                    <input type='text' name='additional_info[]' class='form-control' placeholder='Additional Info' />
                </td>
                <td>
                    <button type='submit' class='btn btn-sm btn-primary'>Update</button>
                </td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='8' class='text-center'>No data found for the selected Program and Batch.</td></tr>";
    }
}
