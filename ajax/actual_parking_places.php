<? 
include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(!empty($_POST['ajax'] || !empty($_POST['value']))){
	$APPLICATION->RestartBuffer();
	$parkingPlace = new ParkingPlace();
	$arResult =$parkingPlace->getParkingPlaceByTariffID(intval($_POST['value']));
	echo json_encode($arResult);
	die();
}
?>