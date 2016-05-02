<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: pubmarketsell.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'PUBMARKETSELL_TITLE';

if (($action == 'sell') || ($action == 'remove'))
	$db->lockGroup(ENT_MARKET);

page_header(); ?>

<br/><img src="/images/public-market.jpg" style="max-width: 550px;"/>
<br/>

<?php

if (ROUND_FINISHED)
	unavailable(lang('PUBMARKETSELL_UNAVAILABLE_END'));
if (!ROUND_STARTED)
	unavailable(lang('PUBMARKETSELL_UNAVAILABLE_START'));
if ($emp1->is_protected())
	unavailable(lang('PUBMARKETSELL_UNAVAILABLE_PROTECT'));
if ($emp1->e_flags & EFLAG_ADMIN)
	unavailable(lang('PUBMARKETSELL_UNAVAILABLE_ADMIN'));

$types = lookup('pubmkt_id_name');
$stores = lookup('pubmkt_name_id');
$defcost = lookup('pvtmkt_name_cost');

// Determines the current selling price of a unit
function getCost ($emp, $type, $default)
{
	global $db, $stores;
	$q = $db->prepare('SELECT MIN(k_price) AS k_price FROM '. MARKET_TABLE .' WHERE k_type = ? AND e_id != ? AND k_time <= ?');
	$q->bindIntValue(1, $stores[$type]);
	$q->bindIntValue(2, $emp->e_id);
	$q->bindIntValue(3, CUR_TIME);
	$q->execute() or warning('Failed to determine market prices', 0);
	$row = $q->fetch();
	$cost = $row['k_price'];
	if ($cost == 0)
		$cost = $default;
	return $cost;
}

// Gets all items on sale and returns how many more of that type can be sold
// Also builds a list so we can display them in table format at our own convenience
function calcBasket ($emp, $type, &$minsell, &$maxsell, &$sales)
{
	global $db, $stores;
	$q = $db->prepare('SELECT k_id,k_type,e_id,k_amt,k_price,k_time FROM '. MARKET_TABLE .' WHERE k_type = ? AND e_id = ?');
	$q->bindIntValue(1, $stores[$type]);
	$q->bindIntValue(2, $emp->e_id);
	$q->execute() or warning('Failed to fetch market items', 0);
	$items = $q->fetchAll();
	$onsale = 0;
	foreach ($items as $item)
	{
		$sales[$item['k_id']] = $item;
		$onsale += $item['k_amt'];
	}
	if ($stores[$type] == MARKET_FOOD)
	{
		$minsell[$type] = max(round(($emp->getData('e_'. $type) + $onsale) * PUBMKT_MINFOOD / 100), 0);
		$maxsell[$type] = max(round(($emp->getData('e_'. $type) + $onsale) * PUBMKT_MAXFOOD / 100) - $onsale, 0);
	}
	else
	{
		$minsell[$type] = max(round(($emp->getData('e_'. $type) + $onsale) * PUBMKT_MINSELL / 100), 0);
		$maxsell[$type] = max(round(($emp->getData('e_'. $type) + $onsale) * PUBMKT_MAXSELL / 100) - $onsale, 0);
	}
}

$sales = array();
$minsell = array();
$maxsell = array();
foreach ($types as $type)
	calcBasket($emp1, $type, $minsell, $maxsell, $sales);

