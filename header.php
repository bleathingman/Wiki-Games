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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="styles.css" rel="stylesheet">
</head>
<header>
  <!--Bar de navigation-->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="index.php">Wiki Games</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link" href="tags">Tags</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="options">Options</a>
          </li>
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
      </div>
    </div>
  </nav>
</header>