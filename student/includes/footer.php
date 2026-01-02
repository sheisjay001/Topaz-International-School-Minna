
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
                    if (parent.querySelector('.notification-badge')) return;

                    const badge = document.createElement('span');
                    badge.className = 'position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle notification-badge';
                    badge.style.width = '10px';
                    badge.style.height = '10px';
                    
                    if (getComputedStyle(parent).position === 'static') {
                        parent.style.position = 'relative';
                    }
                    
                    parent.appendChild(badge);
                    
                    // Show toast only once per session or update logic
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