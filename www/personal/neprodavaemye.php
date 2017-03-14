<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Непродаваемые товары");
$APPLICATION->SetTitle("Непродаваемые товары");
?>

<?$APPLICATION->IncludeComponent(
	"my_components:worstsellers", 
	".default", 
	array(
		"MAIN_PERIOD" => "7",
		"MIN_QUANTITY" => "6",
		"IS_MAIL" => "Y",
		"AJAX_MODE" => "N",
		"CACHE_TIME" => "86400",
		"CACHE_TYPE" => "A",
		"DETAIL_URL" => "",
		"COMPONENT_TEMPLATE" => ".default",
		"SET_TITLE" => "Y",
		"IS_ACTIVE" => "Y",
		"PAGINATION" => "50"
	),
	false
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>