<?php
// 不依赖 PHPUnit，测试套件本身可直接在 PHP 5.6 和 PHP 8.x 运行。
require __DIR__ . '/../vendor/autoload.php';

use IChinese\Open\Client;
use IChinese\Open\Events;
use IChinese\Open\Gen\Meta;
use IChinese\Open\OpenApiException;
use IChinese\Open\Transport;

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$tests = [];
function test($name, $run) { global $tests; $tests[$name] = $run; }
function same($actual, $expected) {
    if ($actual !== $expected) throw new Exception('实际 ' . var_export($actual, true) . '，预期 ' . var_export($expected, true));
}
function check($value) { if (!$value) throw new Exception('断言失败'); }
function raises($fn, $code, $reason = null) {
    try { $fn(); } catch (OpenApiException $e) {
        same($e->errorCode, $code);
        if ($reason !== null) same($e->reason, $reason);
        return $e;
    }
    throw new Exception('预期 OpenApiException ' . $code);
}
function response($body = [], $status = 200, array $headers = []) { return [$status, $headers, json_encode($body)]; }
function failure($status, $reason, array $details = [], array $headers = []) {
    return response(['error' => ['code' => 'TEST', 'message' => 'denied', 'details' => ['reason' => $reason] + $details]], $status, $headers);
}
function client($transport, array $options = []) {
    return new Client('cn.app', 'secret', $options + ['transport' => $transport, 'sleep' => function ($s) {}]);
}
function credentials($url) { return strpos($url, '/auth/') !== false; }

test('应用和机构令牌缓存、按机构隔离及提前续期', function () {
    $now = 1000;
    $count = 0;
    $c = client(function ($method, $url, $headers, $body) use (&$count) {
        $count++;
        return response(['app_access_token' => 'app-' . $count, 'tenant_access_token' => 'tenant-' . $count, 'expires_in' => 600]);
    }, ['clock' => function () use (&$now) { return $now; }]);
    same($c->appAccessToken(), 'app-1');
    same($c->appAccessToken(), 'app-1');
    same($c->tenantAccessToken('cn.one'), 'tenant-2');
    same($c->tenantAccessToken('cn.two'), 'tenant-3');
    same($c->tenantAccessToken('cn.one'), 'tenant-2');
    $now += 301;
    same($c->appAccessToken(), 'app-4');
    $c->clearTokenCache();
    same($c->appAccessToken(), 'app-5');
});

test('401 过期令牌自动刷新一次', function () {
    $tokens = 0; $calls = 0;
    $c = client(function ($method, $url, $headers) use (&$tokens, &$calls) {
        if (credentials($url)) return response(['tenant_access_token' => 't' . ++$tokens, 'expires_in' => 7200]);
        $calls++;
        if ($calls === 1) return failure(401, 'token_expired');
        same($headers['Authorization'], 'Bearer t2');
        return response(['tenant_key' => 'cn.one']);
    });
    same($c->getTenant(['tenantKey' => 'cn.one'])['tenant_key'], 'cn.one');
    same($tokens, 2);
});

test('持续 401 不无限刷新；显式用户令牌不刷新', function () {
    $tokens = 0;
    $c = client(function ($method, $url) use (&$tokens) {
        if (credentials($url)) return response(['tenant_access_token' => 't' . ++$tokens, 'expires_in' => 7200]);
        return failure(401, 'invalid_token');
    });
    raises(function () use ($c) { $c->getTenant(['tenantKey' => 'cn.one']); }, 'TEST');
    same($tokens, 2);
    raises(function () use ($c) { $c->getUserInfo(['userAccessToken' => 'u']); }, 'TEST');
    same($tokens, 2);
});

test('受限方法不能悄悄使用应用令牌', function () {
    $c = client(function () { throw new Exception('不应发请求'); });
    raises(function () use ($c) { $c->getTenant(); }, 'TOKEN_REQUIRED');
});

test('路径编码、重复数组查询、false/0 和自定义请求', function () {
    $c = client(function ($method, $url, $headers) {
        check(strpos($url, '/classes/c%2F1/members') !== false);
        check(strpos($url, 'topic=%E4%B8%AD%E6%96%87&topic=a%26b') !== false);
        check(strpos($url, 'active=false&n=0') !== false);
        check(strpos($url, 'empty=') === false);
        same($headers['Authorization'], 'Bearer explicit');
        same($headers['X-Custom'], 'ok');
        return response(['ok' => true]);
    });
    $options = ['accessToken' => 'explicit', 'headers' => ['authorization' => 'Bearer bad', 'X-Custom' => 'ok']];
    $c->request('GET', '/classes/c%2F1/members', ['topic' => ['中文', 'a&b'], 'active' => false, 'n' => 0, 'empty' => ''], null, $options);
});

