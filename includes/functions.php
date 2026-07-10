<?php
// includes/functions.php

// Memuat konfigurasi database
require_once __DIR__ . '/../config/database.php';

/**
 * Memeriksa apakah admin sudah login
 * @return bool
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Membatasi akses halaman hanya untuk admin (redirect ke login jika belum login)
 */
function require_admin_login() {
    if (!is_admin_logged_in()) {
        $_SESSION['error'] = "Anda harus login terlebih dahulu untuk mengakses halaman ini.";
        header("Location: login.php");
        exit();
    }
}

/**
 * Melakukan proses login admin
 * @param PDO $pdo
 * @param string $username
 * @param string $password
 * @return bool
 */
function login_admin($pdo, $username, $password) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id_admin'];
            $_SESSION['admin_username'] = $admin['username'];
            return true;
        }
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Mendapatkan semua data pendaftaran tiket (dengan pencarian opsional)
 * @param PDO $pdo
 * @param string $search
 * @return array
 */
function get_all_tickets($pdo, $search = '') {
    try {
        if ($search !== '') {
            $sql = "SELECT * FROM pendaftaran_tiket WHERE nama_pemesan LIKE :search OR email LIKE :search ORDER BY tanggal_pesan DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['search' => "%$search%"]);
        } else {
            $sql = "SELECT * FROM pendaftaran_tiket ORDER BY tanggal_pesan DESC";
            $stmt = $pdo->query($sql);
        }
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        die("Gagal memuat data pendaftaran: " . $e->getMessage());
    }
}

/**
 * Mendapatkan data tiket berdasarkan ID
 * @param PDO $pdo
 * @param int $id
 * @return array|false
 */
function get_ticket_by_id($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM pendaftaran_tiket WHERE id_tiket = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        die("Gagal mengambil data tiket: " . $e->getMessage());
    }
}

/**
 * Mendapatkan data tiket berdasarkan Email
 * @param PDO $pdo
 * @param string $email
 * @return array|false
 */
function get_ticket_by_email($pdo, $email) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM pendaftaran_tiket WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        die("Gagal mencari tiket: " . $e->getMessage());
    }
}

/**
 * Mendaftarkan tiket baru ke database
 * @param PDO $pdo
 * @param string $nama
 * @param string $email
 * @param string $kategori
 * @param string $paket_hari
 * @param string $status_pembayaran
 * @param string|null $metode_pembayaran
 * @param string|null $kode_pembayaran
 * @return int|bool Mengembalikan ID tiket yang baru saja dibuat jika berhasil, atau false
 */
function create_ticket($pdo, $nama, $email, $kategori, $paket_hari = '2-Day Pass', $status_pembayaran = 'Pending', $metode_pembayaran = null, $kode_pembayaran = null) {
    try {
        $sql = "INSERT INTO pendaftaran_tiket (nama_pemesan, email, kategori_tiket, paket_hari, status_pembayaran, metode_pembayaran, kode_pembayaran, tanggal_pesan) 
                VALUES (:nama, :email, :kategori, :hari, :status, :metode, :kode, NOW())";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            'nama' => $nama,
            'email' => $email,
            'kategori' => $kategori,
            'hari' => $paket_hari,
            'status' => $status_pembayaran,
            'metode' => $metode_pembayaran,
            'kode' => $kode_pembayaran
        ]);
        if ($success) {
            if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
                return $pdo->lastInsertId('pendaftaran_tiket_id_tiket_seq');
            }
            return $pdo->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Memperbarui data tiket yang sudah ada
 * @param PDO $pdo
 * @param int $id
 * @param string $nama
 * @param string $email
 * @param string $kategori
 * @param string $paket_hari
 * @param string $status_pembayaran
 * @param string|null $metode_pembayaran
 * @param string|null $kode_pembayaran
 * @return bool
 */
function update_ticket($pdo, $id, $nama, $email, $kategori, $paket_hari = '2-Day Pass', $status_pembayaran = 'Pending', $metode_pembayaran = null, $kode_pembayaran = null) {
    try {
        $sql = "UPDATE pendaftaran_tiket SET 
                nama_pemesan = :nama, 
                email = :email, 
                kategori_tiket = :kategori, 
                paket_hari = :hari,
                status_pembayaran = :status, 
                metode_pembayaran = :metode, 
                kode_pembayaran = :kode 
                WHERE id_tiket = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'nama' => $nama,
            'email' => $email,
            'kategori' => $kategori,
            'hari' => $paket_hari,
            'status' => $status_pembayaran,
            'metode' => $metode_pembayaran,
            'kode' => $kode_pembayaran,
            'id' => $id
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Memperbarui status pembayaran tiket (digunakan saat checkout/proses bayar)
 * @param PDO $pdo
 * @param int $id
 * @param string $status
 * @param string $metode
 * @param string $kode
 * @return bool
 */
function update_payment_status($pdo, $id, $status, $metode, $kode) {
    try {
        $sql = "UPDATE pendaftaran_tiket SET 
                status_pembayaran = :status, 
                metode_pembayaran = :metode, 
                kode_pembayaran = :kode 
                WHERE id_tiket = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'status' => $status,
            'metode' => $metode,
            'kode' => $kode,
            'id' => $id
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Menghapus data tiket dari database
 * @param PDO $pdo
 * @param int $id
 * @return bool
 */
function delete_ticket($pdo, $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM pendaftaran_tiket WHERE id_tiket = :id");
        return $stmt->execute(['id' => $id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Memeriksa apakah email sudah terdaftar di database
 * @param PDO $pdo
 * @param string $email
 * @param int $exclude_id (opsional, untuk case update)
 * @return bool
 */
function is_email_registered($pdo, $email, $exclude_id = 0) {
    try {
        if ($exclude_id > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pendaftaran_tiket WHERE email = :email AND id_tiket != :id");
            $stmt->execute(['email' => $email, 'id' => $exclude_id]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pendaftaran_tiket WHERE email = :email");
            $stmt->execute(['email' => $email]);
        }
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        die("Gagal memvalidasi keunikan email: " . $e->getMessage());
    }
}

/**
 * Mengambil data statistik jumlah tiket berdasarkan kategorinya
 * @param PDO $pdo
 * @return array
 */
function get_ticket_statistics($pdo) {
    try {
        return [
            'total' => $pdo->query("SELECT COUNT(*) FROM pendaftaran_tiket")->fetchColumn(),
            'reguler' => $pdo->query("SELECT COUNT(*) FROM pendaftaran_tiket WHERE kategori_tiket = 'Reguler'")->fetchColumn(),
            'vip' => $pdo->query("SELECT COUNT(*) FROM pendaftaran_tiket WHERE kategori_tiket = 'VIP'")->fetchColumn(),
            'vvip' => $pdo->query("SELECT COUNT(*) FROM pendaftaran_tiket WHERE kategori_tiket = 'VVIP'")->fetchColumn(),
        ];
    } catch (PDOException $e) {
        die("Gagal memuat statistik dashboard: " . $e->getMessage());
    }
}

/**
 * Memformat string tanggal database menjadi format bahasa Indonesia
 * @param string $dateStr
 * @return string
 */
function format_tanggal_indo($dateStr) {
    $timestamp = strtotime($dateStr);
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $numHari = date('w', $timestamp);
    $tgl = date('j', $timestamp);
    $numBulan = date('n', $timestamp);
    $tahun = date('Y', $timestamp);
    $jam = date('H:i', $timestamp);
    
    return $hari[$numHari] . ", " . $tgl . " " . $bulan[$numBulan] . " " . $tahun . " - " . $jam . " WIB";
}
?>
