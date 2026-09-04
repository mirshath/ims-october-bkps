<?php
	include 'includes/session.php';

	if(isset($_POST['add'])){
		$student_id = $_POST['student_id'];
		$nic_number = $_POST['nic_number'];
		$firstname = $_POST['firstname'];
		$lastname = $_POST['lastname'];
		$course = $_POST['course'];
		$phone_number = $_POST['phone_number'];
		$email = $_POST['email'];
		$address = $_POST['address'];
		// $filename = $_FILES['photo']['name'];
		// if(!empty($filename)){
		// 	move_uploaded_file($_FILES['photo']['tmp_name'], '../images/'.$filename);	
		// }
		//creating studentid
		// $letters = '';
		// $numbers = '';
		// foreach (range('A', 'Z') as $char) {
		//     $letters .= $char;
		// }
		// for($i = 0; $i < 10; $i++){
		// 	$numbers .= $i;
		// }
		// $student_id = substr(str_shuffle($letters), 0, 3).substr(str_shuffle($numbers), 0, 9);
		//
		$sql = "INSERT INTO students (student_id, nic_number, firstname, lastname, course_id, phone_number, email, address, created_on) VALUES ('$student_id', '$nic_number', '$firstname', '$lastname', '$course', '$phone_number','$email','$address', NOW())";
		if($conn->query($sql)){
			$_SESSION['success'] = 'Student added successfully';
		}
		else{
			$_SESSION['error'] = $conn->error;
		}

	}
	else{
		$_SESSION['error'] = 'Fill up add form first';
	}

	header('location: student.php');
?>