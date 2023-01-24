<? 
include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(!empty($_POST['ajax'])){
	$APPLICATION->RestartBuffer();
	$arFilter = array(
		"ACTIVE" => "Y",
		"IBLOCK_ID" => IB_NS_TARIFF,
		"PROPERTY_ID_PARK" => intval($_POST['value'])
	);
	$arResult = array();

	$arSelect = Array("ID", "NAME");

	$res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
	while($arRes = $res->Fetch()) {
		$arResult[$arRes['ID']] = $arRes['NAME'];
	}

	echo json_encode($arResult);
	die();
}
?>