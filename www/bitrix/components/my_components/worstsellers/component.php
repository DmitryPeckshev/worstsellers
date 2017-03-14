<?php
use Bitrix\Main\Localization\Loc,
	Bitrix\Main\SystemException,
	Bitrix\Main\Loader,
	Bitrix\Sale;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if ($this->StartResultCache(false, false)) {

$arResult = array();
$arResult['days'] = $arParams['MAIN_PERIOD'];

$all_buskets = CSaleBasket::GetList( 
	array(),
	array(),
	false,
	false,
	array(
		"PRODUCT_ID","QUANTITY","LID","NAME","ORDER_ID","DATE_INSERT","DATE_UPDATE"
	)
);

if ($arParams['MAIN_PERIOD'] !== 'нет'):  
	$site_date_format = CSite::GetDateFormat("SHORT");
	$php_date_format = $DB->DateFormatToPHP($site_date_format);
	$date_now = date('Y-m-d');
	$date_limit = new DateTime($date_now);
	$time_difference = '-'.$arParams['MAIN_PERIOD'].' day';
	$date_limit->modify($time_difference);
	$date_limit = $date_limit->format($php_date_format);
endif;


$buskets_array = array();  
while ($all_buskets_row = $all_buskets->Fetch()) { 
	$busket_insert = $DB->FormatDate($all_buskets_row["DATE_INSERT"], $site_date_format, $php_date_format);
	if(strtotime($busket_insert) > strtotime($date_limit) || $arParams['MAIN_PERIOD'] === 'нет'){
		array_push($buskets_array, $all_buskets_row);
	}
}


$catalog_info = CCatalog::GetList(); 
while ($ar_cat = $catalog_info->Fetch()) {
	if($ar_cat['IBLOCK_TYPE_ID'] == 'catalog'){
		$id_catalog = $ar_cat['IBLOCK_ID'];
	}
	if($ar_cat['IBLOCK_TYPE_ID'] == 'offers'){
		$id_offers = $ar_cat['IBLOCK_ID'];
	}
}


$all_elements_filter = Array("IBLOCK_ID"=>$id_catalog);
if($arParams['IS_ACTIVE'] == "Y"){
	$all_elements_filter['ACTIVE'] = "Y";
}
$all_elements = CIBlockElement::GetList(  
	Array("SORT"=>"ASC" ),
	$all_elements_filter,
	false,
	false,
	Array(
	"ID","NAME","ACTIVE","DETAIL_PICTURE","CODE","DETAIL_PAGE_URL"
 )
);

$all_elements_filter['IBLOCK_ID'] = $id_offers;
$all_offers = CIBlockElement::GetList( 
	Array("SORT"=>"ASC" ),
	$all_elements_filter,
	false,
	false,
	Array(
	"ID","NAME","ACTIVE","IBLOCK_SECTION_ID","DETAIL_PICTURE","CODE","DETAIL_PAGE_URL"
	)
);



$offers_result = array();
$offers_price = array();
while ($all_offers_row = $all_offers->Fetch()) {
	$num_of_buy = 0;
	foreach ($buskets_array as $buskets_array_row) {
		if($buskets_array_row['PRODUCT_ID'] == $all_offers_row['ID']){
			$num_of_buy += $buskets_array_row['QUANTITY']; 
		}
	}
	
	$current_product_id = CCatalogSku::GetProductInfo($all_offers_row['ID']);
	if ($num_of_buy > 0) {
		$offers_result_elem = array(
			product_id => $current_product_id['ID'],
			buy_quantity => $num_of_buy,
		);
		array_push($offers_result, $offers_result_elem);
	}
	
	
	$db_price = CPrice::GetList(
        array(),
        array("PRODUCT_ID" => $all_offers_row['ID'])
    );
	
	if ($ar_price = $db_price->Fetch()) {
		if(!$offers_price[$current_product_id['ID']]) {
			$offers_price[$current_product_id['ID']] = array(
				"min_price" => $ar_price["PRICE"], 
				"max_price" => $ar_price["PRICE"], 
				"currency" => $ar_price["CURRENCY"],
			);
		}else{
			if($offers_price[$current_product_id['ID']]["min_price"] > $ar_price["PRICE"]){
				$offers_price[$current_product_id['ID']]["min_price"] = $ar_price["PRICE"];
			}
			if($offers_price[$current_product_id['ID']]["max_price"] < $ar_price["PRICE"]){
				$offers_price[$current_product_id['ID']]["max_price"] = $ar_price["PRICE"];
			}
		}
	}
}


$result_array = array();		
while ($all_elements_row = $all_elements->GetNext()) {
		
	$num_of_buy = 0;
	foreach ($buskets_array as $buskets_array_row) {
		if($buskets_array_row['PRODUCT_ID'] == $all_elements_row['ID']){
			$num_of_buy += $buskets_array_row['QUANTITY']; 
		}
	}
	foreach($offers_result as $offers_result_row) {
		if($all_elements_row['ID'] == $offers_result_row['product_id']) {
			$num_of_buy += $offers_result_row['buy_quantity'];
		}	
	}
	$all_elements_row['NUM_OF_BUYS'] = $num_of_buy;
	$all_elements_row['IMG_URL'] = CFile::GetPath($all_elements_row['DETAIL_PICTURE']);
	$all_elements_row['DETAIL_URL'] = $ar_res['DETAIL_PAGE_URL'];

	$db_price = CPrice::GetList(
        array(),
        array("PRODUCT_ID" => $all_elements_row['ID'])
    );
	if ($ar_price = $db_price->Fetch()) {
		if(!$offers_price[$all_elements_row['ID']]) {
			$offers_price[$all_elements_row['ID']] = array(
				"min_price" => $ar_price["PRICE"], 
				"max_price" => $ar_price["PRICE"], 
				"currency" => $ar_price["CURRENCY"],
			);
		}else{
			if($offers_price[$all_elements_row['ID']]["min_price"] > $ar_price["PRICE"]){
				$offers_price[$all_elements_row['ID']]["min_price"] = $ar_price["PRICE"];
			}
			if($offers_price[$all_elements_row['ID']]["max_price"] < $ar_price["PRICE"]){
				$offers_price[$all_elements_row['ID']]["max_price"] = $ar_price["PRICE"];
			}
		}
	}
	$all_elements_row['MIN_PRICE'] = $offers_price[$all_elements_row['ID']]["min_price"];
	$all_elements_row['MAX_PRICE'] = $offers_price[$all_elements_row['ID']]["max_price"];
	$all_elements_row['CURRENCY'] = $offers_price[$all_elements_row['ID']]["currency"];
	
	$res = CIBlockElement::GetList(array(), array('IBLOCK_ID' => $id_catalog, 'ID' => $all_elements_row['ID']), false, false, array('PROPERTY', 'PROPERTY_ARTNUMBER'));
	while($ob = $res->GetNextElement()) {
		$ar_art = $ob->GetFields();
		$all_elements_row['ARTNUMBER'] = $ar_art['PROPERTY_ARTNUMBER_VALUE'];
	}
	if($num_of_buy <= $arParams['MIN_QUANTITY']) {
		array_push($result_array, $all_elements_row);
	}	
}	

function sort_worstsellers($a, $b) {
	return strcmp($a['NUM_OF_BUYS'], $b['NUM_OF_BUYS']);
}
usort($result_array, "sort_worstsellers");






if($arParams['PAGINATION'] != "все") {
	$rs_ObjectList = new CDBResult;
	$rs_ObjectList->InitFromArray($result_array);
	$rs_ObjectList->NavStart(intval($arParams['PAGINATION']), false);
	$arResult["NAV_STRING"] = $rs_ObjectList->GetPageNavString("", '');
	$arResult["PAGE_START"] = $rs_ObjectList->SelectedRowsCount() - ($rs_ObjectList->NavPageNomer - 1) * $rs_ObjectList->NavPageSize;
	while($ar_Field = $rs_ObjectList->Fetch())
	{
		$arResult["products"][] = $ar_Field;
	}
}else{
	$arResult["products"] = $result_array;
	$arResult["page_off"] = true;
}


$this->IncludeComponentTemplate(); 




//       ********** ПОЧТА **********
if(CModule::IncludeModule("subscribe")){  



	$rub_new = CRubric::GetList(
			array("SORT"=>"ASC", "NAME"=>"ASC"), 
			array("NAME"=>"Непопулярные товары", "LID"=>SITE_ID)
		);
	while($one_rub_new = $rub_new->Fetch()){
		if($one_rub_new["NAME"]==="Непопулярные товары"){
			$rub_exist = true;
			$my_subs_id = $one_rub_new["ID"];
			$min_quant_old = $one_rub_new["DESCRIPTION"];
			$my_subs_info = Array(
				"ACTIVE" => $one_rub_new["ACTIVE"],
				"NAME" => $one_rub_new["NAME"],
				"SORT" => $one_rub_new["SORT"],
				"DESCRIPTION" => $arParams['MIN_QUANTITY'],
				"LID" => $one_rub_new["LID"],
				"AUTO" => $one_rub_new["AUTO"],
				"TIMES_OF_DAY" => $one_rub_new["TIMES_OF_DAY"],
				"LAST_EXECUTED" => $one_rub_new["LAST_EXECUTED"],
				"TEMPLATE" => $one_rub_new["TEMPLATE"],
				"FROM_FIELD" => $one_rub_new["FROM_FIELD"], 
				"DAYS_OF_MONTH" => $one_rub_new["DAYS_OF_MONTH"],
				"DAYS_OF_WEEK" => $one_rub_new["DAYS_OF_WEEK"],
			);
		}else{
				$rub_exist = false;
		}
	}
	
	
	$boss_group = CGroup::GetList(
		($by = "id"),
		($order = "asc"),
		Array("NAME"  => "Руководители",)
	); 
	while ($boss_group_row = $boss_group->Fetch()) {
		$group_id = $boss_group_row["ID"];
	}
	$boss_users = CGroup::GetGroupUser($group_id);
	foreach($boss_users as $one_boss){
		$rs_user = CUser::GetByID($one_boss);
		$ar_user = $rs_user->Fetch();
		
		$subscr = CSubscription::GetList(
			array("ID"=>"ASC"),
			array("USER_ID"=> $ar_user["ID"])
		);
		while($subscr_arr = $subscr->Fetch()) {
			if($subscr_arr["EMAIL"] != $ar_user["EMAIL"]) {
				$arFields = Array(
					"USER_ID" => $ar_user["ID"],
					"FORMAT" => "html",
					"EMAIL" => $ar_user["EMAIL"],
					"ACTIVE" => "Y",
					"RUB_ID" => array('0' => $my_subs_id),
				);
				$subscr_update = new CSubscription;
				$subscr_update->Update($subscr_arr['ID'],$arFields);
			}
		}

		if(!$subscr_arr) {
			$arFields = Array(
				"USER_ID" => $ar_user["ID"],
				"FORMAT" => "html",
				"EMAIL" => $ar_user["EMAIL"],
				"ACTIVE" => "Y",
				"RUB_ID" => array('0' => $my_subs_id),
			);
			$subscr_add = new CSubscription;
			$subscr_add->Add($arFields);
		}
		
	}
	
	
	if($arParams['IS_MAIL'] === 'Y') {
		$my_subs_info["ACTIVE"] = "Y";
		
		switch($arParams['MAIN_PERIOD']){
			case 1:
				if($my_subs_info["DAYS_OF_WEEK"] === "1,2,3,4,5,6,7" && $my_subs_info["DAYS_OF_MONTH"] === "") {
					$my_subs_nochange = true;
				}else{
					$my_subs_info["DAYS_OF_WEEK"] = "1,2,3,4,5,6,7";
					$my_subs_info["DAYS_OF_MONTH"] = "";
				}
				break;
			case 7:
				if(strlen($my_subs_info["DAYS_OF_WEEK"]) === 1 && $my_subs_info["DAYS_OF_MONTH"] === "") {
					$my_subs_nochange = true;
				}else{
					$day_of_week = date("l");
					switch($day_of_week){
						case "Monday":
							$my_subs_info["DAYS_OF_WEEK"] = "1";
							break;
						case "Tuesday":
							$my_subs_info["DAYS_OF_WEEK"] = "2";
							break;
						case "Wednesday":
							$my_subs_info["DAYS_OF_WEEK"] = "3";
							break;
						case "Thursday":
							$my_subs_info["DAYS_OF_WEEK"] = "4";
							break;
						case "Friday":
							$my_subs_info["DAYS_OF_WEEK"] = "5";
							break;
						case "Saturday":
							$my_subs_info["DAYS_OF_WEEK"] = "6";
							break;
						case "Sunday":
							$my_subs_info["DAYS_OF_WEEK"] = "7";
							break;
					}
					$my_subs_info["DAYS_OF_MONTH"] = "";
				}
				break;
			case 15:
				if(strpos($my_subs_info["DAYS_OF_MONTH"],",") != false && $my_subs_info["DAYS_OF_WEEK"] === "") {
					$my_subs_nochange = true;
				}else{
					$day_now = date(d);
					$twice_a_month = ($day_now+15)%30;
					if($day_now > 28){
						$twice_a_month = "1".",".$twice_a_month;
					}elseif($twice_a_month > 28){
						$twice_a_month = "1".",".$day_now;
					}else{
						$twice_a_month = $twice_a_month.",".$day_now;
						$my_subs_info["DAYS_OF_MONTH"] = $twice_a_month;
					}
					$my_subs_info["DAYS_OF_MONTH"] = $twice_a_month;
					$my_subs_info["DAYS_OF_WEEK"] = "";
				}
				break;
			case 30:
				if(strpos($my_subs_info["DAYS_OF_MONTH"],",") == false && $my_subs_info["DAYS_OF_WEEK"] === "") {
					$my_subs_nochange = true;
				}else{ 
					$day_now = date(d);
					if($day_now > 28){
						$my_subs_info["DAYS_OF_MONTH"] = "1";
					}else{
						$my_subs_info["DAYS_OF_MONTH"] = $day_now;
					}
					$my_subs_info["DAYS_OF_WEEK"] = "";
				}
				break;
		}
		
		if(!$rub_exist) {
			$rsSites = CSite::GetByID(SITE_ID);
			$arSite = $rsSites->Fetch();
			$strEmail = $arSite['EMAIL'];
			
			$my_subs = new CRubric;
			$my_subs_ar = Array(
				"ACTIVE" => "Y",
				"NAME" => "Непопулярные товары",
				"SORT" => "100",
				"DESCRIPTION" => "Рассылка списка непопулярных товаров для руководителей",
				"LID" => SITE_ID,
				"AUTO" => "Y",
				"DAYS_OF_MONTH" => $my_subs_info["DAYS_OF_MONTH"],
				"DAYS_OF_WEEK" => $my_subs_info["DAYS_OF_WEEK"],
				"TIMES_OF_DAY" => "12:00",
				"LAST_EXECUTED" => date('d.m.Y H:i:s'),
				"TEMPLATE" => "bitrix/php_interface/subscribe/templates/unpopular_products",
				"FROM_FIELD" => $strEmail.".ru", //от кого
			);
			$my_subs->Add($my_subs_ar);
		
		}else{
			if(!$my_subs_nochange || $arParams['MIN_QUANTITY'] !=$min_quant_old){
				$my_subs = new CRubric;
				$my_subs->Update($my_subs_id, $my_subs_info);		
			}
		}
		
	}else{	
		$my_subs_info["ACTIVE"]	= "N";
		$my_subs = new CRubric;	
		$my_subs->Update($my_subs_id, $my_subs_info);
	}
}



}

?>