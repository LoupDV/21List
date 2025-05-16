<?php
session_start(); // Nécessaire pour les messages de feedback
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'config.php'; // Pour la connexion $conn à ta base de données

// Fonction pour récupérer les détails complets d'un manga depuis Jikan par son MAL ID
function getMangaDetailsJikan($mal_id) {
    if (empty($mal_id) || !is_numeric($mal_id)) {
        return ['error' => "MAL ID invalide."];
    }
    $apiUrl = "https://api.jikan.moe/v4/manga/" . intval($mal_id);

    $options = ['http' => ['method' => "GET", 'header' => "User-Agent: VotreAppMangaAdmin/1.0 (votre@email.com)\r\n"]];
    $context = stream_context_create($options);
    $responseJson = @file_get_contents($apiUrl, false, $context);

    if ($responseJson === FALSE) { return ['error' => "Erreur lors de la requête API Jikan pour les détails."]; }
    $responseData = json_decode($responseJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) { return ['error' => "Erreur décodage JSON détails: " . json_last_error_msg()]; }
    if (!isset($responseData['data'])) { return ['error' => "Réponse API Jikan (détails) invalide."]; }
    return $responseData['data'];
}

// Fonction pour mapper les genres de Jikan en une chaîne
function formatJikanAuthors($authorsArray) {
    if (empty($authorsArray) || !is_array($authorsArray)) {
        return null;
    }
    $authorNames = [];
    foreach ($authorsArray as $author) {
        // AJOUTER CETTE VÉRIFICATION :
        if (is_array($author) && isset($author['name'])) {
            $authorNames[] = trim($author['name']);
        }
    }
    return implode(', ', $authorNames);
}

