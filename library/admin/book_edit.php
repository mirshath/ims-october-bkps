<?php
	include 'includes/session.php';

	if(isset($_POST['edit'])){
		$id = $_POST['id'];
		$accession_number = $_POST['accession_number'];
		$calling_number= $_POST['calling_number'];
		$category = $_POST['category'];
		$author = $_POST['author'];
		$title = $_POST['title'];
		$title = str_replace("'","\'",$title);
		$publish_date = $_POST['publish_date'];

		$sql = "UPDATE books SET accession_number = '$accession_number', calling_number = '$calling_number', category_id = '$category', author = '$author', title = '$title', publish_date = '$publish_date' WHERE id = '$id'";
		if($conn->query($sql)){
			$_SESSION['success'] = 'Book updated successfully';
		}
		else{
			$_SESSION['error'] = $conn->error;
		}
	}
	else{
		$_SESSION['error'] = 'Fill up edit form first';
	}

	header('location:book.php');

?>