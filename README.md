# 开放平台 PHP SDK

> **Prerelease 1.0.0-rc.8.** 这是预发布版，开放平台还没有正式开放，SDK 的默认地址可能连不上。This is a prerelease: the open platform is not open to the public yet, so the SDK's default address may not answer.

PHP 服务端 SDK，与 Node/Python/Go/Java 使用同一份开放接口契约。支持 PHP **5.6.1+**（5.6 系列建议 5.6.40）及 PHP 7/8，MIT 许可证。需要 cURL、OpenSSL、JSON、hash、ctype 扩展；Composer 自动安装 phpseclib 3，兼容旧 PHP 的 AES-256-GCM 事件解密。

**预发布 SDK。** 包已登记到 [Packagist](https://packagist.org/packages/ichinese/open-sdk)，版本随统一发布标签同步。独立公开源码仓库：[ichinese-cn/open-sdk-php](https://github.com/ichinese-cn/open-sdk-php)，包名 `ichinese/open-sdk`。main 分支为预发布源码，版本标签由平台统一发布流程生成。该仓库为发布生成副本，请通过 issue 反馈改动。

## 安装

安装已发布的预发布版本：

```sh
composer require 'ichinese/open-sdk:^1.0@rc'
```

PHP 5.6 / 7.0 / 7.1 使用 [Composer 2.2 LTS](https://getcomposer.org/download/)（`php composer-setup.php --2.2`）；PHP 7.2+ 可用新版 Composer。Composer 1 不作为支持范围。预发布期间需显式允许 `@rc`，不必修改整个应用的 `minimum-stability`。

本地开发：在本目录执行 `composer install`，随后 `composer test`。

## 使用

```php
<?php
require 'vendor/autoload.php';

use IChinese\Open\Client;
use IChinese\Open\OpenApiException;

$client = new Client(getenv('APP_ID'), getenv('APP_SECRET'));
foreach ($client->listTenantMembersAll(['page_size' => 100], ['tenantKey' => 'cn.example']) as $member) {
    echo $member['name'], PHP_EOL;
}
$user = $client->exchangeAuthCode($code); // 普通浏览器 OIDC：($code, $redirectUri, $codeVerifier)
$me = $client->getUserInfo(['userAccessToken' => $user['access_token']]);
```

所有区共用默认地址 `https://open.ichinese.cn`，令牌携带区信息，由网关路由。开发时可传 `['baseUrl' => 'http://open.localhost:4380']`。App Secret、用户令牌只放自己的服务端。

接口方法名与 operationId 一致（驼峰）：路径参数 → 查询数组（如有）→ JSON 请求体（如有）→ options。JSON 响应为关联数组；空 JSON 对象可传 `(object) []`。接口、权限、事件和错误原因常量在 `IChinese\Open\Gen\Meta`，含受平台授权控制的接口，SDK 本身不授予权限。

options 支持 `tenantKey`、`userAccessToken`、`accessToken`、`headers`；都没给时仅在接口接受应用令牌时自动取应用令牌。每个分页接口有 `方法名All()` 生成器，按需读下一页。

客户端配置：`timeout`（每个 HTTP 请求，默认 30 秒）、`maxRetries`（默认 3）、`refreshBeforeSeconds`（默认 300）、`userAgent`、`transport`。自定义 transport 签名为 `(method, url, headers, body, timeout) => [status, responseHeaders, rawBody]`；body 为字符串、文件资源或 null；网络失败抛 `OpenApiException(0, 'NETWORK', ...)`，超时用 `TIMEOUT`。同步使用，多个线程不共享同一实例。

应用和机构令牌按实例缓存，提前续期；401 令牌过期时重新取一次。429 尊重等待标头（日配额不重试）；GET/PUT/DELETE、取令牌及明确幂等的发帖/完成上传操作对网络错误和 5xx 退避重试。普通写入不自动重试；一次性授权码交换和刷新令牌也不重试。`clearTokenCache()` 清缓存，`revokeToken()` 撤销用户或刷新令牌，`request()` 可手动调用尚未生成的方法。

## 发帖与机构看板

```php
$key = $client->uploadImage($userToken, file_get_contents('image.png'), 'image/png');
$file = fopen('lesson.mp4', 'rb');
try {
    $video = $client->uploadVideo($userToken, $file, 'lesson.mp4', 'video/mp4');
} finally {
    fclose($file);
}
$ready = $client->waitVideoReady($userToken, $video['asset_id']);
$post = $client->createPost($userToken, ['text' => '你好']);
$dashboard = $client->getAllInsights($tenantKey, ['currency' => 'CNY']);
```

`uploadVideo` 接受二进制字符串或已打开、可定位的文件资源；资源从当前指针流式上传，由调用方关闭。旧系统处理大文件还取决于自身 PHP 位数和 cURL 能力。上传只发送存储槽要求的标头，不把平台令牌发给存储地址，不自动跟随重定向。

个人/机构各有 `uploadImage[AsOrg]`、`uploadVideo[AsOrg]`、`waitVideoReady[AsOrg]`、`createPost[AsOrg]`；机构版第一个参数是 `tenantKey`。视频轮询可传 `['timeout' => 1800, 'interval' => 2, 'maxInterval' => 15]`。发帖没给 UUID 时生成 UUID v4，同一次调用重试保持一致。业务自身重试请保存并复用 UUID。机构看板按返回 tabs 读取各页，保留隐私抑制的 null 与币种，不换算。

错误统一抛 `OpenApiException`：`status`、`errorCode`（字符串，区别于 PHP 异常的整数 code）、`requestId`、`details`、`reason`、`retryAfter`、`scope`、`neverAsk`、`isScopeError`、`consentUrl`。`consent_required` 要引导本人同意；`video_failed`、`video_not_ready`、`no_org_account` 等原因保持服务端原值。

## 事件和卡片回调

```php
use IChinese\Open\Events;

$payload = Events::parseEvent($headers, file_get_contents('php://input'), $signingSecret, $encryptKey);
if (Events::isUrlVerification($payload)) {
    header('Content-Type: application/json');
    echo json_encode(Events::challengeResponse($payload));
}
```

必须使用收到的原始请求体。默认容忍 300 秒时钟差，签名使用常量时间比较；先验签再解密。卡片用 `parseCardAction()`，相同协议。正常事件需在 5 秒内返回 2xx，按 `header.event_id` 去重（重试保留 ID）。PHP 7.1+ 优先使用原生 OpenSSL GCM；老版本以 phpseclib 的 CTR/GCM 算法计算并验证完整 16 字节标签，通过认证后才返回明文。

完整例子在 `examples/`。真实多区集成由 `DEV_SLOT=n pnpm sdk:test` 注入 `ICH_OPEN_IT`，未设置时仅跳过集成，单元和真实本地 HTTP 传输测试仍执行。GitHub 工作流覆盖 PHP 5.6、7.0、7.4、8.2、8.5。