test('生成的方法编码路径参数并验证必填请求体', function () {
    $c = client(function ($method, $url, $headers, $body) {
        check(strpos($url, '/classes/c%2F1/members') !== false);
        same(json_decode($body, true), ['open_id' => 'ou_1']);
        return response();
    });
    $c->addClassMember('c/1', ['open_id' => 'ou_1'], ['accessToken' => 'u']);
    try { $c->addClassMember('c'); } catch (InvalidArgumentException $e) { return; }
    throw new Exception('缺少 body 未拒绝');
});

test('游标分页保留查询且按需加载', function () {
    $calls = 0;
    $c = client(function ($method, $url) use (&$calls) {
        $calls++;
        check(strpos($url, 'page_size=1') !== false);
        if ($calls === 1) {
            check(strpos($url, 'page_token=') === false);
            return response(['items' => [['open_id' => 'ou_1']], 'has_more' => true, 'page_token' => 'next']);
        }
        check(strpos($url, 'page_token=next') !== false);
        return response(['items' => [['open_id' => 'ou_2']], 'has_more' => false]);
    });
    $all = $c->listTenantMembersAll(['page_size' => 1, 'page_token' => 'ignore'], ['accessToken' => 't']);
    same($calls, 0);
    same(array_column(iterator_to_array($all, false), 'open_id'), ['ou_1', 'ou_2']);
    same($calls, 2);
});

test('429 遵守 Retry-After，日配额不重试', function () {
    $calls = 0; $sleeps = [];
    $c = client(function () use (&$calls) { return ++$calls === 1 ? failure(429, 'rate_limited', [], ['Retry-After' => '2']) : response(); }, ['sleep' => function ($s) use (&$sleeps) { $sleeps[] = $s; }]);
    $c->getUserInfo(['userAccessToken' => 'u']);
    same($sleeps, [2.0]);
    $calls = 0;
    $c = client(function () use (&$calls) { $calls++; return failure(429, 'daily_quota_exceeded'); });
    raises(function () use ($c) { $c->getUserInfo(['userAccessToken' => 'u']); }, 'TEST', 'daily_quota_exceeded');
    same($calls, 1);
});

test('GET 的 5xx 和网络失败重试，普通 POST 不重试', function () {
    foreach (['server', 'network'] as $kind) {
        $calls = 0;
        $c = client(function () use (&$calls, $kind) {
            if (++$calls === 1) {
                if ($kind === 'network') throw new OpenApiException(0, 'NETWORK', 'reset');
                return failure(503, 'busy');
            }
            return response(['ok' => true]);
        });
        same($c->getUserInfo(['accessToken' => 'u'])['ok'], true);
        same($calls, 2);
    }
    $calls = 0;
    $c = client(function () use (&$calls) { $calls++; return failure(503, 'busy'); });
    raises(function () use ($c) { $c->addClassMember('c', ['open_id' => 'ou_1'], ['accessToken' => 'u']); }, 'TEST');
    same($calls, 1);
});

test('授权码交换和 refresh token 不重试，并透传 PKCE', function () {
    $calls = 0;
    $c = client(function ($method, $url, $headers, $body) use (&$calls) {
        $calls++;
        $b = json_decode($body, true);
        same($b['app_secret'], 'secret');
        if (strpos($url, 'exchange_auth_code') !== false) {
            same($b['redirect_uri'], 'https://app.example/callback'); same($b['code_verifier'], 'verifier');
        }
        return failure(503, 'busy');
    });
    raises(function () use ($c) { $c->exchangeAuthCode('code', 'https://app.example/callback', 'verifier'); }, 'TEST');
    same($calls, 1);
    raises(function () use ($c) { $c->refreshUserToken('refresh'); }, 'TEST');
    same($calls, 2);
});

test('带类型的权限错误保留 request id、授权链接和 neverAsk', function () {
    $c = client(function () { return failure(403, 'scope_denied', ['scope' => 'openid', 'never_ask' => true, 'consent_url' => 'https://example.com/agree'], ['X-Request-Id' => 'req-1']); });
    $e = raises(function () use ($c) { $c->getUserInfo(['accessToken' => 'u']); }, 'TEST', 'scope_denied');
    same($e->requestId, 'req-1'); same($e->scope, 'openid'); same($e->neverAsk, true); same($e->isScopeError, true); same($e->consentUrl, 'https://example.com/agree');
});

test('无 JSON 错误的响应、204 和空对象请求体', function () {
    $c = client(function () { return [502, [], '<html>bad gateway</html>']; }, ['maxRetries' => 0]);
    raises(function () use ($c) { $c->getUserInfo(['accessToken' => 'u']); }, 'HTTP_502');
    $c = client(function ($method, $url, $headers, $body) { same($body, '{}'); return [204, [], '']; });
    same($c->request('POST', '/any', null, [], ['accessToken' => 'u']), null);
});

