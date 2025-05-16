<?php
include 'config.php'; // Votre fichier de connexion
include 'notif.php';  // Pour les notifications
session_start(); // Nécessaire pour les notifications de session

// Vérifier si le formulaire a été soumis et si l'utilisateur est connecté (si vous avez un système d'utilisateurs)
// Pour l'instant, on ne vérifie que la soumission.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catalogue_id = isset($_POST['catalogue_id']) ? intval($_POST['catalogue_id']) : 0;
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    // Récupérer la page d'origine depuis le champ caché du formulaire
    $from_page_param = isset($_POST['from_page']) ? htmlspecialchars($_POST['from_page']) : '';

    // Données du formulaire (même si elles viennent de champs cachés, on les récupère pour la BDD si besoin)
    // Dans une structure idéale avec catalogue_id, on n'aurait pas besoin de re-sauvegarder title, author, etc. dans 'mangas'
    // Mais si votre table 'mangas' les contient, il faut les récupérer.
    // Pour cet exemple, je vais supposer que votre table 'mangas' NE stocke PAS title, author, etc.
    // car elle utilise 'catalogue_id' pour faire le lien.

    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $chapters_read = isset($_POST['chapters_read']) ? intval($_POST['chapters_read']) : 0;
    $note = isset($_POST['note']) && $_POST['note'] !== '' ? floatval($_POST['note']) : null; // Permettre une note vide (NULL)
    $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';
    $manga_personnel_id = isset($_POST['manga_personnel_id']) ? intval($_POST['manga_personnel_id']) : 0; // Pour l'UPDATE/DELETE

    if ($catalogue_id === 0 || $user_id === 0) {
        $_SESSION['notification'] = ['type' => 'error', 'message' => 'Données invalides pour la sauvegarde.'];
        // Construire l'URL de redirection avec le paramètre 'from' si disponible
        $redirect_url = 'detail_manga.php?id=' . $catalogue_id;
        if (!empty($from_page_param)) {
            $redirect_url .= '&from=' . $from_page_param;
        }
        header('Location: ' . $redirect_url);
        exit;
    }

        // --- GESTION DE LA SUPPRESSION ---
    if (isset($_POST['delete_manga'])) {
        $suppression_reussie = false; // Initialiser à false

        if ($manga_personnel_id > 0) { // Assurez-vous que $manga_personnel_id est bien l'ID de l'entrée dans la table 'mangas'
            $stmt_delete = $conn->prepare("DELETE FROM mangas WHERE id = ? AND user_id = ?");
            if (!$stmt_delete) {
                // Gérer l'erreur de préparation, peut-être avec une notification et redirection
                $_SESSION['notification'] = ['type' => 'error', 'message' => 'Erreur de préparation de la suppression.'];
                // Rediriger vers la page de détail avec 'from' si possible
                $redirect_url = 'detail_manga.php?id=' . $catalogue_id;
                if (!empty($from_page_param)) {
                    $redirect_url .= '&from=' . $from_page_param;
                }
                header('Location: ' . $redirect_url);
                exit;
            }
            $stmt_delete->bind_param("ii", $manga_personnel_id, $user_id);
            if ($stmt_delete->execute()) {
                if ($stmt_delete->affected_rows > 0) {
                    $_SESSION['notification'] = ['type' => 'success', 'message' => 'Manga supprimé de votre liste !'];
                    $suppression_reussie = true;
                } else {
                    $_SESSION['notification'] = ['type' => 'warning', 'message' => 'Aucun manga n\'a été supprimé (peut-être déjà retiré ou ID incorrect).'];
                }
            } else {
                $_SESSION['notification'] = ['type' => 'error', 'message' => 'Erreur lors de la suppression du manga : ' . $stmt_delete->error];
            }
            $stmt_delete->close();
        } else {
            $_SESSION['notification'] = ['type' => 'error', 'message' => 'Impossible de supprimer : ID de manga personnel manquant.'];
        }

        // Redirection après tentative de suppression
        if ($suppression_reussie) {
            if ($from_page_param === 'index') {
                header('Location: index.php'); // Ou index.php avec des filtres/page si vous les gérez
                exit;
            } elseif ($from_page_param === 'catalogue') {
                 header('Location: catalogue.php'); // Rediriger vers le catalogue si supprimé avec succès et venait de là
                 exit;
            }
        }
        
        // Comportement par défaut si la suppression a échoué ou si 'from_page_param' n'est pas 'index' ou 'catalogue' après une suppression réussie:
        // Retour à la page détail (le manga n'y sera plus dans la liste perso, donc le formulaire sera "Ajouter")
        $redirect_url = 'detail_manga.php?id=' . $catalogue_id;
        if (!empty($from_page_param)) {
            $redirect_url .= '&from=' . $from_page_param;
        }
        header('Location: ' . $redirect_url);
        exit;
    }

    // --- GESTION DE L'AJOUT OU DE LA MISE À JOUR ---
    if (isset($_POST['save_manga'])) {
        // Vérifier si c'est un ajout (INSERT) ou une mise à jour (UPDATE)
        // On se base sur la présence de manga_personnel_id (si > 0, c'est un update d'une entrée existante)
        // Ou on peut aussi vérifier si une entrée existe déjà pour ce catalogue_id et user_id

        $check_stmt = $conn->prepare("SELECT id FROM mangas WHERE catalogue_id = ? AND user_id = ?");
        if (!$check_stmt) {
            die("Erreur de préparation (check): " . $conn->error);
        }
        $check_stmt->bind_param("ii", $catalogue_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $existing_entry = $check_result->fetch_assoc();
        $check_stmt->close();

        if ($existing_entry) { // Mise à jour
            $sql = "UPDATE mangas SET status = ?, chapters_read = ?, note = ?, commentaire = ?, date_updated = NOW()
                    WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("Erreur de préparation (update): " . $conn->error);
            }
            $stmt->bind_param("ssdsii", $status, $chapters_read, $note, $commentaire, $existing_entry['id'], $user_id);
            $action_message = "Manga mis à jour dans votre liste !";
        } else { // Ajout
            $sql = "INSERT INTO mangas (user_id, catalogue_id, status, chapters_read, note, commentaire, date_added, date_updated)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("Erreur de préparation (insert): " . $conn->error);
            }
            $stmt->bind_param("iissds", $user_id, $catalogue_id, $status, $chapters_read, $note, $commentaire);
            $action_message = "Manga ajouté à votre liste !";
        }

        if ($stmt->execute()) {
            $_SESSION['notification'] = ['type' => 'success', 'message' => $action_message];
        } else {
            $_SESSION['notification'] = ['type' => 'error', 'message' => 'Erreur lors de la sauvegarde du manga : ' . $stmt->error];
        }
        $stmt->close();

        // Redirection après sauvegarde/mise à jour
        $redirect_url = 'detail_manga.php?id=' . $catalogue_id;
        if (!empty($from_page_param)) {
            $redirect_url .= '&from=' . $from_page_param;
        }
        header('Location: ' . $redirect_url);
        exit;

    }

} else {
    // Si quelqu'un accède directement à ce script sans POST
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Accès non autorisé.'];
    header('Location: catalogue.php');
    exit;
}

$conn->close();
?>