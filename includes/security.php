<?php
/**
 * Security Helper Functions
 */

// Set Secure Headers
if (!function_exists('set_security_headers')) {
function set_security_headers() {
    // Prevent Clickjacking
    header("X-Frame-Options: SAMEORIGIN");
    
    // XSS Protection (for older browsers)
    header("X-XSS-Protection: 1; mode=block");
    
    // Prevent MIME Type Sniffing
    header("X-Content-Type-Options: nosniff");
    
    // Referrer Policy
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // Strict Transport Security (HSTS) - Enabled for production (1 year)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    }
    
    // Content Security Policy (CSP) - Tuned for Bootstrap, FontAwesome, Google Fonts, SweetAlert2, Paystack
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://js.paystack.co; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://unpkg.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data: https://ui-avatars.com; connect-src 'self' https://*.paystack.co https://*.paystack.com; frame-src 'self' https://js.paystack.co https://standard.paystack.co;");

    // Permissions Policy (F.K.A Feature Policy) - Lock down sensitive features
    header("Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()");
}
}

// Sanitize Output (Anti-XSS)
if (!function_exists('e')) {
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
}

// Generate CSRF Token
if (!function_exists('generate_csrf_token')) {
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
}

// Verify CSRF Token
if (!function_exists('verify_csrf_token')) {
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die("CSRF Validation Failed");
    }
    return true;
}
}

// Initialize Security
if (!defined('SECURITY_HEADERS_SET')) {
    set_security_headers();
    define('SECURITY_HEADERS_SET', true);
}
