<?php
require __DIR__ . '/../vendor/autoload.php';
use IChinese\Open\Client;
$client = new Client(getenv('APP_ID'), getenv('APP_SECRET'));
foreach ($client->listTenantMembersAll(null, ['tenantKey' => getenv('TENANT_KEY')]) as $member) {
    echo $member['open_id'], ' ', $member['name'], PHP_EOL;
}
