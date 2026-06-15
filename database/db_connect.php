<?php
$host = 'localhost';
$username = 'root';
$database = 'db_munchies';

$passwordCandidates = ['', 'password'];
$conn = null;

foreach ($passwordCandidates as $password) {
    $candidate = mysqli_connect($host, $username, $password, $database);

    if ($candidate) {
        $conn = $candidate;
        break;
    }
}

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}
?>