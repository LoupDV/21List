<!DOCTYPE html>
<html lang="fr" class="no-transition">
<head>
    <meta charset="UTF-8">
    <title>21List - Accueil</title>

    <!-- Lien vers la feuille de style principale -->
    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

    <!-- Barre de navigation globale -->
    <?php include 'navbar.php'; ?>
    
    <!-- Contenu principal de la page d'accueil -->
    <div class="home-container">
        <h1>Bienvenue sur 21List 📚</h1>

        <p>Gérez vos lectures de mangas facilement !</p>

        <div class="home-buttons">
            <!-- Boutons d'accès rapides -->
            <a href="catalogue.php" class="btn">📖 Voir le Catalogue</a>
            <a href="index.php" class="btn">📚 Ma Liste</a>
        </div>
    </div>

    <!-- Modale pour les paramètres (changement de thème) -->
    <div id="settings-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); justify-content: center; align-items: center; z-index: 1000;">
        <div class="modal-content" style="background: var(--modal-bg-color); padding: 20px; border-radius: 10px; width: 300px; text-align: center;">
            <h2>⚙️ Paramètres</h2>

            <!-- Sélecteur de thème -->
            <label>🎨 Thème :</label><br>
            <select id="theme-selector" onchange="changeTheme(this.value)">
                <option value="default">🌟 Moderne</option>
                <option value="dark">🌙 Dark Mode</option>
            </select>

            <br><br>

            <!-- Bouton pour fermer la modale -->
            <button onclick="closeSettings()" style="margin-top: 20px; background-color: #f44336; color: white; border: none; padding: 10px 20px; border-radius: 5px;">Fermer</button>
        </div>
    </div>

    <div id="loader" class="loader" style="display: none;"></div>

    <!-- Script principal pour gérer les thèmes et les paramètres -->
    <script src="assets/JS/main.js"></script>

    </body>
</html>
