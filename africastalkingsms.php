function sendSMS($phone, $message) {
    // Africa's Talking API credentials
    $username = 'your_username';
    $apiKey = 'your_api_key';
    
    // Prepare data
    $recipients = $phone;
    $message = urlencode($message);
    $from = 'CARPRO'; // Your shortcode or alphanumeric
    
    // Create API URL
    $url = "https://api.africastalking.com/version1/messaging";
    $postData = [
        'username' => $username,
        'to' => $recipients,
        'message' => $message,
        'from' => $from
    ];
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apiKey: ' . $apiKey,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    // Execute and log result
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE) == 201 ? 'sent' : 'failed';
    curl_close($ch);
    
    // Log the SMS in database
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO sms_logs (recipient_phone, message, status) VALUES (?, ?, ?)");
    $stmt->execute([$phone, $message, $status]);
    
    return $status === 'sent';
}