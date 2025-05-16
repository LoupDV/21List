<?php
//session_start(); // Décommente si tu as besoin de vérifier si l'utilisateur est connecté plus tard
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'config.php';
include 'notif.php';

$manga = null; // Contiendra les données du manga si trouvé
$error_message = '';
$manga_id = null;

// 1. Récupérer et Valider l'ID depuis l'URL
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
     $manga_id = (int)$_GET['id'];
} else {
    $error_message = "ID de Manga invalide ou manquant.";
}

// 2. Si ID valide, interroger la base de données
if ($manga_id && empty($error_message)) {
     try {
        $stmt = $conn->prepare("SELECT * FROM catalogue WHERE id = ?");
        if (!$stmt) {
           throw new Exception("Erreur BDD (prepare): " . $conn->error);
        }

        $stmt->bind_param("i", $manga_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
           $manga = $result->fetch_assoc();
        } else {
           $error_message = "Manga non trouvé dans le catalogue (ID: $manga_id).";
        }
         $stmt->close();

     } catch (Exception $e) {
         $error_message = "Erreur: " . $e->getMessage();
     }
}
 $conn->close();

// Fonction pour afficher une ligne de Stat si la valeur n'est pas vide
function display_stat($label, $value) {
   if (!empty($value)) {
     echo '<p class="detail-stats-item"><strong>' . htmlspecialchars($label) . ':</strong> ' . htmlspecialchars($value) . '</p>';
   }
}

