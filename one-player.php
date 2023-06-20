<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="styles.css" rel="stylesheet">
  <title>Document</title>
</head>

<body>
  <!--Header-->
  <?php include_once('header.php'); ?>
  <br>
  <div class="row row-cols-1 row-cols-md-4 g-3">
    <div class="col">
      <div class="card">
        <img src="images/cave-crawler.jpg" class="card-img-top" alt="Cave Crawler image">
        <div class="card-body">
          <h5 class="card-title">Cave Crawler</h5>
          <br>
          <p class="card-text">Cave Crawler is a short, 2D side-scrolling action platformer.
            Explore caves filled with interesting enemies and fun platforming challenges.</p>
          <br>
          <a href="https://store.steampowered.com/app/1865440/Cave_Crawler/" class="btn btn-secondary">Go Steam</a>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card">
        <img src="images/himno.jpg" class="card-img-top" alt="Himno image">
        <div class="card-body">
          <h5 class="card-title">Himno</h5>
          <p class="card-text">Himno is a peaceful, 2D platformer game with an infinite number of beautiful
            procedurally generated maps. Take a breath, and relax.</p>
          <a href="https://store.steampowered.com/app/931690/Himno/" class="btn btn-secondary">Go Steam</a>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card">
        <img src="images/Yu-Gi-Oh-Duel-Links.jpg" class="card-img-top" alt="Yu-Gi-Oh-Duel-Links image">
        <div class="card-body">
          <h5 class="card-title">Yu-Gi-Oh-Duel-Links</h5>
          <p class="card-text">This is a longer card with supporting text below as a natural lead-in to additional content.</p>
          <a href="#" class="btn btn-secondary">Go Steam</a>
        </div>
      </div>
    </div>
  </div>
</body>

</html>