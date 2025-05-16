<?php
// =================================
// Connexion et notifications
// =================================
ini_set('display_errors', 1); // À garder pour le dev
error_reporting(E_ALL);     // À garder pour le dev
include 'config.php';
include 'notif.php';
session_start(); // Assurez-vous que session_start() est appelé si notif.php ou la gestion user_id l'utilise

$user_id_actuel = 1; // IMPORTANT: Remplacez ceci par l'ID de l'utilisateur connecté (ex: $_SESSION['user_id'])

// =================================
// Pagination
// =================================
$elementsParPage = 30;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $elementsParPage;

// =================================
// Filtres : Recherche, Statut (au lieu de favoris direct ici)
// =================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : ''; // Nouveau filtre par statut

// =================================
// Construction de la requête SQL
// =================================
// On sélectionne les infos de la table 'mangas' (m) et on joint avec 'catalogue' (c)
$query = "SELECT m.id as manga_personnel_id, m.status, m.chapters_read, m.note, m.commentaire, m.favori,
                 c.id as catalogue_id, c.title, c.author, c.genre, c.type, c.cover_image
          FROM mangas m
          JOIN catalogue c ON m.catalogue_id = c.id
          WHERE m.user_id = ?"; // Toujours filtrer par l'utilisateur actuel

$conditions_sql_parts = []; // Pour les conditions supplémentaires après le WHERE user_id
$params = [$user_id_actuel]; // Le premier paramètre est user_id
$types = "i"; // Type pour user_id

