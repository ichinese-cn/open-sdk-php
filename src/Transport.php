<?php
namespace IChinese\Open;

/** 默认 cURL 传输；资源请求体从当前指针流式上传，不跟随重定向。 */
final class Transport
{
    /** @param string|resource|null $body @return array [status, headers, rawBody] */
    public static function request($method, $url, array $headers, $body, $timeout)
    {
        $curl = curl_init($url);
        if ($curl === false) throw new OpenApiException(0, 'NETWORK', '无法初始化 cURL');
        $responseHeaders = [];
        $lines = [];
        foreach ($headers as $key => $value) $lines[] = "$key: $value";
        try {
            curl_setopt_array($curl, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_TIMEOUT_MS => max(1, (int) ceil($timeout * 1000)),
                CURLOPT_CONNECTTIMEOUT_MS => max(1, (int) ceil(min($timeout, 30) * 1000)),
                CURLOPT_HTTPHEADER => $lines,
                CURLOPT_HEADERFUNCTION => function ($handle, $line) use (&$responseHeaders) {
                    if (strpos($line, 'HTTP/') === 0) $responseHeaders = [];
                    elseif (strpos($line, ':') !== false) {
                        list($key, $value) = explode(':', $line, 2);
                        $responseHeaders[strtolower(trim($key))] = trim($value);
                    }
                    return strlen($line);
                },
            ]);
            if (is_resource($body)) {
                $pos = ftell($body);
                $stat = fstat($body);
                $meta = stream_get_meta_data($body);
                if ($pos === false || $stat === false || !$meta['seekable']) throw new \InvalidArgumentException('上传需要可定位、大小已知的文件资源');
                curl_setopt_array($curl, [CURLOPT_UPLOAD => true, CURLOPT_INFILE => $body, CURLOPT_INFILESIZE => $stat['size'] - $pos]);
            } elseif ($body !== null) curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
            $raw = curl_exec($curl);
            if ($raw === false) throw new OpenApiException(0, curl_errno($curl) === CURLE_OPERATION_TIMEDOUT ? 'TIMEOUT' : 'NETWORK', curl_error($curl));
            return [(int) curl_getinfo($curl, CURLINFO_HTTP_CODE), $responseHeaders, $raw];
        } finally {
            // PHP 8 的 CurlHandle 随作用域释放；旧版 cURL 资源需要显式关闭。
            if (is_resource($curl)) curl_close($curl);
        }
    }
}
