<?php
// Pengaturan koneksi database
$host = 'localhost';
$dbname = 'proyek_leasing'; // PASTIKAN NAMA INI SESUAI DENGAN DATABASE ANDA
$user = 'root';
$pass = ''; // Biasanya kosong jika menggunakan XAMPP default
$charset = 'utf8mb4';

// Konfigurasi untuk PDO
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Baris ini membuat variabel $pdo yang akan digunakan di dashboard.php
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>