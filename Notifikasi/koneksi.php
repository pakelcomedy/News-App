<?php
// Konfigurasi koneksi database
$host = 'localhost:3307'; // Port 3307 (default XAMPP jika MySQL dijalankan di port ini)
$db_name = 'news_app';   // Nama database
$username = 'root';       // Username database
$password = '';           // Password database (kosong untuk XAMPP default)

function getKoneksi() {
    global $host, $db_name, $username, $password;

    try {
        // Membuat koneksi PDO dengan charset UTF-8
        $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);

        // Mengatur mode error PDO ke Exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $conn; // Kembalikan objek koneksi
    } catch (PDOException $e) {
        // Catat kesalahan ke log server
        error_log("Database Connection Error: " . $e->getMessage(), 0);

        // Jika produksi, jangan tampilkan pesan kesalahan sensitif
        die("Koneksi ke database gagal. Silakan coba lagi nanti.");
    }
}

// $host = 'localhost';
// $db_name = 'kabare_db';
// $username = 'root'; // Ganti dengan username database Anda
// $password = ''; // Ganti dengan password database Anda

// try {
//     $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
//     $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// } catch (PDOException $e) {
//     echo "Koneksi gagal: " . $e->getMessage();
// }

?>