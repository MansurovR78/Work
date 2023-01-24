<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$payMethod = $_POST['PAY_METHOD'];

// handle expired order creation
if (isset($_POST['expired']) && $_POST['expired'] == 'yes') {
	$request = new ApiRequest('order_penalty', $_POST);
	$orderResult = $request->sendRequest()->getResponse();

	if (!empty($orderResult['ERROR'])) {
		$APPLICATION->RestartBuffer();
		die();
	}

	$orderId = (int) $orderResult['orderID'];
	$userId = (int) $_POST['userID'];

} elseif ($_SESSION['PRE_ORDER_VERSION']) {
	$orderId = (int)$_POST['ORDER_ID'];
	$userId = (int)$_POST['USER_ID'];
} else {
	$payMethods = RestOrder::getPayMethods(true);

	if (empty($_SESSION['ORDER']) OR !isset($payMethods[ $payMethod ]))
	{
		$APPLICATION->RestartBuffer();
		die();
	}
	if(isset($_POST['autoProlongation']))
	{
		$_SESSION['ORDER']['autoProlongation'] = "Y";
	}
	/*Проверка на количество активных тарифов*/
	$arTariffData = array(
		"parkingID" => $_SESSION["ORDER"]["parkID"],
		"tariffID" => $_SESSION["ORDER"]["tariffID"]
	);
	$tariffActiveCount = RestOrder::getActiveTariffOrders($arTariffData);

	CModule::IncludeModule('iblock');
	$arSelect = array("PROPERTY_MAX_AVAILABLE");
	$arFilter = array("IBLOCK_ID" => IB_TARIFF, "ID" => $_SESSION["ORDER"]["tariffID"]);
	$obTariffs = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
	if ($arTariff = $obTariffs->Fetch())
	{
		$maxActiveTariffs = intval($arTariff["PROPERTY_MAX_AVAILABLE_VALUE"]);
	}

	if ($maxActiveTariffs > 0 && $tariffActiveCount >= $maxActiveTariffs){?>
		<div class="form-field">
			<font color="red"></font>
		</div>
		<div class="outer_block">
			<div class="themodal-overlay">
				<div class="modal pie">
					<div class="modal_header pie">
						<h1>Ошибка!</h1>
					</div>
					<div class="modal_body pie">
						<div class="message_block"></div>
						<font color="red"> К сожалению, на данный момент вы не можете приобрести тариф на данную парковку.</font>
					</div>
				</div>
			</div>
		</div>
		<?
		die(); 
	}
	/* /Проверка на количество активных тарифов */

	$_SESSION['ORDER']['PAY_METHOD'] = $payMethod;

	$request = new ApiRequest('order_add', $_SESSION['ORDER']);
	$orderResult = $request->sendRequest()->getResponse();
	if (isset($orderResult['ERROR']))
	{
		$APPLICATION->RestartBuffer();
		die();
	}

	$orderId = (int)$orderResult['orderID'];
	$userId = (int)$_SESSION['ORDER']['userID'];
}
?>
<div class="outer_block">
	<div class="themodal-overlay">
		<div class="modal pie">
			<div class="modal_header pie">
				<h1>Платеж обрабатывается</h1>
			</div>
			<div class="modal_body pie">
				<div class="message_block"></div>
				Начинается платежная транзакция...
			</div>
		</div>
	</div>
</div>

<?
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/sale_payment/payu/payu.cls.php';

CModule::IncludeModule('sale');

$currentCard = ($payMethod == 'CARD');

$redirectUrlParams = array(
	'orderID' => $orderId,
	'prePayed' => 'n'
);

if ( !$orderId || !$userId ){
	echo '<meta http-equiv="refresh" content="0; url=/payback.php?' . http_build_query($redirectUrlParams) . '"/>';
}
$arUser = $USER->GetList(
	($by = ''),
	($order = ''),
	array('ID' => $userId),
	array('SELECT' => array('UF_PAYU_TOKEN'))
)->Fetch();

if ( !$arUser ){
	echo '<meta http-equiv="refresh" content="0; url=/payback.php?' . http_build_query($redirectUrlParams) . '"/>';
}

