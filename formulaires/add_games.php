<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add a Game</title>
    <link rel="stylesheet" href="../styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
</head>

<body>
    <?php include_once('../header.php'); ?>
    <?php
    session_start();
    if (isset($_SESSION['message'])) {
        echo '<div class="message ' . $_SESSION['message_type'] . '">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message'], $_SESSION['message_type']);
    }
    ?>

    <h1 class="title-add-game">Add a Game</h1>

    <div class="form-container">
        <form id="addGameForm" action="../process/add_game_process.php" method="post" enctype="multipart/form-data">
            <div class="form-field">
                <label for="name">Game Name :</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-field">
                <label for="price">Price :</label>
                <input type="text" id="price" name="price" required>
            </div>

            <div class="form-field">
                <label for="image">Image :</label>
                <input type="file" id="image" name="image" required>
            </div>

            <div class="form-field">
                <label for="description">Description :</label>
                <input type="text" id="description" name="description" required>
            </div>

            <div class="form-field">
                <label for="link">Link :</label>
                <input type="text" id="link" name="link" required>
            </div>

            <div class="submit-button">
                <input type="button" id="submitBtn" value="Add Game">
            </div>
        </form>
    </div>

    <script>
        document.querySelector("#submitBtn").addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Êtes-vous sûr?',
                text: "Vous êtes sur le point d'ajouter un nouveau jeu. Êtes-vous sûr de vouloir continuer?",
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui, ajoutez-le!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector("#addGameForm").submit();
                }
            })
        });
    </script>
    <?php include_once('../footer.php'); ?>
</body>

</html>