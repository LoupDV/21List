<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des champs du formulaire
    $title = $_POST['title'];
    $author = $_POST['author'];
    $genre = $_POST['genre'];
    $status = $_POST['status'];
    $chapters_read = $_POST['chapters_read'];

    // Préparation de la requête SQL
    $stmt = $conn->prepare("INSERT INTO mangas (title, author, genre, status, chapters_read) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $title, $author, $genre, $status, $chapters_read);


    // Exécution de la requête
    if ($stmt->execute()) {
        header("Location: index.php?message=Manga ajouté avec succès");
    exit();
    } else {
        echo "Erreur lors de l'ajout : " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un manga</title>
</head>
<body>
    <h1>Ajouter un manga</h1>
    <form action="add.php" method="POST">
        <label for="title">Titre :</label><br>
        <input type="text" id="title" name="title" required><br><br>

        <label for="author">Auteur :</label><br>
        <input type="text" id="author" name="author" required><br><br>

        <label for="genre">Genre :</label><br>
        <input type="text" id="genre" name="genre" required><br><br>

        <label for="chapters_read">Nombre de chapitres lu :</label><br>
        <input type="number" id="chapters_read" name="chapters_read" min="0" value="0"><br></br>

        <label for="status">Statut :</label><br>
        <select id="status" name="status" required>
            <option value="À lire">À lire</option>
            <option value="En cours">En cours</option>
            <option value="Fini">Fini</option>
        </select><br><br>

        <button type="submit">Ajouter</button>
        <button type="button" onclick="window.location.href='index.php';">Annuler</button>
    </form>
</body>
</html>
