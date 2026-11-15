<?php
namespace IChinese\Open;

/** 开放接口错误；errorCode 是字符串错误码，status 为 0 表示未收到 HTTP 响应。 */
final class OpenApiException extends \RuntimeException
{
    public $status;
    public $errorCode;
    public $requestId;
    public $details;
    public $retryAfter;
    public $reason;
    public $scope;
    public $neverAsk;
    public $consentUrl;
    public $isScopeError;

    public function __construct($status, $errorCode, $message = '', $requestId = null, array $details = [], $retryAfter = null, $previous = null)
    {
        parent::__construct($message !== '' ? $message : $errorCode, 0, $previous);
        $this->status = $status;
        $this->errorCode = $errorCode;
        $this->requestId = $requestId;
        $this->details = $details;
        $this->retryAfter = $retryAfter;
        $this->reason = isset($details['reason']) && is_string($details['reason']) ? $details['reason'] : null;
        $this->scope = isset($details['scope']) && is_string($details['scope']) ? $details['scope'] : null;
        $this->neverAsk = isset($details['never_ask']) && $details['never_ask'] === true;
        $this->consentUrl = isset($details['consent_url']) && is_string($details['consent_url']) ? $details['consent_url'] : null;
        $this->isScopeError = $status === 403 && in_array($this->reason, ['scope_denied', 'scope_pending', 'scope_not_declared', 'gold_only'], true);
    }
}
