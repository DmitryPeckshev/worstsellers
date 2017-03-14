<?
use \Bitrix\Main\Loader as Loader;
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!Loader::includeModule("sale") || !Loader::includeModule("iblock") || !Loader::includeModule("catalog"))
{
	ShowError(GetMessage("SBP_NEED_REQUIRED_MODULES"));
	die();
}


$time_values = array(
	"1" => "1",
	"7" => "7",
	"15" => "15",
	"30" => "30",
);
$min_values = array(
	"0" => "0",
	"1" => "1",
	"2" => "2",
	"3" => "3",
	"4" => "4",
	"5" => "5",
	"6" => "10",
	"7" => "15",
	"8" => "20",
	"9" => "25",
	"10" => "30",
	"11" => "50",
	"12" => "100",
	"13" => "нет",
);
$show_values = array(
	"0" => "10",
	"1" => "25",
	"2" => "50",
	"3" => "100",
	"4" => "250",
	"5" => "500",
	"6" => "все",
);
$show_values = array(
	"10" => "10",
	"25" => "25",
	"50" => "50",
	"100" => "100",
	"250" => "250",
	"500" => "500",
	"все" => "все",
);

$arComponentParameters = array(
   "GROUPS" => array(
      "BASE" => array(
         "NAME" => GetMessage("BASE_PHR")
      ),
   ),
   "PARAMETERS" => array(
      "MAIN_PERIOD" => array(
         "PARENT" => "BASE",
         "NAME" => GetMessage("WS_PERIOD"),
         "TYPE" => "LIST",
         "ADDITIONAL_VALUES" => "N",
         "VALUES" => $time_values,
         "REFRESH" => "N",
		 "MULTIPLE" => "N",
		 "DEFAULT" => "7",
      ), 
	  "MIN_QUANTITY" => array(
         "PARENT" => "BASE",
         "NAME" => GetMessage("WS_MIN"),
         "TYPE" => "LIST",
         "ADDITIONAL_VALUES" => "N",
         "VALUES" => $min_values,
         "REFRESH" => "N",
		 "MULTIPLE" => "N",
      ),
	   "IS_MAIL" => array(
		 "PARENT" => "BASE",
         "NAME" => GetMessage("WS_MAIL"),
         "TYPE" => "CHECKBOX",
         "REFRESH" => "N",
		 "DEFAULT" => "Y",
	  ),
	  "IS_ACTIVE" => array(
		 "PARENT" => "BASE",
         "NAME" => GetMessage("WS_ACTIVE"),
         "TYPE" => "CHECKBOX",
         "REFRESH" => "N",
		 "DEFAULT" => "Y",
	  ),
	  "PAGINATION" => array(
         "PARENT" => "BASE",
         "NAME" => GetMessage("WS_PAGINATION"),
         "TYPE" => "LIST",
         "ADDITIONAL_VALUES" => "N",
         "VALUES" => $show_values,
         "REFRESH" => "N",
		 "MULTIPLE" => "N",
		 "DEFAULT" => "3",
      ), 
	  
	"SET_TITLE" => array(),
    "CACHE_TIME" => array(),
	),
);


?>
