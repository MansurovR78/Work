<?
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/urlrewrite.php');

CHTTP::SetStatus("403 Not Found");
@define("ERROR_403","Y");
if(ERROR_403) 
	header("Location:/403.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");?>

<div class="container">
	<div class="b-error">
	    <span class="b-error_n">403</span>
	    <span class="b-error_desc">&nbsp;— доступ запрещён</span>
	    <div class="b-error_text">Доступ к запрашиваемому ресурсу запрещен.</div>
	</div>
</div>
<? 
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>