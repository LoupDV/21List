<?php
// =================================
// Connexion et notifications
// =================================
include 'config.php';
include 'notif.php';

// =================================
// Récupérer les IDs des mangas déjà dans la liste de l'utilisateur actuel
// =================================
$mangasDansMaListeCatalogueIds = [];
$user_id_actuel = 1; // IMPORTANT: Remplacez ceci par l'ID de l'utilisateur connecté (ex: $_SESSION['user_id'])

if (isset($conn) && $conn) { // S'assurer que la connexion existe
    $stmt_ids = $conn->prepare("SELECT catalogue_id FROM mangas WHERE user_id = ?");
    if ($stmt_ids) {
        $stmt_ids->bind_param("i", $user_id_actuel);
        $stmt_ids->execute();
        $result_ids = $stmt_ids->get_result();
        while ($row_id = $result_ids->fetch_assoc()) {
            $mangasDansMaListeCatalogueIds[] = $row_id['catalogue_id'];
        }
        $stmt_ids->close();
    } else {
        // Gérer l'erreur de préparation si nécessaire, ou juste laisser le tableau vide
        // echo "Erreur préparation requête IDs: " . $conn->error;
    }
}

// =================================
// Pagination
// =================================
$elementsParPage = 30; // Combien d'éléments par page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $elementsParPage;

// =================================
// Recherche et filtre Type
// =================================

$type_filter = isset($_GET['type']) ? trim($_GET['type']) : ''; // Tu l'as déjà
$demographic_filter = isset($_GET['demographic']) ? trim($_GET['demographic']) : '';
$status_publication_filter = isset($_GET['status_publication']) ? trim($_GET['status_publication']) : '';
$genre_search_filter = isset($_GET['genre_search']) ? trim($_GET['genre_search']) : '';
// N'oublie pas le $search pour la recherche globale
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';

// =================================
// Construction de la requête SQL
// =================================
$query = "SELECT * FROM catalogue"; // C'est la table principale ici
$conditions = [];
$params = [];
$types = "";

// Si filtre par type
if ($type !== '') {
    $conditions[] = "type = ?";
    $params[] = $type;
    $types .= "s";
}

