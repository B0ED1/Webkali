<?php
// config/mail.php
// Konfigurasi SMTP untuk Pengiriman Email E-Ticket AidFest

return [
    // Ganti ke true untuk mengaktifkan pengiriman email asli ke internet
    'mail_enabled' => true, 

    // Konfigurasi Server SMTP (Contoh di bawah menggunakan Mailtrap)
    'smtp_host'     => 'sandbox.smtp.mailtrap.io', // Gunakan 'smtp.gmail.com' untuk Gmail
    'smtp_port'     => 2525,                       // Gunakan 587 untuk TLS Gmail
    'smtp_secure'   => 'tls',                      // 'tls' (rekomendasi) atau 'ssl'
    'smtp_user'     => 'MASUKKAN_USERNAME_SMTP_ANDA',
    'smtp_pass'     => 'MASUKKAN_PASSWORD_SMTP_ANDA',

    // Pengaturan Pengirim
    'from_email'    => 'no-reply@aidfest.com',
    'from_name'     => 'AidFest 2026 E-Ticket Support',
];
