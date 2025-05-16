<?php
// home.php
ini_set('display_errors', 1); // Utile pour le développement
error_reporting(E_ALL);     // Utile pour le développement

include 'config.php'; // Pour la connexion $conn
// session_start(); // Décommente si tu commences à utiliser les sessions (pour les messages, ou l'état de connexion)
// include 'notif.php'; // Si tu as un système de notifications

// IDs des mangas dans la liste de l'utilisateur (à peupler si l'utilisateur est connecté et si tu as cette fonctionnalité)
// Pour l'instant, on peut le laisser vide ou le commenter
// $mangasDansMaListeCatalogueIds = [];
// if (isset($_SESSION['user_id'])) {
//     // Logique pour récupérer les IDs des mangas de l'utilisateur
// }

// Fonction pour afficher une carte de manga (réutilisable)
function display_manga_card_for_home($manga_data) {
    ?>
    <div class="swiper-slide"> <?php // <-- CHAQUE CARTE EST UN SLIDE ?>
        <div class="card-wrapper">
            <a href="detail_manga.php?id=<?= $manga_data['id'] ?>" class="card-link">
                <div class="card fade-in">
                    <img src="<?= htmlspecialchars($manga_data['cover_image'] ?? 'covers/default_cover.jpg') ?>" 
                         alt="Cover de <?= htmlspecialchars($manga_data['title']) ?>" 
                         class="cover-image" 
                         onerror="this.onerror=null;this.src='covers/default_cover.jpg';">
    
                    <?php /* Panneau au survol optionnel ici */ ?>
                </div>
            </a>
            <div class="card-title-below">
                 <a href="detail_manga.php?id=<?= $manga_data['id'] ?>"><?= htmlspecialchars($manga_data['title']) ?></a>
            </div>
        </div>
    </div> <?php // Fin swiper-slide ?>
    <?php
}

