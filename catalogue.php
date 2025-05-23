<?php
// =================================
// Connexion et notifications
// =================================
include 'config.php';
// include 'notif.php'; // Décommente si tu l'utilises activement

// =================================
// Récupérer les IDs des mangas déjà dans la liste de l'utilisateur actuel
// =================================
$mangasDansMaListeCatalogueIds = [];
if (isset($_SESSION['user_id'])) { // Utilise la session si disponible
    $user_id_actuel = $_SESSION['user_id'];

    if (isset($conn) && $conn) {
        $stmt_ids = $conn->prepare("SELECT catalogue_id FROM mangas WHERE user_id = ?");
        if ($stmt_ids) {
            $stmt_ids->bind_param("i", $user_id_actuel);
            $stmt_ids->execute();
            $result_ids = $stmt_ids->get_result();
            while ($row_id = $result_ids->fetch_assoc()) {
                $mangasDansMaListeCatalogueIds[] = $row_id['catalogue_id'];
            }
            $stmt_ids->close();
        }
    }
} else {
    // Gérer le cas où l'utilisateur n'est pas connecté, si nécessaire
    // Pour l'instant, $mangasDansMaListeCatalogueIds restera vide
}


// =================================
// Pagination
// =================================
$elementsParPage = 30;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $elementsParPage;

// =================================
// Récupération des filtres
// =================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? trim($_GET['type']) : ''; // Pour la colonne 'type'
$demographic_filter = isset($_GET['demographic']) ? trim($_GET['demographic']) : '';
$status_publication_filter = isset($_GET['status_publication']) ? trim($_GET['status_publication']) : '';
$genre_search_filter = isset($_GET['genre_search']) ? trim($_GET['genre_search']) : '';


// =================================
// Construction de la requête SQL
// =================================
// Spécifie les colonnes pour correspondre à ta structure de table et aux besoins du panel
$sql_columns = "id, mal_id, title, title_english, title_japanese, slug, author, artist, genre, demographic, cover_image, banner_image, trailer_url, average_rating, popularity_rank, members_count, jikan_score, type, synopsis, background_info, status_publication, start_date, end_date, num_volumes, num_chapters, num_episodes, season, studio, magazine_serialization";
$query = "SELECT $sql_columns FROM catalogue";

$conditions = [];
$params = [];
$types = "";

