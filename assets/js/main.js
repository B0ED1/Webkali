/**
 * AidFest - Main Client-Side Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Konfirmasi Hapus Data
    const deleteButtons = document.querySelectorAll('.btn-delete-confirm');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const deleteUrl = this.getAttribute('href');
            const pemesanName = this.getAttribute('data-name') || 'pemesan ini';
            
            // Menggunakan confirm bawaan dengan pesan interaktif
            if (confirm(`Apakah Anda yakin ingin menghapus tiket atas nama "${pemesanName}" secara permanen? Action ini tidak dapat dibatalkan.`)) {
                window.location.href = deleteUrl;
            }
        });
    });

    // 2. Validasi Form Real-time (Bootstrap custom validation)
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            // Validasi format email secara custom
            const emailInput = form.querySelector('input[type="email"]');
            const nameInput = form.querySelector('input[name="nama_pemesan"]');
            
            let customValid = true;

            if (nameInput && nameInput.value.trim().length < 3) {
                nameInput.setCustomValidity('Nama minimal harus 3 karakter.');
                customValid = false;
            } else if (nameInput) {
                nameInput.setCustomValidity('');
            }

            if (!form.checkValidity() || !customValid) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });

    // 3. Efek Input Focus Glow
    const inputs = document.querySelectorAll('.form-control, .form-select');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.classList.add('focused');
        });
        input.addEventListener('blur', () => {
            if (input.value === '') {
                input.parentElement.classList.remove('focused');
            }
        });
    });

    // 4. Auto-fade alert banners after 5 seconds
    const alerts = document.querySelectorAll('.alert-aidfest');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 600);
        }, 5000);
    });
});
