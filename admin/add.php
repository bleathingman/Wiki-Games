<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireAdmin();

$errors = [];
$vals   = [
    'name'         => '',
    'description'  => '',
    'genre'        => '',
    'platform'     => '',
    'price'        => '0',
    'image'        => '',
    'game_url'     => '',
    'video_url'    => '',
    'extra_images' => '',
    'release_year' => '',
    'rating'       => '',
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
    $vals['video_url']    = sanitizeUrl($_POST['video_url'] ?? '');
    $vals['extra_images'] = sanitize($_POST['extra_images'] ?? '');
    $vals['release_year'] = sanitizeInt($_POST['release_year'] ?? '');
    $vals['rating']       = (float) min(10, max(0, sanitizeFloat($_POST['rating'] ?? '0')));

    if ($vals['name'] === '')
        $errors['name'] = 'Le nom du jeu est requis.';
    elseif (strlen($_POST['name']) > 100)
        $errors['name'] = 'Nom trop long (max 100 caractères).';

    // Handle file upload if provided
    $uploadedImage = null;
    if (!empty($_FILES['image_file']['name'])) {
        $uploadedImage = handleImageUpload($_FILES['image_file']);
        if ($uploadedImage === false)
            $errors['image'] = 'Image invalide. Formats acceptés : JPG, PNG, WebP, GIF. Max 5 MB.';
    }

    if (empty($errors)) {
        $db   = getDB();
        $img  = $uploadedImage ?? ($vals['image'] ?: null);
        $stmt = $db->prepare('
            INSERT INTO games (name, description, genre, platform, price, image, game_url, video_url, extra_images, release_year, rating, created_by)
            VALUES (:name, :desc, :genre, :platform, :price, :image, :url, :video, :extras, :year, :rating, :user)
        ');
        $stmt->execute([
            ':name'     => $vals['name'],
            ':desc'     => $vals['description'] ?: null,
            ':genre'    => $vals['genre'] ?: null,
            ':platform' => $vals['platform'] ?: null,
            ':price'    => $vals['price'],
            ':image'    => $img,
            ':url'      => $vals['game_url'] ?: null,
            ':video'    => $vals['video_url'] ?: null,
            ':extras'   => $vals['extra_images'] ?: null,
            ':year'     => $vals['release_year'] ?: null,
            ':rating'   => $vals['rating'] ?: null,
            ':user'     => $_SESSION['user_id'],
        ]);

        setFlash('success', '"' . $vals['name'] . '" ajouté avec succès !');
        header('Location: ' . APP_URL . '/admin/index.php');
        exit;
    }
}

$pageTitle = 'Ajouter un jeu';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="form-page">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
        <div>
            <h1 class="page-title">AJOUTER <span>UN JEU</span></h1>
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
                        value="<?= sanitize($vals['genre']) ?>" placeholder="RPG, FPS, Stratégie...">
                </div>
                <div class="form-group">
                    <label class="form-label" for="platform">Plateforme</label>
                    <input type="text" id="platform" name="platform" class="form-input"
                        value="<?= sanitize($vals['platform']) ?>" placeholder="PC, PS5, Xbox...">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="price">Prix (€)</label>
                    <input type="number" id="price" name="price" class="form-input"
                        value="<?= $vals['price'] ?>" min="0" step="0.01" placeholder="0 = Gratuit">
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
                        value="<?= $vals['release_year'] ?>" min="1970" max="<?= date('Y') + 2 ?>" placeholder="<?= date('Y') ?>">
                </div>
                <div class="form-group">
            <div class="form-group">
                <label class="form-label" for="game_url">URL du jeu</label>
                <input type="url" id="game_url" name="game_url" class="form-input"
                    value="<?= sanitize($vals['game_url']) ?>" placeholder="https://...">
            </div>

            <div class="form-group">
                <label class="form-label" for="video_url">URL Vidéo / Trailer YouTube</label>
                <input type="url" id="video_url" name="video_url" class="form-input"
                    value="<?= sanitize($vals['video_url']) ?>" placeholder="https://www.youtube.com/watch?v=...">
                <p class="form-hint">Collez l'URL YouTube du trailer. Elle sera affichée sur la page du jeu.</p>
            </div>

            <p class="form-section-title">Image</p>

            <div class="form-group">
                <label class="form-label" for="image_file">Télécharger une image</label>
                <input type="file" id="image_file" name="image_file" class="form-input"
                    accept="image/jpeg,image/png,image/webp,image/gif">
                <p class="form-hint">JPG, PNG, WebP, GIF — Max 5 MB</p>
                <?php if (isset($errors['image'])): ?>
                    <p class="form-error"><?= sanitize($errors['image']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="image_url">Ou URL d'une image</label>
                <input type="url" id="image_url" name="image_url" class="form-input"
                    value="<?= sanitize($vals['image']) ?>" placeholder="https://...">
                <p class="form-hint">Si les deux sont fournis, le fichier uploadé est prioritaire.</p>
            </div>

            <div class="form-group">
                <label class="form-label" for="extra_images">Images supplémentaires (galerie)</label>
                <textarea id="extra_images" name="extra_images" class="form-input" rows="3"
                    placeholder="https://url1.jpg|https://url2.jpg|https://url3.jpg"><?= sanitize($vals['extra_images']) ?></textarea>
                <p class="form-hint">URLs séparées par <strong>|</strong> — ex: <code>https://img1.jpg|https://img2.jpg</code></p>
            </div>

            <div style="display:flex;gap:1rem;margin-top:1.5rem;justify-content:flex-end">
                <a href="<?= APP_URL ?>/admin/index.php" class="btn-ghost">Annuler</a>
                <button type="submit" class="btn-solid">Ajouter le jeu →</button>
            </div>
        </form>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
