<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<div class="bx-sidebar-block">
	<?$APPLICATION->IncludeComponent(
	"bitrix:menu", 
	"personal_menu", 
	array(
		"ROOT_MENU_TYPE" => "personal",
		"MAX_LEVEL" => "1",
		"MENU_CACHE_TYPE" => "A",
		"CACHE_SELECTED_ITEMS" => "N",
		"MENU_CACHE_TIME" => "36000000",
		"MENU_CACHE_USE_GROUPS" => "Y",
		"MENU_CACHE_GET_VARS" => array(
		),
		"COMPONENT_TEMPLATE" => "personal_menu",
		"CHILD_MENU_TYPE" => "left",
		"USE_EXT" => "N",
		"DELAY" => "N",
		"ALLOW_MULTI_SELECT" => "N"
	),
	false
);?>
<?
global $USER;
$user_groups = $USER->GetUserGroupArray();
$boss_group = CGroup::GetList(
		($by = "id"),($order = "asc"),
		Array("NAME"  => "Руководители",)
	);
$user_is_boss = false;	
while ($boss_group_row = $boss_group->Fetch()) {
	$group_id = $boss_group_row["ID"];
}
foreach($user_groups as $group){
	if($group == $group_id){
		$user_is_boss = true;
	}
}
if($user_is_boss == true):?>
<ul class="bx-inclinkspersonal-list">
<li class="bx-inclinkspersonal-item" display="list-item">
	<a class="bx-inclinkspersonal-item-element" href="/personal/neprodavaemye.php">
		Непродаваемые товары
	</a>
</li>
</ul>
<?endif?>
</div>
<div class="bx-sidebar-block">
	<?$APPLICATION->IncludeComponent(
		"bitrix:main.include",
		"",
		Array(
			"AREA_FILE_SHOW" => "file",
			"PATH" => SITE_DIR."include/socnet_sidebar.php",
			"AREA_FILE_RECURSIVE" => "N",
			"EDIT_MODE" => "html",
		),
		false,
		Array('HIDE_ICONS' => 'Y')
	);?>
</div>

<div class="bx-sidebar-block hidden-xs">
	<?$APPLICATION->IncludeComponent(
		"bitrix:main.include",
		"",
		Array(
			"AREA_FILE_SHOW" => "file",
			"PATH" => SITE_DIR."include/sender.php",
			"AREA_FILE_RECURSIVE" => "N",
			"EDIT_MODE" => "html",
		),
		false,
		Array('HIDE_ICONS' => 'Y')
	);?>
</div>

<div class="bx-sidebar-block">
	<?$APPLICATION->IncludeComponent(
		"bitrix:main.include",
		"",
		Array(
			"AREA_FILE_SHOW" => "file",
			"PATH" => SITE_DIR."include/about.php",
			"AREA_FILE_RECURSIVE" => "N",
			"EDIT_MODE" => "html",
		),
		false,
		Array('HIDE_ICONS' => 'N')
	);?>
</div>

