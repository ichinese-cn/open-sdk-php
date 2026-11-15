<?php
namespace IChinese\Open;

use IChinese\Open\Gen\Meta;
use IChinese\Open\Gen\Operations;

/** 同步服务端客户端；一个实例可缓存多个机构的令牌，不在磁盘保存密钥。 */
class Client
{
    use Operations;

    public $appId;
    public $baseUrl;
    public $maxRetries;
    public $timeout;
    public $refreshBeforeSeconds;
    public $retryBaseSeconds = 0.5;
    private $appSecret;
    private $transport;
    private $sleep;
    private $clock;
    private $userAgent;
    private $tokens = [];

    /**
     * options: baseUrl, timeout（秒，默认 30）, maxRetries（默认 3）, refreshBeforeSeconds（默认 300）,
     * userAgent, transport(method,url,headers,body,timeout) => [status,headers,rawBody]。
     * clock/sleep 可注入，用于测试和应用自身的调度；同步调用不在多个线程间共享实例。
     */
    public function __construct($appId, $appSecret, array $options = [])
    {
        if (!$appId || !$appSecret) throw new \InvalidArgumentException('appId 和 appSecret 必填');
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->baseUrl = rtrim(self::value($options, 'baseUrl', Meta::DEFAULT_BASE_URL), '/');
        if (substr($this->baseUrl, -strlen(Meta::API_PREFIX)) === Meta::API_PREFIX) $this->baseUrl = substr($this->baseUrl, 0, -strlen(Meta::API_PREFIX));
        $this->maxRetries = max(0, (int) self::value($options, 'maxRetries', 3));
        $this->timeout = (float) self::value($options, 'timeout', 30);
        $this->refreshBeforeSeconds = (float) self::value($options, 'refreshBeforeSeconds', 300);
        if ($this->timeout <= 0 || $this->refreshBeforeSeconds < 0) throw new \InvalidArgumentException('timeout 必须大于 0，refreshBeforeSeconds 不能为负数');
        $this->transport = self::value($options, 'transport', [Transport::class, 'request']);
        $this->clock = self::value($options, 'clock', function () { return microtime(true); });
        $this->sleep = self::value($options, 'sleep', function ($seconds) { usleep((int) min(PHP_INT_MAX, max(0, $seconds) * 1000000)); });
        $this->userAgent = 'ichinese-open-sdk-php/' . Meta::VERSION . (isset($options['userAgent']) ? ' ' . $options['userAgent'] : '');
    }

    public function appAccessToken()
    {
        return $this->cached('app', function () {
            $r = $this->send('POST', '/auth/app_access_token', null, $this->credentials());
            return [$r['app_access_token'], $r['expires_in']];
        });
    }

    public function tenantAccessToken($tenantKey)
    {
        return $this->cached('tenant:' . $tenantKey, function () use ($tenantKey) {
            $r = $this->send('POST', '/auth/tenant_access_token', null, $this->credentials() + ['tenant_key' => $tenantKey]);
            return [$r['tenant_access_token'], $r['expires_in']];
        });
    }

    /** 一次性授权码和轮换 refresh token 不自动重试。 */
    public function exchangeAuthCode($code, $redirectUri = null, $codeVerifier = null)
    {
        $body = $this->credentials() + ['code' => $code];
        if ($redirectUri !== null) $body['redirect_uri'] = $redirectUri;
        if ($codeVerifier !== null) $body['code_verifier'] = $codeVerifier;
        return $this->send('POST', '/auth/exchange_auth_code', null, $body, null, false);
    }

    public function refreshUserToken($refreshToken)
    {
        return $this->send('POST', '/auth/refresh_user_token', null, $this->credentials() + ['refresh_token' => $refreshToken], null, false);
    }

    public function revokeToken($token)
    {
        $this->send('POST', '/auth/revoke', null, $this->credentials() + ['token' => $token]);
    }

    public function clearTokenCache()
    {
        $this->tokens = [];
    }

    /** 尚未生成的方法也可以通过 request 调用；options 同生成方法。 */
    public function request($method, $path, $query = null, $body = null, array $options = [])
    {
        return $this->call($path, strtoupper($method), $path, [], $query, $body, self::value($options, 'tokenTypes', ['app', 'tenant', 'user']), $options);
    }

