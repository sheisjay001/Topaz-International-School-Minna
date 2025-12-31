<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

if (isset($_GET['reference'])) {
    $reference = $_GET['reference'];
    
    // VERIFY TRANSACTION WITH PAYSTACK API
    // Replace 'sk_test_xxxxxxxxxxxxxxxxxxxx' with your SECRET KEY
    $secret_key = 'sk_test_xxxxxxxxxxxxxxxxxxxx'; 
    
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer " . $secret_key,
            "Cache-Control: no-cache",
        ),
    ));
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    
    if ($err) {
        die("cURL Error: " . $err);
    }
    
    $result = json_decode($response);
    
    // Check if verification was successful
    // Note: Since we are using a placeholder key, this will likely fail or return invalid.
    // For testing without a real key, you might want to uncomment the line below to bypass verification:
    // $result = (object)['status' => true, 'data' => (object)['status' => 'success', 'amount' => 500000]]; // MOCK DATA
    
    if ($result->status && $result->data->status == 'success') {
        // Payment was successful
        $amount_paid = $result->data->amount / 100; // Convert kobo to naira
        $student_id = $_SESSION['student_id'];
        $receipt_no = 'RCT-' . strtoupper(substr(md5(uniqid()), 0, 8));
        
        // Get Term and Session from URL parameters (passed from fees.php)
        $term = isset($_GET['term']) ? $_GET['term'] : 'First Term';
        $session = isset($_GET['session']) ? $_GET['session'] : '2024/2025';
        
        $payment_method = 'Paystack';
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO payments (student_id, amount_paid, payment_date, payment_method, receipt_no, term, session) VALUES (?, ?, NOW(), ?, ?, ?, ?)");
        $stmt->bind_param("idssss", $student_id, $amount_paid, $payment_method, $receipt_no, $term, $session);
        
        if ($stmt->execute()) {
            // Redirect to fees page with success
            header("Location: fees.php?status=success&receipt=" . $receipt_no);
            exit();
        } else {
            die("Database Error: " . $stmt->error);
        }
        
    } else {
        // Transaction failed or invalid key
        header("Location: fees.php?status=error&message=Verification failed");
        exit();
    }

} else {
    header("Location: fees.php");
    exit();
}
?>