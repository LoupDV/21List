<?php
include 'config.php';

if (!isset($_GET['id'])) {
    echo "ID non spécifié.";
    exit();
}

$id = intval($_GET['id']);

// 1. Si le formulaire a été envoyé (méthode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les nouvelles valeurs
    $title = $_POST['title'];
    $author = $_POST['author'];
    $genre = $_POST['genre'];
    $status = $_POST['status'];
    $chapters_read = $_POST['chapters_read'];

    // Mise à jour dans la base
    $stmt = $conn->prepare("UPDATE mangas SET title = ?, author = ?, genre = ?, status = ?, chapters_read = ? WHERE id = ?");
    $stmt->bind_param("ssssii", $title, $author, $genre, $status, $chapters_read, $id);

    if ($stmt->execute()) {
        header("Location: index.php?msg=modif");
        exit();
    } else {
        echo "Erreur lors de la mise à jour : " . $stmt->error;
    }
} else {
    // 2. Sinon : afficher les infos du manga
    $stmt = $conn->prepare("SELECT * FROM mangas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $manga = $result->fetch_assoc();

    if (!$manga) {
        echo "Manga introuvable.";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier un manga</title>
</head>
<body>
    <h1>Modifier un manga</h1>
    <form action="edit.php?id=<?= $id ?>" method="POST">
        <label for="title">Titre :</label><br>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($manga['title']) ?>" required><br><br>

        <label for="author">Auteur :</label><br>
        <input type="text" id="author" name="author" value="<?= htmlspecialchars($manga['author']) ?>" required><br><br>

        <label for="genre">Genre :</label><br>
        <input type="text" id="genre" name="genre" value="<?= htmlspecialchars($manga['genre']) ?>" required><br><br>

        <label for="status">Statut :</label><br>
        <select id="status" name="status" required>
            <option value="À lire" <?= $manga['status'] === 'À lire' ? 'selected' : '' ?>>À lire</option>
            <option value="En cours" <?= $manga['status'] === 'En cours' ? 'selected' : '' ?>>En cours</option>
            <option value="Fini" <?= $manga['status'] === 'Fini' ? 'selected' : '' ?>>Fini</option>
        </select><br><br>

        <label for="chapters_read">Chapitres lus :</label><br>
        <input type="number" id="chapters_read" name="chapters_read" value="<?= $manga['chapters_read'] ?>" min="0" required><br><br>

        <button type="submit">Enregistrer les modifications</button>
        <button type="button" onclick="window.location.href='index.php';">Annuler</button>
    </form>
</body>
</html>

