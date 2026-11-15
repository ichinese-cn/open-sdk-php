<?php
use IChinese\Open\Transport;
// 真正启动 HTTP 替身，覆盖 cURL 请求体、文件资源和不跟随重定向。
test('真实 cURL：JSON、二进制流及重定向', function () {
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    if (!$socket) throw new Exception($error);
    $address = stream_socket_get_name($socket, false); fclose($socket);
    $command = escapeshellarg(PHP_BINARY) . ' -S ' . escapeshellarg($address) . ' ' . escapeshellarg(__DIR__ . '/router.php');
    $log = tmpfile();
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => $log, 2 => $log], $pipes);
    if (!is_resource($process)) throw new Exception('无法启动测试 HTTP 服务');
    try {
        $ready = false;
        for ($i = 0; $i < 100; $i++) {
            $connection = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
            if ($connection) { fclose($connection); $ready = true; break; }
            usleep(10000);
        }
        check($ready);
        $base = 'http://' . $address;
        list($status, $headers, $raw) = Transport::request('POST', $base . '/echo', ['Content-Type' => 'application/json', 'Authorization' => 'Bearer t'], '{}', 5);
        same($status, 200); same(json_decode($raw, true)['authorization'], 'Bearer t');
        $file = tmpfile(); fwrite($file, str_repeat("abc\x00", 10000)); fseek($file, 4);
        try {
            list($status, $headers, $raw) = Transport::request('PUT', $base . '/echo', [], $file, 5);
            same($status, 200); same(json_decode($raw, true)['size'], 39996);
            same(json_decode($raw, true)['hash'], hash('sha256', str_repeat("abc\x00", 9999)));
            check(is_resource($file));
        } finally { fclose($file); }
        list($status) = Transport::request('GET', $base . '/redirect', ['Authorization' => 'Bearer t'], null, 5);
        same($status, 302);
    } finally {
        fclose($pipes[0]); proc_terminate($process); proc_close($process); fclose($log);
    }
});
