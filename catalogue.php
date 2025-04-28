<?php
// =================================
// Connexion et notifications
// =================================
include 'config.php';
include 'notif.php';

// =================================
// Pagination
// =================================
$elementsParPage = 30; // Combien d'éléments par page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $elementsParPage;

// =================================
// Recherche et filtre Type
// =================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';

// =================================
// Construction de la requête SQL
// =================================
$query = "SELECT * FROM catalogue";
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
    $conditions[] = "(title LIKE ? OR author LIKE ? OR genre LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

// Si conditions, les assembler
if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

// Ajout du LIMIT/OFFSET pour pagination
$query .= " LIMIT ? OFFSET ?";
$params[] = $elementsParPage;
$params[] = $start;
$types .= "ii";

// =================================
// Préparation, binding et exécution
// =================================
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// =================================
// Récupérer les titres de mangas déjà ajoutés
// =================================
$myMangas = [];
$resultMyMangas = $conn->query("SELECT title FROM mangas");
while ($row = $resultMyMangas->fetch_assoc()) {
    $myMangas[] = $row['title'];
}

// =================================
// Calcul du nombre total de mangas pour pagination
// =================================
$countQuery = "SELECT COUNT(*) as total FROM catalogue";
$countResult = $conn->query($countQuery);
$countRow = $countResult->fetch_assoc();
$totalElements = $countRow['total'];
$totalPages = ceil($totalElements / $elementsParPage);
?>


<!DOCTYPE html>
<html lang="fr" class="no-transition">
    <head>
        <meta charset="UTF-8">
        <title>Catalogue de manga</title>

        <!-- Lien vers la feuille de style principale -->
        <link rel="stylesheet" href="assets/css/style.css">

    </head>

    <body>

        <!-- Barre de navigation -->
        <?php include 'navbar.php'; ?>

        <!-- Titre principal -->
        <h1>Catalogue de mangas</h1>

        <!-- Affichage des notifications -->
        <?php showNotification(); ?>

        <!-- Message si aucun résultat trouvé -->
        <div id="noResults" style="display: none; text-align: center; font-weight: bold; margin-top: 20px; color: red;">
            Aucun résultat trouvé 😕
        </div>

        <!-- Barre de recherche et filtre par type -->
        <div class="filter-container">
            <input type="text" id="searchInput" placeholder="🔎 Titre, Auteur, Genre..." class="search-bar">
            
            <select id="typeFilter" class="type-select">
                <option value="">Tous les types</option>
                <option value="Shonen">Shonen</option>
                <option value="Seinen">Seinen</option>
                <option value="Shojo">Shojo</option>
            </select>
        </div>

        <!-- (Duplicate du "noResults", à retirer éventuellement) -->
        <div id="noResults" style="display: none; text-align: center; font-weight: bold; margin-top: 20px; color: red;">
            Aucun résultat trouvé 😕
        </div>

        <!-- Si résultat disponible -->
        <?php if ($result->num_rows > 0): ?>
            <!-- Ancienne structure en tableau, à remplacer par cards -->
            <table>...</table>
        <?php else: ?>
            <p style="text-align: center; font-weight: bold; margin-top: 20px;">Aucun manga trouvé 😕</p>
        <?php endif; ?>

        <!-- Grille d'affichage du catalogue -->
        <div class="grid-container">
            <?php while($row = $result->fetch_assoc()): ?>
                <?php if (!in_array($row['title'], $myMangas)): ?> <!-- Si manga pas déjà ajouté -->
                    
                    <!-- Carte d'un manga du catalogue -->
                    <div class="card fade-in" 
                        onclick="openAddModal(this)"
                        data-title="<?= htmlspecialchars($row['title']) ?>"
                        data-author="<?= htmlspecialchars($row['author']) ?>"
                        data-genre="<?= htmlspecialchars($row['genre']) ?>"
                        data-type="<?= htmlspecialchars($row['type']) ?>"
                        data-cover="<?= htmlspecialchars($row['cover_image']) ?>"
                    >
                        <!-- Image de couverture -->
                        <img src="<?= htmlspecialchars($row['cover_image']) ?>" alt="Cover" class="cover-image" onerror="this.onerror=null;this.src='covers/default_cover.jpg';">

                        <!-- Contenu texte -->
                        <div class="card-content">
                            <h3><?= htmlspecialchars($row['title']) ?></h3>
                            <p><strong>Auteur :</strong> <?= htmlspecialchars($row['author']) ?></p>
                            <p><strong>Genre :</strong> <?= htmlspecialchars($row['genre']) ?></p>
                            <p><strong>Type :</strong> <?= htmlspecialchars($row['type']) ?></p>
                        </div>
                    </div>

                <?php endif; ?>
            <?php endwhile; ?>
        </div>

        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>


        <!-- =========================
        Modale Ajout Manga (AddModal)
        ========================= -->
        <div id="addModal" style="display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.8); justify-content: center; align-items: center; z-index: 1000;">
            <div class="modal-content" style="background: var(--modal-bg-color); padding: 20px; border-radius: 10px; width: 300px; text-align: center;">

                <h3 id="add-modal-title">Ajouter Manga</h3>

                <!-- Formulaire d'ajout de manga personnalisé -->
                <form method="POST" action="save_personalized.php">
                    <input type="hidden" name="title" id="add-title">
                    <input type="hidden" name="author" id="add-author">
                    <input type="hidden" name="genre" id="add-genre">
                    <input type="hidden" name="cover_image" id="add-cover">

                    <!-- Sélection du statut -->
                    <label for="status">Statut :</label><br>
                    <select name="status" required>
                        <option value="À lire">À lire</option>
                        <option value="En cours">En cours</option>
                        <option value="Fini">Fini</option>
                    </select><br><br>

                    <!-- Saisie des chapitres lus -->
                    <label for="chapters_read">Chapitres lus :</label><br>
                    <input type="number" name="chapters_read" min="0" value="0" required><br><br>

                    <!-- Note et commentaire -->
                    <label for="note">Note :</label><br>
                    <input type="number" name="note" min="0" max="5" step="0.5"><br><br>

                    <label for="commentaire">Commentaire :</label><br>
                    <textarea name="commentaire" rows="3" style="width: 100%;"></textarea><br><br>

                    <button type="submit">✅ Ajouter</button>
                    <button type="button" onclick="closeAddModal()" style="background-color: #f44336; color: white;">❌ Annuler</button>
                </form>

            </div>
        </div>

        <!-- =========================
            Modale Paramètres (Theme)
            ========================= -->
        <div id="settings-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); justify-content: center; align-items: center; z-index: 1000;">
            <div class="modal-content" style="background: var(--modal-bg-color); padding: 20px; border-radius: 10px; width: 300px; text-align: center;">

                <h2>⚙️ Paramètres</h2>

                <!-- Sélecteur de thème -->
                <label>🎨 Thème :</label><br>
                <select id="theme-selector" onchange="changeTheme(this.value)">
                    <option value="default">🌟 Moderne</option>
                    <option value="dark">🌙 Dark Mode</option>
                </select>

                <br><br>

                <button onclick="closeSettings()" style="margin-top: 20px; background-color: #f44336; color: white; border: none; padding: 10px 20px; border-radius: 5px;">Fermer</button>
            </div>
        </div>

        <div id="loader" class="loader" style="display: none;"></div>

        <script src="assets/js/main.js"></script>

    </body>
</html>