// Ajout des conditions de filtre
if ($status_filter !== '') {
    $conditions_sql_parts[] = "m.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}
if ($search !== '') {
    // La recherche se fait sur les colonnes de la table 'catalogue'
    $conditions_sql_parts[] = "(c.title LIKE ? OR c.author LIKE ? OR c.genre LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

// S'il y a des conditions supplémentaires, les ajouter
if (!empty($conditions_sql_parts)) {
    $query .= " AND " . implode(" AND ", $conditions_sql_parts);
}

// Ajout de l'ordre
$query .= " ORDER BY c.title ASC"; // Ou m.date_updated DESC, etc.

// Pour le comptage total (AVEC filtres)
$query_count_base = "SELECT COUNT(*) as total
                     FROM mangas m
                     JOIN catalogue c ON m.catalogue_id = c.id
                     WHERE m.user_id = ?"; // Base pour le count

if (!empty($conditions_sql_parts)) {
    $query_count_base .= " AND " . implode(" AND ", $conditions_sql_parts);
}

// Ajout du LIMIT/OFFSET pour la pagination des résultats affichés
$query .= " LIMIT ? OFFSET ?";
$params[] = $elementsParPage;
$params[] = $start;
$types .= "ii";

// =================================
// Préparation, binding et exécution pour les données affichées
// =================================
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Erreur de préparation de la requête principale : " . $conn->error);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// =================================
// Calcul du nombre total de mangas pour pagination (AVEC filtres)
// =================================
// Les paramètres pour le count sont les mêmes que pour la requête principale, SAUF LIMIT et OFFSET
$count_params_for_pagination = array_slice($params, 0, count($params) - 2);
$count_types_for_pagination = substr($types, 0, strlen($types) - 2);

$stmt_count = $conn->prepare($query_count_base);
if (!$stmt_count) {
    die("Erreur de préparation de la requête COUNT : " . $conn->error);
}
// S'il n'y a que user_id comme paramètre pour le count (pas de search ni status_filter)
if (count($count_params_for_pagination) == 1 && $count_params_for_pagination[0] == $user_id_actuel) {
     $stmt_count->bind_param($count_types_for_pagination, $user_id_actuel);
} elseif (!empty($count_params_for_pagination)) {
    $stmt_count->bind_param($count_types_for_pagination, ...$count_params_for_pagination);
}
// Note: si $count_params_for_pagination est vide (ce qui ne devrait pas arriver car user_id est toujours là)
// il ne faut pas appeler bind_param. Mais ici, il y aura toujours au moins user_id.

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
    <title>21List - Ma liste de mangas</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="no-transition">

    <?php include 'navbar.php'; ?>
    <h1>Ma Liste de Mangas</h1> <!-- Titre modifié pour clarté -->
    <?php showNotification(); ?>

    <div class="filter-container">
        <input type="text" id="searchInput" placeholder="Rechercher dans ma liste..." class="search-bar" value="<?= htmlspecialchars($search) ?>">
        <select id="statusFilter" name="status_filter" class="type-select"> <!-- Ajout de name="status_filter" pour soumission serveur si besoin -->
            <option value="">Tous les statuts</option>
            <option value="À lire" <?= ($status_filter == 'À lire') ? 'selected' : '' ?>>À lire</option>
            <option value="En cours" <?= ($status_filter == 'En cours') ? 'selected' : '' ?>>En cours</option>
            <option value="Fini" <?= ($status_filter == 'Fini') ? 'selected' : '' ?>>Fini</option>
            <option value="En pause" <?= ($status_filter == 'En pause') ? 'selected' : '' ?>>En pause</option>
            <option value="Abandonné" <?= ($status_filter == 'Abandonné') ? 'selected' : '' ?>>Abandonné</option>
        </select>
    </div>

    <div id="noResults" style="display: none; /* Géré par JS */">
        Aucun manga correspondant à vos critères dans votre liste.
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="catalogue.php" class="btn go-catalogue">
            <i class="fas fa-plus"></i> Ajouter depuis le catalogue
        </a>
    </div>

    <div class="grid-container">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="card-wrapper">
                <!-- Lien vers la page de détail pour voir/modifier -->
                <a href="detail_manga.php?id=<?= $row['catalogue_id'] ?>&from=index" class="card-link">
                    <div class="card fade-in"
                         data-title="<?= htmlspecialchars($row['title']) ?>"
                         data-author="<?= htmlspecialchars($row['author']) ?>"
                         data-genre="<?= htmlspecialchars($row['genre']) ?>"
                         data-status="<?= htmlspecialchars($row['status']) ?>"
                         data-type="<?= htmlspecialchars($row['type']) ?>"
                         data-chapters-read="<?= htmlspecialchars($row['chapters_read']) ?>"
                         data-note="<?= htmlspecialchars($row['note']) ?>"
                         >
                        <!-- L'icône favori ici nécessiterait un traitement AJAX pour être interactive sans recharger la page -->
                        <!-- Pour l'instant, l'édition du favori se fera sur la page de détail -->
                        <?php if ($row['favori']): ?>
                            <div class="favori-icon-display" title="Favori">
                                <i class="fas fa-star"></i>
                            </div>
                        <?php endif; ?>
                        <img src="<?= htmlspecialchars($row['cover_image']) ?>" alt="Couverture de <?= htmlspecialchars($row['title']) ?>" class="cover-image" onerror="this.onerror=null;this.src='covers/default_cover.jpg';">
                    </div>
                </a>
                <div class="card-title-below">
                    <a href="detail_manga.php?id=<?= $row['catalogue_id'] ?>&from=index"><?= htmlspecialchars($row['title']) ?></a>
                </div>
                <div class="card-info-perso" style="font-size: 0.9em; margin-top: 5px;">
                    <p><strong>Statut :</strong> <?= htmlspecialchars($row['status']) ?></p>
                    <?php if ($row['note']): ?>
                        <p class="stars">
                            <?php for($s = 1; $s <= 5; $s++): ?>
                                <i class="<?= ($s <= $row['note']) ? 'fas fa-star' : (($s - 0.5 <= $row['note']) ? 'fas fa-star-half-alt' : 'far fa-star') ?>"></i>
                            <?php endfor; ?> <!-- Assurez-vous que ce endfor; est bien présent -->
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
        <?php $stmt->close(); ?>
    <?php else: ?>
        <p style="grid-column: 1 / -1; text-align: center;">Vous n'avez aucun manga dans votre liste pour le moment.</p>
    <?php endif; ?>
    </div>

    <!-- La modale editModal est supprimée -->

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php
        $queryParams = [];
        if (!empty($search)) $queryParams['search'] = $search;
        if (!empty($status_filter)) $queryParams['status_filter'] = $status_filter; // Utiliser status_filter

        for ($i = 1; $i <= $totalPages; $i++):
            $pageParams = $queryParams;
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