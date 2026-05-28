<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_blog";

// Menggunakan mysqli dengan pelaporan error di PHP 8.2
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = new mysqli($host, $user, $pass, $db);
    $koneksi->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Mulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}