<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireAdmin();

$id = sanitizeInt($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/admin/index.php'); exit; }

$db   = getDB();
$stmt = $db->prepare('SELECT * FROM games WHERE id = :id');
$stmt->execute([':id' => $id]);
$game = $stmt->fetch();

if (!$game) {
    setFlash('error', 'Jeu introuvable.');
    header('Location: ' . APP_URL . '/admin/index.php');
    exit;
}

$errors = [];
$vals   = [
    'name'         => $game['name'],
    'description'  => $game['description'] ?? '',
    'genre'        => $game['genre'] ?? '',
    'platform'     => $game['platform'] ?? '',
    'price'        => $game['price'],
    'image'        => $game['image'] ?? '',
    'game_url'     => $game['game_url'] ?? '',
    'release_year' => $game['release_year'] ?? '',
    'rating'       => $game['rating'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $vals['name']         = sanitize($_POST['name'] ?? '');
    $vals['description']  = sanitize($_POST['description'] ?? '');
    $vals['genre']        = sanitize($_POST['genre'] ?? '');
    $vals['platform']     = sanitize($_POST['platform'] ?? '');
    $vals['price']        = sanitizeFloat($_POST['price'] ?? '0');
    $vals['image']        = sanitize($_POST['image_url'] ?? '');
    $vals['game_url']     = sanitizeUrl($_POST['game_url'] ?? '');
    $vals['release_year'] = sanitizeInt($_POST['release_year'] ?? '');
    $vals['rating']       = (float) min(10, max(0, sanitizeFloat($_POST['rating'] ?? '0')));

    if ($vals['name'] === '')
        $errors['name'] = 'Le nom du jeu est requis.';

    // Handle file upload if provided
    $uploadedImage = null;
    if (!empty($_FILES['image_file']['name'])) {
        $uploadedImage = handleImageUpload($_FILES['image_file']);
        if ($uploadedImage === false)
            $errors['image'] = 'Image invalide. Formats acceptés : JPG, PNG, WebP, GIF. Max 5 MB.';
    }

    if (empty($errors)) {
        $img = $uploadedImage ?? ($vals['image'] ?: $game['image']);

        $stmt = $db->prepare('
            UPDATE games SET
                name = :name,
                description = :desc,
                genre = :genre,
                platform = :platform,
                price = :price,
                image = :image,
                game_url = :url,
                release_year = :year,
                rating = :rating
            WHERE id = :id
        ');
        $stmt->execute([
            ':name'     => $vals['name'],
            ':desc'     => $vals['description'] ?: null,
            ':genre'    => $vals['genre'] ?: null,
            ':platform' => $vals['platform'] ?: null,
            ':price'    => $vals['price'],
            ':image'    => $img,
            ':url'      => $vals['game_url'] ?: null,
            ':year'     => $vals['release_year'] ?: null,
            ':rating'   => $vals['rating'] ?: null,
            ':id'       => $id,
        ]);

        setFlash('success', '"' . $vals['name'] . '" mis à jour avec succès !');
        header('Location: ' . APP_URL . '/admin/index.php');
        exit;
    }
}

$pageTitle = 'Modifier : ' . $game['name'];
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="form-page">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
        <div>
            <h1 class="page-title">MODIFIER <span>UN JEU</span></h1>
            <p class="page-subtitle"><?= sanitize($game['name']) ?></p>
        </div>
        <a href="<?= APP_URL ?>/admin/index.php" class="btn-ghost">← Retour</a>
    </div>

    <div class="form-card">
        <form method="POST" action="" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <p class="form-section-title">Informations principales</p>

            <div class="form-group">
                <label class="form-label" for="name">Nom du jeu *</label>
                <input type="text" id="name" name="name"
                    class="form-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                    value="<?= sanitize($vals['name']) ?>"
                    maxlength="100" autofocus>
                <?php if (isset($errors['name'])): ?>
                    <p class="form-error"><?= sanitize($errors['name']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-input"><?= sanitize($vals['description']) ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="genre">Genre</label>
                    <input type="text" id="genre" name="genre" class="form-input"
                        value="<?= sanitize($vals['genre']) ?>" placeholder="RPG, FPS...">
                </div>
                <div class="form-group">
                    <label class="form-label" for="platform">Plateforme</label>
                    <input type="text" id="platform" name="platform" class="form-input"
                        value="<?= sanitize($vals['platform']) ?>" placeholder="PC, PS5...">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="price">Prix (€)</label>
                    <input type="number" id="price" name="price" class="form-input"
                        value="<?= $vals['price'] ?>" min="0" step="0.01">
                </div>
                <div class="form-group">
                    <label class="form-label" for="rating">Note (/10)</label>
                    <input type="number" id="rating" name="rating" class="form-input"
                        value="<?= $vals['rating'] ?>" min="0" max="10" step="0.1">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="release_year">Année de sortie</label>
                    <input type="number" id="release_year" name="release_year" class="form-input"
                        value="<?= $vals['release_year'] ?>" min="1970" max="<?= date('Y') + 2 ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="game_url">URL du jeu</label>
                    <input type="url" id="game_url" name="game_url" class="form-input"
                        value="<?= sanitize($vals['game_url']) ?>" placeholder="https://...">
                </div>
            </div>

            <p class="form-section-title">Image</p>

            <?php if ($vals['image']): ?>
            <div style="margin-bottom:1rem">
                <p class="form-label">Image actuelle</p>
                <?php $s = str_starts_with($vals['image'], 'http') ? $vals['image'] : UPLOAD_URL . $vals['image']; ?>
                <img src="<?= $s ?>" alt="" style="max-width:200px;border-radius:8px;border:1px solid var(--border-subtle)">
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="image_file">Remplacer par un fichier</label>
                <input type="file" id="image_file" name="image_file" class="form-input"
                    accept="image/jpeg,image/png,image/webp,image/gif">
                <p class="form-hint">JPG, PNG, WebP, GIF — Max 5 MB</p>
                <?php if (isset($errors['image'])): ?>
                    <p class="form-error"><?= sanitize($errors['image']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="image_url">Ou changer l'URL d'image</label>
                <input type="url" id="image_url" name="image_url" class="form-input"
                    value="<?= sanitize(str_starts_with($vals['image'], 'http') ? $vals['image'] : '') ?>"
                    placeholder="https://...">
            </div>

            <div style="display:flex;gap:1rem;margin-top:1.5rem;justify-content:flex-end">
                <a href="<?= APP_URL ?>/admin/index.php" class="btn-ghost">Annuler</a>
                <button type="submit" class="btn-solid">Enregistrer les modifications →</button>
            </div>
        </form>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
