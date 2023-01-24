<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetAdditionalCss('/css/modal_payment_success.css');
$APPLICATION->SetTitle('Способ оплаты');

$orderFields = array(
	'parkID',
    'tariffID',
    'carNumber',
    'token',
    'date',
    'time',
    'amount',
    'userID',
    'userHash',
    'requestFrom',
    'PHONE',
    'now',
	'autoProlongation'
);

$arPost = $_POST;

$expired = false;

if (isset($arPost['expired']) && $arPost['expired'] == 'yes') {
	$expired = array(
		'userID' => $arPost['userID'],
		'userHash' => $arPost['userHash'],
		'orderID' => $arPost['orderID']
	);
}

foreach ($arPost as $key => $value) {
	if (!in_array($key, $orderFields)) {
		unset($arPost[ $key ]);
	}
}

if (empty($arPost)) {
	// Идентификатор старой версии приложения, где заказ делался перед выбором формы оплаты
	$_SESSION['PRE_ORDER_VERSION'] = true;
	$userId = (int)$_REQUEST['USER_ID'];
	$payMethods = array(
		'CCVISAMC' => 'Оплатить новой картой'
	);
	$tariffID = (int)$_REQUEST['TARIFF_ID'];
} else {
	$_SESSION['ORDER'] = $arPost;
	$userId = (int)$arPost['userID'];
	$payMethods = RestOrder::getPayMethods();
	$tariffID = (int)$arPost['tariffID'];
}

$checkedMethod = key($payMethods);

$arUser = $USER->GetList(
	($by = ''),
	($order = ''),
	array('ID' => $userId),
	array('SELECT' => array('UF_PAYU_TOKEN', 'UF_PAYU_CC', 'UF_PAYU_EXP'))
)->Fetch();

if (!empty($arUser['UF_PAYU_TOKEN']) && ctype_digit($arUser['UF_PAYU_TOKEN'])) {
    $hasCard = false;
} else {
    $hasCard = ! empty($arUser['UF_PAYU_TOKEN']) && ! empty($arUser['UF_PAYU_CC']) && ! empty($arUser['UF_PAYU_EXP']);
}

?>
<script type="text/javascript">
$(document).ready(function () {
	$('.btn-mobile').on('click', function (e) {
		e.preventDefault();
		$(this).css('display', 'none').hide();
		$('#preloader').show();
		$('.modal_body').append('<div style="text-align: center; width: 100%; height: 30px; padding-top: 10px; margin-top: 10px; border: 1px solid grey;">Подождите идет инициализация оплаты...</div>');
		$('.auth-form').submit();
	});
});
</script>
<div class="outer_block">
	<div class="themodal-overlay">
		<div class="modal pie">
			<div class="modal_header pie">
				<h1>Способ оплаты</h1>
			</div>
			<div class="modal_body pie">
				<form target="_top" action="/payment_action.php" method="POST" class="auth-form" style="text-align:left !important;">
					<? if ($expired): ?>
						<input type="hidden" name="expired" value="yes">
						<input type="hidden" name="orderID" value="<?= $expired['orderID'] ?>">
						<input type="hidden" name="userID" value="<?= $expired['userID'] ?>">
						<input type="hidden" name="userHash" value="<?= $expired['userHash'] ?>">
					<? endif ?>
					<? if ($_SESSION['PRE_ORDER_VERSION']): ?>
						<input type="hidden" name="USER_ID" value="<?=$userId?>" />
						<input type="hidden" name="ORDER_ID" value="<?=(int)$_REQUEST['ORDER_ID']?>" />
						<input type="hidden" name="TARIFF_ID" value="<?=(int)$_REQUEST['TARIFF_ID']?>" />
					<? endif ?>
					<div class="message_block"></div>
					<? foreach ($payMethods as $method => $name) { ?>
						<div class="field_row field_text">
							<label>
								<input type="radio" name="PAY_METHOD" value="<?=$method?>" <?=($method == $checkedMethod ? 'checked="checked"' : '')?> /> <?=$name?>
							</label>
						</div>
					<? } ?>

					<? if ($hasCard) {?>
						<div class="field_row field_text">
							<input type="radio" id="current_card" name="PAY_METHOD" value="CARD" class="va_middle"/>
							<label for="current_card" class="va_middle">&nbsp;Оплатить текущей картой</label>
						</div>
						<div class="field_row field_text" style="font-weight:bold;line-height:18px;padding-left:20px;margin-top:-14px;">
							Карта: <?=$arUser['UF_PAYU_CC']?><br />
							Дата: <?=$arUser['UF_PAYU_EXP']?>
						</div>
					<? } ?>
					<?if($arPost["autoProlongation"] == "N"):?>
						<div class="field_row field_text noneDisplay" id="autoProlongation_div">
							<input type="checkbox" id="autoProlongation" name="autoProlongation" class="va_middle"/>
							<label for="autoProlongation" class="va_middle">&nbsp;Автоматическая пролонгация абонемента</label>
						</div>
					<?endif;?>
					<div class="field_row field_btn" style="text-align:center;">
						<input type="submit" value="Оплатить" class="btn-mobile"/>
						<img id="preloader" src="/images/preloader.gif" style="display:none;">
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
?>

<style>
.noneDisplay {
    display: none
}
</style>
<script>
function ChkState() {
		if ($("#current_card").is(":checked")) {
			$("#autoProlongation").attr("checked",true); 
			$("span.custom-check").addClass('checked');
			$("#autoProlongation_div").removeClass("noneDisplay");
		}
		else {
			$("#autoProlongation").removeAttr("checked");
			$("span.custom-check").removeClass('checked');
			$("#autoProlongation_div").addClass("noneDisplay");
		}
}
$(function() {
    $(".va_middle").click(ChkState);
})
</script>
