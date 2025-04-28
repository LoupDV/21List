<?php
include 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Récupérer le manga depuis le catalogue
    $stmt = $conn->prepare("SELECT * FROM catalogue WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $manga = $result->fetch_assoc();

    if ($manga) {
        // Insérer dans la table mangas
        $stmt = $conn->prepare("INSERT INTO mangas (title, author, genre, status, chapters_read) VALUES (?, ?, ?, 'À lire', 0)");
        $stmt->bind_param("sss", $manga['title'], $manga['author'], $manga['genre']);
        $stmt->execute();
    }
}

// Rediriger vers la liste après l'ajout
header("Location: index.php?message=Manga ajouté depuis le catalogue !");
exit();
?>

