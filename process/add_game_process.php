<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Include database connection
include_once('../db_connect.php');

// Include header
include_once('../header.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and prepare data
    $name = htmlspecialchars(strip_tags($_POST['name']));
    $price = htmlspecialchars(strip_tags($_POST['price']));
    $description = htmlspecialchars(strip_tags($_POST['description']));
    $link = htmlspecialchars(strip_tags($_POST['link']));

    // Handle file upload
    $target_dir = "../images/";
    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);

    // Insert into database
    $query = "INSERT INTO games (name, price, image, description, link) VALUES (:name, :price, :image, :description, :link)";

    $stmt = $pdo->prepare($query);

    // bind the parameters
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':image', $target_file);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':link', $link);

    // execute the query
    if ($stmt->execute()) {
        $_SESSION['message'] = "Game was added successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: index.php");
    } else {
        $_SESSION['message'] = "There was an error adding the game.";
        $_SESSION['message_type'] = "alert";
    }
}