// Fonction pour récupérer et afficher une section
function fetch_and_display_section($conn, $section_title, $link_params = [], $where_clause = "", $order_by_clause, $limit = 12 /* Augmente la limite pour avoir plus de slides */) {
    // Génère un ID unique pour chaque carrousel pour pouvoir les initialiser séparément
    $carousel_id = 'carousel-' . strtolower(str_replace(' ', '-', preg_replace("/[^A-Za-z0-9\s-]/", '', $section_title)));

    echo '<section class="home-section">';
    echo '  <div class="section-header">';
    echo '      <h2>' . htmlspecialchars($section_title) . '</h2>';
    $see_all_query_string = http_build_query($link_params);
    echo '      <a href="catalogue.php?' . $see_all_query_string . '" class="see-all-link">Tout voir »</a>';
    echo '  </div>';

    // Structure Swiper
    echo '  <div class="swiper-container" id="' . $carousel_id . '">'; // Conteneur principal avec ID unique
    echo '      <div class="swiper-wrapper">'; // Wrapper pour les slides

    $sql = "SELECT * FROM catalogue";
    if (!empty($where_clause)) {
        $sql .= " WHERE " . $where_clause;
    }
    $sql .= " ORDER BY " . $order_by_clause . " LIMIT " . intval($limit);
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            display_manga_card_for_home($row); // Qui génère maintenant un <div class="swiper-slide">...</div>
        }
    } else {
        echo '<p class="no-results-section">Aucun titre à afficher dans cette section pour le moment.</p>';
    }
    echo '      </div>'; // Fin swiper-wrapper
    // Ajout des boutons de navigation et de la pagination Swiper (optionnel mais recommandé)
    echo '      <div class="swiper-button-next"></div>';
    echo '      <div class="swiper-button-prev"></div>';
    // echo '      <div class="swiper-pagination"></div>'; // Si tu veux des points de pagination
    echo '  </div>'; // Fin swiper-container
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
        <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    </head> 

    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>

    <body class="no-transition">

        <?php include 'navbar.php'; ?>
        
        <div class="home-container">
            <div class="welcome-header">
                <h1 style="font-size: 2.5rem; margin-bottom: 10px;">Bienvenue sur <strong>21List</strong></h1>
                <p style="font-size: 1.2rem; color: #555;">Gérez vos lectures et découvrez de nouveaux titres.</p>
            </div>

            <!-- Les boutons peuvent rester ici ou être déplacés dans la navbar si plus pertinent -->
            <div class="home-buttons" style="display: flex; justify-content: center; gap: 20px; margin-bottom: 50px;">
                <a href="catalogue.php" class="btn go-catalogue">
                    <i class="fas fa-book-open"></i> Explorer le Catalogue Complet
                </a>
                <a href="index.php" class="btn go-liste"> <!-- Ce lien mènera à "Ma Liste" (index.php) -->
                    <i class="fas fa-list"></i> Ma Liste Personnelle
                </a>
            </div>

            <?php
            // --- AFFICHAGE DES SECTIONS ---

            // Section: Top Populaire (basé sur rank MAL, stocké dans popularity_rank)
            fetch_and_display_section(
                $conn, 
                "Les Plus Populaires",
                ['sort_by' => 'popularity_rank_asc'], // Paramètres pour le lien "Tout voir"
                "popularity_rank IS NOT NULL", 
                "CASE WHEN popularity_rank IS NULL THEN 1 ELSE 0 END, popularity_rank ASC, title ASC", 
                6 // Nombre de mangas à afficher
                /*, $mangasDansMaListeCatalogueIds */
            );

            // Section: Nouveautés Récentes (basé sur la date de début de publication)
            fetch_and_display_section(
                $conn, 
                "Nouveautés Récentes",
                ['sort_by' => 'start_date_desc'],
                "start_date IS NOT NULL",
                "start_date DESC, title ASC",
                6
                /*, $mangasDansMaListeCatalogueIds */
            );

            // Section: Top Mangas (Format)
            fetch_and_display_section(
                $conn, 
                "Notre Sélection de Mangas",
                ['type' => 'Manga', 'sort_by' => 'popularity_rank_asc'], // 'type' ici correspond à la colonne format
                "`type` = 'Manga' AND popularity_rank IS NOT NULL", 
                "CASE WHEN popularity_rank IS NULL THEN 1 ELSE 0 END, popularity_rank ASC, title ASC",
                6
                /*, $mangasDansMaListeCatalogueIds */
            );

            // Section: Top Manhwas (Format)
            fetch_and_display_section(
                $conn, 
                "À Découvrir : Manhwas",
                ['type' => 'Manhwa', 'sort_by' => 'popularity_rank_asc'],
                "`type` = 'Manhwa' AND popularity_rank IS NOT NULL",
                "CASE WHEN popularity_rank IS NULL THEN 1 ELSE 0 END, popularity_rank ASC, title ASC",
                6
                /*, $mangasDansMaListeCatalogueIds */
            );

            // Section: Top Shounen (Démographie)
            // Assure-toi d'avoir une colonne 'demographic' remplie avec "Shounen", "Seinen", etc.
            if (true) { // Mettre une condition si tu veux afficher cette section ou pas
                fetch_and_display_section(
                    $conn,
                    "Pour les Fans de Shounen",
                    ['demographic' => 'Shounen', 'sort_by' => 'popularity_rank_asc'],
                    "demographic = 'Shounen' AND popularity_rank IS NOT NULL",
                    "CASE WHEN popularity_rank IS NULL THEN 1 ELSE 0 END, popularity_rank ASC, title ASC",
                    6
                    /*, $mangasDansMaListeCatalogueIds */
                );
            }
            
            // Tu peux ajouter d'autres sections ici (Seinen, Shojo, par genre spécifique...)

            ?>
        </div>

        <!-- Modale pour les paramètres (peut rester si tu laisses le bouton dans la navbar) -->
        <div id="settings-modal" class="modal"> <!-- Utilise la classe .modal pour les styles de base -->
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
        <!-- Dans home.php, juste avant </body> -->
        <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
        <script src="assets/JS/main.js"></script> <?php // Ton script principal qui contient déjà la logique du panel au survol ?>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Initialise chaque carrousel Swiper trouvé sur la page
            const carousels = document.querySelectorAll('.home-section .swiper-container'); // Cible plus spécifiquement
            carousels.forEach(carouselElement => {
                const carouselId = '#' + carouselElement.id; // Récupère l'ID du conteneur
                new Swiper(carouselId, {
                    // Options de SwiperJS
                    slidesPerView: 2, // Nombre de slides visibles sur mobile par défaut
                    spaceBetween: 15,
                    // loop: true, // Optionnel, peut nécessiter plus de slides que slidesPerView * 2
                    
                    navigation: { // Active les flèches
                        nextEl: carouselId + ' .swiper-button-next',
                        prevEl: carouselId + ' .swiper-button-prev',
                    },
                    // pagination: { // Si tu veux la pagination par points
                    //    el: carouselId + ' .swiper-pagination',
                    //    clickable: true,
                    // },
                    breakpoints: {
                        // quand la largeur de la fenêtre est >= 550px (Mobile Moyen/Large)
                        550: { // Ajusté pour être avant 640px si tu as ce breakpoint ailleurs
                            slidesPerView: 3,
                            spaceBetween: 20
                        },
                        // quand la largeur de la fenêtre est >= 768px (Tablette)
                        768: {
                            slidesPerView: 4,
                            spaceBetween: 25
                        },
                        // quand la largeur de la fenêtre est >= 1024px (Petit Desktop)
                        1024: {
                            slidesPerView: 5, // Affiche 5 cartes
                            spaceBetween: 30
                        },
                        // quand la largeur de la fenêtre est >= 1200px (Large Desktop)
                        1200: {
                            slidesPerView: 6, // Affiche 6 cartes (ou moins si tu veux plus de défilement)
                            spaceBetween: 30
                        }
                    }
                    // Tu peux ajouter d'autres options Swiper ici si besoin
                });
            });

            // 2. Effet Fade-in pour les cartes (peut être géré par ton main.js global)
            // Si ton main.js ne cible pas déjà dynamiquement toutes les cartes avec .fade-in,
            // tu peux ajouter cette logique ici spécifiquement pour home.php,
            // mais il est préférable de la rendre générique dans main.js.
            // Le code que tu avais pour le fade-in dans main.js devrait fonctionner s'il cible '.card.fade-in'
            // sans être dépendant de '.grid-container'.

            // Vérifie que ton main.js gère bien le fade-in pour les cartes
            // nouvellement ajoutées ou celles dans les carrousels.
            // Si main.js a déjà :
            // const cards = document.querySelectorAll('.card.fade-in');
            // cards.forEach((card, index) => { /* ... logique de timeout et ajout de .visible ... */ });
            // Cela devrait fonctionner. Le point clé est que le sélecteur soit assez générique.

            // Le code pour les panneaux au survol est DÉJÀ dans main.js et devrait
            // s'attacher aux .card-wrapper où qu'ils soient, y compris dans les swiper-slide.
        });
        </script>
    </body>
</html>
<?php if (isset($conn)) $conn->close(); ?>