$arOrder = CSaleOrder::GetList(
	array(),
	array('ID' => $orderId),
	false,
	false,
	array()
)->Fetch();

CSalePaySystemAction::InitParamArrays($arOrder, $arOrder['ID']);

$dbBasketItems = CSaleBasket::GetList(
	array('NAME' => 'ASC', 'ID' => 'ASC'),
	array(
		'LID' => SITE_ID,
		'ORDER_ID' => $orderId),
	false,
	false,
	array('ID', 'CALLBACK_FUNC', 'MODULE', 'PRODUCT_ID', 'QUANTITY', 'DELAY', 'CAN_BUY', 'PRICE', 'WEIGHT', 'NAME', 'VAT_RATE')
);

$arBasketItems = array();

while($arItems = $dbBasketItems->Fetch()) {
	if(strlen($arItems['CALLBACK_FUNC']) > 0) {
		CSaleBasket::UpdatePrice(
			$arItems['ID'],
			$arItems['CALLBACK_FUNC'],
			$arItems['MODULE'],
			$arItems['PRODUCT_ID'],
			$arItems['QUANTITY']
		);
		$arItems = CSaleBasket::GetByID($arItems['ID']);
	}
    //Дополнительно получаем значение ставки НДС для тарифа
    $result = \Bitrix\Catalog\ProductTable::getList(['filter' => array('=ID'=>$arItems['PRODUCT_ID'])])->fetch();
    //Получаем значение ставки
    if (isset($result['VAT_ID'])) {
        $vat = CCatalogVat::GetByID($result['VAT_ID'])->Fetch();
        $arItems['VAT'] = $vat['RATE'];
    } else {
        $vat = CCatalogVat::GetByID(VAT_ENABLED_ID)->Fetch();
        $arItems['VAT'] = $vat['RATE'];
    }

	$arBasketItems[] = $arItems;
}

$MERCHANT_ID = CSalePaySystemAction::GetParamValue('MERCHANT');
$secretKey =  CSalePaySystemAction::GetParamValue('SECURE_KEY');
$dbOrderProps = CSaleOrderPropsValue::GetList(
	array(),
	array('ORDER_ID' => $orderId, 'CODE' => array('PARKING_ID')),
	false,
	false,
	array('CODE', 'VALUE')
);

while ($arOrderProperty = $dbOrderProps->Fetch()){
	$parking_id = $arOrderProperty['VALUE'];
}

if (isset($parking_id) && !empty($parking_id)) {
	if ($parking_id == 3079) {
		$MERCHANT_ID = 'trhtyujj';
		$secretKey = '8+R5cQ?4]ic*6%T|v3%u';
	}
}

$option = array(
	'merchant' => $MERCHANT_ID,
	'secretkey' => $secretKey,
	'debug' => CSalePaySystemAction::GetParamValue('DEBUG_MODE')
);

$comission = getPaymentCommissionByParkId($parking_id);

//НДС для сервисного сбора
$comissionVAT = CCatalogVat::GetByID(VAT_ENABLED_ID)->Fetch();

