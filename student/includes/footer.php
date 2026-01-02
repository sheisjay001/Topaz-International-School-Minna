
<!-- Footer -->
<footer class="footer mt-auto py-3 bg-white text-center border-top">
    <div class="container">
        <span class="text-muted">© <?php echo date('Y'); ?> Topaz International School Minna. All rights reserved.</span>
    </div>
</footer>

<!-- Mobile Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
    <!-- Toasts will be injected here -->
</div>

<!-- Session Timeout Modal -->
<div class="modal fade" id="sessionTimeoutModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-danger"><i class="fas fa-clock me-2"></i>Session Timeout</h5>
            </div>
            <div class="modal-body">
                Your session will expire in <span id="timeout-countdown" class="fw-bold">2:00</span> minutes. Click "Stay Logged In" to continue.
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" onclick="logoutUser()">Logout</button>
                <button type="button" class="btn btn-primary" onclick="resetSessionTimer()">Stay Logged In</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Scripts -->
<script>
    // --- Mobile Sidebar Toggle ---
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarClose = document.getElementById('sidebarClose');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            if (sidebarOverlay) sidebarOverlay.classList.toggle('active');
        }

        function closeSidebar() {
            sidebar.classList.remove('active');
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
        }

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                toggleSidebar();
            });
        }

        if (sidebarClose) {
            sidebarClose.addEventListener('click', function(e) {
                e.preventDefault();
                closeSidebar();
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebar);
        }
    });

    // --- PWA Registration ---
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('../sw.js').then(registration => {
                console.log('SW registered: ', registration);
            }).catch(registrationError => {
                console.log('SW registration failed: ', registrationError);
            });
        });
    }

    // --- Toast Notification System ---
    function showToast(message, type = 'info') {
        const toastContainer = document.querySelector('.toast-container');
        const toastEl = document.createElement('div');
        const bgClass = type === 'error' ? 'bg-danger' : (type === 'success' ? 'bg-success' : 'bg-primary');
        
        toastEl.className = `toast align-items-center text-white ${bgClass} border-0 mb-2`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        toastContainer.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        
        // Remove from DOM after hidden
        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    }

    // --- Session Timeout Logic ---
    let warningTimeout, logoutTimeout, countdownInterval;
    const WARNING_TIME = 15 * 60 * 1000; // 15 minutes
    const LOGOUT_TIME = 17 * 60 * 1000;  // 17 minutes (2 mins after warning)
    
    function startSessionTimer() {
        clearTimeout(warningTimeout);
        clearTimeout(logoutTimeout);
        clearInterval(countdownInterval);
        
        warningTimeout = setTimeout(showTimeoutWarning, WARNING_TIME);
        logoutTimeout = setTimeout(logoutUser, LOGOUT_TIME);
    }
    
    function resetSessionTimer() {
        // Reset timers
        startSessionTimer();
        
        // Hide modal
        const modalEl = document.getElementById('sessionTimeoutModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.hide();
        
        // Optional: Ping server to keep PHP session alive
        // fetch('api/keep_alive.php');
    }
    
    function showTimeoutWarning() {
        const modalEl = document.getElementById('sessionTimeoutModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        
        // Start Countdown
        let secondsLeft = 120;
        const countdownEl = document.getElementById('timeout-countdown');
        
        countdownInterval = setInterval(() => {
            secondsLeft--;
            const m = Math.floor(secondsLeft / 60);
            const s = secondsLeft % 60;
            countdownEl.textContent = `${m}:${s < 10 ? '0' : ''}${s}`;
            
            if (secondsLeft <= 0) {
                clearInterval(countdownInterval);
                logoutUser();
            }
        }, 1000);
    }
    
    function logoutUser() {
        window.location.href = 'logout.php';
    }
    
    // Reset timer on user activity
    window.onload = startSessionTimer;
    document.onmousemove = () => { clearTimeout(warningTimeout); clearTimeout(logoutTimeout); startSessionTimer(); }; // Simple debounce could be better
    document.onkeypress = startSessionTimer;

    // --- Real-Time Notifications Polling ---
    function checkNotifications() {
        fetch('api/check_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.count > 0) {
                    // Find bell icon and add red dot if not present
                    const bellIcons = document.querySelectorAll('.fa-bell');
                    bellIcons.forEach(icon => {
                        const parent = icon.parentElement;
                        // Avoid duplicate badges
                        if (parent.querySelector('.notification-badge')) return;
    
                        const badge = document.createElement('span');
                        badge.className = 'position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle notification-badge';
                        badge.style.width = '10px';
                        badge.style.height = '10px';
                        
                        if (getComputedStyle(parent).position === 'static') {
                            parent.style.position = 'relative';
                        }
                        
                        parent.appendChild(badge);
                        
                        // Optional: Toast for new notification
                        // showToast(`You have ${data.count} new notifications`, 'info');
                    });
                }
            })
            .catch(err => console.error('Notification check failed', err));
    }
    
    // Poll every 60 seconds
    setInterval(checkNotifications, 60000);
    // Check immediately on load
    checkNotifications();
</script>
</body>
</html>
