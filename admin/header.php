<head>
    <?php
    ini_set('display_errors', 1);
    ?>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />
    <script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="styles.css" rel="stylesheet">
</head>
<!--Bar de navigation-->
<header>
    <nav>
        <ul>
            <li><a href="/">Accueil</a></li>
            <li><a href="add_game.php">Ajouter un jeu</a></li>
            <li><a href="add_game.php">Ajouter une catégorie</a></li>
            <!-- Add more links as needed -->
        </ul>
        <!-- User info dropdown -->
        <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-fill"></i>User
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                <li><a class="dropdown-item" href="#">Infos Utilisateur</a></li>
                <li><a class="dropdown-item" href="#">Deconnexion</a></li>
            </ul>
        </div>
    </nav>
</header>