    protected function call($id, $method, $path, array $params, $query, $body, array $tokenTypes, array $options)
    {
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') throw new \InvalidArgumentException($id . ': ' . $key . ' 必填');
            $path = str_replace('{' . $key . '}', rawurlencode((string) $value), $path);
        }
        if (strpos($path, '{') !== false) throw new \InvalidArgumentException($id . ': 缺少路径参数');
        return $this->send($method, $path, $query, $body, [$id, $tokenTypes, $options]);
    }

    protected function iterate($id, $method, $path, array $params, $query, array $tokenTypes, array $options)
    {
        $q = $query === null ? [] : $query;
        if (!isset($q['page_size'])) $q['page_size'] = 100;
        unset($q['page_token']);
        while (true) {
            $page = $this->call($id, $method, $path, $params, $q, null, $tokenTypes, $options);
            foreach (self::value($page, 'items', []) as $item) yield $item;
            if (empty($page['has_more']) || empty($page['page_token'])) return;
            $q['page_token'] = $page['page_token'];
        }
    }

    public function uploadImage($userAccessToken, $data, $contentType)
    {
        return $this->uploadImageFor(['userAccessToken' => $userAccessToken], $data, $contentType);
    }

    public function uploadImageAsOrg($tenantKey, $data, $contentType)
    {
        return $this->uploadImageFor(['tenantKey' => $tenantKey], $data, $contentType);
    }

    /** $data 是二进制字符串或可定位文件资源；资源流式传输且由调用方关闭。 */
    public function uploadVideo($userAccessToken, $data, $filename, $contentType)
    {
        return $this->uploadVideoFor(['userAccessToken' => $userAccessToken], $data, $filename, $contentType);
    }

    public function uploadVideoAsOrg($tenantKey, $data, $filename, $contentType)
    {
        return $this->uploadVideoFor(['tenantKey' => $tenantKey], $data, $filename, $contentType);
    }

    /** options: timeout 默认 1800 秒；interval 默认 2 秒，翻倍到 maxInterval（默认 15 秒）。 */
    public function waitVideoReady($userAccessToken, $assetId, array $options = [])
    {
        return $this->waitVideoFor(['userAccessToken' => $userAccessToken], $assetId, $options);
    }

    public function waitVideoReadyAsOrg($tenantKey, $assetId, array $options = [])
    {
        return $this->waitVideoFor(['tenantKey' => $tenantKey], $assetId, $options);
    }

    public function createPost($userAccessToken, array $post)
    {
        return $this->createPostFor(['userAccessToken' => $userAccessToken], $post);
    }

    public function createPostAsOrg($tenantKey, array $post)
    {
        return $this->createPostFor(['tenantKey' => $tenantKey], $post);
    }

    public function getAllInsights($tenantKey, array $query = [])
    {
        unset($query['tab']);
        $first = $this->getInsights($query, ['tenantKey' => $tenantKey]);
        $out = [$first['tab'] => $first];
        foreach (self::value($first, 'tabs', []) as $tab) {
            if (!isset($out[$tab])) $out[$tab] = $this->getInsights($query + ['tab' => $tab], ['tenantKey' => $tenantKey]);
        }
        return $out;
    }

    private function uploadImageFor(array $who, $data, $contentType)
    {
        $slot = $this->createCommunityImageUpload(['content_type' => $contentType, 'size_bytes' => strlen($data)], $who);
        $this->putUpload($slot['upload_url'], self::value($slot, 'headers', []), $data, strlen($data));
        return $slot['key'];
    }

    private function uploadVideoFor(array $who, $data, $filename, $contentType)
    {
        if (is_resource($data)) {
            $stat = fstat($data);
            $position = ftell($data);
            $meta = stream_get_meta_data($data);
            if ($stat === false || $position === false || !$meta['seekable']) throw new \InvalidArgumentException('上传需要可定位、大小已知的文件资源');
            $size = $stat['size'] - $position;
        } elseif (is_string($data)) $size = strlen($data);
        else throw new \InvalidArgumentException('data 需要二进制字符串或文件资源');
        $slot = $this->createCommunityVideoUpload(['filename' => $filename, 'content_type' => $contentType, 'size_bytes' => $size], $who);
        $this->putUpload($slot['upload']['url'], self::value($slot['upload'], 'headers', []), $data, $size);
        return $this->send('POST', '/community/videos/' . rawurlencode($slot['asset_id']) . '/complete', null, null, ['completeCommunityVideoUpload', ['user', 'tenant'], $who], true);
    }

    private function putUpload($url, array $headers, $data, $size)
    {
        if (!isset(array_change_key_case($headers, CASE_LOWER)['content-length'])) $headers['Content-Length'] = (string) $size;
        list($status, $responseHeaders, $raw) = call_user_func($this->transport, 'PUT', $url, $headers, $data, $this->timeout);
        if ($status < 200 || $status >= 300) throw new OpenApiException($status, 'UPLOAD_FAILED', '上传失败', null, ['reason' => 'upload_failed', 'body' => substr($raw, 0, 500)]);
    }

    private function waitVideoFor(array $who, $assetId, array $options)
    {
        $deadline = call_user_func($this->clock) + self::value($options, 'timeout', 1800);
        $interval = self::value($options, 'interval', 2);
        $max = self::value($options, 'maxInterval', 15);
        if ($interval <= 0 || $max <= 0) throw new \InvalidArgumentException('轮询间隔必须大于 0');
        while (true) {
            $video = $this->getCommunityVideo($assetId, $who);
            if ($video['status'] === 'ready') return $video;
            if ($video['status'] === 'failed') throw new OpenApiException(409, 'CONFLICT', '视频转码失败', null, ['reason' => 'video_failed', 'asset_id' => $assetId, 'error' => self::value($video, 'error')]);
            $left = $deadline - call_user_func($this->clock);
            if ($left <= 0) throw new OpenApiException(0, 'TIMEOUT', '视频尚未就绪', null, ['reason' => 'video_not_ready', 'asset_id' => $assetId, 'status' => $video['status']]);
            call_user_func($this->sleep, min($interval, $left));
            $interval = min($interval * 2, $max);
        }
    }

    private function createPostFor(array $who, array $post)
    {
        if (empty($post['uuid'])) {
            $strong = false;
            $bytes = openssl_random_pseudo_bytes(16, $strong);
            if ($bytes === false || !$strong) throw new OpenApiException(0, 'NETWORK', '无法生成安全的 uuid');
            $bytes[6] = chr((ord($bytes[6]) & 15) | 64);
            $bytes[8] = chr((ord($bytes[8]) & 63) | 128);
            $hex = bin2hex($bytes);
            $post['uuid'] = substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
        }
        return $this->send('POST', '/community/posts', null, $post, ['createCommunityPost', ['user', 'tenant'], $who], true);
    }

    private function credentials()
    {
        return ['app_id' => $this->appId, 'app_secret' => $this->appSecret];
    }

    private function cached($key, $fetch)
    {
        if (isset($this->tokens[$key]) && $this->tokens[$key][1] - $this->refreshBeforeSeconds > call_user_func($this->clock)) return $this->tokens[$key][0];
        list($token, $expires) = $fetch();
        $this->tokens[$key] = [$token, call_user_func($this->clock) + $expires];
        return $token;
    }

    private function tokenFor($id, array $tokenTypes, array $options)
    {
        if (!empty($options['accessToken'])) return [$options['accessToken'], null];
        if (!empty($options['userAccessToken'])) return [$options['userAccessToken'], null];
        if (!empty($options['tenantKey'])) return [$this->tenantAccessToken($options['tenantKey']), 'tenant:' . $options['tenantKey']];
        if (in_array('app', $tokenTypes, true)) return [$this->appAccessToken(), 'app'];
        throw new OpenApiException(0, 'TOKEN_REQUIRED', $id . ' 需要 tenantKey 或 userAccessToken');
    }

    private function send($method, $path, $query = null, $body = null, $auth = null, $retryPost = null)
    {
        $pairs = [];
        foreach ($query === null ? [] : $query as $key => $value) {
            if ($value === null || $value === '') continue;
            foreach (is_array($value) ? $value : [$value] as $entry) {
                $v = is_bool($entry) ? ($entry ? 'true' : 'false') : (string) $entry;
                $pairs[] = rawurlencode($key) . '=' . rawurlencode($v);
            }
        }
        $url = $this->baseUrl . Meta::API_PREFIX . $path . ($pairs ? '?' . implode('&', $pairs) : '');
        $data = null;
        if ($body !== null) {
            $data = json_encode($body === [] ? new \stdClass() : $body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($data === false) throw new \InvalidArgumentException('请求体不是有效 JSON：' . json_last_error_msg());
        }
        $options = $auth === null ? [] : $auth[2];
        $idempotent = in_array($method, ['GET', 'PUT', 'DELETE'], true) || ($method === 'POST' && ($retryPost === true || ($auth === null && $retryPost !== false)));
        $refreshed = false;
        $attempt = 0;
        while (true) {
            $headers = self::value($options, 'headers', []);
            $headers['Accept'] = 'application/json';
            $headers['User-Agent'] = $this->userAgent;
            $cacheKey = null;
            if ($auth !== null) {
                list($token, $cacheKey) = $this->tokenFor($auth[0], $auth[1], $options);
                // 消除不同大小写的 Authorization，保证只发送当前选中的令牌。
                foreach (array_keys($headers) as $k) if (strtolower($k) === 'authorization') unset($headers[$k]);
                $headers['Authorization'] = 'Bearer ' . $token;
            }
            if ($data !== null) $headers['Content-Type'] = 'application/json';
            $canRetry = $attempt < $this->maxRetries;
            try {
                list($status, $resHeaders, $raw) = call_user_func($this->transport, $method, $url, $headers, $data, $this->timeout);
            } catch (OpenApiException $e) {
                if ($canRetry && $idempotent && $e->status === 0 && in_array($e->errorCode, ['TIMEOUT', 'NETWORK'], true)) {
                    call_user_func($this->sleep, $this->backoff($attempt++));
                    continue;
                }
                throw $e;
            }
            if ($status >= 200 && $status < 300) {
                if ($status === 204 || $raw === '') return null;
                $json = json_decode($raw, true);
                if (json_last_error() !== JSON_ERROR_NONE) throw new OpenApiException($status, 'INVALID_PAYLOAD', '响应不是有效 JSON');
                return $json;
            }
            $resHeaders = array_change_key_case($resHeaders, CASE_LOWER);
            $parsed = json_decode($raw, true);
            $err = isset($parsed['error']) && is_array($parsed['error']) ? $parsed['error'] : [];
            $error = new OpenApiException($status, self::value($err, 'code', 'HTTP_' . $status), self::value($err, 'message', 'HTTP ' . $status), self::value($err, 'request_id', self::value($resHeaders, 'x-request-id')), is_array(self::value($err, 'details')) ? $err['details'] : [], self::number(self::value($resHeaders, 'retry-after')));
            if ($status === 429 && $canRetry && $error->reason !== 'daily_quota_exceeded') {
                $wait = $error->retryAfter;
                if ($wait === null) $wait = self::number(self::value($resHeaders, 'x-ratelimit-reset'));
                call_user_func($this->sleep, $wait !== null && $wait >= 0 ? $wait : $this->backoff($attempt));
                $attempt++;
                continue;
            }
            if (in_array($status, [500, 502, 503, 504], true) && $canRetry && $idempotent) {
                call_user_func($this->sleep, $this->backoff($attempt++));
                continue;
            }
            if ($status === 401 && $cacheKey !== null && !$refreshed && in_array($error->reason, ['invalid_token', 'token_expired'], true)) {
                unset($this->tokens[$cacheKey]);
                $refreshed = true;
                continue;
            }
            throw $error;
        }
    }

    private function backoff($attempt)
    {
        $base = $this->retryBaseSeconds * pow(2, $attempt);
        return min(30, $base + mt_rand() / mt_getrandmax() * $base * 0.25);
    }

    private static function number($value)
    {
        return is_numeric($value) && is_finite((float) $value) ? (float) $value : null;
    }

    private static function value(array $array, $key, $default = null)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }
}
