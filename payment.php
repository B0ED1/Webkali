<?php
// Memulai session
session_start();

// Memuat file fungsi helper
require_once 'includes/functions.php';

// Membaca ID tiket dari GET atau dari session booking terakhir
$id = 0;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
} elseif (isset($_SESSION['last_booking_id'])) {
    $id = (int)$_SESSION['last_booking_id'];
}

// Validasi ID tiket
if ($id <= 0) {
    $_SESSION['error'] = "Tidak ada transaksi pembayaran yang aktif.";
    header("Location: index.php");
    exit();
}

// Mengambil data tiket dari database
$ticket = get_ticket_by_id($pdo, $id);
if (!$ticket) {
    $_SESSION['error'] = "Data transaksi tiket tidak ditemukan.";
    header("Location: index.php");
    exit();
}

// Jika tiket sudah lunas, langsung arahkan ke E-ticket pembeli di index.php
if ($ticket['status_pembayaran'] === 'Lunas') {
    header("Location: index.php?email_search=" . urlencode($ticket['email']) . "#cek-tiket");
    exit();
}

// Menghitung nominal harga tiket berdasarkan kategori dan paket hari
$harga = 0;
if ($ticket['paket_hari'] === '2-Day Pass') {
    if ($ticket['kategori_tiket'] === 'Reguler') {
        $harga = 4500000;
    } elseif ($ticket['kategori_tiket'] === 'VIP') {
        $harga = 12000000;
    } elseif ($ticket['kategori_tiket'] === 'VVIP') {
        $harga = 35000000;
    }
} else { // Day 1 atau Day 2
    if ($ticket['kategori_tiket'] === 'Reguler') {
        $harga = 2500000;
    } elseif ($ticket['kategori_tiket'] === 'VIP') {
        $harga = 7000000;
    }
}

// Memproses pembayaran tiruan saat disubmit
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode = isset($_POST['metode_pembayaran']) ? trim($_POST['metode_pembayaran']) : '';

    if (empty($metode)) {
        $error = "Silakan pilih salah satu metode pembayaran.";
    } else {
        // Generate kode transaksi tiruan (contoh: TRX-84321)
        $kode_pembayaran = 'TRX-' . mt_rand(10000, 99999);
        
        // Memperbarui status pembayaran menjadi Lunas di database
        if (update_payment_status($pdo, $id, 'Lunas', $metode, $kode_pembayaran)) {
            // Hapus session booking sementara jika ada
            if (isset($_SESSION['last_booking_id'])) {
                unset($_SESSION['last_booking_id']);
            }
            
            $_SESSION['success'] = "Pembayaran sukses! E-Ticket Anda untuk kategori <strong>" . $ticket['kategori_tiket'] . "</strong> kini aktif.";
            header("Location: index.php?email_search=" . urlencode($ticket['email']) . "#cek-tiket");
            exit();
        } else {
            $error = "Terjadi kesalahan sistem saat memproses pembayaran. Silakan coba lagi.";
        }
    }
}

// Set base path
$base_path = '';

