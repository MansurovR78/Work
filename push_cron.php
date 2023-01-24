<?php

$_SERVER["DOCUMENT_ROOT"] = dirname(__FILE__);
set_time_limit(0);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

CModule::IncludeModule('sale');
CModule::IncludeModule('iblock');

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/qsoft/classes/push/PushMessage.php';

$obPushMessage = new PushMessage;
//получаем все заказы, кроме принятых, выполенных и исторических
$orders = []; //массив заказов
$users = []; //массив пользователей
$message = 'Время вашей парковки истекло. Для выезда потребуется продление тарифа.'; //сообщение при окончании времени парковки

$filter = ['STATUS_ID' => ['E', 'L', 'P']];

$res = CSaleOrder::GetList(
    [],
    $filter,
    false,
    false,
    [
        'ID',
        'USER_ID',
        'STATUS_ID',
        'PROPERTY_VAL_BY_CODE_PARKING_ID',
        'PROPERTY_VAL_BY_CODE_USE_END',
    ]
);
while ($order = $res->Fetch()) {
    if ($order['STATUS_ID'] !== 'N' && $order['USER_ID'] != FAST_ENTRANCE_USER_ID) {
        $orders[$order['ID']]['PARKING_ID'] = $order['PROPERTY_VAL_BY_CODE_PARKING_ID'];
        $orders[$order['ID']]['ID'] = $order['ID'];
        $orders[$order['ID']]['USER_ID'] = (int) $order['USER_ID'];
        $orders[$order['ID']]['END'] = (int) strtotime(date('d-m-Y H:i', $order['PROPERTY_VAL_BY_CODE_USE_END']));
    }
}

foreach($orders as $order_id => $order){
    if($order["END"] <= time() && $order["END"] >= time() - PUSH_CRON_TIME){
        if(($order['PARKING_ID'] == 5015 || $order['PARKING_ID'] == 296346) && $order_id > 100800){
            $obPushMessage->sendMessage($order['USER_ID'], $order, 'Заказ № '.$order_id.' '.$message);
            echo ' Заказ № '.$order_id.' '.$message.'<br>';
        }
    }
}