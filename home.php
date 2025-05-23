<?php
// home.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'config.php'; // Pour la connexion $conn

// Fonction pour afficher une carte de manga (réutilisable)
function display_manga_card_for_home($manga_data)
{
    if (!is_array($manga_data)) {
        $manga_data = [];
    }

    $id = isset($manga_data['id']) ? $manga_data['id'] : '#';
    $title = isset($manga_data['title']) ? htmlspecialchars($manga_data['title']) : 'Titre inconnu';
    $cover_image = isset($manga_data['cover_image']) ? htmlspecialchars($manga_data['cover_image']) : 'covers/default_cover.jpg';
?>
    <div class="card-wrapper">

        <a href="detail_manga.php?id=<?= $id ?>" class="card-link">
            <div class="card fade-in">
                <img src="<?= $cover_image ?>"
                    alt="Cover de <?= $title ?>"
                    class="cover-image"
                    onerror="this.onerror=null;this.src='covers/default_cover.jpg';">
            </div>
        </a>
        <div class="card-title-below">
            <a href="detail_manga.php?id=<?= $id ?>">
                <?= $title ?>
            </a>
        </div>

        <div class="card-info-panel">
            <div class="panel-content">
                <h3 class="panel-title"><?= $title ?></h3>

                <?php
                $metaItems = [];
                // Utilisation de la colonne 'type'
                $type = isset($manga_data['type']) ? htmlspecialchars(ucfirst($manga_data['type'])) : '';
                if (!empty($type)) {
                    $metaItems[] = $type;
                }

                // Utilisation de la colonne 'start_date' pour l'année
                $year = '';
                if (!empty($manga_data['start_date'])) {
                    $timestamp = strtotime($manga_data['start_date']);
                    if ($timestamp !== false) {
                        $parsed_year = date('Y', $timestamp);
                        if ($parsed_year > 0) {
                            $year = $parsed_year;
                            $metaItems[] = $year;
                        }
                    }
                }

                // Utilisation de 'num_chapters' et 'num_episodes'
                $count_indicator = '';
                if (isset($manga_data['num_chapters']) && $manga_data['num_chapters'] !== null && filter_var($manga_data['num_chapters'], FILTER_VALIDATE_INT) !== false && $manga_data['num_chapters'] > 0) {
                    $count_indicator = htmlspecialchars($manga_data['num_chapters']) . " Chaps";
                } elseif (isset($manga_data['num_episodes']) && $manga_data['num_episodes'] !== null && filter_var($manga_data['num_episodes'], FILTER_VALIDATE_INT) !== false && $manga_data['num_episodes'] > 0) {
                    $count_indicator = htmlspecialchars($manga_data['num_episodes']) . " Eps";
                }
                if (!empty($count_indicator)) {
                    $metaItems[] = $count_indicator;
                }

                if (!empty($metaItems)): ?>
                    <div class="panel-meta">
                        <?= implode(' <span class="meta-separator">•</span> ', $metaItems) ?>
                    </div>
                <?php endif; ?>

                <?php
                // Utilisation de la colonne 'genre' (au singulier)
                $genres_string = isset($manga_data['genre']) ? $manga_data['genre'] : ''; // Changé 'genres' en 'genre'
                if (!empty($genres_string)):
                    $genresArray = [];
                    if (is_string($genres_string)) {
                        $decodedGenres = json_decode($genres_string, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedGenres)) {
                            $genresArray = $decodedGenres;
                        } else {
                            $genresArray = array_map('trim', explode(',', $genres_string));
                        }
                    } elseif (is_array($genres_string)) {
                        $genresArray = $genres_string;
                    }
                    $genresArray = array_filter(array_map('trim', $genresArray)); // Nettoie et filtre les chaînes vides
                ?>
                    <?php if (!empty($genresArray)): ?>
                        <div class="panel-genres">
                            <?php foreach ($genresArray as $g): ?>
                                <span class="genre-tag"><?= htmlspecialchars($g) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php
                // Utilisation de 'jikan_score' pour le score
                $score = isset($manga_data['jikan_score']) ? $manga_data['jikan_score'] : null; // Changé 'score' en 'jikan_score'
                if ($score !== null && is_numeric($score) && (float)$score > 0): ?>
                    <div class="panel-score">
                        <i class="fas fa-star"></i> <?= htmlspecialchars(number_format((float)$score, 1)) ?>
                    </div>
                <?php endif; ?>

                <?php /* Optionnel: Synopsis - Utilisation de la colonne 'synopsis'
                $synopsis_text = isset($manga_data['synopsis']) ? $manga_data['synopsis'] : '';
                if (!empty($synopsis_text)): ?>
                <div class="panel-synopsis">
                    <p>
                        <?= htmlspecialchars(mb_substr($synopsis_text, 0, 120)) ?>
                        <?= (mb_strlen($synopsis_text) > 120) ? '...' : '' ?>
                    </p>
                </div>
                <?php endif; */ ?>
            </div>
        </div>
    </div>
<?php
}