test('发帖生成 UUID，同一次调用的重试保持 UUID；机构令牌路径一致', function () {
    foreach ([false, true] as $org) {
        $bodies = [];
        $c = client(function ($method, $url, $headers, $body) use (&$bodies) {
            if (credentials($url)) return response(['tenant_access_token' => 't', 'expires_in' => 7200]);
            $bodies[] = json_decode($body, true);
            return count($bodies) === 1 ? failure(503, 'busy') : response(['post_id' => 'p']);
        });
        $result = $org ? $c->createPostAsOrg('cn.org', ['text' => '你好']) : $c->createPost('u', ['text' => '你好']);
        same($result['post_id'], 'p'); same($bodies[0], $bodies[1]);
        check((bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $bodies[0]['uuid']));
    }
});

test('图片 PUT 仅发送存储请求头，不泄露平台令牌', function () {
    foreach ([false, true] as $org) {
        $c = client(function ($method, $url, $headers, $body) {
            if (credentials($url)) return response(['tenant_access_token' => 't', 'expires_in' => 7200]);
            if ($method === 'PUT') { same($url, 'https://storage.example/upload'); check(!isset($headers['Authorization'])); same($headers['Content-Length'], '3'); same($body, 'abc'); return [200, [], '']; }
            same(json_decode($body, true)['size_bytes'], 3);
            return response(['key' => 'image', 'upload_url' => 'https://storage.example/upload', 'headers' => ['x-store' => 'signed']]);
        });
        same($org ? $c->uploadImageAsOrg('cn.org', 'abc', 'image/png') : $c->uploadImage('u', 'abc', 'image/png'), 'image');
    }
});

test('视频资源从当前指针流式上传、确认接口可重试', function () {
    $file = tmpfile(); fwrite($file, 'skip-video'); fseek($file, 5);
    try {
        foreach ([false, true] as $org) {
            fseek($file, 5); $complete = 0;
            $c = client(function ($method, $url, $headers, $body) use ($file, &$complete) {
                if (credentials($url)) return response(['tenant_access_token' => 't', 'expires_in' => 7200]);
                if ($method === 'PUT') { same($body, $file); same(stream_get_contents($body), 'video'); same($headers['Content-Length'], '5'); check(!isset($headers['Authorization'])); return [200, [], '']; }
                if (strpos($url, '/complete') !== false) return ++$complete === 1 ? failure(503, 'busy') : response(['asset_id' => 'a/1', 'status' => 'processing']);
                same(json_decode($body, true)['size_bytes'], 5);
                return response(['asset_id' => 'a/1', 'upload' => ['url' => 'https://storage.example/video']]);
            });
            $r = $org ? $c->uploadVideoAsOrg('cn.org', $file, 'a.mp4', 'video/mp4') : $c->uploadVideo('u', $file, 'a.mp4', 'video/mp4');
            same($r['asset_id'], 'a/1'); same($complete, 2); check(is_resource($file));
        }
    } finally { fclose($file); }
});

test('上传存储错误保留原因', function () {
    $c = client(function ($method) { return $method === 'PUT' ? [403, [], 'storage refused'] : response(['key' => 'k', 'upload_url' => 'https://storage.example']); });
    raises(function () use ($c) { $c->uploadImage('u', 'abc', 'image/png'); }, 'UPLOAD_FAILED', 'upload_failed');
});

test('视频轮询 ready、失败和超时', function () {
    $now = 0; $calls = 0;
    $c = client(function () use (&$calls) { return response(['status' => ++$calls === 3 ? 'ready' : 'processing']); }, ['clock' => function () use (&$now) { return $now; }, 'sleep' => function ($s) use (&$now) { $now += $s; }]);
    same($c->waitVideoReady('u', 'a')['status'], 'ready'); same($now, 6);
    $c = client(function ($method, $url) { return credentials($url) ? response(['tenant_access_token' => 't', 'expires_in' => 7200]) : response(['status' => 'failed', 'error' => 'bad']); });
    raises(function () use ($c) { $c->waitVideoReadyAsOrg('cn.org', 'a', ['timeout' => 0]); }, 'CONFLICT', 'video_failed');
    $c = client(function () { return response(['status' => 'processing']); });
    raises(function () use ($c) { $c->waitVideoReady('u', 'a', ['timeout' => 0]); }, 'TIMEOUT', 'video_not_ready');
});

test('机构看板保留隐私 null 和币种；每个 tab 只读一次', function () {
    $calls = [];
    $c = client(function ($method, $url) use (&$calls) {
        if (credentials($url)) return response(['tenant_access_token' => 't', 'expires_in' => 7200]);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
        $tab = isset($q['tab']) ? $q['tab'] : 'overview'; $calls[] = $tab; same($q['currency'], 'EUR');
        return response(['tab' => $tab, 'tabs' => ['overview', 'income', 'income'], 'count' => null, 'withheld' => true]);
    });
    $r = $c->getAllInsights('cn.org', ['tab' => 'ignored', 'currency' => 'EUR']);
    same($calls, ['overview', 'income']); same($r['income']['count'], null); same($r['income']['withheld'], true);
});

