<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add a Game</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include_once('header.php'); ?>

    <h1>Add a Game</h1>

    <form action="add_game_process.php" method="post" enctype="multipart/form-data">
        <label for="name">Game Name:</label><br>
        <input type="text" id="name" name="name" required><br>

        <label for="description">Description:</label><br>
        <textarea id="description" name="description" required></textarea><br>

        <label for="image">Image:</label><br>
        <input type="file" id="image" name="image" required><br>

        <label for="link">Link:</label><br>
        <input type="text" id="link" name="link" required><br>

        <input type="submit" value="Add Game">
    </form>

    <?php include_once('footer.php'); ?>
</body>

</html>