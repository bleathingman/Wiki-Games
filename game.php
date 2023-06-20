<?php
// Include database connection
include_once('db_connect.php');

// Get the game id from the URL
$id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: missing ID.');

// Query to select the game
$query = "SELECT * FROM games WHERE id = ? LIMIT 0,1";

$stmt = $pdo->prepare($query);

$stmt->bindParam(1, $id);

$stmt->execute();

$row = $stmt->fetch(PDO::FETCH_ASSOC);

// Now you can use $row['name'], $row['description'], etc. to display the game data.
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $row['name']; ?></title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include_once('header.php'); ?>

    <h1><?php echo $row['name']; ?></h1>
    <img src="images/<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>">
    <p><?php echo $row['description']; ?></p>

    <?php include_once('footer.php'); ?>
</body>

</html>