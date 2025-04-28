<?php
// =================================
// Connexion et notifications
// =================================
include 'config.php';
include 'notif.php';

// =================================
// Pagination
// =================================
$elementsParPage = 30; // Combien de mangas par page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $elementsParPage;

// =================================
// Recherche et Favoris
// =================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$favoris = isset($_GET['favoris']) ? intval($_GET['favoris']) : 0;

// =================================
// Construction de la requête SQL
// =================================
$query = "SELECT * FROM mangas";
$conditions = [];
$params = [];
$types = "";

// Ajout des conditions
if ($favoris == 1) {
    $conditions[] = "favori = 1";
}
if ($search !== '') {
    $conditions[] = "(title LIKE ? OR author LIKE ? OR genre LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

// S'il y a des conditions, les ajouter
if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

// Ajout du LIMIT/OFFSET pour la pagination
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
// Calcul du nombre total de mangas pour pagination
// =================================
$countQuery = "SELECT COUNT(*) as total FROM mangas";
$countResult = $conn->query($countQuery);
$countRow = $countResult->fetch_assoc();
$totalElements = $countRow['total'];
$totalPages = ceil($totalElements / $elementsParPage);
?>

                
<!DOCTYPE html>
<html lang="fr" class="no-transition">
<head>
    <meta charset="UTF-8">
    <title>21List - Ma liste de mangas</title>

    <!-- Lien vers la feuille de style principale -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <!-- Barre de navigation globale -->
    <?php include 'navbar.php'; ?>

    <!-- Titre principal -->
    <h1>21List</h1>

    <!-- Affichage d'une éventuelle notification -->
    <?php showNotification(); ?>

    <!-- Barre de recherche et filtre par statut -->
    <div class="filter-container">
        <input type="text" id="searchInput" placeholder="🔎 Rechercher un manga..." class="search-bar">
        
        <select id="statusFilter" class="type-select">
            <option value="">Tous les statuts</option>
            <option value="À lire">À lire</option>
            <option value="En cours">En cours</option>
            <option value="Fini">Fini</option>
        </select>
    </div>

    <!-- Message si aucun résultat n'est trouvé -->
    <div id="noResults" style="display: none; text-align: center; font-weight: bold; margin-top: 20px; color: red;">
        Aucun résultat trouvé 😕
    </div>

    <!-- Bouton pour aller vers le catalogue et ajouter un manga -->
    <a href="catalogue.php" style="display: inline-block; margin-bottom: 20px; padding: 10px 20px; background-color:rgb(87, 102, 88); color: white; text-decoration: none; border-radius: 5px;">
        ➕ Ajouter un manga
    </a>

    <!-- Grille d'affichage des mangas -->
    <div class="grid-container">

    <?php while($row = $result->fetch_assoc()): ?>

        <!-- Carte individuelle de manga -->
        <div class="card fade-in" 
            
            data-id="<?= $row['id'] ?>"
            data-title="<?= htmlspecialchars($row['title']) ?>"
            data-author="<?= htmlspecialchars($row['author']) ?>"
            data-genre="<?= htmlspecialchars($row['genre']) ?>"
            data-status="<?= htmlspecialchars($row['status']) ?>"
            data-chapters="<?= $row['chapters_read'] ?>"
            data-note="<?= $row['note'] ?>"
            data-commentaire="<?= htmlspecialchars($row['commentaire']) ?>"
            onclick="openEditModal(this)"
        >

            <!-- ⭐ Icône Favori (cliquable sans ouvrir la modale) -->
            <div class="favori-icon" onclick="event.stopPropagation(); toggleFavori(<?= $row['id'] ?>, this)">
                <?php if ($row['favori']): ?>
                    <i class="fas fa-star"></i> <!-- Favori actif -->
                <?php else: ?>
                    <i class="far fa-star"></i> <!-- Favori inactif -->
                <?php endif; ?>
            </div>

            <!-- Image de couverture du manga -->
            <img src="<?= htmlspecialchars($row['cover_image']) ?>" alt="Cover" class="cover-image" onerror="this.onerror=null;this.src='covers/default_cover.jpg';">

            <!-- Contenu texte de la carte -->
            <div class="card-content">
                <h3><?= htmlspecialchars($row['title']) ?></h3>

                <p><strong>Statut :</strong> <?= htmlspecialchars($row['status']) ?></p>

                <!-- Si le manga a une note -->
                <?php if (!empty($row['note']) && $row['note'] > 0): ?>
                    <div class="stars">
                        <?php
                        // Calcul et affichage des étoiles en fonction de la note
                        $note = floatval($row['note']);
                        $fullStars = floor($note);
                        $halfStar = ($note - $fullStars) >= 0.5 ? 1 : 0;
                        $emptyStars = 5 - $fullStars - $halfStar;

                        for ($i = 0; $i < $fullStars; $i++) echo "★"; // Étoiles pleines
                        if ($halfStar) echo "⯨"; // Demi-étoile
                        for ($i = 0; $i < $emptyStars; $i++) echo "☆"; // Étoiles vides
                        ?>
                    </div>
                <?php else: ?>
                    <!-- Si pas de note -->
                    <div class="stars" style="color: gray; font-style: italic;">
                        Pas encore noté
                    </div>
                <?php endif; ?>
            </div>
            <!-- Fin du contenu de la carte -->

        </div>

    <?php endwhile; ?> <!-- Fin du while de toutes les cartes -->
    </div>


    <!-- =========================
     Modale d'Édition d'un Manga
     ========================= -->
    <div id="editModal" style="display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.8); justify-content: center; align-items: center; z-index: 1000;">
        <div class="modal-content" style="background: var(--modal-bg-color); padding: 20px; border-radius: 10px; width: 300px; text-align: center;">

            <h3 id="edit-modal-title">Modifier Manga</h3>

            <!-- 🖋️ Formulaire pour MODIFIER -->
            <form method="POST" action="update.php">
                <input type="hidden" name="id" id="edit-id">

                <label for="title">Titre :</label><br>
                <input type="text" name="title" id="edit-title" readonly><br><br>

                <label for="author">Auteur :</label><br>
                <input type="text" name="author" id="edit-author" readonly><br><br>

                <label for="genre">Genre :</label><br>
                <input type="text" name="genre" id="edit-genre" readonly><br><br>

                <label for="status">Statut :</label><br>
                <select name="status" id="edit-status" required>
                    <option value="À lire">À lire</option>
                    <option value="En cours">En cours</option>
                    <option value="Fini">Fini</option>
                </select><br><br>

                <label for="chapters_read">Chapitres lus :</label><br>
                <input type="number" name="chapters_read" id="edit-chapters-read" min="0" required><br><br>

                <label for="note">Note:</label><br>
                <input type="number" name="note" id="edit-note" min="0" max="5" step="0.5"><br><br>

                <label for="commentaire">Commentaire :</label><br>
                <textarea name="commentaire" id="edit-commentaire" rows="3" style="width: 100%;"></textarea><br><br>

                <button type="submit" style="background-color: var(--accent-color); color: white; border: none; padding: 8px 16px; border-radius: 5px;">✅ Enregistrer</button>
            </form>

            <br>

            <!-- 🗑️ Formulaire pour SUPPRIMER -->
            <form method="POST" action="delete.php">
                <input type="hidden" name="id" id="delete-id">
                <button type="submit" style="background-color: #f44336; color: white; border: none; padding: 8px 16px; border-radius: 5px;">🗑️ Supprimer</button>
            </form>

            <br>

            <!-- ❌ Bouton Fermer -->
            <button onclick="closeEditModal()" style="background-color: grey; color: white; border: none; padding: 8px 16px; border-radius: 5px;">❌ Fermer</button>

        </div>
    </div>

    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>



    <!-- =========================
    Modale Paramètres (Thème)
    ========================= -->
    <div id="settings-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); justify-content: center; align-items: center; z-index: 1000;">
        <<div class="modal-content" style="background: var(--modal-bg-color); padding: 20px; border-radius: 10px; width: 300px; text-align: center;">
            <h2>⚙️ Paramètres</h2>

            <!-- Sélecteur de thème -->
            <label>🎨 Thème :</label><br>
            <select id="theme-selector" onchange="changeTheme(this.value)">
                <option value="default">🌟 Moderne</option>
                <option value="dark">🌙 Dark Mode</option>
            </select>

            <br><br>

            <!-- Bouton pour fermer la modale -->
            <button onclick="closeSettings()" style="margin-top: 20px; background-color: #f44336; color: white; border: none; padding: 10px 20px; border-radius: 5px;">Fermer</button>
        </div>
    </div>

    <div id="loader" class="loader" style="display: none;"></div>

    <!-- =========================
    Script - Gestion notifications
    ========================= -->
    
    <script src="assets/js/main.js"></script>




    </body>
</html>