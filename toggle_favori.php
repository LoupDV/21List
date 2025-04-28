<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    $result = $conn->query("SELECT favori FROM mangas WHERE id = $id");
    $row = $result->fetch_assoc();
    $favori = $row['favori'];

    $newFavori = ($favori == 0) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE mangas SET favori = ? WHERE id = ?");
    $stmt->bind_param("ii", $newFavori, $id);

    if ($stmt->execute()) {
        echo "ok"; // Réponse rapide au JS
    } else {
        echo "error";
    }
}
?>
