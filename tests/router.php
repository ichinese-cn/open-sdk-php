<?php
if ($_SERVER['REQUEST_URI'] === '/redirect') {
    header('Location: /echo', true, 302);
    return;
}
header('Content-Type: application/json');
$body = file_get_contents('php://input');
echo json_encode(['method' => $_SERVER['REQUEST_METHOD'], 'size' => strlen($body), 'hash' => hash('sha256', $body), 'authorization' => isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : null]);
