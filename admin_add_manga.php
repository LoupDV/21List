<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config.php'; // Pour la connexion DB plus tard, si on vérifie les doublons
// La fonction searchMangaJikan pourrait être dans un fichier d'helpers inclus ici
// Pour l'instant, on peut la copier/coller ou la réécrire pour ce contexte

function searchMangaJikanForAdmin($searchTerm) {
    $encodedSearchTerm = urlencode($searchTerm);
    // On peut prendre plus de résultats pour l'admin
    $apiUrl = "https://api.jikan.moe/v4/manga?q=" . $encodedSearchTerm . "&limit=10&sfw"; // sfw pour "Safe For Work" results

    $options = ['http' => ['method' => "GET", 'header' => "User-Agent: VotreAppMangaAdmin/1.0 (votre@email.com)\r\n"]];
    $context = stream_context_create($options);
    $responseJson = @file_get_contents($apiUrl, false, $context);

    if ($responseJson === FALSE) { return ['error' => "Erreur requête API Jikan."]; }
    $responseData = json_decode($responseJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) { return ['error' => "Erreur décodage JSON: " . json_last_error_msg()]; }
    if (!isset($responseData['data'])) { return ['error' => "Réponse API Jikan invalide."]; }
    return $responseData['data'];
}

$searchResults = null;
$searchTerm = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_manga_jikan'])) {
    $searchTerm = trim($_POST['manga_title_search']);
    if (!empty($searchTerm)) {
        $searchResults = searchMangaJikanForAdmin($searchTerm);
    } else {
        $searchResults = ['error' => "Veuillez entrer un titre à rechercher."];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Ajouter Manga depuis Jikan</title>
    <link rel="stylesheet" href="assets/css/style.css"> <!-- Ton CSS principal -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .admin-container { max-width: 900px; margin: 20px auto; padding: 20px; background-color: var(--card-bg); border-radius: var(--border-radius); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .search-form-admin { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-form-admin input[type="text"] { flex-grow: 1; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
        .jikan-result-item { display: flex; gap: 15px; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; }
        .jikan-result-item img { width: 80px; height: auto; object-fit: cover; border-radius: 4px; }
        .jikan-result-info { flex-grow: 1; }
        .jikan-result-info h4 { margin-top: 0; margin-bottom: 5px; font-size: 1.1em; color: var(--accent-color); }
        .jikan-result-info p { font-size: 0.9em; margin: 3px 0; color: var(--text-color); }
        .jikan-result-actions button { /* Style pour le bouton "Ajouter" */ }
        .error-message { color: red; font-weight: bold; }
        .success-message { color: green; font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; // Si tu as une navbar ?>
    <div class="admin-container">
        <h1>Ajouter un Manga depuis Jikan</h1>

        <?php
        // Afficher les messages de process_add_from_jikan.php s'il y en a (via session ou GET param)
        if (isset($_SESSION['jikan_message'])) {
            $message_type = $_SESSION['jikan_message_type'] ?? 'info';
            echo "<p class='" . ($message_type === 'error' ? 'error-message' : 'success-message') . "'>" . htmlspecialchars($_SESSION['jikan_message']) . "</p>";
            unset($_SESSION['jikan_message']);
            unset($_SESSION['jikan_message_type']);
        }
        ?>

        <form method="POST" action="admin_add_manga.php" class="search-form-admin">
            <input type="text" name="manga_title_search" placeholder="Titre du manga à rechercher sur Jikan..." value="<?= htmlspecialchars($searchTerm) ?>" required>
            <button type="submit" name="search_manga_jikan" class="btn confirm"><i class="fas fa-search"></i> Rechercher sur Jikan</button>
        </form>

        <?php if (is_array($searchResults)): ?>
            <?php if (isset($searchResults['error'])): ?>
                <p class="error-message"><?= htmlspecialchars($searchResults['error']) ?></p>
            <?php elseif (count($searchResults) > 0): ?>
                <h2>Résultats de Jikan :</h2>
                <?php foreach ($searchResults as $manga): ?>
                    <div class="jikan-result-item">
                        <?php
                            $imageUrl = $manga['images']['jpg']['image_url'] ?? 'covers/default_cover.jpg'; // Pour l'aperçu, image_url suffit
                        ?>
                        <img src="<?= htmlspecialchars($imageUrl) ?>" alt="Cover de <?= htmlspecialchars($manga['title']) ?>">
                        <div class="jikan-result-info">
                            <h4><?= htmlspecialchars($manga['title']) ?> (MAL ID: <?= $manga['mal_id'] ?>)</h4>
                            <p><strong>Type:</strong> <?= htmlspecialchars($manga['type'] ?? 'N/A') ?></p>
                            <p><strong>Statut:</strong> <?= htmlspecialchars($manga['status'] ?? 'N/A') ?></p>
                            <p><strong>Score:</strong> <?= htmlspecialchars($manga['score'] ?? 'N/A') ?></p>
                            <p><strong>Synopsis (début):</strong> <?= htmlspecialchars(substr($manga['synopsis'] ?? '', 0, 100)) ?>...</p>
                        </div>
                        <div class="jikan-result-actions">
                            <form method="POST" action="process_add_from_jikan.php">
                                <input type="hidden" name="mal_id" value="<?= $manga['mal_id'] ?>">
                                <input type="hidden" name="title" value="<?= htmlspecialchars($manga['title']) ?>">
                                <!-- On pourrait passer plus de données ici pour éviter un second appel API dans process_add,
                                     mais pour avoir les données les plus complètes, un appel avec mal_id est mieux. -->
                                <button type="submit" name="add_to_catalogue" class="btn go-liste">
                                    <i class="fas fa-plus-circle"></i> Ajouter à mon Catalogue
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun résultat trouvé sur Jikan pour "<?= htmlspecialchars($searchTerm) ?>".</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script src="assets/JS/main.js"></script> <?php // Pour les thèmes, modales si besoin ?>
</body>
</html>