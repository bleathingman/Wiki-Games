<?php

// Include database connection
include_once('../db_connect.php');

// Get the game ID from the URL
$id = $_GET['id'];

// Query to get the game
$query = "SELECT * FROM games WHERE id = :id";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

$game = $stmt->fetch();

?>

<!DOCTYPE html>
<html lang="fr">

<!-- Add the rest of your HTML here, including the form which will use the $game variable to populate the fields -->

<form action="../process/update_game_process.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?php echo $game['id']; ?>">
    <label for="name">Name:</label>
    <input type="text" id="name" name="name" value="<?php echo $game['name']; ?>">
    <label for="description">Description:</label>
    <input type="text" id="description" name="description" value="<?php echo $game['description']; ?>">
    <label for="image">Image:</label>
    <input type="file" id="image" name="image">
    <label for="link">Link:</label>
    <input type="text" id="link" name="link" value="<?php echo $game['link']; ?>">
    <input type="submit" value="Update">
</form>

</html>