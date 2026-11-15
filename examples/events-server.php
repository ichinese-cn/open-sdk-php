<?php
// 作为 PHP HTTP 路由使用，反向代理传递原始 X-Ich-* 标头。
require __DIR__ . '/../vendor/autoload.php';
use IChinese\Open\Events;
use IChinese\Open\OpenApiException;
$headers = [];
foreach (['Timestamp', 'Nonce', 'Signature'] as $key) {
    $serverKey = 'HTTP_X_ICH_' . strtoupper($key);
    if (isset($_SERVER[$serverKey])) $headers['X-Ich-' . $key] = $_SERVER[$serverKey];
}
try {
    $payload = Events::parseEvent($headers, file_get_contents('php://input'), getenv('SIGNING_SECRET'), getenv('ENCRYPT_KEY') ?: null);
    header('Content-Type: application/json');
    if (Events::isUrlVerification($payload)) echo json_encode(Events::challengeResponse($payload));
    else {
        // 在返回 2xx 前将事件持久化到自己的幂等队列，以 header.event_id 去重后异步处理。
        echo '{}';
    }
} catch (OpenApiException $e) {
    http_response_code($e->status);
    echo json_encode(['code' => $e->errorCode]);
}
