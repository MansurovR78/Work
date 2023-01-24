<?

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    define("NO_KEEP_STATISTIC", true);
    define("NOT_CHECK_PERMISSIONS",true);

    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
    require_once('bitrix/php_interface/include/qsoft/classes/api_fastEntrance/api_fastEntrance.php');

    $fastEntrance = new ApiFastEntrance();

    //Оплата
    if (isset($_POST['pay'])) {
        if(empty($_POST['BILL_EMAIL'])){
            $APPLICATION->RestartBuffer();
            header('Content-type: application/json');
            echo json_encode(['error' => 'Поле email обязательно для заполнения']);
            die();
        }

        //Проверка хеша (верификация данных со страницы заказа)
        $orderID = intval($_GET['order_id']);
        $arOrder = CSaleOrder::GetList(
            array(),
            array('ID' => $orderID),
            false,
            false,
            array()
        )->Fetch();

        CSalePaySystemAction::InitParamArrays($arOrder, $orderID);

        $option = array(
            'baseUrl' => 'https://secure.payu.ru',
            'merchant' => CSalePaySystemAction::GetParamValue('MERCHANT'),
            'secretkey' => CSalePaySystemAction::GetParamValue('SECURE_KEY'),
            'debug' => CSalePaySystemAction::GetParamValue('DEBUG_MODE')
        );

        // Проверим наличие индивидуального мерчанта для парковки
        $orderRes = CSaleOrder::GetList(
            ['ID' => 'DESC'],
            ['ID' => $orderID],
            false,
            false,
            [
                'ID',
                'PROPERTY_VAL_BY_CODE_PARKING_XML_ID',
            ]
        )->Fetch();

        // Проверяем по внешнему ключу, если парковка в списке индивидуальных парковок, то меняем кабинет PAYU.
        if (isset($orderRes['PROPERTY_VAL_BY_CODE_PARKING_XML_ID']) && ! empty($orderRes['PROPERTY_VAL_BY_CODE_PARKING_XML_ID'])) {
            if (in_array($orderRes['PROPERTY_VAL_BY_CODE_PARKING_XML_ID'], array_keys($INDIVIDUAL_PARKINGS))) {
                $option['merchant'] = $INDIVIDUAL_PARKINGS[$orderRes['PROPERTY_VAL_BY_CODE_PARKING_XML_ID']]['MERCHANT_ID'];
                $option['secretkey'] = $INDIVIDUAL_PARKINGS[$orderRes['PROPERTY_VAL_BY_CODE_PARKING_XML_ID']]['SECRET_KEY'];
            }
        }

        if($option['debug'] == 'Y'){
            $option['baseUrl'] = 'https://sandbox.payu.ru';
            $option['merchant'] = 'aparkcom';
            $option['secretkey'] = 'a1park_secret';
        }

        $postOrderHash = $_POST['ORDER_HASH'];
        unset($_POST['ORDER_HASH']);
        unset($_POST['pay']);

        ksort($_POST);
        $postString = '';
        foreach ($_POST as $key => $val) {
            if($key === 'PAY_METHOD'){
                $val = 'CCVISAMC';
            }

            if($key === 'LU_ENABLE_TOKEN'){
                $val = 1;
            }

            if (is_array($val)) {
                foreach ($val as $v) {
                    $postString .= strlen($v) . $v;
                }
            } else {
                $postString .= strlen($val) . $val;
            }
        }
        $postHash = hash_hmac('md5', $postString, $option['secretkey']);

        //Отправка запроса оплаты
        if (hash_equals($postHash, $postOrderHash) || $option['debug'] == 'Y') {
            $paymentMethod = 'CCVISAMC';
            if(isset($_POST['PAY_METHOD'])){
                $paymentMethod = $_POST['PAY_METHOD'];
            }

            $headers = [
                'X-Header-Merchant' => $option['merchant'],
                'X-Header-Date' => date('c'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ];

            $data = [
                'merchantPaymentReference' => $_POST['ORDER_REF'],
                'currency' => 'RUB',
                'returnUrl' => $_POST['BACK_REF'],
                'authorization' => [
                    'paymentMethod' => $paymentMethod,
                ],
                'client' => [
                    'billing' => [
                        'firstName' => $_POST['BILL_FNAME'],
                        'lastName' => $_POST['BILL_LNAME'],
                        'email' => $_POST['BILL_EMAIL'],
                        'countryCode' => $_POST['BILL_COUNTRYCODE'],
                        'phone' => $_POST['BILL_PHONE']
                    ]
                ],
                'products' => []
            ];

            if($paymentMethod == 'CCVISAMC'){
                $data['authorization']['usePaymentPage'] = 'YES';
            }

            foreach ($_POST['ORDER_PNAME'] as $k => $name){
                $data['products'][] = [
                    'name' => $name,
                    'sku' => $_POST['ORDER_PCODE'][$k],
                    'additionalDetails' => $_POST['ORDER_PINFO'][$k],
                    'unitPrice' => $_POST['ORDER_PRICE'][$k],
                    'quantity' => $_POST['ORDER_QTY'][$k],
                    'vat' => $_POST['ORDER_VAT'][$k]
                ];
            }

            $body = json_encode($data);

            $baseString = $headers['X-Header-Merchant'];
            $baseString .= $headers['X-Header-Date'];
            $baseString .= 'POST';
            $baseString .= '/api/v4/payments/authorize';
            $baseString .= md5($body);

            $realPostHash = hash_hmac('sha256', $baseString, $option['secretkey']);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $option['baseUrl'] . '/api/v4/payments/authorize',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'X-Header-Signature: ' . $realPostHash,
                    'X-Header-Merchant: ' . $headers['X-Header-Merchant'],
                    'X-Header-Date: ' . $headers['X-Header-Date'],
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $APPLICATION->RestartBuffer();
            header('Content-type: application/json');
            try{
                $result = json_decode($response, true);
                if($result['status'] === 'SUCCESS'){
                    $paymentResult = $result['paymentResult'];
                    if(isset($paymentResult['bankResponseDetails'])) {
                        setcookie('blocked_payment', 'Y', time() + 60 * 1, '/');
                        setcookie('retry_payment', time() + 60 * 1, time() + 60 * 1, '/');
                        echo json_encode(['url' => $paymentResult['bankResponseDetails']['customBankNode']['url']]);
                    }else{
                        echo json_encode(['url' => $paymentResult['url']]);
                    }
                    die();
                }else{
                    echo json_encode(['error' => $result['message']]);
                }
            }catch (Exception $exception){
                echo json_encode(['error' => $exception->getMessage()]);
            }

            /*require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/sale_payment/payu/payu.cls.php';

            $_POST['BILL_EMAIL'] = filter_var($_POST['BILL_EMAIL'], FILTER_VALIDATE_EMAIL) ?: 'fe_user@a1park.com';

            $option['luUrl'] = CSalePaySystemAction::GetParamValue('LU_URL');

            $pay = PayU::getInst()->setOptions($option)->setData($_POST)->QSOFT_LU();

            $_POST['ORDER_HASH'] = $pay['PARAMS']['ORDER_HASH'];

            //Создание и отправка формы
            ?>
            <form method="POST" action="<?=$option['luUrl']?>" id="FEPayForm" name="FEPayForm" style="display: none;">
            <?
            foreach ($_POST as $name => $value) {
                if (is_array($value)) {
                    foreach ($value as $key => $val) {
                        ?>
                            <input name="<?=$name.'[]'?>" type="hidden" value="<?=$val?>" id="<?=$name.$key?>">
                        <?
                    }
                } else {
                    ?>
                        <input name="<?=$name?>" type="hidden" value="<?=$value?>" id="<?=$name?>">
                    <?
                }
            }
            ?>
            <input type="submit" value="Оплатить">
            </form>
            <script>
                window.onload = function(){
                    document.getElementById("FEPayForm").submit();
                };
            </script>
            <?*/
            die();
        } else {
            ApiFastEntrance::logError('WARNING', Loc::getMessage('FAST_ENTRANCE_WARNING_WRONG_DATA'), $orderID);
        }
    } elseif (isset($_POST['departure'])) { //Запрос на выезд от самой страницы

        $result = $fastEntrance->InitDeparture();

        $APPLICATION->RestartBuffer();
        header('Content-type: application/json');
        echo $result;
        die();

    } else { //Запрос от стенда
        //Если после оплаты GET+POST-запрос - редиректим на страницу быстрого заезда без лишних параметров
        if (isset($_GET['3dsecure']) && isset($_GET['result'])) {
            unset($_GET['result']);
            unset($_GET['3dsecure']);
            unset($_GET['date']);
            unset($_GET['ctrl']);
            unset($_GET['payrefno']);
            $url = $APPLICATION->GetCurPageParam();

            // Задаем куки при удачной оплате.
            setcookie('blocked_payment', 'Y', time() + 60 * 3, '/');
            setcookie('retry_payment', time() + 60 * 3, time() + 60 * 3, '/');
            header("Location: " . $url);
            die();
        }

        if(!empty($_POST['signature'])) {
            $orderID = intval($_GET['order_id']);
            try {
                $result = json_decode($_POST['body']);
                switch ($result->code) {
                    case 200:
                    case 201:
                    case 202:
                        $url = $APPLICATION->GetCurPageParam();
                        // Задаем куки при удачной оплате.
                        setcookie('blocked_payment', 'Y', time() + 60 * 3, '/');
                        setcookie('retry_payment', time() + 60 * 3, time() + 60 * 3, '/');
                        header("Location: " . $url);
                        die();
                        break;
                    default:
                        ShowError($result->message);
                        die();
                        break;
                }
            }catch (Exception $e){
                ApiFastEntrance::logError('WARNING', $e->getMessage(), $orderID);
            }
        }

        $result = $fastEntrance->FastEntrance(file_get_contents('php://input'));

        $APPLICATION->RestartBuffer();
        header('Content-type: application/json');
        echo $result;
        die();

    }

} else {

    if (isset($_GET['check_pay_status']) && $_GET['check_pay_status'] === 'Y') {
        define("NO_KEEP_STATISTIC", true);
        define("NOT_CHECK_PERMISSIONS",true);
    }

    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
    require_once('bitrix/php_interface/include/qsoft/classes/api_fastEntrance/api_fastEntrance.php');

    //Избавляемся от параметров, пришедших после оплаты
    if (isset($_GET['3dsecure']) && isset($_GET['result'])) {
        unset($_GET['result']);
        unset($_GET['3dsecure']);
        unset($_GET['date']);
        unset($_GET['ctrl']);
        unset($_GET['payrefno']);
        $url = $APPLICATION->GetCurPageParam();

        // Задаем куки при удачной оплате.
        setcookie('blocked_payment', 'Y', time() + 60 * 5, '/');
        setcookie('retry_payment', time() + 60 * 5, time() + 60 * 5, '/');
        header("Location: " . $url);
        die();
    }

    Loc::loadMessages(__FILE__);

    $APPLICATION->SetTitle(Loc::getMessage('FAST_ENTRANCE_TITLE_CORRECT', ['#ORDER#' => intval($_GET['order_id'])]));

    function showErrorMess($message) {
        global $APPLICATION;
        $APPLICATION->SetTitle(Loc::getMessage('FAST_ENTRANCE_TITLE_ERROR'));
        echo '<span style="color: red;">' . $message . '</span>';
    }

    if (! Loader::includeModule('sale')) {
        showErrorMess(Loc::getMessage('FAST_ENTRANCE_ERROR_NO_MODULE_SALE'));
        die();
    }

    if (isset($_GET['order_id']) && isset($_GET['order_code'])) {

        //Получаем информацию о заказе
        $orderID = intval($_GET['order_id']);
        $orderInfo = [];
        $linkIsValid = false;

        if ($orderID > 0) {
            $dbOrder = CSaleOrder::GetList(
                ['ID' => 'DESC'],
                ['ID' => $orderID],
                false,
                false,
                [
                    'ID',
                    'STATUS_ID',
                    'DATE_INSERT',
                    'DATE_STATUS',
                    'PROPERTY_VAL_BY_CODE_PARKING_ID',
                    'PROPERTY_VAL_BY_CODE_PARKING_XML_ID',
                    'PROPERTY_VAL_BY_CODE_USE_START',
                    'PROPERTY_VAL_BY_CODE_USE_END',
                    'PROPERTY_VAL_BY_CODE_FAST_ENTRANCE_TOKEN',
                    'SUM_PAID',
                    'PRICE'
                ]
            );

            if ($orderInfo = $dbOrder->Fetch()) {
                //Проверяем валидность ссылки
                if (hash_equals($orderInfo['PROPERTY_VAL_BY_CODE_FAST_ENTRANCE_TOKEN'], $_GET['order_code'])) {
                    $linkIsValid = true;
                }
            } else {
                ApiFastEntrance::logError('WARNING', Loc::getMessage('FAST_ENTRANCE_WARNING_WRONG_DATA'), $orderID);
                showErrorMess(Loc::getMessage('FAST_ENTRANCE_ERROR_ORDER_NOT_FOUND'));
                die();
            }
        }

        if (! $linkIsValid) {
            ApiFastEntrance::logError('WARNING', Loc::getMessage('FAST_ENTRANCE_WARNING_WRONG_DATA'), $orderID);
            showErrorMess(Loc::getMessage('FAST_ENTRANCE_ERROR_INCORRECT_LINK'));
            die();

        } else {

            /** ОСНОВНАЯ ЛОГИКА СТРАНИЦЫ ЗАКАЗА */

            //Если есть ошибка от PayU - показываем
            if (isset($_GET['err']) && ! empty($_GET['err'])) {
                showErrorMess(Loc::getMessage('FAST_ENTRANCE_ERROR_PAYU_ERROR', ['#ERROR#' => $_GET['err']]));
            }

            //Код стенда
            $secretKey = ApiFastEntrance::getCode($orderInfo['PROPERTY_VAL_BY_CODE_PARKING_XML_ID']);

            //Получаем информацию о парковке
            if (isset($orderInfo['PROPERTY_VAL_BY_CODE_PARKING_ID']) && ! empty($orderInfo['PROPERTY_VAL_BY_CODE_PARKING_ID'])) {
                $obParking = CIBlockElement::GetList(
                    array(),
                    ['IBLOCK_ID' => IB_PARKING, 'ID' => $orderInfo['PROPERTY_VAL_BY_CODE_PARKING_ID']],
                    false,
                    false,
                    ['ID', 'NAME', 'PROPERTY_ADDRESS']
                );
                while ($parking = $obParking->Fetch()) {
                    $orderInfo['PARKING_NAME'] = $parking['NAME'];
                    $orderInfo['PARKING_ID'] = $orderInfo['PROPERTY_VAL_BY_CODE_PARKING_ID'];
                    $orderInfo['PARKING_XML_ID'] = $orderInfo['PROPERTY_VAL_BY_CODE_PARKING_XML_ID'];
                    $orderInfo['PARKING_ADDRESS'] = $parking['PROPERTY_ADDRESS_VALUE'];
                }
            }

            //Получаем ID тарифа
            $dbRes = \Bitrix\Sale\Basket::getList([
                'select' => ['PRODUCT_ID'],
                'filter' => [
                    '=ORDER_ID' => $orderInfo['ID'],
                    '=LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
                ]
            ]);
            if ($item = $dbRes->fetch())
            {
                $orderInfo['TARIFF_ID'] = $item['PRODUCT_ID'];
            }

            //Дедлайн выезда с парковки
            if ($orderInfo['STATUS_ID'] === 'O') {
                $deadline = (int) MakeTimeStamp($orderInfo['DATE_STATUS'], CSite::GetDateFormat()) + FE_DEPARTURE_DELAY;
            } else {
                $deadline = time();
            }

            // Если по заказу был выезд и время выезда превышено - возвращаем статус "Въезд", удаляем из таблицы выездов
            if (($orderInfo['STATUS_ID'] === 'O' || $orderInfo['STATUS_ID'] === 'E') && time() >= $deadline) {
                CSaleOrder::StatusOrder($orderID, 'E');
                $orderInfo['STATUS_ID'] = 'E';
                $departureInfo = ApiFastEntrance::departureProcessGetInfo($orderInfo['PARKING_XML_ID']);
                if ($departureInfo && time() >= strtotime($departureInfo['DATE_EXPIRES'] && $departureInfo['ORDER_ID'] == $orderInfo['ID'])) {
                    ApiFastEntrance::departureProcessRemoveInfo($orderInfo['PARKING_XML_ID']);
                }
            }

            //Отображение статуса
            $orderInfo['STATUS_NAME'] = CSaleStatus::GetByID($orderInfo['STATUS_ID'])['NAME'];
            if ($orderInfo['STATUS_ID'] === 'E') {
                $orderInfo['STATUS_NAME'] = Loc::getMessage('FAST_ENTRANCE_ORDER_INFO_ON_PARKING');
            } elseif ($orderInfo['STATUS_ID'] === 'P') {
                $orderInfo['STATUS_NAME'] = Loc::getMessage('FAST_ENTRANCE_ORDER_INFO_READY_TO_ENTER');
            }

            //Если AJAX-проверка статуса по оплате
            if (isset($_GET['check_pay_status']) && $_GET['check_pay_status'] === 'Y') {

                //Пересчет суммы заказа (тихий)
                ApiFastEntrance::recalculateOrder($orderInfo, true);

                $APPLICATION->RestartBuffer();
                $result = [
                    'PAYED' => $orderInfo['LEFT_TO_PAY'] !== 0.0 ? 'N' : 'Y'
                ];
                header('Content-type: application/json');
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                die();

            } else {

                //Пересчет суммы заказа
                ApiFastEntrance::recalculateOrder($orderInfo);

            }

            //Данные для формы
            if ($orderInfo['LEFT_TO_PAY'] !== 0.0) {
                $arDataSend = ApiFastEntrance::getFieldsData($orderID, $orderInfo);
            }

            /** ВИЗУАЛЬНАЯ ЧАСТЬ */

            //Информация о тарифе
            ?>
            <style>
                body *{
                    box-sizing: border-box;
                }
                .page-content.inner{
                    padding: 0 2% 0;
                    min-height: auto;
                }
                .page-content .table,
                .page-content .theader{
                    margin-top: 0 !important;
                }
                .payButton:last-child{
                    margin-bottom: 0;
                }
            </style>
            <div class="page-content inner">
                <div class="table">
                    <div class="theader" id="common_header"><?=Loc::getMessage('FAST_ENTRANCE_ORDER_INFO_TITLE')?></div>
                    <div class="theader" id="update_header" style="display: none;"><?=Loc::getMessage('FAST_ENTRANCE_ORDER_INFO_TITLE_UPDATE')?></div>
                    <? if (isset($orderInfo['PARKING_ID'])): ?>
                        <div class="tr">
                            <div class="td"><?=Loc::getMessage('FAST_ENTRANCE_ORDER_INFO_PARKING')?></div>
                            <div class="td"><?=$orderInfo['PARKING_NAME'] . (isset($orderInfo['PARKING_ADDRESS']) ? ' (' . $orderInfo['PARKING_ADDRESS'] . ')' : '')?></div>
                        </div>
                    <? endif; ?>
                    <div class="tr">
                        <div class="td"><?=Loc::getMessage('FAST_ENTRANCE_ORDER_INFO_ID')?></div>
                        <div class="td"><?=$orderInfo["ID"]?></div>
                    </div>
                    <div class="tr">
                        <div class="td"><?=Loc::getMessage("FAST_ENTRANCE_ORDER_INFO_CREATED_AT")?></div>
                        <div class="td"><?=FormatDate('d F Y', strtotime($orderInfo["DATE_INSERT"]))?></div>
                    </div>
                    <div class="tr">
                        <div class="td"><?=Loc::getMessage("FAST_ENTRANCE_ORDER_INFO_STATUS")?></div>
                        <div class="td"><?=$orderInfo['STATUS_NAME']?></div>
                    </div>
                    <?if (! empty($orderInfo["PROPERTY_VAL_BY_CODE_USE_START"])):?>
                        <div class="tr">
                            <div class="td"><?=Loc::getMessage("FAST_ENTRANCE_ORDER_INFO_USE_START")?></div>
                            <div class="td"><?=FormatDate('d F Y H:i', $orderInfo["PROPERTY_VAL_BY_CODE_USE_START"])?></div>
                        </div>
                    <?endif?>
                    <?if (! empty($orderInfo["PROPERTY_VAL_BY_CODE_USE_END"])):?>
                        <div class="tr">
                            <div class="td"><?=Loc::getMessage("FAST_ENTRANCE_ORDER_INFO_USE_END")?></div>
                            <div class="td"><?=FormatDate('d F Y H:i', $orderInfo["PROPERTY_VAL_BY_CODE_USE_END"])?></div>
                        </div>
                    <?endif?>
                    <div class="tr">
                        <div class="td"><?=Loc::getMessage("FAST_ENTRANCE_ORDER_INFO_SUM")?></div>
                        <div class="td"><?=Loc::getMessage("FAST_ENTRANCE_ORDER_INFO_TO_PAY", ['#PRICE#' => (float) $orderInfo['PRICE']])?></div>
                    </div>
                    <div class="tr">
                        <div class="td"><?=Loc::getMessage("FAST_ENTRANCE_ORDER_INFO_SUM_REMAINS")?></div>
                        <div class="td"><?=Loc::getMessage("FAST_ENTRANCE_ORDER_INFO_LEFT_TO_PAY", ['#PRICE#' => (float) $orderInfo['LEFT_TO_PAY'], '#TOTAL#' => isset($arDataSend) ? (float) $arDataSend['ORDER_PRICE[0]'] + (float) $arDataSend['ORDER_PRICE[1]'] : 0])?></div>
                    </div>
                </div>
                <? /* ОБЛАСТЬ КНОПОК ОПЛАТЫ */ ?>
                <? if ($orderInfo['STATUS_ID'] === 'E' && $orderInfo['LEFT_TO_PAY'] !== 0.0 /* && есть неоплаченный остаток */): ?>
                    <form method="POST" action="" class="form" onsubmit="return false;">
                        <input name="pay" type="hidden" value="1">
                        <input name="MERCHANT" type="hidden" value="<?=$arDataSend['MERCHANT']?>" id="MERCHANT">
                        <input name="ORDER_REF" type="hidden" value="<?=$arDataSend['ORDER_REF']?>" id="ORDER_REF">
                        <input name="ORDER_DATE" type="hidden" value="<?=$arDataSend['ORDER_DATE']?>" id="ORDER_DATE">
                        <input name="PRICES_CURRENCY" type="hidden" value="<?=$arDataSend['PRICES_CURRENCY']?>" id="PRICES_CURRENCY">
                        <input name="PAY_METHOD" type="hidden" value="<?=$arDataSend['PAY_METHOD']?>" id="PAY_METHOD">
                        <input name="LANGUAGE" type="hidden" value="<?=$arDataSend['LANGUAGE']?>" id="LANGUAGE">
                        <input name="AUTOMODE" type="hidden" value="<?=$arDataSend['AUTOMODE']?>" id="AUTOMODE">
                        <input name="BILL_FNAME" type="hidden" value="<?=$arDataSend['BILL_FNAME']?>" id="BILL_FNAME">
                        <input name="BILL_LNAME" type="hidden" value="<?=$arDataSend['BILL_LNAME']?>" id="BILL_LNAME">
                        <input name="BILL_PHONE" type="hidden" value="<?=$arDataSend['BILL_PHONE']?>" id="BILL_PHONE">
                        <input name="BILL_COUNTRYCODE" type="hidden" value="<?=$arDataSend['BILL_COUNTRYCODE']?>" id="BILL_COUNTRYCODE">
                        <input name="LU_ENABLE_TOKEN" type="hidden" value="<?=$arDataSend['LU_ENABLE_TOKEN']?>" id="LU_ENABLE_TOKEN">
                        <input name="BACK_REF" type="hidden" value="<?=$arDataSend['BACK_REF']?>" id="BACK_REF">
                        <input name="ORDER_PNAME[]" type="hidden" value="<?=$arDataSend['ORDER_PNAME[0]']?>" id="ORDER_PNAME0">
                        <input name="ORDER_PCODE[]" type="hidden" value="<?=$arDataSend['ORDER_PCODE[0]']?>" id="ORDER_PCODE0">
                        <input name="ORDER_PINFO[]" type="hidden" value="<?=$arDataSend['ORDER_PINFO[0]']?>" id="ORDER_PINFO0">
                        <input name="ORDER_PRICE[]" type="hidden" value="<?=$arDataSend['ORDER_PRICE[0]']?>" id="ORDER_PRICE0">
                        <input name="ORDER_QTY[]" type="hidden" value="<?=$arDataSend['ORDER_QTY[0]']?>" id="ORDER_QTY0">
                        <input name="ORDER_VAT[]" type="hidden" value="<?=$arDataSend['ORDER_VAT[0]']?>" id="ORDER_VAT0">
                        <input name="ORDER_PRICE_TYPE[]" type="hidden" value="<?=$arDataSend['ORDER_PRICE_TYPE[0]']?>" id="ORDER_PRICE_TYPE0">
                        <?if (isset($arDataSend['ORDER_PNAME[1]'])) :?>
                            <input name="ORDER_PNAME[]" type="hidden" value="<?=$arDataSend['ORDER_PNAME[1]']?>" id="ORDER_PNAME1">
                            <input name="ORDER_PCODE[]" type="hidden" value="<?=$arDataSend['ORDER_PCODE[1]']?>" id="ORDER_PCODE1">
                            <input name="ORDER_PINFO[]" type="hidden" value="<?=$arDataSend['ORDER_PINFO[1]']?>" id="ORDER_PINFO1">
                            <input name="ORDER_PRICE[]" type="hidden" value="<?=$arDataSend['ORDER_PRICE[1]']?>" id="ORDER_PRICE1">
                            <input name="ORDER_QTY[]" type="hidden" value="<?=$arDataSend['ORDER_QTY[1]']?>" id="ORDER_QTY1">
                            <input name="ORDER_VAT[]" type="hidden" value="<?=$arDataSend['ORDER_VAT[1]']?>" id="ORDER_VAT1">
                            <input name="ORDER_PRICE_TYPE[]" type="hidden" value="<?=$arDataSend['ORDER_PRICE_TYPE[1]']?>" id="ORDER_PRICE_TYPE1">
                        <?endif;

                        // Проверяем, существует ли куки блокировки оплаты на случай удачной оплаты.
                        $blocked = false;
                        if (isset($_COOKIE['blocked_payment']) && $_COOKIE['blocked_payment'] == 'Y') {
                            $blocked = true;
                        }
                        ?>
                        <input name="ORDER_HASH" type="hidden" value="<?=$arDataSend['ORDER_HASH']?>" id="ORDER_HASH">
                        <? if (! $blocked) :?>
                            <span id="dep_btn_info" class="order_help-text" style="color: black;"><?=Loc::getMessage("FAST_ENTRANCE_BUTTONS_PAYMENT_EMAIL_HINT")?></span>
                            <input name="BILL_EMAIL" type="email" style="border: 1px solid black; margin: 10px 0 20px 0;" id="BILL_EMAIL" placeholder="example@a1park.com" required value="<?=$arDataSend['BILL_EMAIL']?>" />
                        <?endif;?>
                        <? // Блокируем кнопку в случае проведения оплаты ?>
                        <button type="button" class="btn payButton" style="margin-top: 0;<?=$blocked ? "background-color:gray;" : "" ?>" id="payButton" <?=$blocked ? 'disabled' : "" ?>><?=$blocked ? "Идет оплата <span class='loader'></span>" : "Оплатить" ?></button>
                        <button type="button" class="btn payButton" style="margin-top: 0; background-color: #1d1346; <?=$blocked ? "display:none;background-color:gray;" : "" ?>" id="sbpPayButton" <?=$blocked ? 'disabled' : "" ?>><?=$blocked ? "Идет оплата <span class='loader'></span>" : 'Оплатить
                        <svg style="vertical-align: middle" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 120 120" fill="none"><path d="M0 26.12l14.532 25.975v15.844L.017 93.863 0 26.12z" fill="#5B57A2"/><path d="M55.797 42.643l13.617-8.346 27.868-.026-41.485 25.414V42.643z" fill="#D90751"/><path d="M55.72 25.967l.077 34.39-14.566-8.95V0l14.49 25.967z" fill="#FAB718"/><path d="M97.282 34.271l-27.869.026-13.693-8.33L41.231 0l56.05 34.271z" fill="#ED6F26"/><path d="M55.797 94.007V77.322l-14.566-8.78.008 51.458 14.558-25.993z" fill="#63B22F"/><path d="M69.38 85.737L14.531 52.095 0 26.12l97.223 59.583-27.844.034z" fill="#1487C9"/><path d="M41.24 120l14.556-25.993 13.583-8.27 27.843-.034L41.24 120z" fill="#017F36"/><path d="M.017 93.863l41.333-25.32-13.896-8.526-12.922 7.922L.017 93.863z" fill="#984995"/></svg> сбп' ?></button>
                        <?if($blocked):?>
                            <div style="text-align: center;">
                                <div style="display:inline-block;color: #856404;background-color: #fff3cd;border:1px solid #ffeeba;font-size: 14px;line-height:1.15;padding: 0.75rem;">сейчас появится кнопка выезд, не закрывайте окно</div>
                            </div>
                        <?endif;?>
                        <?/*if (isset($_COOKIE['retry_payment'])):
                            $diff = $_COOKIE['retry_payment'] - time();
                            $minutes = floor($diff % 3600 / 60);
                            $seconds = floor($diff % 60);
                            ?>
                            <div style="font-size: 0.95em;text-align: center">Повторить попытку через <span id="retry-counter"><?=$minutes?> мин. <?=$seconds?> сек.</span></div>
                            <script>
                                const timerContainer = document.querySelector('#retry-counter');
                                const retryTime = <?=$_COOKIE['retry_payment']?> * 1000;
                                const timer = setInterval(function(){
                                    let now = new Date().getTime();
                                    let diff = retryTime - now;
                                    let minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                                    let seconds = Math.floor((diff % (1000 * 60)) / 1000);
                                    timerContainer.innerHTML = minutes + ' мин. ' + seconds + ' сек.';
                                    if(diff <= 0){
                                        clearInterval(timer);
                                        timerContainer.innerHTML = '0 мин. 0 сек.';
                                        document.location.reload();
                                    }
                                }, 1000);
                            </script>
                        <?endif;*/?>
                    </form>
                    <style>
                        .validated input:invalid {
                            background-color: ivory;
                            border: none;
                            outline: 2px solid red;
                            border-radius: 5px;
                        }
                    </style>
                    <script type="application/javascript">
                        let timeout = 25000;
                        if(getCookie('blocked_payment')){
                            timeout = 5000;
                        }

                        let unpayedReloader = setInterval(() => {
                            $('#common_header').css('display', 'none');
                            $('#update_header').css('display', 'block');
                            $.ajax({
                                type: "GET",
                                url: window.location.pathname + window.location.search + '&check_pay_status=Y',
                                success: function(response) {
                                    if (response.PAYED) {
                                        if (response.PAYED == 'Y') {
                                            document.location.reload();
                                        }
                                        if (!getCookie('blocked_payment')) {
                                            location.reload();
                                        }
                                    }
                                    $('#common_header').css('display', 'block');
                                    $('#update_header').css('display', 'none');
                                },
                                error: function () {
                                    $('#common_header').css('display', 'block');
                                    $('#update_header').css('display', 'none');
                                }
                            });
                        }, timeout);

                        const btnPayList = document.querySelectorAll('.payButton');
                        const payMethodInput = document.querySelector('#PAY_METHOD');
                        const enableTokenInput = document.querySelector('#LU_ENABLE_TOKEN');
                        const btnPayCard = document.querySelector('#payButton');
                        const btnPaySBP = document.querySelector('#sbpPayButton');
                        for (let btnPay of btnPayList) {
                            btnPay.addEventListener('click', function (e) {
                                e.preventDefault();
                                const btnPayTarget = e.target.closest('.payButton');
                                const form = btnPay.closest('form');
                                form.classList.add('validated')
                                if (form.checkValidity()) {
                                    switch (btnPayTarget.id) {
                                        case 'payButton':
                                            payMethodInput.value = 'CCVISAMC';
                                            enableTokenInput.value = 1;
                                            break;
                                        case 'sbpPayButton':
                                            payMethodInput.value = 'FASTER_PAYMENTS';
                                            enableTokenInput.value = 0;
                                            break;
                                    }

                                    btnPayCard.innerHTML = "Подождите <span class='loader'></span>";
                                    btnPayCard.disabled = true;
                                    btnPayCard.style.backgroundColor = 'gray';
                                    btnPaySBP.style.display = 'none';

                                    clearInterval(unpayedReloader);

                                    const data = $(form).serialize();
                                    $.ajax({
                                        url: window.location.href,
                                        data: data,
                                        type: 'POST',
                                        dataType: 'json',
                                        success: function (response) {
                                            if(response.error){
                                                alert(response.error);
                                                btnPayCard.innerHTML = "Оплатить";
                                                btnPayCard.disabled = false;
                                                btnPayCard.style.backgroundColor = '#4d9b2b';
                                                btnPaySBP.style.display = 'block';
                                            }
                                            if(response.url){
                                                window.location.href = response.url;
                                            }
                                        },
                                        error: (e) => {
                                            console.log(e)
                                        }
                                    });
                                } else {
                                }
                            });
                        }

                        function getCookie(name) {
                            const value = `; ${document.cookie}`;
                            const parts = value.split(`; ${name}=`);
                            if (parts.length === 2) {
                                return true;
                            }
                            return false;
                        }
                    </script>
                <?endif;?>
                <? /* ОБЛАСТЬ КНОПКИ ВЫЕЗДА */ ?>
                <? if (($orderInfo['STATUS_ID'] === 'E' || $orderInfo['STATUS_ID'] === 'O') && $orderInfo['LEFT_TO_PAY'] === 0.0):
                    setcookie('blocked_payment', 'N', time() + 60 * 5, '/');
                    setcookie('retry_payment', 0, -1, '/');
                ?>
                <script type="text/javascript">
                    $(function () {
                        $(document).ready(function () {
                            $('#dep_btn_err').hide();
                            $("#dep_btn_warn").hide();
                            $("#dep_btn_warn2").hide();
                            <? if ($orderInfo['STATUS_ID'] === 'O'): ?>
                            dep_tick();
                            <? endif; ?>
                        });
                        $('#departureRequest').on('submit', function(event) {
                            event.preventDefault();
                            var data = $(this).serialize();
                            $('#fe_dep_btn').addClass('__disabled');
                            $('#dep_btn_err').hide();
                            $.ajax({
                                type: "POST",
                                url: '/fast_entrance.php',
                                data: data,
                                success: function(response) {
                                    if (response.departureSuccess) {
                                        $('#dep_btn_err').hide();
                                        $('#dep_btn_info').text('<?=Loc::getMessage("FAST_ENTRANCE_BUTTONS_DEPARTURE_IN_PROGRESS")?>');
                                        $('#dep_btn_info').show();
                                        $("#departure_deadline_timer").text(response.deadline);
                                        dep_tick();
                                    } else {
                                        $('#dep_btn_info').hide();
                                        $('#dep_btn_err').text(response.error);
                                        $('#dep_btn_err').show();
                                        $('#fe_dep_btn').removeClass('__disabled');
                                    }
                                },
                                error: function(xhr, ajaxOptions, thrownError) {
                                    $('#dep_btn_info').hide();
                                    $('#dep_btn_err').text(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                                    $('#dep_btn_err').show();
                                    $('#fe_dep_btn').removeClass('__disabled');
                                }
                            });
                        });
                        function dep_tick(){
                            var time = Number($("#departure_deadline_timer").text());
                            time -= parseInt(new Date().getTime()/1000);
                            if (time > 0) {
                                var mess = '<?=Loc::getMessage("FAST_ENTRANCE_BUTTONS_DEPARTURE_WARNING")?>'.replace('#TIME#', time);
                                $("#dep_btn_warn").text(mess);
                                $("#dep_btn_warn").show();
                                $("#dep_btn_warn2").show();
                                setTimeout(dep_tick, 2000);
                            } else {
                                $("#dep_btn_warn").hide();
                                $("#dep_btn_warn2").hide();
                                $('#fe_dep_btn').removeClass('__disabled');
                                $('#dep_btn_info').text('<?=Loc::getMessage("FAST_ENTRANCE_BUTTONS_DEPARTURE_AVAILABLE")?>');
                                if (time <= 0 && time > -1) document.location.reload();
                            }
                        }
                    });
                </script>
                <div class="table">
                    <?/* ПОДСКАЗКА НАД КНОПКОЙ ОПЛАТЫ/ВЫЕЗДА */?>
                    <? if ($orderInfo['STATUS_ID'] === 'E'): ?>
                        <span id="dep_btn_info" class="order_help-text" style="font-weight: bold; color: black;"><?=Loc::getMessage("FAST_ENTRANCE_BUTTONS_DEPARTURE_AVAILABLE")?></span>
                    <? elseif ($orderInfo['STATUS_ID'] === 'O'): ?>
                        <span id="dep_btn_info" class="order_help-text" style="font-weight: bold; color: dimgrey;"><?=Loc::getMessage("FAST_ENTRANCE_BUTTONS_DEPARTURE_IN_PROGRESS")?></span>
                    <? endif; ?>
                    <?/* ПРЕДУПРЕЖДЕНИЯ, ОШИБКА */?>
                    <span id="dep_btn_warn" class="order_help-text" style="font-weight: bold; color: #ff9900;"></span>
                    <span id="dep_btn_warn2" class="order_help-text" style="font-weight: bold; color: #ff0000;"><br><br><?=Loc::getMessage("FAST_ENTRANCE_BUTTONS_DEPARTURE_WARNING_2")?></span>
                    <span id="dep_btn_err" class="order_help-text" style="font-weight: bold; color: red;"></span>
                    <?/* ФОРМА ЗАПРОСА НА ВЫЕЗД */?>
                    <form action="" method="post" id="departureRequest">
                        <input type="hidden" name="parkingID" value="<?=$orderInfo['PARKING_XML_ID']?>">
                        <input type="hidden" name="orderID" value="<?=$orderInfo['ID']?>">
                        <input type="hidden" name="tariffID" value="<?=$orderInfo['TARIFF_ID']?>">
                        <input type="hidden" name="code" value="<?=md5($orderInfo['PARKING_XML_ID'] . $orderInfo['ID'] . $orderInfo['TARIFF_ID'] . $secretKey)?>">
                        <input type="hidden" name="departure" value="Y">
                        <button id="fe_dep_btn"
                                class="btn order_move-in-btn btn-height <?=$orderInfo['STATUS_ID'] == 'O' ? '__disabled' : ''?>"
                                name="departureBtn" <?=$orderInfo['STATUS_ID'] == 'O' ? 'disabled' : ''?>
                                style="background-color: rgb(254, 207, 0);">
                            <img src="/bitrix/templates/mobile/images/loader.gif" class="loader" style="display:none">
                            <span id="btn_txt" style="font-weight: bold;" data-text="<?=Loc::getMessage("FAST_ENTRANCE_BUTTONS_DEPARTURE")?>"><?=Loc::getMessage("FAST_ENTRANCE_BUTTONS_DEPARTURE")?></span>
                        </button>
                    </form>
                    <?/* ТАЙМЕР ОСТАВШЕГОСЯ ВРЕМЕНИ НА ВЫЕЗД */?>
                    <span id="departure_deadline_timer" style="display: none;"><?=$deadline?></span>
                </div>
                <? endif; ?>
            </div>
            <?
        }
    } else {
        ApiFastEntrance::logError('WARNING', Loc::getMessage('FAST_ENTRANCE_WARNING_WRONG_DATA'), isset($_GET['order_id']) ? $_GET['order_id'] : 0);
        showErrorMess(Loc::getMessage('FAST_ENTRANCE_ERROR_INCORRECT_LINK'));
        die();

    }

    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
}


