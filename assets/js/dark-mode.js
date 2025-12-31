document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('darkModeToggle');
    const body = document.body;
    
    // Check if toggle exists (it might not be on every page if I missed one)
    if (!toggle) return;

    const icon = toggle.querySelector('i');
    const text = toggle.lastChild; // The text node " Dark Mode"

    // Function to set mode
    function setDarkMode(isDark) {
        if (isDark) {
            body.classList.add('dark-mode');
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
            if (text.nodeType === 3) text.textContent = " Light Mode";
        } else {
            body.classList.remove('dark-mode');
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
            if (text.nodeType === 3) text.textContent = " Dark Mode";
        }
    }

    // Check preference on load
    if (localStorage.getItem('darkMode') === 'enabled') {
        setDarkMode(true);
    }

    toggle.addEventListener('click', (e) => {
        e.preventDefault();
        
        if (body.classList.contains('dark-mode')) {
            setDarkMode(false);
            localStorage.setItem('darkMode', 'disabled');
        } else {
            setDarkMode(true);
            localStorage.setItem('darkMode', 'enabled');
        }
    });
});
