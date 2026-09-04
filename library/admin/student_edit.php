<?php
	include 'includes/session.php';

	if(isset($_POST['edit'])){
		$id = $_POST['id'];
		$student_id = $_POST['student_id'];
		$nic_number = $_POST['nic_number'];
		$firstname = $_POST['firstname'];
		$lastname = $_POST['lastname'];
		$course = $_POST['course'];
		$phone_number = $_POST['phone_number'];
		$email = $_POST['email'];
		$address = $_POST['address'];

		$sql = "UPDATE students SET student_id ='$student_id', nic_number='$nic_number', firstname = '$firstname', lastname = '$lastname', course_id = '$course',phone_number='$phone_number',email='$email',address='$address' WHERE id = '$id'";
		if($conn->query($sql)){
			$_SESSION['success'] = 'Student updated successfully';
		}
		else{
			$_SESSION['error'] = $conn->error;
		}
	}
	else{
		$_SESSION['error'] = 'Fill up edit form first';
	}

	header('location:student.php');

?>