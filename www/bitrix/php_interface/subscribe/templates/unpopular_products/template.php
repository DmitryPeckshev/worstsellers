<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
global $SUBSCRIBE_TEMPLATE_RUBRIC;
$SUBSCRIBE_TEMPLATE_RUBRIC=$arRubric;
global $APPLICATION;
?> <!--STYLE type=text/css>
.text {font-family: Verdana, Arial, Helvetica, sans-serif; font-size:12px; color: #1C1C1C; font-weight: normal;}
.newsdata{font-family: Arial, Helvetica, sans-serif; font-size:12px; font-weight:bold; color: #346BA0; text-decoration:none;}
H1 {font-family: Verdana, Arial, Helvetica, sans-serif; color:#346BA0; font-size:15px; font-weight:bold; line-height: 16px; margin-bottom: 1mm;}
</STYLE-->
<h1>Непопулярные товары</h1><?

$rub_all = CRubric::GetList(
	array("SORT"=>"ASC", "NAME"=>"ASC"), 
	array("NAME"=>"Непопулярные товары")
);

while($one_rub_new = $rub_all->Fetch()){
	if($one_rub_new["NAME"]==="Непопулярные товары"){
		$dayz_of_month = $one_rub_new["DAYS_OF_MONTH"];
		$dayz_of_weekz = $one_rub_new["DAYS_OF_WEEK"];
		$min_quant = $one_rub_new["DESCRIPTION"];
	}
}
if($dayz_of_weekz === "1,2,3,4,5,6,7" && $dayz_of_month === "") {
	$table_period = "1";
}
if(strlen($dayz_of_weekz) === 1 && $dayz_of_month === "") {
	$table_period = "7";
}
if(strpos($dayz_of_month,",") != false && $dayz_of_weekz === "") {
	$table_period = "15";
}
if(strpos($dayz_of_month,",") == false && $dayz_of_weekz === "") {
	$table_period = "30";
}

 
$APPLICATION->IncludeComponent(
	"my_components:worstsellers",
	"",
	Array(
		"CACHE_TIME" => "0",
		"CACHE_TYPE" => "N",
		"IS_MAIL" => "Y",
		"MAIN_PERIOD" => $table_period,
		"MIN_QUANTITY" => $min_quant,
		"SET_TITLE" => "Y"
		"PAGINATION" => "все",
	)
);

?><br>