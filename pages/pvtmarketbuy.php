<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: pvtmarketbuy.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'PVTMARKETBUY_TITLE';

page_header(); ?>

<br/><img src="/images/market.jpg" style="max-width: 550px;"/>
<br/>

<?php

if (ROUND_FINISHED)
	unavailable(lang('PVTMARKETBUY_UNAVAILABLE_END'));
if (!ROUND_STARTED)
	unavailable(lang('PVTMARKETBUY_UNAVAILABLE_START'));

$types = lookup('list_mkt');
$stores = lookup('pvtmkt_name_id');

// Calculates the buying price of a military unit
function getCost ($emp, $base)
{
	$cost = $base;
	$costbonus = 1 - ((1 - PVTM_SHOPBONUS) * ($emp->e_bldcost / $emp->e_land) + PVTM_SHOPBONUS * ($emp->e_bldcash / $emp->e_land));

	$cost *= $costbonus;
	$cost *= (2 - $emp->getModifier('market'));
	if ($cost < $base * 0.6)
		$cost = $base * 0.6;
	return round($cost);
}

$costs = array();
$costs['trparm'] = getCost($emp1, PVTM_TRPARM);
$costs['trplnd'] = getCost($emp1, PVTM_TRPLND);
$costs['trpfly'] = getCost($emp1, PVTM_TRPFLY);
$costs['trpsea'] = getCost($emp1, PVTM_TRPSEA);
$costs['food'] = round(PVTM_FOOD);

if ($action == 'buy') do
{
	if (!isFormPost())
		break;
	foreach ($types as $type)
	{
		$amount = fixInputNum(getFormVar('buy_'. $type));
		if ($amount == 0)
			continue;
		$price = $costs[$type];
		$cost = $amount * $price;
		if ($amount > $emp1->getData($stores[$type]))
		{
			notice(lang('PVTMARKETBUY_NOT_ENOUGH_UNITS', $emp1->era->getData($type)));
			continue;
		}
		if ($cost > $emp1->e_cash)
		{
			notice(lang('PVTMARKETBUY_NOT_ENOUGH_MONEY', $emp1->era->getData($type)));
			continue;
		}
		$emp1->e_cash -= $cost;
		$emp1->addData('e_'. $type, $amount);
		$emp1->subData($stores[$type], $amount);
		notice(lang('PVTMARKETBUY_COMPLETE', number($amount), $emp1->era->getData($type), money($cost)));
		logevent(varlist(array('type', 'amount', 'cost'), get_defined_vars()));
	}
} while (0);
notices();
?>
<form method="post" action="?location=pvtmarketbuy">
<table class="inputtable">
<tr><td colspan="3"><a href="?location=pvtmarketbuy"><?php echo lang('PVTMARKET_LINK_BUY'); ?></a></td>
    <td colspan="3" class="ar"><a href="?location=pvtmarketsell"><?php echo lang('PVTMARKET_LINK_SELL'); ?></a></td></tr>
<tr><th class="al"><?php echo lang('COLUMN_UNIT'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_OWNED'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_AVAIL'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_PRICE'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_CANBUY'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_BUY'); ?></th></tr>
<?php
foreach ($types as $type)
{
	$canbuy = min(floor($emp1->e_cash / $costs[$type]), $emp1->getData($stores[$type]));
?>
<tr><td><?php echo lang($emp1->era->getData($type)); ?></td>
    <td class="ar"><?php echo number($emp1->getData('e_'. $type)); ?></td>
    <td class="ar"><?php echo number($emp1->getData($stores[$type])); ?></td>
    <td class="ar"><?php echo money($costs[$type]); ?></td>
    <td class="ar"><?php echo number($canbuy) .' '. copybutton('buy_'. $type, number($canbuy)); ?></td>
    <td class="ar"><input type="text" name="buy_<?php echo $type; ?>" id="buy_<?php echo $type; ?>" size="8" value="0" /></td></tr>
<?php
}
?>
<tr><td colspan="6" class="ac"><input type="hidden" name="action" value="buy" /><input type="submit" value="<?php echo lang('PVTMARKETBUY_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
page_footer();
?>
