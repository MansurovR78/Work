<?php
$_SERVER["DOCUMENT_ROOT"] = dirname(__FILE__);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$cron = new CronIvideon();

switch ($argv[1]) {
    case 'REFRESH':
        $cron->refresh();
        break;
    default:
        $cron->run();
        break;
}

