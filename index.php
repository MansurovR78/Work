<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "А1 PARK");
$APPLICATION->SetPageProperty("NOT_SHOW_NAV_CHAIN", "Y");
$APPLICATION->SetTitle("А1Park");
?><!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '621834998878351');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=621834998878351&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->

<div class="wide promo"> 			 
  <h2> 
    <p style="margin-bottom: 7.5pt;" class="MsoNormal"><span style="text-align: center;">Паркуйтесь через систему А1 </span><span lang="EN-US" style="margin: 0px; padding: 0px; text-align: center;">PARK</span><span style="text-align: center;"> - это удобно!</span></p>
   </h2>
 
<style>.bx-viewport{height:365px !important;}</style>
 	 			 	 
  <div class="slider"> 					 	 
    <ul class="bx-slider"> 						 	 
      <li class="block"> 	 	<a href="/service/" class="wrap" > 								 	 		<img src="<?=SITE_DIR?>images/slider/1.png" class="color"  /> 	 		<img src="<?=SITE_DIR?>images/slider/1gray.png" class="preview"  /> 							 	 	</a> 							 	 		<span><a href="/service/" >Гарантированная парковка в любой точке города и окрестностях.</a></span> 						 	 </li>
     	 						 	 
      <li class="block"> 							 	 	<a href="/service/" class="wrap" > 								 	 		<img src="<?=SITE_DIR?>images/slider/2.png" class="color"  /> 								 	 		<img src="<?=SITE_DIR?>images/slider/2gray.png" class="preview"  /> 							 	 	</a> 							 	 	<span><a href="/service/" >Постоянно обновляющиеся данные обо всех паркингах Москвы и МО.</a></span> 						 	 </li>
     	 						 	 
      <li class="block"> 							 	 	<a href="/service/" class="wrap" > 								 	 		<img src="<?=SITE_DIR?>images/slider/3.png" class="color"  /> 								 	 		<img src="<?=SITE_DIR?>images/slider/3gray.png" class="preview"  /> 							 	 	</a> 							 	 	<span><a href="/service/" >30 секунд и паркинг забронирован! Бронируйте место по дороге на встречу.</a></span> 						 	 </li>
     	 	 
      <li class="block"> 							 	 	<a href="/service/" class="wrap" > 								 	 		<img src="<?=SITE_DIR?>images/slider/4.png" class="color"  /> 								 	 		<img src="<?=SITE_DIR?>images/slider/4gray.png" class="preview"  /> 							 	 	</a> 							 	 	<span><a href="/service/" >Гибкая система тарифов, скидки и программы лояльности.</a></span> 						 	 </li>
     	 						 	 
      <li class="block"> 							 	 	<a href="/service/" class="wrap" > 								 	 		<img src="<?=SITE_DIR?>images/slider/5.png" class="color"  /> 								 	 		<img src="<?=SITE_DIR?>images/slider/5gray.png" class="preview"  /> 							 	 	</a> 							 	 	<span><a href="/service/" >Въезд на паркинг и выезд из него с помощью мобильного телефона.</a></span> 						 	 </li>
     						 					 	</ul>
   		<span class="back"></span> 					 		<span class="next"></span> 				 	</div>
 
<script>

				// Слайдер на главной
				$('.bx-slider').bxSlider({
					pager: false,
					nextSelector: '.next',
					prevSelector: '.back',
					nextText: ' ',
					prevText: ' ',
					useCSS: false,
					minSlides: 3,
					maxSlides: 3,
					moveSlides: 1,
					slideWidth: '224px',
					speed: 230,
					onSliderLoad: function(currentIndex){
						//$('.bx-slider .block').not('.bx-clone').eq(1).addClass('center active').find('img').not('.pie').addClass('pie');
						$('.bx-slider .block').not('.bx-clone').eq(1).addClass('center active');
						
						$('.bx-slider .block img').not('.pie').addClass('pie');
						$('.bx-slider .block a').not('.pie').addClass('pie');
					},
					onSlideBefore: function(slideElement, oldIndex, newIndex){
						$('.bx-slider .block').removeClass('center active').find('img').removeClass('pie pie_first-child').addClass('pie pie_first-child');
						
						$('.bx-slider .block a').removeClass('pie pie_first-child').addClass('pie pie_first-child');
					},
					onSlideAfter: function(slideElement, oldIndex, newIndex){
						$('.bx-slider .block').removeClass('center active').find('img').removeClass('pie pie_first-child').addClass('pie pie_first-child');
						slideElement.next('.block').addClass('center active').find('img').removeClass('pie pie_first-child').addClass('pie pie_first-child');
							
						$('.bx-slider .block a').removeClass('pie pie_first-child').addClass('pie pie_first-child');
						slideElement.next('.block a').removeClass('pie pie_first-child').addClass('pie pie_first-child');
					}
					/*,onSlideNext: function(slideElement, oldIndex, newIndex) {

					}*/
				});

				/*setInterval(setPie, 300);

				function setPie()
				{
					$('.bx-clone').find('a').addClass('for_pie')
					$('.for_pie').addClass('pie');
				}*/



				/**
				// Слайдер на главной  
				//(работает с одним лишь багом - бордер квадратный у 2-х из 5-ти элементов)
				$('.bx-slider').bxSlider({
					pager: false,
					nextSelector: '.next',
					prevSelector: '.back',
					nextText: ' ',
					prevText: ' ',
					useCSS: false,
					minSlides: 3,
					maxSlides: 3,
					moveSlides: 1,
					slideWidth: '224px',
					speed: 230,
					onSliderLoad: function(currentIndex){
						//$('.bx-slider .block').not('.bx-clone').eq(1).addClass('center active').find('img').not('.pie').addClass('pie');
						$('.bx-slider .block').not('.bx-clone').eq(1).addClass('center active');
						$('.bx-slider .block img').not('.pie').addClass('pie');
						$('.bx-slider .block a').find('a').not('.pie').addClass('pie');
						$('.block.bx-clone').addClass('pie');
					},
					onSlideBefore: function(slideElement, oldIndex, newIndex){
						$('.bx-slider .block').removeClass('center active').find('img').removeClass('pie pie_first-child').addClass('pie pie_first-child');
						//$('.bx-slider .block').find('a').not('.pie').addClass('pie');
					},
					onSlideAfter: function(slideElement, oldIndex, newIndex){
						$('.bx-slider .block').removeClass('center active').find('img').removeClass('pie pie_first-child').addClass('pie pie_first-child');
						slideElement.next('.block').addClass('center active').find('img').removeClass('pie pie_first-child').addClass('pie pie_first-child');
						//$('.bx-slider .block').find('a').not('.pie').addClass('pie');
					}
				});
				//$('.bx-clone').each(function(index, value){
					//$(this).addClass('pie_first-child');
				//});
				*/

				</script>
 			</div>
 		 
