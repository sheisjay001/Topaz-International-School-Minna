// Initialize AOS (Animate On Scroll)
document.addEventListener('DOMContentLoaded', function() {
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true,
        mirror: false,
    });

    // Initialize SweetAlert Toast
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    window.showToast = function(icon, title) {
        Toast.fire({
            icon: icon,
            title: title
        });
    };

    // Global Loading State for Forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Check if form is valid (if browser validation passes)
            if (this.checkValidity()) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.classList.contains('no-loading')) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading...';
                    
                    // Store original text to restore if needed (e.g. via timeout or if page doesn't reload)
                    // But usually page reloads or redirects.
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }, 10000); // Reset after 10s just in case
                }
            }
        });
    });
});
