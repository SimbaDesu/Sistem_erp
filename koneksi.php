<?php
// koneksi.php
$host = "localhost";
$user = "root";   // default user di XAMPP
$pass = "";       // password default kosong
$db   = "sistem_erp"; // samakan dengan nama database di phpMyAdmin

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>
