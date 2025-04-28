<?php
$host = "localhost";
$user = "root";
$password = "root";
$dbname = "21List_db";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}
?>