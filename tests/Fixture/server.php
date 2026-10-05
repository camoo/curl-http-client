<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($uri === '/redirect') {
    header('Location: /destination', true, 302);
    exit;
}

if ($uri === '/destination') {
    header('Content-Type: application/json');
    echo json_encode(['redirected' => true]);
    exit;
}

if ($uri === '/auth') {
    $user = $_SERVER['PHP_AUTH_USER'] ?? '';
    $pass = $_SERVER['PHP_AUTH_PW'] ?? '';
    if ($user === 'testuser' && $pass === 'testpass') {
        header('Content-Type: application/json');
        echo json_encode(['authenticated' => true]);
    } else {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['authenticated' => false]);
    }
    exit;
}

if ($uri === '/post') {
    header('Content-Type: application/json');
    $body = file_get_contents('php://input');
    if ($body === '' && !empty($_POST)) {
        $body = http_build_query($_POST);
    }
    echo json_encode(['received' => $body]);
    exit;
}

if ($uri === '/headers') {
    header('Content-Type: application/json');
    header('X-Custom-Response: Header1');
    header('X-Custom-Response: Header2', false);
    echo json_encode(['headers' => getallheaders()]);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);
