<?php
// 示例路由：网页将 requestAuthCode 得到的 code 交给自己的服务端。
require __DIR__ . '/../vendor/autoload.php';
use IChinese\Open\Client;
use IChinese\Open\OpenApiException;
$client = new Client(getenv('APP_ID'), getenv('APP_SECRET'));
$input = json_decode(file_get_contents('php://input'), true);
try {
    $user = $client->exchangeAuthCode($input['code'], isset($input['redirect_uri']) ? $input['redirect_uri'] : null, isset($input['code_verifier']) ? $input['code_verifier'] : null);
    session_start();
    // 令牌存在后端会话；生产环境配置 HTTPS、HttpOnly、SameSite 和 CSRF 防护。
    $_SESSION['open_user'] = $user;
    header('Content-Type: application/json');
    echo json_encode(['open_id' => $user['open_id']]);
} catch (OpenApiException $e) {
    http_response_code($e->status ? $e->status : 502);
    echo json_encode(['code' => $e->errorCode, 'reason' => $e->reason, 'request_id' => $e->requestId]);
}
