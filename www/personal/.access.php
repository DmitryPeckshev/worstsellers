<?
$boss_group = CGroup::GetList(
	($by = "id"),($order = "asc"),
	Array("NAME"  => "Руководители",)
);
while ($boss_group_row = $boss_group->Fetch()) {
	$group_id = $boss_group_row["ID"];
}
$PERM["neprodavaemye.php"]["*"]="D";
$PERM["neprodavaemye.php"][$group_id]="X";
?>