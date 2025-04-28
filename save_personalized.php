<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $genre = $_POST['genre'];
    $status = $_POST['status'];
    $chapters_read = intval($_POST['chapters_read']);
    $cover_image = $_POST['cover_image'];
    $note = isset($_POST['note']) ? floatval($_POST['note']) : null;
    $commentaire = isset($_POST['commentaire']) ? $_POST['commentaire'] : null;

    $stmt = $conn->prepare("INSERT INTO mangas (title, author, genre, status, chapters_read, cover_image, note, commentaire) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssisss", $title, $author, $genre, $status, $chapters_read, $cover_image, $note, $commentaire);

    if ($stmt->execute()) {
        header("Location: catalogue.php?msg=ajout");
        exit();
    } else {
        echo "Erreur lors de l'ajout : " . $stmt->error;
    }
}
?>
