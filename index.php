<?php
session_start();

if (isset($_SESSION['message'])) {
  echo '<div class="message ' . $_SESSION['message_type'] . '">' . $_SESSION['message'] . '</div>';
  unset($_SESSION['message'], $_SESSION['message_type']);
}

// Include database connection
include_once('./db_connect.php');

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
  <title>Wiki Games</title>
</head>

<body>
  <!--Header-->
  <?php include_once('header.php'); ?>

  <!-- Swiper -->
  <div class="swiper mySwiper">
    <div class="swiper-wrapper">
      <div class="swiper-slide slide-1">
        <a href="clicker-heroes.php"><img src="images/clicker-heros.jpg" alt="Clicker Heros"></a>
      </div>
      <div class="swiper-slide slide-2">
        <a href="amongus.php"><img src="images/amongus.jpg" alt="Among Us"></a>
      </div>
      <div class="swiper-slide slide-3">
        <a href="cave-crawler.php"><img src="images/cave-crawler.jpg" alt="Cave Crawler"></a>
      </div>
      <div class="swiper-slide slide-4">Slide 4</div>
      <div class="swiper-slide slide-5">Slide 5</div>
      <div class="swiper-slide slide-6">Slide 6</div>
      <div class="swiper-slide slide-7">Slide 7</div>
      <div class="swiper-slide slide-8">Slide 8</div>
      <div class="swiper-slide slide-9">Slide 9</div>
    </div>
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
    <div class="swiper-pagination"></div>
  </div>

  <a class="btn btn-primary btn-lg" href="404.php" role="button">Go to 404</a>

  <div class="games-grid">
    <?php foreach ($games as $game) : ?>
      <div class="game-card">
        <img src="./images/<?php echo $game['image']; ?>" alt="<?php echo $game['name']; ?>">
        <h2><?php echo $game['name']; ?></h2>
        <p><?php echo $game['description']; ?></p>
        <a href="<?php echo $game['link']; ?>">Play Now</a>
        <!-- Add the new Edit and Delete links -->
        <a href="./formulaires/edit_games.php?id=<?php echo $game['id']; ?>">Edit</a>
        <a href="./formulaires/delete_game.php?id=<?php echo $game['id']; ?>">Delete</a>
      </div>
    <?php endforeach; ?>
  </div>


  <?php include_once('footer.php'); ?>

</body>

</html>

<script>
  var swiper = new Swiper(".mySwiper", {
    slidesPerView: 3,
    spaceBetween: 30,
    slidesPerGroup: 3,
    loop: true,
    loopFillGroupWithBlank: true,
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
    },
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
  });
</script>