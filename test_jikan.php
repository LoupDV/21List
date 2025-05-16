<?php
// test_jikan.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function searchMangaJikan($searchTerm) {
    // Encode le terme de recherche pour l'URL
    $encodedSearchTerm = urlencode($searchTerm);
    $apiUrl = "https://api.jikan.moe/v4/manga?q=" . $encodedSearchTerm . "&limit=5"; // Limite à 5 résultats pour le test

    echo "Requête API : " . $apiUrl . "<br><br>";

    // Utiliser file_get_contents avec gestion des erreurs et user-agent
    // Un User-Agent est souvent requis ou recommandé par les APIs
    $options = [
        'http' => [
            'method' => "GET",
            'header' => "User-Agent: MonAppDeManga/1.0 (contact@example.com)\r\n" // Remplace par tes infos
        ]
    ];
    $context = stream_context_create($options);
    $responseJson = @file_get_contents($apiUrl, false, $context); // Le @ supprime les warnings PHP natifs, on gère l'erreur après

    if ($responseJson === FALSE) {
        echo "Erreur lors de la requête à l'API Jikan.<br>";
        // Tu peux analyser $http_response_header ici pour plus de détails sur l'erreur HTTP si besoin
        return null;
    }

    $responseData = json_decode($responseJson, true); // true pour un tableau associatif PHP

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Erreur lors du décodage JSON : " . json_last_error_msg() . "<br>";
        return null;
    }

    // Vérifier la structure de la réponse de Jikan (elle a une clé "data")
    if (isset($responseData['data'])) {
        return $responseData['data'];
    } else {
        echo "La structure de la réponse de l'API n'est pas celle attendue (clé 'data' manquante).<br>";
        print_r($responseData); // Affiche la réponse pour débogage
        return null;
    }
}

// --- Test de la fonction ---
$mangaName = "One piece"; // Change pour tester avec d'autres mangas
echo "<h2>Recherche pour : " . htmlspecialchars($mangaName) . "</h2>";

$results = searchMangaJikan($mangaName);

if ($results) {
    if (count($results) > 0) {
        echo "<ul>";
        foreach ($results as $manga) {
            $mal_id = $manga['mal_id'];
            $title = $manga['title'];

            // Prioriser large_image_url, puis image_url, puis small_image_url
            $imageUrl = $manga['images']['jpg']['large_image_url'] ??
                        $manga['images']['jpg']['image_url'] ??
                        $manga['images']['jpg']['small_image_url'] ??
                        'covers/default_cover.jpg'; // Fallback

            // CORRECTION ICI : Définir $synopsis pour chaque manga
            $currentSynopsis = $manga['synopsis'] ?? null; // Récupérer le synopsis de l'API
            if ($currentSynopsis) {
                $synopsisText = substr($currentSynopsis, 0, 150) . (strlen($currentSynopsis) > 150 ? "..." : "");
            } else {
                $synopsisText = 'Pas de synopsis disponible.';
            }
            // FIN DE LA CORRECTION

            $score = $manga['score'] ?? 'N/A';

            echo "<li>";
            echo "<strong>ID MAL :</strong> " . htmlspecialchars($mal_id) . "<br>";
            echo "<strong>Titre :</strong> " . htmlspecialchars($title) . "<br>";
            echo "<strong>Score :</strong> " . htmlspecialchars($score) . "<br>";
            echo "<img src='" . htmlspecialchars($imageUrl) . "' alt='" . htmlspecialchars($title) . "' width='100'><br>";
            // Utiliser la variable $synopsisText corrigée
            echo "<strong>Synopsis (début) :</strong> " . htmlspecialchars($synopsisText) . "<br>";
            echo "<a href='https://api.jikan.moe/v4/manga/" . $mal_id . "' target='_blank'>Voir détails JSON complets</a>";
            echo "</li><br>";
        }
        echo "</ul>";
    } else {
        echo "Aucun résultat trouvé pour '" . htmlspecialchars($mangaName) . "'.";
    }
} else {
    echo "La recherche n'a pas pu être effectuée.";
}

?>