test('所有服务端事件向量：签名、加密和卡片解析', function () {
    $v = json_decode(file_get_contents(__DIR__ . '/test-vectors.json'), true);
    same(Events::sign($v['signing_secret'], $v['timestamp'], $v['nonce'], $v['plain_body']), $v['plain_signature']);
    foreach (['plain', 'encrypted'] as $kind) {
        $h = ['X-Ich-Timestamp' => $v['timestamp'], 'X-Ich-Nonce' => $v['nonce'], 'X-Ich-Signature' => $v[$kind . '_signature']];
        $r = Events::parseEvent($h, $v[$kind . '_body'], $v['signing_secret'], $v['encrypt_key'], 300, (float) $v['timestamp']);
        same($r, json_decode($v['decrypted'], true));
        same(Events::parseCardAction($h, $v[$kind . '_body'], $v['signing_secret'], $v['encrypt_key'], 300, (float) $v['timestamp']), $r);
    }
});

test('验签拒绝篡改、过期和非法时间戳；解密拒绝错误密钥和 tag', function () {
    $v = json_decode(file_get_contents(__DIR__ . '/test-vectors.json'), true);
    $h = ['X-Ich-Timestamp' => $v['timestamp'], 'X-Ich-Nonce' => $v['nonce'], 'X-Ich-Signature' => $v['plain_signature']];
    raises(function () use ($h, $v) { Events::verifySignature($h, $v['plain_body'] . ' ', $v['signing_secret'], 300, (float) $v['timestamp']); }, 'INVALID_SIGNATURE');
    raises(function () use ($h, $v) { Events::verifySignature($h, $v['plain_body'], $v['signing_secret'], 300, (float) $v['timestamp'] + 301); }, 'INVALID_SIGNATURE');
    $h['X-Ich-Timestamp'] = '1e10';
    raises(function () use ($h) { Events::verifySignature($h, '{}', 'secret'); }, 'INVALID_SIGNATURE');
    $encoded = json_decode($v['encrypted_body'], true)['encrypt'];
    raises(function () use ($encoded) { Events::decrypt($encoded, 'wrong'); }, 'INVALID_PAYLOAD');
    $bytes = base64_decode($encoded); $bytes[strlen($bytes) - 1] = chr(ord($bytes[strlen($bytes) - 1]) ^ 1);
    raises(function () use ($bytes, $v) { Events::decrypt(base64_encode($bytes), $v['encrypt_key']); }, 'INVALID_PAYLOAD');
    raises(function () { Events::decrypt('bad', 'key'); }, 'INVALID_PAYLOAD');
});

test('URL challenge 与有签名的非法 JSON', function () {
    $now = (string) time();
    $raw = '{"type":"url_verification","challenge":"abc"}';
    $h = ['x-ich-timestamp' => $now, 'x-ich-nonce' => 'n', 'x-ich-signature' => Events::sign('s', $now, 'n', $raw)];
    $p = Events::parseEvent($h, $raw, 's'); check(Events::isUrlVerification($p)); same(Events::challengeResponse($p), ['challenge' => 'abc']);
    foreach (['bad json', '[]', '{"encrypt":"abc"}'] as $bad) {
        $h['x-ich-signature'] = Events::sign('s', $now, 'n', $bad);
        raises(function () use ($h, $bad) { Events::parseEvent($h, $bad, 's'); }, 'INVALID_PAYLOAD');
    }
});

test('独立 Node GCM 向量：空消息、分组边界与认证标签', function () {
    $fixture = json_decode(file_get_contents(__DIR__ . '/gcm-vectors.json'), true);
    foreach ($fixture['vectors'] as $v) {
        same(Events::decrypt($v['encrypted'], $fixture['key']), $v['plain']);
        $bytes = base64_decode($v['encrypted']);
        $bytes[strlen($bytes) - 1] = chr(ord($bytes[strlen($bytes) - 1]) ^ 1);
        raises(function () use ($bytes, $fixture) { Events::decrypt(base64_encode($bytes), $fixture['key']); }, 'INVALID_PAYLOAD');
    }
});

require __DIR__ . '/transport.php';
require __DIR__ . '/integration.php';
$failed = 0;
foreach ($tests as $name => $run) {
    try { $run(); echo "PASS $name\n"; }
    catch (Exception $e) { $failed++; fwrite(STDERR, "FAIL $name: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n"); }
}
echo count($tests) . ' tests, ' . $failed . ' failures (PHP ' . PHP_VERSION . ")\n";
exit($failed ? 1 : 0);
