<?php
$arUrlRewrite=array (
  0 => 
  array (
    'CONDITION' => '#^/a1park_api/(personal)/(change|detail|cars_info|update_google_keys)$#',
    'RULE' => 'category=$1&action=$2',
    'ID' => '',
    'PATH' => '/api/user.php',
    'SORT' => 100,
  ),
  1 => 
  array (
    'CONDITION' => '#^/a1park_api/(order)/(list|detail|paystatus|activetariffs|penalty)?#',
    'RULE' => 'category=$1&action=$2',
    'ID' => '',
    'PATH' => '/api/order.php',
    'SORT' => 100,
  ),
  3 => 
  array (
    'CONDITION' => '#^/a1park_api/(parking)/(coordinates).+$#',
    'RULE' => 'category=$1&action=$2',
    'ID' => '',
    'PATH' => '/api/parking.php',
    'SORT' => 100,
  ),
  2 => 
  array (
    'CONDITION' => '#^/a1park_api/(balance)/(list|purchase)$#',
    'RULE' => 'category=$1&action=$2',
    'ID' => '',
    'PATH' => '/api/balance.php',
    'SORT' => 100,
  ),
  4 => 
  array (
    'CONDITION' => '#^/online/([\\.\\-0-9a-zA-Z]+)(/?)([^/]*)#',
    'RULE' => 'alias=$1',
    'ID' => '',
    'PATH' => '/desktop_app/router.php',
    'SORT' => 100,
  ),
  6 => 
  array (
    'CONDITION' => '#^/a1park_api/(parking)/(detail).+$#',
    'RULE' => 'category=$1&action=$2',
    'ID' => '',
    'PATH' => '/api/parking.php',
    'SORT' => 100,
  ),
  5 => 
  array (
    'CONDITION' => '#^/a1park_api/(registration|login)$#',
    'RULE' => 'category=$1',
    'ID' => '',
    'PATH' => '/api/user.php',
    'SORT' => 100,
  ),
  8 => 
  array (
    'CONDITION' => '#^/a1park_api/(action)/(parking)?#',
    'RULE' => 'category=$1&action=$2',
    'ID' => '',
    'PATH' => '/api/action.php',
    'SORT' => 100,
  ),
  7 => 
  array (
    'CONDITION' => '#^/a1park_api/(parking)/(cost).+$#',
    'RULE' => 'category=$1&action=$2',
    'ID' => '',
    'PATH' => '/api/parking.php',
    'SORT' => 100,
  ),
  9 => 
  array (
    'CONDITION' => '#^/a1park_api/(action)/(move)?#',
    'RULE' => 'category=$1&action=$2',
    'ID' => '',
    'PATH' => '/api/action.php',
    'SORT' => 100,
  ),
  10 => 
  array (
    'CONDITION' => '#^/bitrix/services/ymarket/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/bitrix/services/ymarket/index.php',
    'SORT' => 100,
  ),
  11 => 
  array (
    'CONDITION' => '#^/mobile_app/parking/#',
    'RULE' => '',
    'ID' => 'a1park:parking.subscription',
    'PATH' => '/mobile_app/parking/index.php',
    'SORT' => 100,
  ),
  12 => 
  array (
    'CONDITION' => '#^/online/(/?)([^/]*)#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/desktop_app/router.php',
    'SORT' => 100,
  ),
  14 => 
  array (
    'CONDITION' => '#^/stssync/calendar/#',
    'RULE' => '',
    'ID' => 'bitrix:stssync.server',
    'PATH' => '/bitrix/services/stssync/calendar/index.php',
    'SORT' => 100,
  ),
  13 => 
  array (
    'CONDITION' => '#^/mobile_app/order/#',
    'RULE' => '',
    'ID' => 'bitrix:sale.personal.order',
    'PATH' => '/mobile_app/order/index.php',
    'SORT' => 100,
  ),
  15 => 
  array (
    'CONDITION' => '#^/service/news/#',
    'RULE' => '',
    'ID' => 'bitrix:news',
    'PATH' => '/service/news.php',
    'SORT' => 100,
  ),
  16 => 
  array (
    'CONDITION' => '#^/parking/#',
    'RULE' => '',
    'ID' => 'a1park:parking.subscription',
    'PATH' => '/parking/index.php',
    'SORT' => 100,
  ),
  17 => 
  array (
    'CONDITION' => '#^/order/#',
    'RULE' => '',
    'ID' => 'bitrix:sale.personal.order',
    'PATH' => '/order/index.php',
    'SORT' => 100,
  ),
  18 =>
  array (
    'CONDITION' => '#^/rest/#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/bitrix/services/rest/index.php',
    'SORT' => 100,
  ),
    19 =>
        array (
            'CONDITION' => '#^/video/([\\.\\-0-9a-zA-Z]+)(/?)([^/]*)#',
            'RULE' => 'alias=$1&videoconf',
            'ID' => 'bitrix:im.router',
            'PATH' => '/desktop_app/router.php',
            'SORT' => 100,
        ),
);
