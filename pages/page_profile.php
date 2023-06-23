<?php
session_start();

if (isset($_SESSION['message'])) {
    echo '<div class="message ' . $_SESSION['message_type'] . '">' . $_SESSION['message'] . '</div>';
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Include database connection
include_once('./db_connect.php');

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
</head>

<body>
    <header>
        <!--Header-->
        <?php include_once('./header.php'); ?>
    </header>

</body>

</html>