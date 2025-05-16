<?php
// test_php_config.php
echo "Version PHP : " . phpversion() . "\n";
echo "Fichier php.ini chargé : " . php_ini_loaded_file() . "\n";
echo "Fichiers .ini scannés en plus : " . php_ini_scanned_files() . "\n";
echo "Valeur de extension_dir : " . ini_get('extension_dir') . "\n";
echo "Extension mysqli chargée ? : " . (extension_loaded('mysqli') ? 'Oui' : 'Non') . "\n";
echo "Extension curl chargée ? : " . (extension_loaded('curl') ? 'Oui' : 'Non') . "\n";
echo "Extension openssl chargée ? : " . (extension_loaded('openssl') ? 'Oui' : 'Non') . "\n";
?>