<?php

session_start();

// Include database connection
include_once('../db_connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $link = $_POST['link'];

    // Update game details
    $query = "UPDATE games SET name = :name, price = :price, link = :link WHERE id = :id";

    $stmt = $pdo->prepare($query);

    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':link', $link);

    // Handling the image
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // Validate the uploaded file
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);

        if (in_array($file_extension, $allowed_extensions)) {
            // Rename the file and move it to the appropriate directory
            $new_filename = uniqid() . '.' . $file_extension;
            move_uploaded_file($_FILES['image']['tmp_name'], '../images/' . $new_filename);

            // Update the image in the database
            $query = "UPDATE games SET image = :image WHERE id = :id";

            $stmt = $pdo->prepare($query);

            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':image', $new_filename);

            $stmt->execute();
        }
    }

    // Execute the update
    if ($stmt->execute()) {
        $_SESSION['message'] = "Game updated successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "There was a problem updating the game.";
        $_SESSION['message_type'] = 'error';
    }

    // Redirect back to the games page
    header('Location: ../');
} else {
    // Redirect to the add game page if the form was not submitted
    header('Location: ../formUlAires/add_game.php');
}
