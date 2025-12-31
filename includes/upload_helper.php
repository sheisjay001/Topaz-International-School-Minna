<?php
function uploadProfileImage($conn, $table, $user_id, $file) {
    // 1. Check for Cloudinary Credentials (Environment Variables)
    // Try getenv first, then $_ENV/$_SERVER as fallback
    $cloud_name = getenv('CLOUDINARY_CLOUD_NAME') ?: ($_ENV['CLOUDINARY_CLOUD_NAME'] ?? ($_SERVER['CLOUDINARY_CLOUD_NAME'] ?? null));
    $api_key    = getenv('CLOUDINARY_API_KEY')    ?: ($_ENV['CLOUDINARY_API_KEY']    ?? ($_SERVER['CLOUDINARY_API_KEY']    ?? null));
    $api_secret = getenv('CLOUDINARY_API_SECRET') ?: ($_ENV['CLOUDINARY_API_SECRET'] ?? ($_SERVER['CLOUDINARY_API_SECRET'] ?? null));

    $is_vercel  = getenv('VERCEL') || (isset($_SERVER['VERCEL']) && $_SERVER['VERCEL']);

    // Common Validation
    $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
    $fileName = basename($file['name']);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($file['size'] > 5000000) {
        return ['status' => 'error', 'message' => 'File is too large. Max 5MB allowed.'];
    }

    if (!in_array($fileType, $allowTypes)) {
        return ['status' => 'error', 'message' => 'Sorry, only JPG, JPEG, PNG, & GIF files are allowed.'];
    }

    // --- Option A: Cloudinary Upload (Preferred if Configured) ---
    if ($cloud_name && $api_key && $api_secret) {
        $timestamp = time();
        $params = [
            'timestamp' => $timestamp,
            'folder'    => 'topaz_schools/profile_images',
            'public_id' => $table . '_' . $user_id . '_' . $timestamp
        ];

        // Generate Signature
        ksort($params);
        $stringToSign = "";
        foreach ($params as $key => $value) {
            $stringToSign .= "$key=$value&";
        }
        $stringToSign = rtrim($stringToSign, "&");
        $stringToSign .= $api_secret;
        $signature = sha1($stringToSign);

        // Prepare POST fields
        $postFields = $params;
        $postFields['api_key'] = $api_key;
        $postFields['signature'] = $signature;
        $postFields['file'] = new CURLFile($file['tmp_name']);

        // Execute Upload
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.cloudinary.com/v1_1/$cloud_name/image/upload");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            $data = json_decode($response, true);
            $secure_url = $data['secure_url'];

            // Update Database
            $sql = "UPDATE $table SET photo = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $secure_url, $user_id);

            if ($stmt->execute()) {
                return ['status' => 'success', 'message' => 'Profile picture updated successfully.', 'path' => $secure_url];
            } else {
                return ['status' => 'error', 'message' => 'Database update failed: ' . $conn->error];
            }
        } else {
            $error_data = json_decode($response, true);
            $error_msg = $error_data['error']['message'] ?? 'Unknown Cloudinary error';
            return ['status' => 'error', 'message' => 'Cloudinary Upload Failed: ' . $error_msg];
        }
    }
    
    // --- Option B: Local File Upload (Fallback for Standard Hosting) ---
    elseif (!$is_vercel) {
        $target_dir = __DIR__ . "/../uploads/profile_images/";
        
        if (!file_exists($target_dir)) {
            if (!mkdir($target_dir, 0777, true)) {
                return ['status' => 'error', 'message' => 'Failed to create upload directory.'];
            }
        }

        $newFileName = $table . '_' . $user_id . '_' . time() . '.' . $fileType;
        $targetFilePath = $target_dir . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            $db_path = "uploads/profile_images/" . $newFileName;
            
            $sql = "UPDATE $table SET photo = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $db_path, $user_id);

            if ($stmt->execute()) {
                return ['status' => 'success', 'message' => 'Profile picture updated successfully.', 'path' => $db_path];
            } else {
                return ['status' => 'error', 'message' => 'Database update failed: ' . $conn->error];
            }
        } else {
            return ['status' => 'error', 'message' => 'Sorry, there was an error uploading your file to server.'];
        }
    }

    // --- Option C: Error (Vercel but No Cloudinary) ---
    else {
        return ['status' => 'error', 'message' => 'Serverless environment detected. Please configure CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, and CLOUDINARY_API_SECRET environment variables.'];
    }
}
?>