if ( !$currentCard )
{
	if ( !empty($arUser['UF_PAYU_TOKEN']) ) {
		$USER->Update($UserID, array(
				'UF_PAYU_TOKEN' => '',
				'UF_PAYU_CC' => '',
				'UF_PAYU_EXP' => ''
			)
		);
	}

	$dbOrderProps = CSaleOrderPropsValue::GetList(
		array(),
		array('ORDER_ID' => $orderId, 'CODE' => array('PARKING_ID')),
		false,
		false,
		array('CODE', 'VALUE')
		);

	while ($arOrderProperty = $dbOrderProps->Fetch()){
		$arOrderProps[$arOrderProperty['CODE']] = $arOrderProperty['VALUE'];
	}
	$arParking = CIBlockElement::GetList(
            array(),
            array('IBLOCK_ID' => IB_PARKING, 'ID' => $arOrderProps['PARKING_ID']),
            false,
            false,
            array('XML_ID', 'PROPERTY_SERVICE_COMMISSION')
    )->Fetch();

	$payuOrderID = "PayuOrder_" . $orderId . "_" . $userId . "_" . md5( "payuOrder_".time() );

	$backref = CSalePaySystemAction::GetParamValue("BACK_REF");
	$backref = str_replace(array('#ORDER_ID#', '#USER_ID#'), array($orderId, $userId), $backref);

	$arDataSend = array(
		'MERCHANT' => $MERCHANT_ID,
		'ORDER_REF' => $payuOrderID,
		'ORDER_DATE' => date('Y-m-d H:i:s'),
		//'ORDER_SHIPPING' => $arOrder['PRICE_DELIVERY'],
		'PRICES_CURRENCY' => CSalePaySystemAction::GetParamValue('PRICE_CURRENCY'),
		'DISCOUNT' => $arOrder['DISCOUNT_VALUE'],
		'PAY_METHOD' => CSalePaySystemAction::GetParamValue('PAY_METHOD'),
		'LANGUAGE' => CSalePaySystemAction::GetParamValue('LANGUAGE'),
		'AUTOMODE' => CSalePaySystemAction::GetParamValue('AUTOMODE'),
		'BILL_FNAME' => CSalePaySystemAction::GetParamValue('BILL_FNAME'),
		'BILL_LNAME' => CSalePaySystemAction::GetParamValue('BILL_LNAME'),
		'BILL_EMAIL' => CSalePaySystemAction::GetParamValue('BILL_EMAIL'),
		'BILL_COUNTRYCODE' => CSalePaySystemAction::GetParamValue('BILL_COUNTRYCODE'),
		'LU_ENABLE_TOKEN' => CSalePaySystemAction::GetParamValue('LU_ENABLE_TOKEN')
	);

	if($arDataSend['DISCOUNT'] == 0) unset($arDataSend['DISCOUNT']);

	if($backref != ''){
		$arDataSend['BACK_REF'] = $backref;
	}

	foreach($arBasketItems as $val) {
		$arDataSend['ORDER_PNAME'][] = "Услуга паркования";
		$arDataSend['ORDER_PCODE'][] = $val['PRODUCT_ID'];
		$arDataSend['ORDER_PINFO'][] = $arParking['XML_ID'];
		$arDataSend['ORDER_PRICE'][] = $val['PRICE'];
		$arDataSend['ORDER_QTY'][] = (int) $val['QUANTITY'];
		$arDataSend['ORDER_VAT'][] = isset($val['VAT']) ? intval($val['VAT']) : 0;
        $arDataSend['ORDER_PRICE_TYPE'][] = "GROSS";

        if (!empty($comission)){
            $arDataSend['ORDER_PNAME'][] = 'Сервисный сбор НОП';
            $arDataSend['ORDER_PCODE'][] = 'PAYMENT_COMMISSION';
            $arDataSend['ORDER_PINFO'][] = $arParking['XML_ID'];
            $arDataSend['ORDER_PRICE'][] = $val['PRICE']*$comission/100;
            $arDataSend['ORDER_QTY'][] = (int) $val['QUANTITY'];
            $arDataSend['ORDER_VAT'][] = $comissionVAT['RATE'];
            $arDataSend['ORDER_PRICE_TYPE'][] = "GROSS";
        }
	}
    debugfile2($arDataSend, '$arDataSend', 'payulog');

	$option['luUrl'] = CSalePaySystemAction::GetParamValue('LU_URL');

	$pay = PayU::getInst()->setOptions($option)->setData($arDataSend)->QSOFT_LU();
    debugfile2($pay, '$pay', 'payulog');
	?>

				<form action="<?=$option['luUrl']?>" method="POST" id="frmMain">
					<?foreach ( $pay['PARAMS'] as $payFieldName => $payFieldValue ) {?>
						<input type="hidden" name="<?=$payFieldName?>" value="<?=$payFieldValue?>" />
					<?}?>
					<input type="submit" style="display:none;" id="frmMainSubmit" />
				</form>
			<script type="text/javascript">
				document.getElementById('frmMainSubmit').click();
			</script>

	<?
}
else
{
	$deleteTokenCodes = array(
		601, # Not sufficient funds
		602, # Expired card
		604 # Invalid card
	);

	$dbOrderProps = CSaleOrderPropsValue::GetList(
		array(),
		array('ORDER_ID' => $orderId, 'CODE' => array('PARKING_ID')),
		false,
		false,
		array('CODE', 'VALUE')
	);

	while ($arOrderProperty = $dbOrderProps->Fetch()){
		$arOrderProps[$arOrderProperty['CODE']] = $arOrderProperty['VALUE'];
	}
    $arParking = CIBlockElement::GetList(
        array(),
        [
            'IBLOCK_ID' => IB_PARKING,
            'ID' => $arOrderProps['PARKING_ID']
        ],
        false,
        false,
        array('XML_ID', 'PROPERTY_SERVICE_COMMISSION')
    )->Fetch();

	$payuOrderID = "PayuOrder_" . $orderId . "_" . $userId . "_" . md5( "payuOrder_".time() );

	$backref = CSalePaySystemAction::GetParamValue("BACK_REF");
	$backref = str_replace(array('#ORDER_ID#', '#USER_ID#'), array($orderId, $userId), $backref);

	$pay_method_value = CSalePaySystemAction::GetParamValue('PAY_METHOD');
	$pay_method = (!empty($pay_method_value) && isset($pay_method_value)) ? $pay_method_value : 'CCVISAMC';

	$comission = (float) $arParking['PROPERTY_SERVICE_COMMISSION_VALUE'];

	foreach($arBasketItems as $val) {
		$arDataSend = array(
			"MERCHANT" => $MERCHANT_ID,
			"ORDER_REF" => $payuOrderID,
			"ORDER_DATE" => gmdate('Y-m-d H:i:s'),

			//First product details begin
			"ORDER_PNAME[0]" => "Услуга паркования",
			"ORDER_PCODE[0]" => $val['PRODUCT_ID'],
			"ORDER_PINFO[0]" => $arParking['XML_ID'],
			"ORDER_PRICE[0]" => $val['PRICE'],
			"ORDER_QTY[0]" => (int) $val['QUANTITY'],
			"ORDER_VAT[0]" => isset($val['VAT']) ? intval($val['VAT']) : 0,
			"ORDER_PRICE_TYPE[0]" => "GROSS",
			//First product details end

			"PRICES_CURRENCY" => CSalePaySystemAction::GetParamValue('PRICE_CURRENCY') ? CSalePaySystemAction::GetParamValue('PRICE_CURRENCY') : "RUB",
			"PAY_METHOD" => $pay_method,
			"CC_TOKEN" => $arUser['UF_PAYU_TOKEN'],
			"CC_CVV" => "",

			//Return URL on the Merchant webshop side that will be used in case of 3DS enrolled cards authorizations.
			"BACK_REF" => $backref,
			'BILL_FNAME' => CSalePaySystemAction::GetParamValue('BILL_FNAME'),
			'BILL_LNAME' => CSalePaySystemAction::GetParamValue('BILL_LNAME'),
			'BILL_EMAIL' => CSalePaySystemAction::GetParamValue('BILL_EMAIL'),
			'BILL_COUNTRYCODE' => CSalePaySystemAction::GetParamValue('BILL_COUNTRYCODE'),
			'BILL_PHONE' => CSalePaySystemAction::GetParamValue('BILL_PHONE') ? CSalePaySystemAction::GetParamValue('BILL_PHONE') : '+79999999999'
		);
        //Second product details begin
        if (!empty($comission)) {
            $arDataSend['ORDER_PNAME[1]'] = 'Сервисный сбор НОП';
            $arDataSend['ORDER_PCODE[1]'] = 'PAYMENT_COMMISSION';
            $arDataSend['ORDER_PINFO[1]'] = $arParking['XML_ID'];
            $arDataSend['ORDER_PRICE[1]'] = $val['PRICE']*$comission/100;
            $arDataSend['ORDER_QTY[1]'] = (int) $val['QUANTITY'];
            $arDataSend['ORDER_VAT[1]'] = $comissionVAT['RATE'];
            $arDataSend['ORDER_PRICE_TYPE[1]'] = 'GROSS';
        }
        //Second product details end
	}

	$url = PAYU_API_V3_URL;

//begin HASH calculation
	ksort( $arDataSend );

	$hashString =  "" ;

	foreach ( $arDataSend as $key => $val ) {
		$hashString .=  strlen ( $val ) .  $val ;
	}

	$arDataSend [ "ORDER_HASH" ] = hash_hmac( "md5" ,  $hashString ,  $secretKey );

	debugfile2($arDataSend, '$arDataSend', 'payulog');
//end HASH calculation

	$ch = curl_init();
	curl_setopt( $ch , CURLOPT_URL,  $url );
	curl_setopt( $ch , CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt( $ch , CURLOPT_RETURNTRANSFER, true);
	curl_setopt( $ch , CURLOPT_TIMEOUT, 60);
	curl_setopt( $ch , CURLOPT_POST, 1);
	curl_setopt( $ch , CURLOPT_POSTFIELDS, http_build_query($arDataSend));

    $content = curl_exec( $ch );
	debugfile2($content, '$content', 'payulog');
	$curlerrcode = curl_errno( $ch );
	$curlerr = curl_error( $ch );

	if ( empty ( $curlerr ) &&  empty ( $curlerrcode )) {
		$parsedXML = @simplexml_load_string( $content );
		if ( $parsedXML !== FALSE) {

			//Get PayU Transaction reference.
			//Can be stored in your system DB, linked with your current order, for match order in case of 3DSecure enrolled cards
			//Can be empty in case of invalid parameters errors
			$payuTranReference =  $parsedXML ->REFNO;

			if ( $parsedXML ->STATUS ==  "SUCCESS" ) {

				//In case of 3DS enrolled cards, PayU will return the extra XML tag URL_3DS that contains a unique url for each
				//transaction. For example https://secure.payu.com.ru/order/alu_return_3ds.php?request_id=2Xrl85eakbSBr3WtcbixYQ%3D%3D.
				//The merchant must redirect the browser to this url to allow user to authenticate.
				//After the authentification process ends the user will be redirected to BACK_REF url
				//with payment result in a HTTP POST request - see 3ds return sample.
				if (( $parsedXML ->RETURN_CODE ==  "3DS_ENROLLED" ) && (! empty ( $parsedXML ->URL_3DS))) {
				header( "Location:" .  $parsedXML ->URL_3DS);
				die ();
				}

				echo "SUCCES [PayU reference number: " .  $payuTranReference .  "]" ;
			}  else {
				echo "FAILED: " .  $parsedXML ->RETURN_MESSAGE .  " [" .  $parsedXML ->RETURN_CODE .  "]" ;
				if (! empty ( $payuTranReference )) {
				//the transaction was register to PayU system, but some error occured during the bank authorization.
				//See $parsedXML->RETURN_MESSAGE and $parsedXML->RETURN_CODE for details
				echo " [PayU reference number: " .  $payuTranReference .  "]" ;
				}
			}
		}
	}  else {
		//Was an error comunication between servers
		echo "cURL error: " .  $curlerr ;
	}

	if ( $content['code'] == 0 && !empty($content['tran_ref_no']) ){
		$redirectUrlParams['prePayed'] = 'y';
	}
	else if ( $content['code'] != 0 && !in_array($content['code'], $deleteTokenCodes) ) {
		$file = $_SERVER['DOCUMENT_ROOT'] . '/q_script/logs/payment/' . date('d_m_Y') . '.log';
		$logContent = date('H:i:s') . ':::' . $content['code'] . ':::' . $content['message'] . "\r\n";
		file_put_contents($file, $logContent, FILE_APPEND);
	}

	if ( in_array($content['code'], $deleteTokenCodes) ){
		$USER->Update($userId, array('UF_PAYU_TOKEN' => '', 'UF_PAYU_CC' => '', 'UF_PAYU_EXP' => ''));
	}
	echo '<meta http-equiv="refresh" content="0; url=/payback.php?' . http_build_query($redirectUrlParams) . '"/>';
}


require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
?>
