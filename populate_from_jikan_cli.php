<?php
// populate_from_jikan_cli.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Augmenter le temps max d'exécution pour les scripts longs
set_time_limit(0); // 0 = pas de limite (attention sur hébergement partagé)
ini_set('memory_limit', '256M'); // Augmenter la limite mémoire si besoin

include 'config.php'; // Pour $conn
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


echo "Début du script de peuplement massif depuis Jikan...\n";

// 1. Définir combien de pages de "top mangas" récupérer
$nombreDePagesTop = 40; // Par exemple, les 2 premières pages (environ 50 mangas si 25 par page)
$mangasAjoutes = 0;
$mangasIgnores = 0;
$erreursApi = 0;

for ($page = 1; $page <= $nombreDePagesTop; $page++) {
    echo "Récupération de la page $page des top mangas...\n";
    $topMangaUrl = "https://api.jikan.moe/v4/top/manga?page=" . $page . "&sfw";
    
    $options = ['http' => ['method' => "GET", 'header' => "User-Agent: MonScriptPeuplement/1.0\r\n"]];
    $context = stream_context_create($options);
    $responseJson = @file_get_contents($topMangaUrl, false, $context);

    if ($responseJson === FALSE) {
        echo "Erreur API Jikan en récupérant la page $page des top mangas. Passage à la suite.\n";
        $erreursApi++;
        sleep(2); // Pause plus longue en cas d'erreur
        continue;
    }
    $responseData = json_decode($responseJson, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($responseData['data']) || empty($responseData['data'])) {
        echo "Réponse invalide ou pas de données pour la page $page des top mangas. Fin.\n";
        break; // Arrêter si une page de top est vide ou invalide
    }

    foreach ($responseData['data'] as $topManga) {
        $mal_id = $topManga['mal_id'];
        $title_top = $topManga['title'];

        echo "Traitement de : " . htmlspecialchars($title_top) . " (MAL ID: $mal_id)\n";

        // Vérifier si déjà en BDD
        $stmt_check = $conn->prepare("SELECT id FROM catalogue WHERE mal_id = ?");
        if(!$stmt_check) { echo "Erreur prepare check: " . $conn->error . "\n"; continue; }
        $stmt_check->bind_param("i", $mal_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            echo "  -> Déjà dans le catalogue. Ignoré.\n";
            $mangasIgnores++;
            $stmt_check->close();
            continue; // Passe au manga suivant du top
        }
        $stmt_check->close();

        // S'il n'est pas en BDD, récupérer les détails complets
        echo "  -> Récupération des détails complets depuis Jikan...\n";
        $jikanData = getMangaDetailsJikan($mal_id); // Ta fonction existante
        sleep(2); // PAUSE IMPORTANTE pour respecter les rate limits de Jikan (ajuster si besoin)

        if (isset($jikanData['error'])) {
            echo "  -> Erreur Jikan pour détails de MAL ID $mal_id: " . $jikanData['error'] . "\n";
            $erreursApi++;
            continue;
        }

        // --- MAPPING AJUSTÉ ---
        $title = $jikanData['title'] ?? null;
        $title_english = $jikanData['title_english'] ?? null;
        $title_japanese = $jikanData['title_japanese'] ?? null;
        $slug = $title ? strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))) : null;
        
        $author = formatJikanAuthors($jikanData['authors'] ?? []);
        $artist = $author; // Décision : pour l'instant, artiste = auteur si Jikan ne sépare pas

        $genres_from_jikan = formatJikanGenres($jikanData['genres'] ?? []);
        $themes_from_jikan = formatJikanGenres($jikanData['themes'] ?? []);
        $combined_genres_list = [];
        if (!empty($genres_from_jikan)) $combined_genres_list[] = $genres_from_jikan;
        if (!empty($themes_from_jikan)) $combined_genres_list[] = $themes_from_jikan;
        $genre_to_db = implode(', ', array_filter($combined_genres_list));
        if (empty($genre_to_db)) $genre_to_db = null;


        $type_format_from_jikan = $jikanData['type'] ?? null; // "Manga", "Manhwa" etc. -> pour ta colonne 'type' (ou 'format')
        $demographic_from_jikan = formatJikanGenres($jikanData['demographics'] ?? []); // "Shounen", "Seinen" -> pour ta colonne 'demographic'
        if (empty($demographic_from_jikan)) $demographic_from_jikan = null;


        $synopsis = $jikanData['synopsis'] ?? null;
        if ($synopsis) { // Nettoyage de la signature MAL
             $synopsis = str_replace("[Written by MAL Rewrite]", "", $synopsis);
             $synopsis = trim($synopsis);
        }
        $background_info = $jikanData['background'] ?? null;
        $status_publication = $jikanData['status'] ?? null;
        
        $start_date_raw = $jikanData['published']['from'] ?? null;
        $start_date = $start_date_raw ? substr($start_date_raw, 0, 10) : null;
        $end_date_raw = $jikanData['published']['to'] ?? null;
        $end_date = $end_date_raw ? substr($end_date_raw, 0, 10) : null;
        
        $num_volumes = $jikanData['volumes'] ?? null;
        $num_chapters = $jikanData['chapters'] ?? null;
        $num_episodes = null; // Pour les mangas
        $season = null; // Pour les mangas
        $studio = null; // Pour les mangas

        $magazine_serialization = null;
        if (!empty($jikanData['serializations']) && isset($jikanData['serializations'][0]['name'])) {
            $magazine_serialization = $jikanData['serializations'][0]['name'];
        }
        $cover_image = $jikanData['images']['jpg']['large_image_url'] ?? $jikanData['images']['jpg']['image_url'] ?? $jikanData['images']['jpg']['small_image_url'] ?? null;
        $banner_image = null; 
        $trailer_url = null;
        
        $jikan_score = $jikanData['score'] ?? null; 
        $average_rating_db = null; // Pour tes utilisateurs
        
        $rank_mal = $jikanData['rank'] ?? null;
        $members_mal = $jikanData['members'] ?? 0;


        // --- INSERT AJUSTÉ ---
        // Assure-toi que cette liste de colonnes correspond EXACTEMENT à ta table
        // et que tu as une variable PHP mappée pour chacune.
        $sql_insert = "INSERT INTO catalogue (
            mal_id, title, title_english, title_japanese, slug, author, artist, genre, `type`, demographic,
            synopsis, background_info, status_publication, start_date, end_date, 
            num_volumes, num_chapters, num_episodes, season, studio,
            magazine_serialization, cover_image, banner_image, trailer_url, 
            average_rating, popularity_rank, members_count, jikan_score 
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // 28 '?'

        $stmt_insert = $conn->prepare($sql_insert);
        if (!$stmt_insert) { echo "  -> Erreur prepare INSERT: " . $conn->error . "\n"; continue; }

        // Vérifie la chaîne de types et les variables. 28 types ici.
        $stmt_insert->bind_param("issssssssssssssiiissssssidsd", 
            $mal_id, $title, $title_english, $title_japanese, $slug, $author, $artist, $genre_to_db, $type_format_from_jikan, $demographic_from_jikan,
            $synopsis, $background_info, $status_publication, $start_date, $end_date,
            $num_volumes, $num_chapters, $num_episodes, $season, $studio,
            $magazine_serialization, $cover_image, $banner_image, $trailer_url,
            $average_rating_db, $rank_mal, $members_mal, $jikan_score
        );
        // --- FIN BLOC INSERT ---

        if ($stmt_insert->execute()) {
            echo "  -> Ajouté avec succès !\n";
            $mangasAjoutes++;
        } else {
            echo "  -> Erreur lors de l'ajout: " . $stmt_insert->error . "\n";
        }
        $stmt_insert->close();
    } // Fin foreach topManga
    
    if ($page < $nombreDePagesTop && !empty($responseData['data'])) {
        echo "Pause avant la page suivante de top mangas...\n";
        sleep(5); // Pause plus longue entre les pages de listing
    }

} // Fin for page

$conn->close();
echo "\n--- Script de peuplement terminé ---\n";
echo "Mangas ajoutés : $mangasAjoutes\n";
echo "Mangas ignorés (déjà présents) : $mangasIgnores\n";
echo "Erreurs API rencontrées : $erreursApi\n";
?>