<?
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/urlrewrite.php');

CHTTP::SetStatus("404 Not Found");
@define("ERROR_404","Y");
if(ERROR_404) 
	header("Location:/404.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");?>

<div class="container">
	<div class="b-error">
	    <span class="b-error_n">404</span>
	    <span class="b-error_desc">&nbsp;— страница не найдена</span>
	    <div class="b-error_text">Данная страница была удалена с сайта, либо ее никогда не существовало.</div>
	</div>
</div>
<? 
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>