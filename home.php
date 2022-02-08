<!DOCTYPE html>
<html lang="fr">
<<<<<<< HEAD

=======
>>>>>>> syko
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>

<body>
  <!--Header-->
  <?php include_once('header.php'); ?>
  <!-- Swiper -->
  <div class="swiper mySwiper">
    <div class="swiper-wrapper">
      <div class="swiper-slide slide-1"><img src="images/clicker-heros.jpg" alt="Clicker Heros"></div>
      <div class="swiper-slide slide-2"><img src="images/amongus.jpg" alt="Among Us"></div>
      <div class="swiper-slide slide-3"><img src="images/cave-crawler.jpg" alt="Cave Crawler"></div>
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
  <a class="btn btn-primary btn-lg" href="toto.php" role="button">Learn more</a>

  <!-- Initialize Swiper -->
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

</body>

</html>