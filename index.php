<?php
session_start();

if (!isset($_SESSION['user_id'])) {
  header('Location: /wiki-games/login');
  exit();
}

if (isset($_SESSION['message'])) {
  echo '<div class="message ' . $_SESSION['message_type'] . '">' . $_SESSION['message'] . '</div>';
  unset($_SESSION['message'], $_SESSION['message_type']);
}

// Include database connection
include_once('./db_connect.php');

// Define the number of games per page
$games_per_page = 12;

// Determine the current page number
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// Calculate the offset for the query
$offset = ($current_page - 1) * $games_per_page;

// Prepare the SQL query
$query = "SELECT * FROM games LIMIT :offset, :games_per_page";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':games_per_page', $games_per_page, PDO::PARAM_INT);
$stmt->execute();

$games = $stmt->fetchAll();

// Get the total number of games
$total_games = $pdo->query("SELECT COUNT(*) FROM games")->fetchColumn();

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
  <header>
    <!--Header-->
    <?php include_once('header.php'); ?>
  </header>

  <div class="container-fluid">
    <div class="row">
      <div class="col-md-2">
        <!-- Tags go here -->
        <h5>Tags</h5>
        <ul class="list-tags">
          <!-- you will replace this with your PHP code to dynamically generate the list -->
          <li class="list-tags-item"><a>Tag 1</a></li>
          <li class="list-tags-item"><a>Tag 1</a></li>
          <li class="list-tags-item"><a>Tag 1</a></li>
          <!-- end of list -->
        </ul>
      </div>
      <div class="col-md-9">
        <div class="games-grid">
          <?php foreach ($games as $game) : ?>
            <div class="game-card">
              <img src="./images/<?php echo $game['image']; ?>" alt="<?php echo $game['name']; ?>">
              <h2><?php echo $game['name']; ?></h2>
              <p class="price">
                <!-- Conditional price display -->
                <?php
                if ($game['price'] == 0) {
                  echo 'Gratuit';
                } else {
                  echo $game['price'] . ' €';
                }
                ?>
              </p>
              <a target="_blank" href="<?php echo $game['link']; ?>">En savoir plus</a>
              <div class="action-btn">
                <!-- Add the new Edit and Delete links -->
                <a href="./formulaires/edit_games.php?id=<?php echo $game['id']; ?>">Modifier</a>
                <a href="javascript:void(0);" onclick="confirmDelete('./formulaires/delete_game.php?id=<?php echo $game['id']; ?>', 
                '<?php echo $game['name']; ?>')">Supprimer</a>
              </div>
            </div>
          <?php endforeach; ?>

          <!-- Add Game Card -->
          <div class="game-card">
            <a href="add_games">
              <img src="./images/add-icon.png" alt="Add Game">
              <h2 class="add-games">Ajouter un jeu</h2>
            </a>
          </div>
        </div>

        <!-- Pagination -->
        <div class="pagination">
          <?php for ($page = 1; $page <= ceil($total_games / $games_per_page); $page++) : ?>
            <a href="?page=<?php echo $page; ?>"><?php echo $page; ?></a>
          <?php endfor; ?>
        </div>
      </div>
    </div>
  </div>
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

  function confirmDelete(deleteUrl, gameName) {
    Swal.fire({
      title: 'Êtes-vous sûr de vouloir supprimer ' + gameName + '?',
      text: "Vous ne pourrez pas revenir en arrière!",
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Oui, supprimez-le!'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = deleteUrl;
      }
    })
  }
</script>