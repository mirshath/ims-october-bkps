<?php

	include 'includes/session.php';

	if(isset($_POST['add'])){
		$accession_number = $_POST['accession_number'];
		$calling_number = $_POST['calling_number'];
		$category = $_POST['category'];
		$author = $_POST['author'];
		$title = $_POST['title'];
		$title = str_replace("'","\'",$title);
		$publish_date = $_POST['publish_date'];

		$sql = "INSERT INTO books (accession_number, calling_number,category_id, author, title, publish_date) VALUES ('$accession_number', '$calling_number', '$category','$author', '$title', '$publish_date')";
		if($conn->query($sql)){
			$_SESSION['success'] = 'Book added successfully';
		}
		else{
			$_SESSION['error'] = $conn->error;
		}
	}	
	else{
		$_SESSION['error'] = 'Fill up add form first';
	}

	header('location: book.php');

?>