<div class="wide-mobile"> 			 
  <div class="fix mobileApp pie"> 				 
    <div class="container"> 					 
      <div class="left text"> 						 
        <h2>Скачайте A1PARK на телефон</h2>
       						В свободном доступе бесплатное приложение для мобильных устройств &ndash; моментальный паркинг онлайн VK49865
        <br />
       </div>
     					<a href="https://itunes.apple.com/us/app/a1park/id673100326" class="appStore" target="itunes_store" ></a> 					<a href="https://play.google.com/store/apps/details?id=com.a1park.a1park&hl=ru" id="bxid_570034" class="googlePlay" ></a> 					</div>
   			</div>
 		</div>
 		 
<div class="wide-news"> 			 
  <div class="b-cont b-cont__tall"> 			<?$APPLICATION->IncludeComponent(
	"bitrix:news.list", 
	"a1park", 
	array(
		"IBLOCK_TYPE" => "news",
		"IBLOCK_ID" => "31",
		"NEWS_COUNT" => "3",
		"SORT_BY1" => "ACTIVE_FROM",
		"SORT_ORDER1" => "DESC",
		"SORT_BY2" => "ACTIVE_FROM",
		"SORT_ORDER2" => "DESC",
		"FILTER_NAME" => "",
		"FIELD_CODE" => array(
			0 => "",
			1 => "",
		),
		"PROPERTY_CODE" => array(
			0 => "",
			1 => "",
		),
		"CHECK_DATES" => "Y",
		"DETAIL_URL" => "",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"AJAX_OPTION_HISTORY" => "N",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "36000000",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "Y",
		"PREVIEW_TRUNCATE_LEN" => "",
		"ACTIVE_DATE_FORMAT" => "d.m.Y",
		"SET_TITLE" => "Y",
		"SET_STATUS_404" => "N",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "Y",
		"ADD_SECTIONS_CHAIN" => "Y",
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"DISPLAY_TOP_PAGER" => "N",
		"DISPLAY_BOTTOM_PAGER" => "Y",
		"PAGER_TITLE" => "Новости",
		"PAGER_SHOW_ALWAYS" => "Y",
		"PAGER_TEMPLATE" => "",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "Y",
		"AJAX_OPTION_ADDITIONAL" => "",
		"COMPONENT_TEMPLATE" => "a1park",
		"SET_BROWSER_TITLE" => "Y",
		"SET_META_KEYWORDS" => "Y",
		"SET_META_DESCRIPTION" => "Y",
		"SET_LAST_MODIFIED" => "N",
		"INCLUDE_SUBSECTIONS" => "Y",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"SHOW_404" => "N",
		"FILE_404" => "",
		"MESSAGE_404" => ""
	),
	false
);?> 			</div>
 		</div>
 		 
<div class="wide video"> 				 
  <div class="container"> 					 
    <h2> </h2>
   
    <div></div>
   				 
    <div style="text-align: center;" class="block"><a href="/parking/payment.php" ><img src="/upload/medialibrary/b7e/fffffffffffffff copy.png" title="call_center" border="0" align="middle" alt="fffffffffffffff copy.png" width="360" height="320"  /></a></div>
   	 
<!--  <div class="block"> 						
    	<img id="bxid_93725" style="cursor: default;" src="/bitrix/components/bitrix/player/images/icon.gif"  />					</div> -->
 				</div>
 			</div>
 <?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>