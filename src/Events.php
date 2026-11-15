<?php
namespace IChinese\Open;

use phpseclib3\Crypt\AES;

/** 原始请求体 HMAC 验签；phpseclib 兼容 PHP 5.6 的 AES-256-GCM。 */
final class Events
{
    public static function sign($signingSecret, $timestamp, $nonce, $rawBody)
    {
        return 'v1=' . hash_hmac('sha256', "$timestamp.$nonce.$rawBody", $signingSecret);
    }

    public static function verifySignature(array $headers, $rawBody, $signingSecret, $toleranceSeconds = 300, $now = null)
    {
        $headers = array_change_key_case($headers, CASE_LOWER);
        $timestamp = isset($headers['x-ich-timestamp']) ? $headers['x-ich-timestamp'] : '';
        $nonce = isset($headers['x-ich-nonce']) ? $headers['x-ich-nonce'] : '';
        $signature = isset($headers['x-ich-signature']) ? $headers['x-ich-signature'] : '';
        if (!is_string($timestamp) || !ctype_digit($timestamp) || !is_string($nonce) || $nonce === '' || !is_string($signature) || $signature === '') {
            throw new OpenApiException(401, 'INVALID_SIGNATURE', '缺少或无效的签名标头');
        }
        if (abs(($now === null ? microtime(true) : $now) - (float) $timestamp) > $toleranceSeconds || !hash_equals(self::sign($signingSecret, $timestamp, $nonce, $rawBody), $signature)) {
            throw new OpenApiException(401, 'INVALID_SIGNATURE', '签名不匹配或时间戳已过期');
        }
    }

    public static function decrypt($encrypted, $encryptKey)
    {
        $bytes = base64_decode($encrypted, true);
        if ($bytes === false || strlen($bytes) < 28) throw new OpenApiException(400, 'INVALID_PAYLOAD', '加密数据无效');
        $key = hash('sha256', $encryptKey, true);
        $nonce = substr($bytes, 0, 12);
        $ciphertext = substr($bytes, 12, -16);
        $tag = substr($bytes, -16);
        if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
            $plain = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
            if ($plain !== false) return $plain;
        } else {
            // 老 PHP 的 GCM 解密错误路径在部分 phpseclib 版本中会递归。使用库提供的 CTR
            // 和 GCM 加密重新计算认证标签，常量时间验证通过后才返回明文，不走该错误路径。
            $ctr = new AES('ctr');
            $ctr->setKey($key);
            $ctr->setIV($nonce . pack('N', 2));
            $plain = $ctr->decrypt($ciphertext);
            $gcm = new AES('gcm');
            $gcm->setKey($key);
            $gcm->setNonce($nonce);
            $gcm->encrypt($plain);
            if (hash_equals($gcm->getTag(16), $tag)) return $plain;
        }
        throw new OpenApiException(400, 'INVALID_PAYLOAD', '解密失败，请检查 encrypt key');
    }

    public static function parseEvent(array $headers, $rawBody, $signingSecret, $encryptKey = null, $toleranceSeconds = 300, $now = null)
    {
        self::verifySignature($headers, $rawBody, $signingSecret, $toleranceSeconds, $now);
        $payload = self::jsonObject($rawBody);
        if (isset($payload['encrypt'])) {
            if (!is_string($payload['encrypt']) || !$encryptKey) throw new OpenApiException(400, 'INVALID_PAYLOAD', '加密推送需要 encrypt key');
            $payload = self::jsonObject(self::decrypt($payload['encrypt'], $encryptKey));
        }
        return $payload;
    }

    public static function parseCardAction(array $headers, $rawBody, $signingSecret, $encryptKey = null, $toleranceSeconds = 300, $now = null)
    {
        return self::parseEvent($headers, $rawBody, $signingSecret, $encryptKey, $toleranceSeconds, $now);
    }

    public static function isUrlVerification($payload)
    {
        return is_array($payload) && isset($payload['type']) && $payload['type'] === 'url_verification';
    }

    public static function challengeResponse(array $payload)
    {
        return ['challenge' => $payload['challenge']];
    }

    private static function jsonObject($raw)
    {
        $object = json_decode($raw);
        if (json_last_error() !== JSON_ERROR_NONE || !$object instanceof \stdClass) {
            throw new OpenApiException(400, 'INVALID_PAYLOAD', '推送不是有效的 JSON 对象');
        }
        return json_decode($raw, true);
    }
}
