<?php
include 'config.php';

if (isset($_POST['id'])) {
    $id = intval($_POST['id']); // Sécuriser l'entrée

    $stmt = $conn->prepare("DELETE FROM mangas WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header('Location: index.php?message=Suppression+réussie');
        exit();
    } else {
        echo "Erreur lors de la suppression : " . $stmt->error;
    }
} else {
    echo "ID manquant.";
}
?>

<?php
var_dump($_POST);
exit;
?>