// Fonction pour récupérer et afficher une section
function fetch_and_display_section($conn, $section_title, $order_by_clause, $link_params = [], $where_clause = "", $limit = 6)
{
    echo '<section class="home-section">';
    echo '  <div class="section-header">';
    echo '      <h2>' . htmlspecialchars($section_title) . '</h2>';
    $see_all_query_string = http_build_query($link_params);
    echo '      <a href="catalogue.php?' . $see_all_query_string . '" class="see-all-link">Tout voir »</a>';
    echo '  </div>';
    echo '  <div class="section-grid">';

    // Requête SQL utilisant les noms de colonnes de TA TABLE
    $sql_columns = "id, title, cover_image, type, start_date, num_chapters, num_episodes, genre, jikan_score, synopsis, popularity_rank, demographic, status_publication";
    $sql = "SELECT $sql_columns FROM catalogue";

    if (!empty($where_clause)) {
        $sql .= " WHERE " . $where_clause;
    }
    $sql .= " ORDER BY " . $order_by_clause . " LIMIT " . intval($limit);

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            display_manga_card_for_home($row);
        }
    } else {
        echo '<p class="no-results-section">Aucun titre à afficher dans cette section pour le moment.</p>';
    }
    echo '  </div>';
    echo '</section>';
}
?>

<!DOCTYPE html>
<html lang="fr" class="no-transition">

<head>
    <meta charset="UTF-8">
    <title>21List - Accueil</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="home-container">
        <div class="welcome-header">
            <h1 style="font-size: 2.5rem; margin-bottom: 10px;">Bienvenue sur <strong>21List</strong></h1>
            <p style="font-size: 1.2rem; color: #555;">Gérez vos lectures et découvrez de nouveaux titres.</p>
        </div>
        <div class="home-buttons" style="display: flex; justify-content: center; gap: 20px; margin-bottom: 50px;">
            <a href="catalogue.php" class="btn go-catalogue">
                <i class="fas fa-book-open"></i> Explorer le Catalogue Complet
            </a>
            <a href="index.php" class="btn go-liste">
                <i class="fas fa-list"></i> Ma Liste Personnelle
            </a>
        </div>

        <?php
        // --- AFFICHAGE DES SECTIONS ---
        fetch_and_display_section(
            $conn,
            "Les Plus Populaires",
            "CASE WHEN popularity_rank IS NULL THEN 1 ELSE 0 END, popularity_rank ASC, title ASC",
            ['sort_by' => 'popularity_rank_asc'],
            "popularity_rank IS NOT NULL",
            6
        );
        fetch_and_display_section(
            $conn,
            "Nouveautés Récentes",
            "start_date DESC, title ASC",
            ['sort_by' => 'start_date_desc'],
            "start_date IS NOT NULL",
            6
        );
        fetch_and_display_section(
            $conn,
            "Notre Sélection de Mangas",
            "CASE WHEN popularity_rank IS NULL THEN 1 ELSE 0 END, popularity_rank ASC, title ASC",
            ['type' => 'Manga', 'sort_by' => 'popularity_rank_asc'], // Assure-toi que 'type' est bien 'Manga' dans ta BDD
            "`type` = 'Manga' AND popularity_rank IS NOT NULL",
            6
        );
        fetch_and_display_section(
            $conn,
            "À Découvrir : Manhwas",
            "CASE WHEN popularity_rank IS NULL THEN 1 ELSE 0 END, popularity_rank ASC, title ASC",
            ['type' => 'Manhwa', 'sort_by' => 'popularity_rank_asc'], // Assure-toi que 'type' est bien 'Manhwa'
            "`type` = 'Manhwa' AND popularity_rank IS NOT NULL",
            6
        );
        // Section Shounen - Utilise la colonne 'demographic' si elle est remplie, sinon adapte
        fetch_and_display_section(
            $conn,
            "Pour les Fans de Shounen",
            "CASE WHEN popularity_rank IS NULL THEN 1 ELSE 0 END, popularity_rank ASC, title ASC",
            // Si tu veux que le lien "Tout voir" filtre par démographie Shounen
            ['demographic' => 'Shounen', 'sort_by' => 'popularity_rank_asc'],
            // Clause WHERE pour la section
            "`demographic` = 'Shounen' AND popularity_rank IS NOT NULL",
            6
        );
        ?>
    </div>

    <div id="settings-modal" class="modal">
        <div class="modal-content">
            <h2>⚙️ Paramètres</h2>
            <label for="theme-selector">🎨 Thème :</label><br>
            <select id="theme-selector" onchange="changeTheme(this.value)">
                <option value="default">🌟 Moderne</option>
                <option value="dark">🌙 Dark Mode</option>
            </select>
            <br><br>
            <button type="button" onclick="closeSettings()" class="btn cancel" style="margin-top: 20px;">
                <i class="fas fa-times"></i> Fermer
            </button>
        </div>
    </div>
    <div id="loader" class="loader" style="display: none;"></div>
    <script src="assets/JS/main.js"></script>
</body>

</html>
<?php if (isset($conn)) $conn->close(); ?>
