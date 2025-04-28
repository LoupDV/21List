<?php
function showNotification() {
    if (isset($_GET['msg'])) {
        $text = "";
        $color = "";

        switch ($_GET['msg']) {
            case 'modif':
                $text = "✅ Manga modifié avec succès";
                $color = "#4CAF50"; // Vert
                break;
            case 'suppr':
                $text = "🗑️ Manga supprimé avec succès";
                $color = "#f44336"; // Rouge
                break;
            case 'ajout':
                $text = "✅ Manga ajouté à votre liste !";
                $color = "#4CAF50"; // Vert
                break;
            default:
                return; // Ne rien afficher si le message n'est pas reconnu
        }

        echo '
        <div id="message" style="
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: ' . $color . ';
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            font-weight: bold;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            opacity: 1;
            transition: opacity 1s ease-out;
        ">
            ' . $text . '
        </div>

        <script>
            const msg = document.getElementById("message");
            if (msg) {
                setTimeout(() => {
                    msg.style.opacity = "0";
                    setTimeout(() => msg.remove(), 1000);
                }, 3000);
            }
        </script>
        ';
    }
}
?>
