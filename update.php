<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $status = $_POST['status'];
    $chapters_read = intval($_POST['chapters_read']);
    $note = isset($_POST['note']) ? floatval($_POST['note']) : null;
    $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';

    $stmt = $conn->prepare("UPDATE mangas SET status = ?, chapters_read = ?, note = ?, commentaire = ? WHERE id = ?");
    $stmt->bind_param("sidsi", $status, $chapters_read, $note, $commentaire, $id);

    if ($stmt->execute()) {
        header("Location: index.php?message=Manga modifié avec succès");
        exit();
    } else {
        echo "Erreur lors de la mise à jour : " . $stmt->error;
    }
}
?>
