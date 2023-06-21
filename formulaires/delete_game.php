<?php
// Start session
session_start();

// Include database connection
include_once('../db_connect.php');

// Check if ID is set in URL
if (isset($_GET['id'])) {
    // Get the ID from the URL
    $id = $_GET['id'];

    // Prepare the SQL query
    $query = "DELETE FROM games WHERE id = :id";

    // Prepare the statement
    $stmt = $pdo->prepare($query);

    // Bind the ID to the PDO
    $stmt->bindParam(':id', $id);

    // Execute the statement
    if ($stmt->execute()) {
        // Successfully deleted the record
        $_SESSION['message'] = "Le jeu a été supprimé avec succès";
        $_SESSION['message_type'] = 'success';
        header('Location: ../index.php');
    } else {
        // There was an error
        $_SESSION['message'] = "Désolé, une erreur s'est produite lors de la suppression de votre jeu.";
        $_SESSION['message_type'] = 'error';
        header('Location: ../index.php');
    }
} else {
    // No ID set in the URL
    $_SESSION['message'] = "Aucun ID de jeu n'a été trouvé. Veuillez vérifier l'URL et réessayer.";
    $_SESSION['message_type'] = 'error';
    header('Location: ../index.php');
}
