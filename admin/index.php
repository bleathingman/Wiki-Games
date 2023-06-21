<?php
session_start();

// Include database connection
include_once('../db_connect.php');

// Query to get all games ordered by creation date
$query = "SELECT * FROM games ORDER BY created_at DESC";

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
    <title>Administration - Wiki Games</title>
    <!-- You might need to adjust the path -->
    <link rel="stylesheet" href="../styles.css">
</head>

<body>
    <!--Header-->
    <?php include_once('header.php'); ?>

    <div class="admin-table">
        <h2>Liste des Jeux</h2>
        <table>
            <tr>
                <th>Nom</th>
                <th>Date de création</th>
            </tr>

            <?php foreach ($games as $game) : ?>
                <tr>
                    <td><?php echo $game['name']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($game['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

</body>

</html>