if ($action == 'sell') do
{
	if (!isFormPost())
		break;
	$q = $db->prepare('INSERT INTO '. MARKET_TABLE .' (k_type,e_id,k_amt,k_price,k_time) VALUES (?,?,?,?,?)');
	$q->bindIntValue(2, $emp1->e_id);
	$q->bindIntValue(5, CUR_TIME + 3600 * PUBMKT_START);

	foreach ($types as $type)
	{
		$minprice = $defcost[$type] * 0.2;
		$maxprice = $defcost[$type] * 2.5;
		$amount = fixInputNum(getFormVar('sell_'. $type));
		$price = fixInputNum(getFormVar('price_'. $type));
		if ($amount == 0)
			continue;
		if ($amount > $maxsell[$type])
		{
			notice(lang('PUBMARKETSELL_TOO_MANY_UNITS', number($maxsell[$type]), $emp1->era->getData($type)));
			continue;
		}
		if ($amount < $minsell[$type])
		{
			notice(lang('PUBMARKETSELL_TOO_FEW_UNITS', number($minsell[$type]), $emp1->era->getData($type)));
			continue;
		}
		if ($price < $minprice)
		{
			notice(lang('PUBMARKETSELL_PRICE_TOO_LOW', $emp1->era->getData($type), money($minprice)));
			continue;
		}
		if ($price > $maxprice)
		{
			notice(lang('PUBMARKETSELL_PROCE_TOO_HIGH', $emp1->era->getData($type), money($maxprice)));
			continue;
		}
		$emp1->subData('e_'. $type, $amount);
		$maxsell[$type] -= $amount;

		$q->bindIntValue(1, $stores[$type]);
		$q->bindIntValue(3, $amount);
		$q->bindIntValue(4, $price);
		$q->execute() or warning('Failed to add items to market', 0);
		$id = $db->lastInsertId($db->getSequence(MARKET_TABLE));
		// and add it to $sales so we can display it in the list
		$item = array('k_id' => $id, 'k_type' => $stores[$type], 'e_id' => $emp1->e_id, 'k_amt' => $amount, 'k_price' => $price, 'k_time' => CUR_TIME + 3600 * PUBMKT_START);
		$sales[$id] = $item;
		logevent(varlist(array('type', 'amount', 'price'), get_defined_vars()));
	}
} while (0);
if ($action == 'remove') do
{
	if (!isFormPost())
		break;
	if (PUBMKT_MINTIME < 0)
		break;	// not allowed to manually remove units

	$id = fixInputNum(getFormVar('remove_id'));
	$q = $db->prepare('SELECT * FROM '. MARKET_TABLE .' WHERE k_id = ? AND e_id = ?');
	$q->bindIntValue(1, $id);
	$q->bindIntValue(2, $emp1->e_id);
	$q->execute() or warning('Failed to fetch items to remove from market', 0);
	if ($q->rowCount() == 0)
	{
		notice(lang('PUBMARKETSELL_CANNOT_REMOVE'));
		break;
	}
	$item = $q->fetch();	// from the above check, the row is guaranteed to exist

	if ($item['k_time'] <= CUR_TIME - 3600 * PUBMKT_MINTIME)
	{
		// remove it from the market, so as to not hold up other buyers
		$q = $db->prepare('DELETE FROM '. MARKET_TABLE .' WHERE k_id = ?');
		$q->bindIntValue(1, $id);
		$q->execute() or warning('Failed to delete items from market', 0);

		$type = $types[$item['k_type']];
		$amount = $item['k_amt'];
		$lost = floor($amount * 0.2);
		$returned = $amount - $lost;

		$emp1->addData('e_'. $type, $returned);
		notice(lang('PUBMARKETSELL_REMOVE_COMPLETE', number($item['k_amt']), $emp1->era->getData($type), number($returned)));
		logevent(varlist(array('type', 'amount', 'returned'), get_defined_vars()));
		// lost goods fund the jackpot at 20% of their base value
		$world->adjust('lotto_current_jackpot', round($lost * $defcost[$type] / 5));
	}
	else	notice(lang('PUBMARKETSELL_REMOVE_TOO_SOON'));
} while (0);
notices();

$costs = array();
foreach ($types as $type)
	$costs[$type] = getCost($emp1, $type, $defcost[$type]);
?>
<?php echo lang('PUBMARKETSELL_SALES_HEADER'); ?>
<table class="inputtable">
<tr><th class="al"><?php echo lang('COLUMN_UNIT'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_QUANTITY'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_PRICE'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_STATUS'); ?></th></tr>
<?php
foreach ($sales as $item)
{
	$type = $types[$item['k_type']];
?>
<tr><td><?php echo lang($emp1->era->getData($type)); ?></td>
    <td class="ar"><?php echo number($item['k_amt']); ?></td>
    <td class="ar"><?php echo money($item['k_price']); ?></td>
    <td class="ar"><?php
	$itemtime = CUR_TIME - $item['k_time'];
	if ($itemtime >= 0)
	{
		echo lang('PUBMARKETSELL_SALES_ONSALE', round($itemtime / 3600, 1));
		if ((PUBMKT_MINTIME >= 0) && ($itemtime >= 3600 * PUBMKT_MINTIME))
			echo ' <form method="post" action="?location=pubmarketsell"><div><input type="hidden" name="action" value="remove" /><input type="hidden" name="remove_id" value="'. $item['k_id'] .'" /><input type="submit" value="'. lang('PUBMARKETSELL_SALES_REMOVE') .'" /></div></form>';
	}
	else	echo lang('PUBMARKETSELL_SALES_INTRANSIT', round(-$itemtime / 3600, 1));
?></td></tr>
<?php
}
?>
    <tr><td colspan="4"><hr /></td></tr>
</table>
<?php echo lang('PUBMARKETSELL_SALES_DELAY', PUBMKT_START); ?><br />
<form method="post" action="?location=pubmarketsell">
<table class="inputtable">
<tr><td colspan="2"><a href="?location=pubmarketbuy"><?php echo lang('PUBMARKET_LINK_BUY'); ?></a></td>
    <td>&nbsp;</td>
    <td colspan="2" class="ar"><a href="?location=pubmarketsell"><?php echo lang('PUBMARKET_LINK_SELL'); ?></a></td></tr>
<tr><th class="al"><?php echo lang('COLUMN_UNIT'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_OWNED'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_PRICE'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_CANSELL'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_SELL'); ?></th></tr>
<?php
foreach ($types as $type)
{
?>
<tr><td><?php echo lang($emp1->era->getData($type)); ?></td>
    <td class="ar"><?php echo number($emp1->getData('e_'. $type)); ?></td>
    <td class="ar"><input type="text" name="price_<?php echo $type; ?>" value="<?php echo money($costs[$type]); ?>" size="5" /></td>
    <td class="ar"><?php echo number($maxsell[$type]) .' '. copybutton('sell_'. $type, number($maxsell[$type])); ?></td>
    <td class="ar"><input type="text" name="sell_<?php echo $type; ?>" id="sell_<?php echo $type; ?>" value="0" size="8" /></td></tr>
<?php
}
?>
<tr><td colspan="5" class="ac"><input type="hidden" name="action" value="sell" /><input type="submit" value="<?php echo lang('PUBMARKETSELL_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
page_footer();
?>
