<?php
$fixtureRaw = getenv('ICH_OPEN_IT');
if (!$fixtureRaw) {
    echo "SKIP 真实开放接口集成测试（未设置 ICH_OPEN_IT）\n";
    return;
}
$fixture = json_decode(substr(ltrim($fixtureRaw), 0, 1) === '{' ? $fixtureRaw : file_get_contents($fixtureRaw), true);
if (!is_array($fixture)) throw new Exception('无效 ICH_OPEN_IT');

function fixtureClient(array $fx) { return new \IChinese\Open\Client($fx['app_id'], $fx['app_secret'], ['baseUrl' => $fx['base']]); }
function freshCode(array $fx) {
    list($status, $headers, $raw) = \IChinese\Open\Transport::request('GET', $fx['code_url'], [], null, 30);
    same($status, 200); return json_decode($raw, true)['code'];
}

test('集成：机构令牌与成员游标分页', function () use ($fixture) {
    $c = fixtureClient($fixture);
    same($c->getTenant(['tenantKey' => $fixture['tenant_key']])['tenant_key'], $fixture['tenant_key']);
    $members = iterator_to_array($c->listTenantMembersAll(['page_size' => 1], ['tenantKey' => $fixture['tenant_key']]), false);
    check(count($members) > 0); check(strpos($members[0]['open_id'], 'ou_') === 0);
});

test('集成：授权码仅用一次、用户信息、刷新和撤销', function () use ($fixture) {
    $c = fixtureClient($fixture); $code = freshCode($fixture);
    $user = $c->exchangeAuthCode($code);
    check(strpos($user['open_id'], 'ou_') === 0);
    same($c->getUserInfo(['userAccessToken' => $user['access_token']])['open_id'], $user['open_id']);
    try { $c->exchangeAuthCode($code); throw new Exception('授权码重复使用未拒绝'); }
    catch (\IChinese\Open\OpenApiException $e) { same($e->status, 400); same($e->reason, 'code_used'); }
    $next = $c->refreshUserToken($user['refresh_token']); same($next['open_id'], $user['open_id']);
    $c->revokeToken($next['access_token']);
    try { $c->getUserInfo(['userAccessToken' => $next['access_token']]); throw new Exception('撤销后令牌仍可用'); }
    catch (\IChinese\Open\OpenApiException $e) { same($e->status, 401); }
});

test('集成：用户令牌不能调用机构接口', function () use ($fixture) {
    $c = fixtureClient($fixture); $user = $c->exchangeAuthCode(freshCode($fixture));
    try { $c->getTenant(['userAccessToken' => $user['access_token']]); }
    catch (\IChinese\Open\OpenApiException $e) { same($e->status, 403); check((bool) $e->requestId); return; }
    throw new Exception('用户令牌越权调用未拒绝');
});
