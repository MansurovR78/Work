<?
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/urlrewrite.php');

CHTTP::SetStatus("503 Not Found");
@define("ERROR_503","Y");
if(ERROR_503) 
	header("Location:/404.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");?>

<div class="container page-error">
	<div class="b-error">
	    <span class="b-error_n">503</span>
	    <span class="b-error_desc">&nbsp;— ошибка сервера</span>
	    <div class="b-error_text">Извините, сайт временно недоступен.</div>
	</div>
</div>
<? 
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>