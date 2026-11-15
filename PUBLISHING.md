# PHP SDK 发布准备

用户已授权创建公开源码仓库 `ichinese-cn/open-sdk-php` 并初始化 PHP SDK；Packagist 首次登记已由用户完成，当前可读到 dev-main。平台生产推送与正式 SDK tag 仍按统一发布流程执行。

## 用户需要准备

1. **Packagist 账号**：在 https://packagist.org/ 注册并验证邮箱。可关联 GitHub 账号，便于自动同步。包名拟用 `ichinese/open-sdk`；若 `ichinese` vendor 已由别人使用，需要其维护者授权；账号名不必叫 `ichinese`，以实际提交时的命名权限为准。
2. **GitHub 组织管理权限**：沿用已有 `ichinese-cn`。已创建公开仓库 `open-sdk-php`，默认分支 main；不要把平台单体仓库公开。将现有 GitHub App `ichinese-sdk-release` 的安装范围包含新仓库，继续使用现有 Contents/Workflows 写权限，不用另建发布 PAT。
3. **首次登记和自动更新**：SDK 第一个经过验证的 tag 推送后，在 Packagist 的 Submit 中提交 `https://github.com/ichinese-cn/open-sdk-php`。启用官方 GitHub 自动更新集成（或 Packagist 的 GitHub webhook），随后 tag 自动被索引。人工提交、GitHub 授权和 webhook 配置留待用户授权后执行。
4. **实际运行环境信息**：提供 `php -v`、`php -m`、`php --ri curl` 的输出即可（不需要服务器密码）。最低 5.6.1，5.6 系列建议 5.6.40，检查 curl/openssl/json/hash/ctype；外网请求需要可用 CA 证书和服务端支持的 TLS。5.6/7.0/7.1 用 Composer 2.2 LTS，安装器加 `--2.2`；新 Composer 不在这些旧运行时执行。Composer 安装依赖时还需 Zip 扩展或 unzip/7z（也可用 Git 源码安装），这不是 SDK 运行时的额外扩展要求。

使用者的安装命令（首次发布成功后）：

```sh
composer require 'ichinese/open-sdk:^1.0@rc'
```

`@rc` 仅对这个包允许预发布，不放宽应用其他依赖的稳定性。

## 本地与自动发布流程

- `pnpm sdk:gen` / `pnpm check:sdk`：生成/检查所有接口、分页方法、常量、Composer 元数据和共享事件向量。
- `pnpm test` 的 sdk-tools 任务包含 PHP 单元测试；没有 PHP 或 Composer 时遵循现有跨语言工具链策略，明确记录跳过。专用 GitHub CI 矩阵始终安装对应工具链并运行，覆盖 PHP 5.6、7.0、7.4、8.2、8.5。
- `DEV_SLOT=n pnpm sdk:test`：统一真实多区集成，PHP 消费同一个 `ICH_OPEN_IT` fixture。
- `pnpm sdk:publish -- --verify`：为全部 SDK 生成独立副本并在副本上验证；不加 `--push` 不会推送。PHP 的验证包含 Composer 元数据校验、安装依赖、测试及 ZIP 打包。
- PHP 副本在 `sdks/.release/src-php/`，包含自己的 LICENSE、README、生成代码、工作流、测试向量及 RELEASE.json，剔除 vendor、composer.lock 和本机生成物。源码中的基础版本在发布时替换成统一实际版本（含 rc.N）；Composer 包不写固定 version，由 tag 决定。
- 沿用 D96：生产接口部署完成后统一发布任务同步各仓库，PHP 在 Go 前推送，Go 仍最后作为整次发布的记录。独立 PHP 工作流校验 tag 与 Meta::VERSION、运行兼容矩阵、打包。
- Packagist 本身由仓库 webhook 读取 tag，与 GitHub Actions 校验独立；正式推送前应完成独立副本验证。无需 npm token、PyPI token、Maven GPG 或额外的 Packagist CI 写入密钥。

正式发布前核对 Packagist 自动同步状态和实际登记地址。PHP SDK 继续与其他语言使用同一个版本号，不自行从 rc.1 重新编号。

依据：Packagist 官方发布与自动更新说明 https://packagist.org/about ，Composer 旧 PHP 下载渠道 https://getcomposer.org/download/ 。
