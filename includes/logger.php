<?php
/**
 * Audit Logger Helper
 */

if (!function_exists('log_activity')) {
function log_activity($conn, $user_id, $user_type, $activity, $details = null) {
    // Ensure table exists (in a real app, this should be in migration/setup)
    // We assume the table `activity_logs` exists as seen in student_login.php
    
    // Check if table has 'details' column, if not, we might need to alter it or ignore details
    // For safety, we'll try to insert. If it fails due to column missing, we should handle it.
    // However, given I can't easily alter table schema without being sure, I'll stick to the known schema:
    // user_id, user_type, activity, ip_address.
    // If we want 'details', we should add that column.
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $details_str = is_array($details) ? json_encode($details) : $details;
    
    // Prepare statement
    // Note: Assuming 'details' column exists. If not, this query will fail. 
    // I will check `student_login.php` again. It used:
    // INSERT INTO activity_logs (user_id, user_type, activity, ip_address)
    // So 'details' column might not exist. I'll stick to the known schema for now and append details to activity if needed.
    
    if ($details) {
        $activity .= " - " . $details_str;
    }
    
    // Truncate activity to fit if necessary (assuming 255 chars)
    if (strlen($activity) > 255) {
        $activity = substr($activity, 0, 252) . '...';
    }

    // Use 'timestamp' column as per setup_security.php, not 'created_at'
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, activity, ip_address, timestamp) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $user_type, $activity, $ip_address);
        $stmt->execute();
        $stmt->close();
    }
}
}