// Filtre par type (colonne 'type')
if ($type_filter !== '') {
    $conditions[] = "type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

// Filtre par recherche globale
if ($search !== '') {
    // Adapte les colonnes de recherche à celles qui ont du sens dans ta table catalogue
    $conditions[] = "(title LIKE ? OR title_english LIKE ? OR author LIKE ? OR genre LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

// Filtre par demographic
if ($demographic_filter !== '') {
    $conditions[] = "demographic = ?";
    $params[] = $demographic_filter;
    $types .= "s";
}

// Filtre par status_publication
if ($status_publication_filter !== '') {
    $conditions[] = "status_publication = ?";
    $params[] = $status_publication_filter;
    $types .= "s";
}

// Filtre par genre (recherche dans la colonne 'genre')
if ($genre_search_filter !== '') {
    $conditions[] = "genre LIKE ?"; // La colonne est 'genre' (singulier)
    $params[] = "%" . $genre_search_filter . "%";
    $types .= "s";
}


if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query_count = "SELECT COUNT(*) as total FROM catalogue" . (empty($conditions) ? "" : " WHERE " . implode(" AND ", $conditions));

$query .= " ORDER BY CASE WHEN popularity_rank IS NULL THEN 1 ELSE 0 END, popularity_rank ASC, title ASC";
$query .= " LIMIT ? OFFSET ?";
$params[] = $elementsParPage;
$params[] = $start;
$types .= "ii";

// =================================
// Exécution de la requête pour les données
// =================================
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Erreur de préparation de la requête principale : " . $conn->error);
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// =================================
// Exécution de la requête pour le total (pagination)
// =================================
$stmt_count = $conn->prepare($query_count);
if (!$stmt_count) {
    die("Erreur de préparation de la requête COUNT : " . $conn->error);
}
// Reconstruire les params pour la requête count (sans LIMIT/OFFSET)
$count_params_for_pagination = array_slice($params, 0, count($params) - 2);
$count_types_for_pagination = substr($types, 0, -2);
if (!empty($count_params_for_pagination)) {
    $stmt_count->bind_param($count_types_for_pagination, ...$count_params_for_pagination);
}
$stmt_count->execute();
$count_result_data = $stmt_count->get_result()->fetch_assoc();
$totalElements = $count_result_data['total'];
$totalPages = ceil($totalElements / $elementsParPage);
$stmt_count->close();

?>

<!DOCTYPE html>
<html lang="fr" class="no-transition">

<head>
    <meta charset="UTF-8">
    <title>Catalogue - 21List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="no-transition">

    <?php include 'navbar.php'; ?>
    <h1>Catalogue</h1>
    <?php // showNotification(); // Décommente si tu l'utilises
    ?>

    <div id="noResults" style="display: <?= ($result->num_rows === 0 && (!empty($search) || !empty($type_filter) || !empty($demographic_filter) || !empty($status_publication_filter) || !empty($genre_search_filter))) ? 'block' : 'none' ?>; text-align: center; font-weight: bold; margin-top: 20px; color: #555;">
        Aucun résultat trouvé pour votre recherche/filtre 😕
    </div>

    <div class="filter-container">
        <input type="text" id="searchInput" placeholder="Rechercher par titre, auteur, genre..." class="search-bar" value="<?= htmlspecialchars($search) ?>">

        <select id="typeFilter" name="type" class="type-select">
            <option value="">Tous les Types</option>
            <option value="Manga" <?= ($type_filter == 'Manga') ? 'selected' : '' ?>>Manga</option>
            <option value="Manhwa" <?= ($type_filter == 'Manhwa') ? 'selected' : '' ?>>Manhwa</option>
            <option value="Manhua" <?= ($type_filter == 'Manhua') ? 'selected' : '' ?>>Manhua</option>
            <option value="Anime" <?= ($type_filter == 'Anime') ? 'selected' : '' ?>>Anime</option>
            {/* Ajoute d'autres types si besoin */}
        </select>

        <select id="demographicFilter" name="demographic" class="type-select">
            <option value="">Toutes Démographies</option>
            <option value="Shounen" <?= ($demographic_filter == 'Shounen') ? 'selected' : '' ?>>Shounen</option>
            <option value="Seinen" <?= ($demographic_filter == 'Seinen') ? 'selected' : '' ?>>Seinen</option>
            <option value="Shojo" <?= ($demographic_filter == 'Shojo') ? 'selected' : '' ?>>Shojo</option>
            <option value="Josei" <?= ($demographic_filter == 'Josei') ? 'selected' : '' ?>>Josei</option>
            <option value="Kids" <?= ($demographic_filter == 'Kids') ? 'selected' : '' ?>>Enfants</option>
        </select>

        <select id="statusPublicationFilter" name="status_publication" class="type-select">
            <option value="">Tous Statuts Publication</option>
            <option value="Publishing" <?= ($status_publication_filter == 'Publishing') ? 'selected' : '' ?>>En cours</option>
            <option value="Finished" <?= ($status_publication_filter == 'Finished') ? 'selected' : '' ?>>Terminé</option>
            <option value="On Hiatus" <?= ($status_publication_filter == 'On Hiatus') ? 'selected' : '' ?>>En pause</option>
            <option value="Not yet published" <?= ($status_publication_filter == 'Not yet published') ? 'selected' : '' ?>>Pas encore publié</option>
            <option value="Cancelled" <?= ($status_publication_filter == 'Cancelled') ? 'selected' : '' ?>>Annulé</option>
        </select>

        <button type="button" id="searchButton" class="btn confirm" aria-label="Lancer la recherche">
            <i class="fas fa-search"></i>
        </button>
    </div>

    <div class="grid-container">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()):
                $card_id = isset($row['id']) ? $row['id'] : '#';
                $card_title = isset($row['title']) ? htmlspecialchars($row['title']) : 'Titre inconnu';
                $card_cover_image = isset($row['cover_image']) ? htmlspecialchars($row['cover_image']) : 'covers/default_cover.jpg';
            ?>
                <div class="card-wrapper">
                    <a href="detail_manga.php?id=<?= $card_id ?>&from=catalogue" class="card-link">
                        <div class="card fade-in">
                            <img src="<?= $card_cover_image ?>" alt="Cover de <?= $card_title ?>" class="cover-image" onerror="this.onerror=null;this.src='covers/default_cover.jpg';">
                            <?php if (in_array($row['id'], $mangasDansMaListeCatalogueIds)): ?>
                                <span class="in-list-indicator" title="Dans votre liste"><i class="fas fa-check-circle"></i></span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="card-title-below">
                        <a href="detail_manga.php?id=<?= $card_id ?>&from=catalogue"><?= $card_title ?></a>
                    </div>

                    <div class="card-info-panel">
                        <div class="panel-content">
                            <h3 class="panel-title"><?= isset($row['title_english']) && !empty($row['title_english']) ? htmlspecialchars($row['title_english']) : $card_title ?></h3>

                            <?php
                            $metaItemsPanel = [];
                            if (isset($row['type']) && !empty($row['type'])) {
                                $metaItemsPanel[] = htmlspecialchars(ucfirst($row['type']));
                            }
                            if (isset($row['start_date']) && !empty($row['start_date'])) {
                                $timestampPanel = strtotime($row['start_date']);
                                if ($timestampPanel !== false) {
                                    $yearPanel = date('Y', $timestampPanel);
                                    if ($yearPanel > 0) {
                                        $metaItemsPanel[] = $yearPanel;
                                    }
                                }
                            }
                            $count_indicator_panel = '';
                            if (isset($row['num_chapters']) && $row['num_chapters'] !== null && $row['num_chapters'] > 0) {
                                $count_indicator_panel = htmlspecialchars($row['num_chapters']) . " Chaps";
                            } elseif (isset($row['num_episodes']) && $row['num_episodes'] !== null && $row['num_episodes'] > 0) {
                                $count_indicator_panel = htmlspecialchars($row['num_episodes']) . " Eps";
                            }
                            if (!empty($count_indicator_panel)) {
                                $metaItemsPanel[] = $count_indicator_panel;
                            }
                            if (isset($row['status_publication']) && !empty($row['status_publication'])) {
                                $metaItemsPanel[] = htmlspecialchars($row['status_publication']);
                            }


                            if (!empty($metaItemsPanel)): ?>
                                <div class="panel-meta">
                                    <?= implode(' <span class="meta-separator">•</span> ', $metaItemsPanel) ?>
                                </div>
                            <?php endif; ?>

                            <?php
                            $genres_string_panel = isset($row['genre']) ? $row['genre'] : ''; // Utilise 'genre'
                            if (!empty($genres_string_panel)):
                                $genresArrayPanel = [];
                                if (is_string($genres_string_panel)) {
                                    $decodedGenresPanel = json_decode($genres_string_panel, true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedGenresPanel)) {
                                        $genresArrayPanel = $decodedGenresPanel;
                                    } else {
                                        $genresArrayPanel = array_map('trim', explode(',', $genres_string_panel));
                                    }
                                } elseif (is_array($genres_string_panel)) {
                                    $genresArrayPanel = $genres_string_panel;
                                }
                                $genresArrayPanel = array_filter(array_map('trim', $genresArrayPanel));
                            ?>
                                <?php if (!empty($genresArrayPanel)): ?>
                                    <div class="panel-genres">
                                        <?php foreach ($genresArrayPanel as $gPanel): ?>
                                            <span class="genre-tag"><?= htmlspecialchars($gPanel) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php
                            $scorePanel = isset($row['jikan_score']) ? $row['jikan_score'] : (isset($row['average_rating']) ? $row['average_rating'] : null); // Priorise jikan_score, puis average_rating
                            if ($scorePanel !== null && is_numeric($scorePanel) && (float)$scorePanel > 0): ?>
                                <div class="panel-score">
                                    <i class="fas fa-star"></i> <?= htmlspecialchars(number_format((float)$scorePanel, 1)) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($row['demographic']) && !empty($row['demographic'])): ?>
                                <p class="panel-demographic" style="font-size:0.8em; margin-top:5px; color: #aaa;">Démographie: <?= htmlspecialchars($row['demographic']) ?></p>
                            <?php endif; ?>

                            <?php /* Synopsis
                            $synopsisPanel = isset($row['synopsis']) ? $row['synopsis'] : '';
                            if (!empty($synopsisPanel)): ?>
                            <div class="panel-synopsis">
                                <p><?= htmlspecialchars(mb_substr($synopsisPanel, 0, 100)) . (mb_strlen($synopsisPanel) > 100 ? '...' : '') ?></p>
                            </div>
                            <?php endif; */ ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php elseif (empty($search) && empty($type_filter) && empty($demographic_filter) && empty($status_publication_filter) && empty($genre_search_filter)): ?>
            <p style="grid-column: 1 / -1; text-align: center;">Aucun manga dans le catalogue pour le moment.</p>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $page_query_params = [];
            if (!empty($search)) $page_query_params['search'] = $search;
            if (!empty($type_filter)) $page_query_params['type'] = $type_filter;
            if (!empty($demographic_filter)) $page_query_params['demographic'] = $demographic_filter;
            if (!empty($status_publication_filter)) $page_query_params['status_publication'] = $status_publication_filter;
            if (!empty($genre_search_filter)) $page_query_params['genre_search'] = $genre_search_filter;

            for ($i = 1; $i <= $totalPages; $i++):
                $current_page_params = $page_query_params;
                $current_page_params['page'] = $i;
                $queryString = http_build_query($current_page_params);
            ?>
                <a href="?<?= $queryString ?>" class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

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