?>
<!DOCTYPE html>
<html lang="fr" class="no-transition">
<head>
    <meta charset="UTF-8">
    <title><?= $manga ? htmlspecialchars($manga['title']) : 'Détail Manga' ?></title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Styles pour la page de détail (A METTRE DANS STYLE.CSS !) */
         body { background-color: var(--bg-color); }
         .detail-page-container { max-width: 1100px; margin: 30px auto; padding: 20px; background-color: var(--card-bg); border-radius: var(--border-radius); box-shadow: 0 2px 10px rgba(0,0,0,0.1);  }
         .detail-grid { display: grid; grid-template-columns: 250px 1fr; gap: 30px; }
         
         .detail-left { grid-column: 1 / 2; }
         .detail-left .cover-image-large { width: 100%; height: auto; border-radius: var(--border-radius); margin-bottom: 20px; }
         .detail-actions .btn { margin-bottom: 10px; width: 100%; box-sizing: border-box;}
         
         .detail-right { grid-column: 2 / 3; text-align: left; }
         .detail-titles h1, .detail-titles h2 { margin: 0 0 10px 0; text-align: left; line-height: 1.2;}
         .detail-titles h1 { font-size: 2em; color: var(--text-color); }
         .detail-titles h2 { font-size: 1.2em; color: #666; font-weight: 400; font-style: italic; }
         
         .detail-genres { display: flex; flex-wrap: wrap; gap: 8px; margin: 20px 0; }
         /* Réutilise le style .genre-tag que tu as déjà défini */
         
         .detail-section { margin-bottom: 25px; }
         .detail-section h4 { font-size: 1.3em; margin-bottom: 10px; border-bottom: 2px solid var(--accent-color); padding-bottom: 5px; color: var(--accent-color);}
         .detail-section p, .detail-stats-item {
             font-size: 0.95em;
             line-height: 1.6;
             color: var(--text-color);
             margin-bottom: 5px;
          }
          .error-message { color: red; font-weight: bold; text-align: center; padding: 30px;}

         /* Responsive */
          @media (max-width: 768px) {
             .detail-grid { grid-template-columns: 1fr; /* Une seule colonne */ }
             .detail-left, .detail-right { grid-column: 1 / -1; }
             .detail-left { text-align: center; max-width: 280px; margin: 0 auto 20px auto;}
          }

    </style>
</head>
<body class="no-transition">

    <?php include 'navbar.php'; ?>

    <div class="detail-page-container">

        <?php if (!empty($error_message)): ?>
            <p class="error-message"><?= htmlspecialchars($error_message) ?></p>

        <?php elseif ($manga): ?>
            <div class="detail-grid">

                <!-- COLONNE GAUCHE -->
                <aside class="detail-left">
                    <img class="cover-image-large" src="<?= htmlspecialchars($manga['cover_image'] ?? 'covers/default_cover.jpg') ?>" alt="Cover de <?= htmlspecialchars($manga['title']) ?>" onerror="this.onerror=null;this.src='covers/default_cover.jpg';">

                    <div class="detail-actions">
                       <button type="button" class="btn confirm"><i class="fas fa-plus"></i> Ajouter à ma Liste</button>
                       <!-- Ce bouton ne fera rien pour l'instant, il faudra le relier à la partie utilisateur -->
                    </div>
                     
                    <div class="detail-stats detail-section">
                       <h4>Infos Rapides</h4>
                       <?php display_stat("Format", $manga['type']); ?>
                       <?php display_stat("Statut", $manga['status_publication']); ?>
                       <?php display_stat("Volumes", $manga['num_volumes']); ?>
                       <?php display_stat("Chapitres", $manga['num_chapters']); ?>
                       <?php display_stat("Note", $manga['jikan_score']); ?>
                    </div>
                </aside>

                 <!-- COLONNE DROITE -->
                <main class="detail-right">
                    <div class="detail-titles">
                         <h1><?= htmlspecialchars($manga['title']) ?></h1>
                         <?php if (!empty($manga['title_english']) && $manga['title_english'] !== $manga['title']): ?>
                           <h2><?= htmlspecialchars($manga['title_english']) ?></h2>
                         <?php endif; ?>
                         <?php if (!empty($manga['title_japanese'])): ?>
                           <h2><?= htmlspecialchars($manga['title_japanese']) ?></h2>
                         <?php endif; ?>
                    </div>

                    <?php // --- AFFICHAGE DES GENRES ---
                        if (!empty($manga['genre'])):
                         $genres = explode(',', $manga['genre']);
                    ?>
                     <div class="detail-genres">
                         <?php foreach ($genres as $genre_item): ?>
                            <span class="genre-tag"><?= htmlspecialchars(trim($genre_item)) ?></span>
                         <?php endforeach; ?>
                     </div>
                    <?php endif; ?>


                    <?php // --- SYNOPSIS ---
                        $synopsis_a_afficher = '';
                        // Ta logique pour choisir entre synopsis_fr et synopsis anglais
                        if (!empty($manga['synopsis_fr'])) {
                            $synopsis_a_afficher = $manga['synopsis_fr'];
                        } elseif (!empty($manga['synopsis'])) {
                            $synopsis_a_afficher = $manga['synopsis'];
                        }

                        if (!empty($synopsis_a_afficher)):
                            // NETTOYAGE DU SYNOPSIS
                            $cleaned_synopsis = str_replace("[Written by MAL Rewrite]", "", $synopsis_a_afficher);
                            // Tu peux aussi enchaîner d'autres str_replace si MAL utilise d'autres signatures
                            // $cleaned_synopsis = str_replace("[Source: AutreSite]", "", $cleaned_synopsis);
                            $cleaned_synopsis = trim($cleaned_synopsis); // Enlève les espaces en début/fin
                    ?>
                     <div class="detail-section">
                        <h4>Synopsis</h4>
                        <p><?= nl2br(htmlspecialchars($cleaned_synopsis)) ?></p>
                     </div>
                    <?php endif; ?>
                    
                     <?php // --- BACKGROUND ---
                     if (!empty($manga['background_info'])): ?>
                     <div class="detail-section">
                        <h4>Background</h4>
                         <p><?= nl2br(htmlspecialchars($manga['background_info'])) ?></p>
                     </div>
                     <?php endif; ?>

                    <?php // --- AUTRES INFOS --- ?>
                     <div class="detail-section">
                        <h4>Informations</h4>
                         <?php display_stat("Auteur(s)", $manga['author']); ?>
                         <?php display_stat("Artiste(s)", $manga['artist']); ?>
                         <?php display_stat("Publication", $manga['magazine_serialization']); ?>
                         <?php display_stat("Début", $manga['start_date']); ?>
                         <?php display_stat("Fin", $manga['end_date']); ?>
                     </div>

                </main>

            </div><!-- /detail-grid -->

        <?php else: ?>
             <p class="error-message">Le manga demandé n'a pas pu être chargé.</p>
        <?php endif; ?>

    </div><!-- /detail-page-container -->

    <script src="assets/JS/main.js"></script>
</body>
</html>