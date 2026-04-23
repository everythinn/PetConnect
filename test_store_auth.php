<?php
$loginData = json_encode(['email' => 'test@test.com', 'password' => 'password123']);
$opts = [
    'http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/json',
        'content' => $loginData,
        'ignore_errors' => true
    ]
];
$context  = stream_context_create($opts);
$loginResult = file_get_contents('http://localhost:8000/api/auth/login', false, $context);
$loginJson = json_decode($loginResult, true);
if (empty($loginJson['token'])) {
    die("Login failed: $loginResult\n");
}
$token = $loginJson['token'];

$opts = [
    'http' => [
        'method'  => 'GET',
        'header'  => "Authorization: Bearer $token\r\n",
        'ignore_errors' => true
    ]
];
$context  = stream_context_create($opts);
$storeResult = file_get_contents('http://localhost:8000/api/store', false, $context);
echo "STATUS: $http_response_header[0]\n";
echo "BODY: $storeResult\n";
