<?php
$host = 'localhost';
$db   = 'db_aidfest';
$user = 'root';
$pass = ''; // Default password untuk MySQL di XAMPP biasanya kosong
$charset = 'utf8mb4';

try {
    // Koneksi ke server MySQL tanpa memilih database terlebih dahulu
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Membuat database jika belum ada
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db`");

    // 1. Membuat/Menyesuaikan Tabel Pendaftaran Tiket
    $tableExists = $pdo->query("SHOW TABLES LIKE 'pendaftaran_tiket'")->rowCount() > 0;
    if (!$tableExists) {
        $sql = "CREATE TABLE pendaftaran_tiket (
            id_tiket INT AUTO_INCREMENT PRIMARY KEY,
            nama_pemesan VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            kategori_tiket ENUM('Reguler', 'VIP', 'VVIP') NOT NULL,
            paket_hari ENUM('Day 1', 'Day 2', '2-Day Pass') DEFAULT '2-Day Pass',
            status_pembayaran ENUM('Pending', 'Lunas', 'Batal') DEFAULT 'Pending',
            metode_pembayaran VARCHAR(50) DEFAULT NULL,
            kode_pembayaran VARCHAR(50) DEFAULT NULL,
            tanggal_pesan DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $pdo->exec($sql);

        // Menambahkan data awal (seeding) untuk keperluan demo
        $stmt = $pdo->prepare("INSERT INTO pendaftaran_tiket (nama_pemesan, email, kategori_tiket, paket_hari, status_pembayaran, metode_pembayaran, kode_pembayaran, tanggal_pesan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['Rian Hidayat', 'rian.hidayat@example.com', 'VIP', '2-Day Pass', 'Lunas', 'Transfer Bank BCA', 'TRX-78321', date('Y-m-d H:i:s', strtotime('-2 days'))]);
        $stmt->execute(['Amelia Putri', 'amelia.putri@example.com', 'VVIP', '2-Day Pass', 'Pending', null, null, date('Y-m-d H:i:s', strtotime('-1 day'))]);
        $stmt->execute(['Budi Santoso', 'budi.santoso@example.com', 'Reguler', 'Day 1', 'Lunas', 'GoPay', 'TRX-90124', date('Y-m-d H:i:s')]);
    } else {
        // Migrasi kolom jika tabel sudah ada dari langkah sebelumnya
        $columns = $pdo->query("DESCRIBE pendaftaran_tiket")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('paket_hari', $columns)) {
            $pdo->exec("ALTER TABLE pendaftaran_tiket ADD COLUMN paket_hari ENUM('Day 1', 'Day 2', '2-Day Pass') DEFAULT '2-Day Pass' AFTER kategori_tiket");
        }
        if (!in_array('status_pembayaran', $columns)) {
            $pdo->exec("ALTER TABLE pendaftaran_tiket ADD COLUMN status_pembayaran ENUM('Pending', 'Lunas', 'Batal') DEFAULT 'Pending' AFTER kategori_tiket");
        }
        if (!in_array('metode_pembayaran', $columns)) {
            $pdo->exec("ALTER TABLE pendaftaran_tiket ADD COLUMN metode_pembayaran VARCHAR(50) DEFAULT NULL AFTER status_pembayaran");
        }
        if (!in_array('kode_pembayaran', $columns)) {
            $pdo->exec("ALTER TABLE pendaftaran_tiket ADD COLUMN kode_pembayaran VARCHAR(50) DEFAULT NULL AFTER metode_pembayaran");
        }
    }

    // 2. Membuat Tabel Admin jika belum ada
    $adminTableExists = $pdo->query("SHOW TABLES LIKE 'admin'")->rowCount() > 0;
    if (!$adminTableExists) {
        $sqlAdmin = "CREATE TABLE admin (
            id_admin INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $pdo->exec($sqlAdmin);

        // Menambahkan akun admin bawaan (Username: admin, Password: password123)
        $username = 'admin';
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        $stmtAdmin = $pdo->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
        $stmtAdmin->execute([$username, $hashedPassword]);
    }
} catch (PDOException $e) {
    die("Kesalahan Koneksi Database: " . $e->getMessage());
}
?>