// Fais de même pour formatJikanGenres
function formatJikanGenres($genresArray) {
    if (empty($genresArray) || !is_array($genresArray)) {
        return null;
    }
    $genreNames = [];
    foreach ($genresArray as $genre) {
        // AJOUTER CETTE VÉRIFICATION :
        if (is_array($genre) && isset($genre['name'])) {
            $genreNames[] = trim($genre['name']);
        }
    }
    return implode(', ', $genreNames);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_catalogue'])) {
    $mal_id = $_POST['mal_id'] ?? null;
    $title_from_form = $_POST['title'] ?? 'ce manga'; // Pour les messages

    if (!$mal_id) {
        $_SESSION['jikan_message'] = "Erreur : MAL ID manquant.";
        $_SESSION['jikan_message_type'] = "error";
        header("Location: admin_add_manga.php");
        exit;
    }

    // 1. Vérifier si le manga existe déjà dans notre BDD (basé sur mal_id)
    $stmt_check = $conn->prepare("SELECT id FROM catalogue WHERE mal_id = ?");
    if(!$stmt_check) {
        $_SESSION['jikan_message'] = "Erreur préparation (vérification doublon): " . $conn->error;
        $_SESSION['jikan_message_type'] = "error";
        header("Location: admin_add_manga.php");
        exit;
    }
    $stmt_check->bind_param("i", $mal_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $_SESSION['jikan_message'] = htmlspecialchars($title_from_form) . " (MAL ID: $mal_id) est déjà dans votre catalogue.";
        $_SESSION['jikan_message_type'] = "info"; // ou "warning"
        $stmt_check->close();
        header("Location: admin_add_manga.php");
        exit;
    }
    $stmt_check->close();

    // 2. Récupérer les détails complets de Jikan
    $jikanData = getMangaDetailsJikan($mal_id);

    if (isset($jikanData['error'])) {
        $_SESSION['jikan_message'] = "Erreur Jikan pour MAL ID $mal_id: " . $jikanData['error'];
        $_SESSION['jikan_message_type'] = "error";
        header("Location: admin_add_manga.php");
        exit;
    }

    // 3. Mapper les données Jikan à nos colonnes de table `catalogue`
    //    Adapte ceci EXACTEMENT à tes noms de colonnes et aux champs Jikan
    $title = $jikanData['title'] ?? null;
    $title_english = $jikanData['title_english'] ?? null;
    $title_japanese = $jikanData['title_japanese'] ?? null;
    // Pour le slug, tu pourrais générer un slug simple à partir du titre
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

    $author = formatJikanAuthors($jikanData['authors'] ?? []);
    // Jikan a 'authors' et 'serializations' (qui contient le magazine).
    // 'artists' est moins courant pour les mangas directement dans Jikan, souvent inclus dans authors.
    // Si 'artists' est séparé, tu devras le trouver dans la structure de Jikan.

    $genre = formatJikanGenres($jikanData['genres'] ?? []);
    if(empty($genre)) { // Fallback sur les thèmes ou démos si genres vides
        $genre = formatJikanGenres($jikanData['themes'] ?? []);
    }
     if(empty($genre)) {
        $genre = formatJikanGenres($jikanData['demographics'] ?? []);
    }


    $type = $jikanData['type'] ?? null; // Ex: "Manga", "Manhwa", "Novel"
    $demographic_data = formatJikanGenres($jikanData['demographics'] ?? []); 
    $synopsis_raw = $jikanData['synopsis'] ?? null;
    $synopsis = null;
    if ($synopsis_raw) {
        $synopsis = str_replace("[Written by MAL Rewrite]", "", $synopsis_raw);
        // ou avec preg_replace:
        // $synopsis = preg_replace('/\[\s*(?:Written by|Source:)\s*[^\]]+\]\s*$/', '', $synopsis_raw);
        $synopsis = trim($synopsis);
    }
    $status_publication = $jikanData['status'] ?? null; // Ex: "Finished", "Publishing"
    $background_info = $jikanData['background'] ?? null;
    
    // Dates: Jikan fournit published.from et published.to au format ISO 8601 (YYYY-MM-DDTHH:mm:ss+00:00)
    // On ne veut que la partie date YYYY-MM-DD
    $start_date_raw = $jikanData['published']['from'] ?? null;
    $start_date = $start_date_raw ? substr($start_date_raw, 0, 10) : null;
    $end_date_raw = $jikanData['published']['to'] ?? null;
    $end_date = $end_date_raw ? substr($end_date_raw, 0, 10) : null;
    
    $num_volumes = $jikanData['volumes'] ?? null;
    $num_chapters = $jikanData['chapters'] ?? null;
    // num_episodes n'est généralement pas pour les mangas dans Jikan, mais pour les animes.
    // Si tu as une colonne num_episodes pour les adaptations anime, tu la laisserais NULL ici.
    $num_episodes = null; // Ou si tu as une logique pour le trouver, ajoute-la

    // 'season' et 'studio' sont plus pertinents pour les animes.
    // Pour un manga, tu pourrais stocker le magazine de sérialisation.
    $magazine_serialization = null;
    if (!empty($jikanData['serializations']) && isset($jikanData['serializations'][0]['name'])) {
        $magazine_serialization = $jikanData['serializations'][0]['name'];
    }

    $cover_image = $jikanData['images']['jpg']['large_image_url'] ?? $jikanData['images']['jpg']['image_url'] ?? null;
    // banner_image: Jikan ne fournit pas de "banner_image" distincte pour les mangas. Tu peux la laisser NULL ou utiliser cover_image.
    $banner_image = null; 
    $trailer_url = null; // Jikan a `trailer.url` pour les animes, pas directement pour les mangas.

    $average_rating_mal = $jikanData['score'] ?? null; // Score MAL
    // Ta colonne average_rating sera pour les notes de TES utilisateurs. Pour l'instant, on peut la laisser NULL
    // ou y mettre le score MAL si tu veux un point de départ.
    $average_rating = $jikanData['score'] ?? null; // Ou $average_rating_mal si tu veux l'utiliser comme valeur initiale
    
    $popularity_rank_mal = $jikanData['rank'] ?? null;
    $members_count_mal = $jikanData['members'] ?? 0;


    // 4. Préparer et exécuter la requête INSERT
    // Adapte la liste des colonnes et des ? à ta structure exacte
    $sql_insert = "INSERT INTO catalogue (
        mal_id, title, title_english, title_japanese, slug, author, genre, `type`,
        synopsis, status_publication, start_date, end_date, num_volumes, num_chapters, num_episodes,
        magazine_serialization, cover_image, banner_image, trailer_url, average_rating,
        popularity_rank, members_count
        -- created_at, updated_at sont automatiques
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_insert = $conn->prepare($sql_insert);
    if (!$stmt_insert) {
        $_SESSION['jikan_message'] = "Erreur préparation INSERT: " . $conn->error;
        $_SESSION['jikan_message_type'] = "error";
        header("Location: admin_add_manga.php");
        exit;
    }

    // 's' pour string, 'i' pour integer, 'd' pour double/decimal
    // Compte bien tes '?' et tes types. Il y en a 22 ici.
    $stmt_insert->bind_param("issssssssssssiisssssid",
        $mal_id, $title, $title_english, $title_japanese, $slug, $author, $genre, $type,
        $synopsis, $status_publication, $start_date, $end_date, $num_volumes, $num_chapters, $num_episodes,
        $magazine_serialization, $cover_image, $banner_image, $trailer_url, $average_rating,
        $popularity_rank_mal, $members_count_mal
    );

    if ($stmt_insert->execute()) {
        $_SESSION['jikan_message'] = htmlspecialchars($title) . " a été ajouté avec succès à votre catalogue !";
        $_SESSION['jikan_message_type'] = "success";
    } else {
        $_SESSION['jikan_message'] = "Erreur lors de l'ajout de " . htmlspecialchars($title) . ": " . $stmt_insert->error;
        $_SESSION['jikan_message_type'] = "error";
    }
    $stmt_insert->close();

} else {
    // Rediriger si accès direct ou méthode incorrecte
    $_SESSION['jikan_message'] = "Accès non autorisé.";
    $_SESSION['jikan_message_type'] = "error";
}

$conn->close();
header("Location: admin_add_manga.php"); // Toujours rediriger vers la page d'admin
exit;
?>