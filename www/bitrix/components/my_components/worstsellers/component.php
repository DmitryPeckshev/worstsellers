<?php
use Bitrix\Main\Localization\Loc,
	Bitrix\Main\SystemException,
	Bitrix\Main\Loader,
	Bitrix\Sale;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if ($this->StartResultCache(false, false)) {

$arResult = array();
$arResult['days'] = $arParams['MAIN_PERIOD'];

$date_limit = new DateTime(date('Y-m-d h:m:s'));
$time_difference = '-'.$arParams['MAIN_PERIOD'].' day';
$date_limit->modify($time_difference);
$date_limit = $date_limit->format('d.m.Y h:m:s');
$date_limit = new \Bitrix\Main\Type\DateTime($date_limit);

$all_buskets = CSaleBasket::GetList( 
	array(),
	array(
		"LID" => SITE_ID,
		">DATE_INSERT" => $date_limit,
	),
	false,
	false,
	array(
		"PRODUCT_ID","QUANTITY","LID","DATE_INSERT",
	)
);

$BusketsQuantities = array(); 
$BusketsProductsId = array(); 
$UnnecessaryProducts = array();
while ($all_buskets_row = $all_buskets->Fetch()) { 
	if($BusketsQuantities[$all_buskets_row["PRODUCT_ID"]] != false) {
		$BusketsQuantities[$all_buskets_row["PRODUCT_ID"]] += $all_buskets_row["QUANTITY"];	
	}else{
		$BusketsQuantities[$all_buskets_row["PRODUCT_ID"]] = $all_buskets_row["QUANTITY"];	
		if($all_buskets_row["QUANTITY"] <= $arParams['MIN_QUANTITY']){
			array_push($BusketsProductsId, $all_buskets_row["PRODUCT_ID"]);
		}else{
			array_push($UnnecessaryProducts, $all_buskets_row["PRODUCT_ID"]);
		}
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

$all_elements_filter = Array("IBLOCK_ID"=>$id_offers);  // фильтр для предложений и товаров
if($arParams['IS_ACTIVE'] == "Y"){
	$all_elements_filter['ACTIVE'] = "Y";
}
$all_elements_filter["!ID"] = $UnnecessaryProducts;

if($id_offers !== false) {
	$all_offers = CIBlockElement::GetList(  // все предложения
		Array(),
		$all_elements_filter,
		false,
		false,
		Array(
			"ID","NAME","ACTIVE","DETAIL_PAGE_URL"
		)
	);
	$OffersArray = array();
	while($all_offers_row = $all_offers->Fetch()) { // все предложения вывод
		array_push($OffersArray, $all_offers_row["ID"]);
	}
}

if(!empty($OffersArray)) {
	$dbOffersPrice = CPrice::GetListEx( // цены всех предложений
		array(),
		array("PRODUCT_ID" => $OffersArray),
		false,
		false,
		array("PRODUCT_ID", "PRICE", "CURRENCY")
	);

	$OffersPricesArray = array();
	while($dbOffersPriceRow = $dbOffersPrice->Fetch()) { // все предложения вывод
		$OffersPricesArray[$dbOffersPriceRow["PRODUCT_ID"]] = array("PRICE" => $dbOffersPriceRow["PRICE"], "CURRENCY" => $dbOffersPriceRow["CURRENCY"]);
	}
	
	$SKU_Array = CCatalogSKU::getProductList(  //массив соответствий товаров предложениям
		$OffersArray,
		$id_offers
	);
	
	$SupportArray = array();
	foreach($SKU_Array as $IdOffer => $IdProduct){
		if($SupportArray[$IdProduct["ID"]]['NUM_OF_BUYS'] == false) {
			$SupportArray[$IdProduct["ID"]]['NUM_OF_BUYS'] = $BusketsQuantities[$IdOffer];
		}else{
			$SupportArray[$IdProduct["ID"]]['NUM_OF_BUYS'] += $BusketsQuantities[$IdOffer];
		}
		if($SupportArray[$IdProduct["ID"]]['NUM_OF_BUYS'] > $arParams['MIN_QUANTITY'] && !in_array($IdProduct["ID"])){
			array_push($UnnecessaryProducts, $IdProduct["ID"]);
		}
		if($OffersPricesArray[$IdOffer]['PRICE'] < $SupportArray[$IdProduct["ID"]]['MIN_PRICE'] || $SupportArray[$IdProduct["ID"]]['MIN_PRICE'] == false){
			$SupportArray[$IdProduct["ID"]]['MIN_PRICE'] = $OffersPricesArray[$IdOffer]['PRICE'];
			$SupportArray[$IdProduct["ID"]]['CURRENCY'] = $OffersPricesArray[$IdOffer]['CURRENCY'];
		}
		if($OffersPricesArray[$IdOffer]['PRICE'] > $SupportArray[$IdProduct["ID"]]['MAX_PRICE']){
			$SupportArray[$IdProduct["ID"]]['MAX_PRICE'] = $OffersPricesArray[$IdOffer]['PRICE'];
			$SupportArray[$IdProduct["ID"]]['CURRENCY'] = $OffersPricesArray[$IdOffer]['CURRENCY'];
		}
	}
}	

$AllProducts = array();
$AllProductsID = array();
$all_elements_filter["!ID"] = $UnnecessaryProducts;
$all_elements_filter['IBLOCK_ID'] = $id_catalog;
$DBProducts = CIBlockElement::GetList(  // все продукты
	Array(),
	$all_elements_filter,
	false,
	false,
	Array(
		"ID","NAME","ACTIVE","DETAIL_PAGE_URL"
	)
);
while ($DBProductsRow = $DBProducts->GetNext()) {
	$ChildArray = array("ID"=>$DBProductsRow["ID"],"NAME"=>$DBProductsRow["NAME"],"DETAIL_PAGE_URL"=>$DBProductsRow["DETAIL_PAGE_URL"]);
	array_push($AllProducts, $ChildArray);
	array_push($AllProductsID, $DBProductsRow["ID"]);
}

$dbProductPrice = CPrice::GetListEx( // цены всех продуктов
    array(),
    array("PRODUCT_ID" => $AllProductsID),
    false,
    false,
    array("PRODUCT_ID", "PRICE", "CURRENCY")
);
$ProductsPricesArray = array();
while($dbProductPriceRow = $dbProductPrice->Fetch()) { 
	$ProductsPricesArray[$dbProductPriceRow["PRODUCT_ID"]] = array("PRICE" => $dbProductPriceRow["PRICE"], "CURRENCY" => $dbProductPriceRow["CURRENCY"]);
}

$AllArtnumbers = array();
$dbArtnumbers = CIBlockElement::GetList(     // все артикулы
	array(), 
	array('IBLOCK_ID' => $id_catalog, 'ID' => $AllProductsID), 
	false, 
	false, 
	array('ID', 'PROPERTY', 'PROPERTY_ARTNUMBER')
);
while($dbArtnumbersRow = $dbArtnumbers->GetNextElement()) {
	$arArt = $dbArtnumbersRow->GetFields();
	$AllArtnumbers[$arArt['ID']] = $arArt['PROPERTY_ARTNUMBER_VALUE'];
}

for($i=0; $i<count($AllProducts); $i++){
	$AllProducts[$i]["ARTNUMBER"] = $AllArtnumbers[$AllProducts[$i]["ID"]];
	if($ProductsPricesArray[$AllProducts[$i]["ID"]] != false){
		$AllProducts[$i]['MIN_PRICE'] = $ProductsPricesArray[$AllProducts[$i]["ID"]]["PRICE"];
		$AllProducts[$i]['MAX_PRICE'] = $ProductsPricesArray[$AllProducts[$i]["ID"]]["PRICE"];
		$AllProducts[$i]['CURRENCY'] = $ProductsPricesArray[$AllProducts[$i]["ID"]]["CURRENCY"];
	}
	if($BusketsQuantities[$AllProducts[$i]["ID"]] != false){
		$AllProducts[$i]['NUM_OF_BUYS'] = $BusketsQuantities[$AllProducts[$i]["ID"]];
	}
	if($SupportArray[$AllProducts[$i]["ID"]]["MIN_PRICE"] != false && $SupportArray[$AllProducts[$i]["ID"]]["MAX_PRICE"] != false) {
		$AllProducts[$i]['MIN_PRICE'] = $SupportArray[$AllProducts[$i]["ID"]]["MIN_PRICE"];
		$AllProducts[$i]['MAX_PRICE'] = $SupportArray[$AllProducts[$i]["ID"]]["MAX_PRICE"];
		$AllProducts[$i]['CURRENCY'] = $SupportArray[$AllProducts[$i]["ID"]]["CURRENCY"];
	}
	
	if($AllProducts[$i]['NUM_OF_BUYS'] == false) {
		$AllProducts[$i]['NUM_OF_BUYS'] = $SupportArray[$AllProducts[$i]["ID"]]['NUM_OF_BUYS'];
	}else{
		$AllProducts[$i]['NUM_OF_BUYS'] += $SupportArray[$AllProducts[$i]["ID"]]['NUM_OF_BUYS'];
	}
}

function sort_worstsellers($a, $b) {
	return strcmp($a['NUM_OF_BUYS'], $b['NUM_OF_BUYS']);
}
usort($AllProducts, "sort_worstsellers");

if($arParams['PAGINATION'] != "все") {
	$rs_ObjectList = new CDBResult;
	$rs_ObjectList->InitFromArray($AllProducts);
	$rs_ObjectList->NavStart(intval($arParams['PAGINATION']), false);
	$arResult["NAV_STRING"] = $rs_ObjectList->GetPageNavString("", '');
	$arResult["PAGE_START"] = $rs_ObjectList->SelectedRowsCount() - ($rs_ObjectList->NavPageNomer - 1) * $rs_ObjectList->NavPageSize;
	while($ar_Field = $rs_ObjectList->Fetch())
	{
		$arResult["products"][] = $ar_Field;
	}
}else{
	$arResult["products"] = $AllProducts;
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


} // cashe

?>