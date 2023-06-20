<?php
// Include database connection
include_once('db_connect.php');

// Get data from the form
$name = $_POST['name'];
$description = $_POST['description'];
$link = $_POST['link'];

// Handle the uploaded file
$target_dir = "images/";
$image = basename($_FILES["image"]["name"]);
$target_file = $target_dir . $image;

if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
    echo "The file " . basename($_FILES["image"]["name"]) . " has been uploaded.";
} else {
    echo "Sorry, there was an error uploading your file.";
}

// Query to insert the new game
$query = "INSERT INTO games (name, description, image, link) VALUES (?, ?, ?, ?)";

$stmt = $pdo->prepare($query);

$stmt->execute([$name, $description, $image, $link]);

header("Location: index.php");
