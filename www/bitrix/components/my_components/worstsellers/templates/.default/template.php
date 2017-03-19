<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<div class="worstsallers_container">
<div class="worstsallers_row worstsallers_thead"><div class="worstsallers_row_name worstsallers_thead_name">Название товара</div><div class="worstsallers_row_art worstsallers_thead_art">Артикул</div><div class="worstsallers_row_price worstsallers_thead_price">Цена</div><div class="worstsallers_row_buys worstsallers_thead_buys">Продано<br/>за <?
	if($arResult['days'] === 1 || $arResult['days'] === "1"){
		echo $arResult['days']." день";
	}elseif($arResult['days'] === "нет"){
		echo "все время";
	}else{
		echo $arResult['days']." дней";
	}
	?></div><div class="worstsallers_row_link worstsallers_thead_link">Ссылка</div>
</div><? foreach($arResult["products"] as $one_product): ?>
<div class="worstsallers_row"><div class="worstsallers_row_name"><?
	echo $one_product['NAME'];
	?></div><div class="worstsallers_row_art"><?
	echo $one_product['ARTNUMBER'];
	?></div><div class="worstsallers_row_price"><?
	if($one_product["MIN_PRICE"]==$one_product["MAX_PRICE"]){
		echo CurrencyFormat($one_product["MIN_PRICE"], $one_product["CURRENCY"]);
		}else{
			echo CurrencyFormat($one_product["MIN_PRICE"], $one_product["CURRENCY"]);
			echo " -<br/>- ";
			echo CurrencyFormat($one_product["MAX_PRICE"], $one_product["CURRENCY"]);
		}?></div><div class="worstsallers_row_buys"><?
		echo $one_product['NUM_OF_BUYS'];
		if(!$one_product['NUM_OF_BUYS']){echo '0';}
		?></div><div class="worstsallers_row_link"><a href="<?echo $one_product['DETAIL_PAGE_URL']?>" target="_blank">Страница<br/>товара</a></div>
</div><? endforeach ?><br/>
<?echo $arResult["NAV_STRING"];?>
<br/>
</div>