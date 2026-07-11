<?php
// config/mail.php
// Konfigurasi SMTP untuk Pengiriman Email E-Ticket AidFest

return [
    // Ganti ke true untuk mengaktifkan pengiriman email asli ke internet
    'mail_enabled' => true, 

    // Konfigurasi Server SMTP untuk Gmail SMTP
    'smtp_host'     => 'smtp.gmail.com',           // Host SMTP Gmail
    'smtp_port'     => 587,                        // Port TLS Gmail
    'smtp_secure'   => 'tls',                      // Jenis enkripsi
    'smtp_user'     => 'budiarif396@gmail.com', 
    'smtp_pass'     => 'qawlmxakhxbeefqa',

    // Pengaturan Pengirim
    'from_email'    => 'budiarif396@gmail.com',
    'from_name'     => 'AidFest 2026 E-Ticket Support',
];
