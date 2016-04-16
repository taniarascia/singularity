<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: pvtmarketsell.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'PVTMARKETSELL_TITLE';

page_header();

if (ROUND_FINISHED)
	unavailable(lang('PVTMARKETSELL_UNAVAILABLE_END'));
if (!ROUND_STARTED)
	unavailable(lang('PVTMARKETSELL_UNAVAILABLE_START'));

$types = lookup('list_mkt');
$limits = lookup('pvtmkt_name_limit');
$stores = lookup('pvtmkt_name_id');

// Calculates the selling price of a military unit
function getCost ($emp, $base, $multiplier)
{
	$cost = $base * $multiplier;

	// when selling units, the bonus INCREASES the price, so you can get more for them
	$costbonus = 1 + (1 - PVTM_SHOPBONUS) * ($emp->e_bldcost / $emp->e_land) + PVTM_SHOPBONUS * ($emp->e_bldcash / $emp->e_land);

	$cost *= $costbonus;
	$cost /= (2 - $emp->getModifier('market'));
	if ($cost > $base * 0.5)
		$cost = $base * 0.5;
	return round($cost);
}

$costs = array();
$costs['trparm'] = getCost($emp1, PVTM_TRPARM, 0.32);
$costs['trplnd'] = getCost($emp1, PVTM_TRPLND, 0.34);
$costs['trpfly'] = getCost($emp1, PVTM_TRPFLY, 0.36);
$costs['trpsea'] = getCost($emp1, PVTM_TRPSEA, 0.38);
$costs['food'] = round(PVTM_FOOD * 0.20);

if ($action == 'sell') do
{
	if (!isFormPost())
		break;
	foreach ($types as $type)
	{
		$amount = fixInputNum(getFormVar('sell_'. $type));
		if ($amount == 0)
			continue;
		$price = $costs[$type];
		$cost = $amount * $price;
		if (isset($limits[$type]) && ($amount > (PVTM_MAXSELL - $emp1->getData($limits[$type])) / 10000 * $emp1->getData('e_'. $type)))
		{
			notice(lang('PVTMARKETSELL_TOO_MANY_UNITS', $emp1->era->getData($type)));
			continue;
		}
		if (!isset($limits[$type]) && ($amount > $emp1->getData('e_'. $type)))
		{
			notice(lang('PVTMARKETSELL_NOT_ENOUGH_UNITS', $emp1->era->getData($type)));
			continue;
		}
		$emp1->e_cash += $cost;
		if (isset($limits[$type]))
			$emp1->addData($limits[$type], round(($amount / $emp1->getData('e_'. $type)) * 10000));
		$emp1->subData('e_'. $type, $amount);
//		$emp1->addData($stores[$type], $amount);	// allow items to be bought back
		notice(lang('PVTMARKETSELL_COMPLETE', number($amount), $emp1->era->getData($type), money($cost)));
		logevent(varlist(array('type', 'amount', 'cost'), get_defined_vars()));
	}
} while (0);
notices();
?>
<form method="post" action="?location=pvtmarketsell">
<table class="inputtable">
<tr><td colspan="2"><a href="?location=pvtmarketbuy"><?php echo lang('PVTMARKET_LINK_BUY'); ?></a></td>
    <td></td>
    <td colspan="2" class="ar"><a href="?location=pvtmarketsell"><?php echo lang('PVTMARKET_LINK_SELL'); ?></a></td></tr>
<tr><th class="al"><?php echo lang('COLUMN_UNIT'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_OWNED'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_PRICE'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_CANSELL'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_SELL'); ?></th></tr>
<?php
foreach ($types as $type)
{
	if ($type == 'food')
		$cansell = $emp1->getData('e_'. $type);
	else	$cansell = floor((PVTM_MAXSELL - $emp1->getData($limits[$type])) / 10000 * $emp1->getData('e_'. $type));
?>
<tr><td><?php echo lang($emp1->era->getData($type)); ?></td>
    <td class="ar"><?php echo number($emp1->getData('e_'. $type)); ?></td>
    <td class="ar"><?php echo money($costs[$type]); ?></td>
    <td class="ar"><?php echo number($cansell) .' '. copybutton('sell_'. $type, number($cansell)); ?></td>
    <td class="ar"><input type="text" name="sell_<?php echo $type; ?>" id="sell_<?php echo $type; ?>" size="8" value="0" /></td></tr>
<?php
}
?>
<tr><td colspan="6" class="ac"><input type="hidden" name="action" value="sell" /><input type="submit" value="<?php echo lang('PVTMARKETSELL_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
page_footer();
?>
