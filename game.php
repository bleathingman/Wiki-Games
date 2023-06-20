<?php
// Include database connection
include_once('db_connect.php');

// Query to get all games
$query = "SELECT * FROM games";

$stmt = $pdo->prepare($query);
$stmt->execute();

$games = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Games</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include_once('header.php'); ?>

    <div class="games-grid">
        <?php foreach ($games as $game) : ?>
            <div class="game-card">
                <img src="images/<?php echo $game['image']; ?>" alt="<?php echo $game['name']; ?>">
                <h2><?php echo $game['name']; ?></h2>
                <p><?php echo $game['description']; ?></p>
                <a href="<?php echo $game['link']; ?>">Play Now</a>
            </div>
        <?php endforeach; ?>
    </div>

    <?php include_once('footer.php'); ?>
</body>

</html>