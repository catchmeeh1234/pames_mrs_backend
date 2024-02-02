<?php
    // your-protected-route.php
    include_once "../includes/class-autoload.inc.php";
    $authInstance = new Auth();

    $headers = getallheaders();
    $token = isset($headers['Authorization']) ? explode(' ', $headers['Authorization'])[1] : null;

    if ($token && $authInstance->validateJWT($token)) {
        // Proceed with your protected logic
        echo json_encode(array('message' => 'Access granted!'));
    } else {
        http_response_code(401);
        echo json_encode(array('message' => 'Unauthorized'));
    }