// Load Header Global
include_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="form-card-container" style="max-width: 600px;">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4 no-print">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-indigo"><i class="fa-solid fa-house me-1"></i>Beranda</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pembayaran Tiket</li>
            </ol>
        </nav>

        <div class="form-card">
            <!-- Header Ringkasan Pembayaran -->
            <div class="form-card-header" style="background: linear-gradient(135deg, #0f172a 0%, #312e81 100%);">
                <span class="badge bg-warning text-dark px-3 py-1 rounded-pill mb-2"><i class="fa-solid fa-clock-rotate-left me-1"></i>Menunggu Pembayaran</span>
                <h3 class="fw-bold mb-0">Checkout Pembayaran</h3>
                <p class="mb-0 opacity-75 fs-6 mt-1">Selesaikan pembayaran untuk mengaktifkan E-Ticket Anda.</p>
            </div>

            <!-- Body Checkout -->
            <div class="form-card-body">
                <!-- Tampilkan Error jika ada -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-aidfest d-flex align-items-center mb-4" role="alert">
                        <i class="fa-solid fa-circle-exclamation fs-5 me-2"></i>
                        <div><?php echo $error; ?></div>
                    </div>
                <?php endif; ?>

                <!-- Rincian Tiket / Detail Billing -->
                <div class="p-3 bg-light rounded-3 mb-4 border">
                    <h6 class="fw-bold text-slate-800 border-bottom pb-2 mb-3"><i class="fa-solid fa-file-invoice-dollar me-2 text-indigo"></i>Rincian Tagihan</h6>
                    
                    <div class="row mb-2">
                        <div class="col-6 text-slate-500">Nama Pemesan</div>
                        <div class="col-6 text-end fw-semibold text-slate-800"><?php echo htmlspecialchars($ticket['nama_pemesan']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6 text-slate-500">Alamat Email</div>
                        <div class="col-6 text-end text-slate-800" style="font-size: 0.9rem;"><?php echo htmlspecialchars($ticket['email']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6 text-slate-500">Kategori Tiket</div>
                        <div class="col-6 text-end">
                            <?php 
                            $badgeClass = '';
                            if ($ticket['kategori_tiket'] == 'Reguler') $badgeClass = 'badge-reguler';
                            elseif ($ticket['kategori_tiket'] == 'VIP') $badgeClass = 'badge-vip';
                            elseif ($ticket['kategori_tiket'] == 'VVIP') $badgeClass = 'badge-vvip';
                            ?>
                            <span class="badge badge-category <?php echo $badgeClass; ?>"><?php echo $ticket['kategori_tiket']; ?></span>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6 text-slate-500">Pilihan Hari</div>
                        <div class="col-6 text-end fw-semibold text-slate-800"><?php echo htmlspecialchars($ticket['paket_hari']); ?></div>
                    </div>
                    
                    <div class="border-top my-3"></div>
                    
                    <div class="row align-items-center">
                        <div class="col-6 fw-bold text-slate-700">Total Pembayaran</div>
                        <div class="col-6 text-end fw-bold text-indigo fs-4">Rp <?php echo number_format($harga, 0, ',', '.'); ?></div>
                    </div>
                </div>

                <!-- Form Simulasi Pembayaran -->
                <form action="payment.php?id=<?php echo $id; ?>" method="POST" id="form-pembayaran">
                    <h6 class="fw-bold text-slate-800 mb-3"><i class="fa-solid fa-credit-card me-2 text-indigo"></i>Pilih Metode Pembayaran</h6>
                    
                    <!-- Pilihan Transfer Bank -->
                    <div class="mb-3">
                        <div class="form-check p-3 border rounded-3 d-flex align-items-center cursor-pointer mb-2 hover-bg-light">
                            <input class="form-check-input ms-0 me-3" type="radio" name="metode_pembayaran" id="metode_bca" value="Transfer Bank BCA" required>
                            <label class="form-check-label w-100 cursor-pointer d-flex align-items-center justify-content-between" for="metode_bca">
                                <span class="fw-semibold text-slate-800"><i class="fa-solid fa-building-columns me-2 text-muted"></i>Transfer Bank BCA</span>
                                <span class="text-muted" style="font-size: 0.8rem;">Virtual Account</span>
                            </label>
                        </div>
                        
                        <div class="form-check p-3 border rounded-3 d-flex align-items-center cursor-pointer mb-2 hover-bg-light">
                            <input class="form-check-input ms-0 me-3" type="radio" name="metode_pembayaran" id="metode_mandiri" value="Transfer Bank Mandiri">
                            <label class="form-check-label w-100 cursor-pointer d-flex align-items-center justify-content-between" for="metode_mandiri">
                                <span class="fw-semibold text-slate-800"><i class="fa-solid fa-building-columns me-2 text-muted"></i>Transfer Bank Mandiri</span>
                                <span class="text-muted" style="font-size: 0.8rem;">Virtual Account</span>
                            </label>
                        </div>

                        <!-- Pilihan E-Wallet -->
                        <div class="form-check p-3 border rounded-3 d-flex align-items-center cursor-pointer mb-2 hover-bg-light">
                            <input class="form-check-input ms-0 me-3" type="radio" name="metode_pembayaran" id="metode_gopay" value="GoPay">
                            <label class="form-check-label w-100 cursor-pointer d-flex align-items-center justify-content-between" for="metode_gopay">
                                <span class="fw-semibold text-slate-800"><i class="fa-solid fa-wallet me-2 text-muted"></i>GoPay</span>
                                <span class="text-muted" style="font-size: 0.8rem;">Scan QRIS</span>
                            </label>
                        </div>
                        
                        <div class="form-check p-3 border rounded-3 d-flex align-items-center cursor-pointer mb-2 hover-bg-light">
                            <input class="form-check-input ms-0 me-3" type="radio" name="metode_pembayaran" id="metode_dana" value="Dana">
                            <label class="form-check-label w-100 cursor-pointer d-flex align-items-center justify-content-between" for="metode_dana">
                                <span class="fw-semibold text-slate-800"><i class="fa-solid fa-wallet me-2 text-muted"></i>DANA</span>
                                <span class="text-muted" style="font-size: 0.8rem;">Nomor Telepon</span>
                            </label>
                        </div>
                    </div>

                    <!-- Petunjuk Simulasi Pembayaran -->
                    <div class="alert alert-info border-0 bg-light-subtle text-slate-600 mb-4" style="font-size: 0.85rem; border-left: 4px solid #0dcaf0 !important;">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        <strong>Catatan Simulasi:</strong> Halaman ini mensimulasikan sistem pembayaran asli. Pilih metode pembayaran, lalu tekan tombol <strong>Bayar Sekarang</strong> untuk menyelesaikan pembelian Anda.
                    </div>

                    <!-- Tombol Konfirmasi Bayar -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-premium-primary py-2 btn-lg fs-6" id="confirm-payment-btn">
                            <i class="fa-solid fa-circle-check me-2"></i>Bayar Sekarang
                        </button>
                        <a href="home.php" class="btn btn-outline-secondary py-2" id="cancel-payment-btn">
                            Bayar Nanti & Kembali
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
// Load Footer Global
include_once 'includes/footer.php'; 
?>
