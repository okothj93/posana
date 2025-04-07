// In process_mpesa.php
function initiateSTKPush($phone, $amount, $lease_id) {
    $consumerKey = 'your_consumer_key';
    $consumerSecret = 'your_consumer_secret';
    $BusinessShortCode = 'your_shortcode';
    $Passkey = 'your_passkey';
    $TransactionType = 'CustomerPayBillOnline';
    $CallBackURL = 'https://yourdomain.com/mpesa_callback.php';
    $AccountReference = 'Lease' . $lease_id;
    $TransactionDesc = 'Car Rental Payment';
    
    // Get access token
    $credentials = base64_encode($consumerKey . ':' . $consumerSecret);
    $ch = curl_init('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    $token = json_decode($response)->access_token;
    
    // Prepare STK Push
    $Timestamp = date('YmdHis');
    $Password = base64_encode($BusinessShortCode . $Passkey . $Timestamp);
    
    $stk_push_data = [
        'BusinessShortCode' => $BusinessShortCode,
        'Password' => $Password,
        'Timestamp' => $Timestamp,
        'TransactionType' => $TransactionType,
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => $BusinessShortCode,
        'PhoneNumber' => $phone,
        'CallBackURL' => $CallBackURL,
        'AccountReference' => $AccountReference,
        'TransactionDesc' => $TransactionDesc
    ];
    
    // Initiate STK Push
    $ch = curl_init('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stk_push_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}