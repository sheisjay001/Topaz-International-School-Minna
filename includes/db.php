<?php
include_once __DIR__ . '/config.php';
include_once __DIR__ . '/security.php';

if (IS_DEV) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

// Get environment variables or fallback to provided TiDB credentials
$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$dbname = DB_NAME;
$port = DB_PORT;

// Suppress default error handling to show custom page
mysqli_report(MYSQLI_REPORT_OFF);

// Initialize MySQLi
$conn = mysqli_init();

// TiDB requires SSL/TLS.
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

// Connect with timeout and SSL
if (!@mysqli_real_connect($conn, $host, $user, $pass, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
    // If connection fails, show a nice error page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Error</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; }
            .error-card { max-width: 500px; padding: 2rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        </style>
    </head>
    <body>
        <div class="card error-card border-danger">
            <div class="card-body text-center">
                <h1 class="text-danger display-1 mb-3"><i class="fas fa-exclamation-triangle"></i></h1>
                <h3 class="card-title text-danger fw-bold">Database Error</h3>
                <p class="card-text text-muted">We could not connect to the school database.</p>
                <div class="alert alert-warning text-start small">
                    <strong>Technical Details:</strong><br>
                    <?php echo mysqli_connect_error(); ?>
                </div>
                <p class="small text-muted mt-3">
                    <strong>Possible Solutions:</strong><br>
                    1. Check internet connection (Cloud DB requires internet).<br>
                    2. If on Vercel, check Environment Variables.<br>
                    3. Check if database credentials are correct.
                </p>
                <a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">Return to Home</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Ensure DB Schema is up to date (Tables must exist BEFORE session starts)
// include_once __DIR__ . '/db_setup.php'; // DISABLED for performance on Vercel. Run setup_db.php manually.

// Custom Session Handler for Vercel (Database-backed)
class DbSessionHandler implements SessionHandlerInterface {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    #[\ReturnTypeWillChange]
    public function open($savePath, $sessionName) { return true; }

    #[\ReturnTypeWillChange]
    public function close() { return true; }

    #[\ReturnTypeWillChange]
    public function read($id) {
        $stmt = $this->conn->prepare("SELECT data FROM sessions WHERE id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['data'];
        }
        return "";
    }

    #[\ReturnTypeWillChange]
    public function write($id, $data) {
        $timestamp = time();
        $stmt = $this->conn->prepare("REPLACE INTO sessions (id, data, timestamp) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $id, $data, $timestamp);
        return $stmt->execute();
    }

    #[\ReturnTypeWillChange]
    public function destroy($id) {
        $stmt = $this->conn->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->bind_param("s", $id);
        return $stmt->execute();
    }

    #[\ReturnTypeWillChange]
    public function gc($maxlifetime) {
        $old = time() - $maxlifetime;
        $stmt = $this->conn->prepare("DELETE FROM sessions WHERE timestamp < ?");
        $stmt->bind_param("i", $old);
        return $stmt->execute();
    }
}

// Set Custom Session Handler
if (session_status() == PHP_SESSION_NONE) {
    $handler = new DbSessionHandler($conn);
    session_set_save_handler($handler, true);
    session_start();
}