// Si recherche
if ($search !== '') {
    $conditions[] = "(title LIKE ? OR author LIKE ? OR genre LIKE ?)"; // Assurez-vous que ces colonnes existent dans 'catalogue'
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

if ($demographic_filter !== '') {
    $conditions[] = "demographic = ?"; // Assure-toi que ta colonne s'appelle bien 'demographic'
    $params[] = $demographic_filter;
    $types .= "s";
}
if ($status_publication_filter !== '') {
    $conditions[] = "status_publication = ?";
    $params[] = $status_publication_filter;
    $types .= "s";
}
if ($genre_search_filter !== '') {
    $conditions[] = "genre LIKE ?";
    $params[] = "%" . $genre_search_filter . "%";
    $types .= "s";
}

// Si conditions, les assembler
if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

// Ajout de l'ordre (optionnel mais bien)
$query .= " ORDER BY CASE WHEN popularity_rank IS NULL THEN 1 ELSE 0 END, popularity_rank ASC, title ASC";

// Ajout du LIMIT/OFFSET pour pagination
$query_count = str_replace("SELECT *", "SELECT COUNT(*) as total", explode(" ORDER BY", $query)[0]); // Pour le total SANS limit/offset

$query .= " LIMIT ? OFFSET ?";
$params[] = $elementsParPage;
$params[] = $start;
$types .= "ii";

// =================================
// Préparation, binding et exécution pour les données
// =================================
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Erreur de préparation de la requête principale : " . $conn->error); // Message d'erreur clair
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result(); // C'est ici que vous récupérez les mangas du catalogue

// =================================
// Calcul du nombre total de mangas pour pagination (AVEC filtres)
// =================================
// On doit refaire une requête COUNT avec les mêmes conditions WHERE
$count_params_for_pagination = array_slice($params, 0, count($params) - 2); // Exclure LIMIT et OFFSET
$count_types_for_pagination = substr($types, 0, -2); // Exclure les 'ii' de LIMIT et OFFSET

$stmt_count = $conn->prepare($query_count);
if (!$stmt_count) {
    die("Erreur de préparation de la requête COUNT : " . $conn->error);
}
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
    <title>Catalogue de manga</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="no-transition">

    <?php include 'navbar.php'; ?>
    <h1>Catalogue de mangas</h1>
    <?php showNotification(); ?>

    <div id="noResults" style="display: none; text-align: center; font-weight: bold; margin-top: 20px; color: red;">
        Aucun résultat trouvé 😕
    </div>

    <div class="filter-container">
        <input type="text" id="searchInput" placeholder="Rechercher..." class="search-bar" value="<?= htmlspecialchars($search) ?>">

        <!-- Filtre spécifique à la page -->
        <!-- Dans catalogue.php, dans .filter-container -->
        <select id="demographicFilter" name="demographic" class="type-select">
            <option value="">Toutes les Démographies</option>
            <option value="Shounen" <?= ($demographic_filter == 'Shounen') ? 'selected' : '' ?>>Shounen</option>
            <option value="Seinen" <?= ($demographic_filter == 'Seinen') ? 'selected' : '' ?>>Seinen</option>
            <option value="Shojo" <?= ($demographic_filter == 'Shojo') ? 'selected' : '' ?>>Shojo</option>
            <option value="Josei" <?= ($demographic_filter == 'Josei') ? 'selected' : '' ?>>Josei</option>
            <!-- Ajoute "Kids" si pertinent -->
        </select>

        <select id="statusPublicationFilter" name="status_publication" class="type-select">
            <option value="">Tous les Statuts</option>
            <option value="Publishing" <?= ($status_publication_filter == 'Publishing') ? 'selected' : '' ?>>En cours</option>
            <option value="Finished" <?= ($status_publication_filter == 'Finished') ? 'selected' : '' ?>>Terminé</option>
            <option value="On Hiatus" <?= ($status_publication_filter == 'On Hiatus') ? 'selected' : '' ?>>En pause</option>
            <option value="Not yet published" <?= ($status_publication_filter == 'Not yet published') ? 'selected' : '' ?>>Pas encore publié</option>
        </select>

        <!-- AJOUT DU BOUTON RECHERCHER -->
        <button type="button" id="searchButton" class="btn confirm" aria-label="Lancer la recherche">
            <i class="fas fa-search"></i>
        </button>
        <!-- FIN DE L'AJOUT -->

    </div>

    <div class="grid-container">
        <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                <div class="card-wrapper">
                    <a href="detail_manga.php?id=<?= $row['id'] ?>&from=catalogue" class="card-link">
                        <div class="card fade-in">
                            <img src="<?= htmlspecialchars($row['cover_image']) ?>" alt="Cover de <?= htmlspecialchars($row['title']) ?>" class="cover-image" onerror="this.onerror=null;this.src='covers/default_cover.jpg';">
                            <?php if (in_array($row['id'], $mangasDansMaListeCatalogueIds)): ?>
                                <span class="in-list-indicator" title="Dans votre liste"><i class="fas fa-check-circle"></i></span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="card-title-below">
                         <a href="detail_manga.php?id=<?= $row['id'] ?>&from=catalogue"><?= htmlspecialchars($row['title']) ?></a>
                    </div>

                    <?php // On peut garder quelques infos minimes ici si on veut, ou tout mettre dans le panel ?>
                    <div class="card-extra-info" style="display: none;"> <?php /* Caché pour l'instant, on verra si on en a besoin */ ?>
                        <?php if (!empty($row['status_publication'])): ?>
                            <p><strong>Statut :</strong> <?= htmlspecialchars($row['status_publication']) ?></p>
                        <?php endif; ?>
                    </div>

                    <?php // NOUVEAU: PANNEAU D'INFORMATION LATÉRAL ?>
                    <div class="card-info-panel">
                        <div class="panel-arrow"></div> <?php // Flèche pour pointer vers la carte ?>
                        <div class="panel-content">
                            <?php if (!empty($row['start_date'])): // Exemple: date de prochain épisode ou statut ?>
                                <?php
                                // Logique simple pour "prochain épisode" (à améliorer grandement)
                                $startDate = new DateTime($row['start_date']);
                                $now = new DateTime();
                                if ($row['status_publication'] === 'En cours' && $startDate > $now) {
                                    $diff = $now->diff($startDate);
                                    echo '<p class="panel-next-ep">Prochain ep dans ' . $diff->days . ' jours</p>';
                                } elseif ($row['status_publication'] === 'Terminé') {
                                    echo '<p class="panel-status">Terminé</p>';
                                }
                                ?>
                            <?php endif; ?>
                            <?php if (!empty($row['average_rating']) && $row['average_rating'] > 0): ?>
                                <span class="panel-score">
                                    <i class="fas fa-smile" style="color: lightgreen;"></i> <?= htmlspecialchars(round($row['average_rating'] / 10 * 100)) ?>%
                                </span>
                            <?php endif; ?>

                            <h4 class="panel-title"><?= htmlspecialchars($row['title_english'] ?? $row['title']) ?></h4>
                            
                            <div class="panel-meta">
                                <?php if (!empty($row['studio'])): ?>
                                    <span><?= htmlspecialchars($row['studio']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($row['type'])): ?>
                                    <span><?= htmlspecialchars($row['type']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($row['num_episodes']) && $row['num_episodes'] > 0): ?>
                                    <span><?= htmlspecialchars($row['num_episodes']) ?> épisodes</span>
                                <?php elseif (!empty($row['num_volumes']) && $row['num_volumes'] > 0): ?>
                                    <span><?= htmlspecialchars($row['num_volumes']) ?> volumes</span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($row['genre'])):
                                $genres = explode(',', $row['genre']); // Sépare les genres s'ils sont stockés comme "Action,Comédie"
                            ?>
                                <div class="panel-genres">
                                    <?php foreach ($genres as $genre_item): ?>
                                        <span class="genre-tag"><?= htmlspecialchars(trim($genre_item)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php /* Synopsis peut être trop long pour ce type de panel, à voir.
                                     Si tu le veux, garde la div overlay-synopsis et adapte son style. */
                            ?>
                            <?php /*
                            <?php if (!empty($row['synopsis'])): ?>
                                <div class="overlay-synopsis panel-synopsis">
                                    <p><?= nl2br(htmlspecialchars(substr($row['synopsis'], 0, 100))) . (strlen($row['synopsis']) > 100 ? '...' : '') ?></p>
                                </div>
                            <?php endif; ?>
                            */ ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <?php if (empty($search) && empty($type)): // S'affiche seulement s'il n'y a aucun manga du tout et pas de filtre ?>
                <p style="grid-column: 1 / -1; text-align: center;">Aucun manga dans le catalogue pour le moment.</p>
            <?php endif; // Le message "Aucun résultat trouvé" sera géré par JS pour les filtres actifs ?>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php
        // Construction des liens de pagination en conservant les filtres actuels
        $queryParams = [];
        if (!empty($search)) $queryParams['search'] = $search;
        if (!empty($type)) $queryParams['type'] = $type;

        for ($i = 1; $i <= $totalPages; $i++):
            $pageParams = $queryParams; // Copie pour ne pas modifier $queryParams
            $pageParams['page'] = $i;
            $queryString = http_build_query($pageParams);
        ?>
            <a href="?<?= $queryString ?>" class="<?= ($i == $page) ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <!-- =========================
    Modale Paramètres (Thème)
    ========================= -->
    <div id="settings-modal" class="modal"> <!-- Utilisez la classe .modal pour les styles de base -->
        <div class="modal-content"> <!-- Un seul .modal-content ici -->
            <h2>⚙️ Paramètres</h2>

            <label for="theme-selector">🎨 Thème :</label><br> <!-- Ajout de for pour l'accessibilité -->
            <select id="theme-selector" onchange="changeTheme(this.value)">
                <option value="default">🌟 Moderne</option>
                <option value="dark">🌙 Dark Mode</option>
                <!-- Ajoutez d'autres thèmes si vous en avez -->
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