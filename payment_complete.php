<?

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetAdditionalCss('/css/modal_payment_success.css');
$APPLICATION->SetTitle('Оплата завершена');

$orderId = (int)$_GET['orderId'];

if ($orderId)
{
	CModule::IncludeModule('sale');
	CModule::IncludeModule('catalog');
	$dbOrderProp = CSaleOrderPropsValue::GetOrderProps($orderId);

	while ($arOrderProp = $dbOrderProp->Fetch())
	{
		if ($arOrderProp['CODE'] == 'PAY_METHOD')
		{
			$payMethod = $arOrderProp['VALUE'];
		}
		if ($arOrderProp['CODE'] == 'AUTO_PROLONGATION') 
		{
			$autoProlongation = $arOrderProp['VALUE'];
		}
	}
	$dbBasketItems = CSaleBasket::GetList(
		array(),
		array("ORDER_ID" => $orderId),
		false,
		false,
		array("PRODUCT_ID")
	);
	$arItems = $dbBasketItems->Fetch();
	$ar_res = CCatalogProduct::GetByIDEx($arItems["PRODUCT_ID"]);
	$canAutoProlongate = $ar_res["PROPERTIES"]["AUTO_PROLONGATION"]["VALUE"];
	if(!empty($canAutoProlongate) && $autoProlongation == "N")
	{
		$attr = "disabled='disabled'";
	}

	if ($payMethod != 'CCVISAMC')
	{
		$prePayed = 'n';

		if (isset($_GET['TRS']))
		{
			$payuAnswer = htmlspecialchars(trim($_GET['TRS']));
			$prePayed = (strtoupper($payuAnswer) == 'AUTH') ? 'y' : 'n';
		}

		LocalRedirect('/payback.php?orderID=' . $orderId . '&prePayed=' . $prePayed);
	}
}

if ( isset($_GET['finish']) )
{
	require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
	if ( !isset($_POST['SAVE']) && $_POST['USER_ID'] ){
		$USER->Update($_POST['USER_ID'], array('UF_PAYU_TOKEN' => '', 'UF_PAYU_CC' => '', 'UF_PAYU_EXP' => ''));
	}
	if ( isset($_POST['autoProlongation']) && $_POST['ORDER_ID'] ){
		RestOrder::SetAutoProlongation($_POST['ORDER_ID'], "Y");
	}
	$orderId = (int)$_POST['ORDER_ID'];
	$prePayed = 'n';

	if(isset($_POST['TRS']))
	{
		$payuAnswer = htmlspecialchars(trim($_POST['TRS']));
		$prePayed = (strtoupper($payuAnswer) == 'AUTH') ? 'y' : 'n';
	}

	LocalRedirect('/payback.php?orderID=' . $orderId . '&prePayed=' . $prePayed);
}

?>

<div class="outer_block">
	<div class="themodal-overlay">
		<div class="modal pie">
			<div class="modal_header pie">
				<h1>Завершение оплаты</h1>
			</div>
			<div class="modal_body pie">
				<form target="_top" action="?finish" method="POST" class="auth-form">
					<input type="hidden" name="USER_ID" value="<?=(int)$_GET['userId']?>" />
					<input type="hidden" name="ORDER_ID" value="<?=(int)$_GET['orderId']?>" />
					<input type="hidden" name="TRS" value="<?=htmlspecialchars($_GET['TRS'])?>" />
					<div class="message_block"></div>
					<div class="field_row field_checkbox">
						<input type="checkbox" checked="checked" <?=$attr?> id="SAVE" name="SAVE" class="va_middle"/>
						<label for="SAVE" class="va_middle">&nbsp;Сохранить платежные данные</label>
					</div>
					<?if(!empty($canAutoProlongate) && $autoProlongation == "N"):?>
						<div class="field_row field_checkbox">
							<input type="checkbox" checked="checked" id="autoProlongation" name="autoProlongation" class="va_middle"/>
							<label for="autoProlongation" class="va_middle">&nbsp;Автоматическая пролонгация абонемента</label>
						</div>
					<?endif;?>
					<div class="field_row field_btn">
						<input type="submit" value="Завершить" class="btn-green_n"/>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<? require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>

<script>
function ChkState() {
		if ($("#autoProlongation").is(":checked")) {
			$("#SAVE").attr("disabled",true);
			$("#SAVE").siblings('span').addClass('disabled');
			
		}
		else {
			$("#SAVE").removeAttr("disabled");
			$("#SAVE").siblings('span').removeClass('disabled');
		}
}
$(function() {
    $("#autoProlongation").click(ChkState);
})
function ChkState2() {
		if ($("#SAVE").is(":checked") == false) {
			$("#autoProlongation").attr("disabled",true);
			$("#autoProlongation").siblings('span').addClass('disabled');
			
		}
		else {
			$("#autoProlongation").removeAttr("disabled"); 
			$("#autoProlongation").siblings('span').removeClass('disabled');
		}
}
$(function() {
    $("#SAVE").click(ChkState2);
})
</script>