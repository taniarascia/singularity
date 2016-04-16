<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: pubmarketbuy.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'PUBMARKETBUY_TITLE';

if ($action == 'buy')
	$db->lockGroup(ENT_MARKET);

page_header();

if (ROUND_FINISHED)
	unavailable(lang('PUBMARKETBUY_UNAVAILABLE_END'));
if (!ROUND_STARTED)
	unavailable(lang('PUBMARKETBUY_UNAVAILABLE_START'));
if ($emp1->is_protected())
	unavailable(lang('PUBMARKETBUY_UNAVAILABLE_PROTECT'));
if ($emp1->e_flags & EFLAG_ADMIN)
	unavailable(lang('PUBMARKETBUY_UNAVAILABLE_ADMIN'));

$types = lookup('list_mkt');
$stores = lookup('pubmkt_name_id');

// Determines the current buying price of a unit
function getCost ($emp, $type, &$amount, &$price)
{
	global $db, $stores;
	$q = $db->prepare('SELECT SUM(k_amt) AS k_amt,k_price FROM '. MARKET_TABLE .' WHERE k_type = ? AND e_id != ? AND k_time <= ? GROUP BY k_price HAVING k_price = MIN(k_price)');
	$q->bindIntValue(1, $stores[$type]);
	$q->bindIntValue(2, $emp->e_id);
	$q->bindIntValue(3, CUR_TIME);
	$q->execute() or warning('Failed to get market amounts and prices', 0);
	if ($row = $q->fetch())
	{
		$amount = $row['k_amt'];
		$price = $row['k_price'];
	}
	else	$amount = $price = 0;
}

if ($action == 'buy') do
{
	if (!isFormPost())
		break;
	foreach ($types as $type)
	{
		$amount = fixInputNum(getFormVar('buy_'. $type));
		if ($amount == 0)
			continue;
		$price = fixInputNum(getFormVar('price_'. $type));
		// require that they have enough money to buy what they wanted at the price they saw
		// if prices go down, well, lucky them - they won't have to pay as much
		if ($amount * $price > $emp1->e_cash)
		{
			notice(lang('PUBMARKETBUY_NOT_ENOUGH_MONEY', $emp1->era->getData($type)));
			continue;
		}

		$bought = array();	// how many units were bought at each price
		$totalspent = 0;

		// Fetch all market entries which satisfy the criteria
		$q = $db->prepare('SELECT k_id,e_id,k_amt,k_price FROM '. MARKET_TABLE .' WHERE k_type = ? AND k_price <= ? AND e_id != ? AND k_time <= ? AND k_amt > 0 ORDER BY k_price ASC, k_time ASC');
		$q->bindIntValue(1, $stores[$type]);
		$q->bindIntValue(2, $price);
		$q->bindIntValue(3, $emp1->e_id);
		$q->bindIntValue(4, CUR_TIME);
		$q->execute() or warning('Failed to fetch market items', 0);
		$items = $q->fetchAll();

		$upd = $db->prepare('UPDATE '. MARKET_TABLE .' SET k_amt = ? WHERE k_id = ?');
		$del = $db->prepare('DELETE FROM '. MARKET_TABLE .' WHERE k_id = ?');
		foreach ($items as $item)
		{
			$buyamt = min($item['k_amt'], $amount);

			if (!isset($bought[$item['k_price']]))
				$bought[$item['k_price']] = 0;
			$bought[$item['k_price']] += $buyamt;

			$spent = $buyamt * $item['k_price'];
			$taxed = round($spent * 0.05);
			$totalspent += $spent;

			$emp1->addData('e_'. $type, $buyamt);
			$emp1->e_cash -= $spent;
			if ($item['k_amt'] == $buyamt)
			{
				$del->bindIntValue(1, $item['k_id']);
				$del->execute() or warning('Failed to remove bought items from market', 0);
			}
			else
			{
				$upd->bindIntValue(1, $item['k_amt'] - $buyamt);
				$upd->bindIntValue(2, $item['k_id']);
				$upd->execute() or warning('Failed to update bought items on market', 0);
			}

			$emp_a = prom_empire::cached_load($item['e_id']);
			addEmpireNews(EMPNEWS_ATTACH_MARKET_SELL, $emp1, $emp_a, $stores[$type], $buyamt, $spent, $spent - $taxed);
			$emp_a = NULL;

			// Public Market taxes go into the lottery
			$world->adjust('lotto_current_jackpot', $taxed);

			$amount -= $buyamt;
			if ($amount == 0)
				break;
		}
		if (array_sum($bought) > 0)
		{
			if (min(array_keys($bought)) < $price)
				notice(lang('PUBMARKETBUY_CHEAPER_ARRIVED', $emp1->era->getData($type)));
			$buys = array();
			foreach ($bought as $cost => $amt)
				$buys[] = lang('PUBMARKETBUY_BOUGHT_LINE', number($amt), $emp1->era->getData($type), money($cost));
			if (count($bought) == 1)
				notice(lang('PUBMARKETBUY_BOUGHT_SINGLE', commalist($buys), money($totalspent)));
			else	notice(lang('PUBMARKETBUY_BOUGHT_MULTIPLE', commalist($buys), number(array_sum($bought)), $emp1->era->getData($type), money($totalspent)));
			if ($amount > 0)
				notice(lang('PUBMARKETBUY_BOUGHT_NOT_ALL', number($amount), $emp1->era->getData($type)));
		}
		else	notice(lang('PUBMARKETBUY_BOUGHT_NONE', number($amount), $emp1->era->getData($type), money($price)));
		logevent(varlist(array('type', 'price', 'bought', 'totalspent', 'amount'), get_defined_vars()));
	}
} while (0);
notices();

$costs = array();
$amounts = array();
foreach ($types as $type)
	getCost($emp1, $type, $amounts[$type], $costs[$type]);
?>
<form method="post" action="?location=pubmarketbuy">
<table class="inputtable">
<tr><td colspan="3"><a href="?location=pubmarketbuy"><?php echo lang('PUBMARKET_LINK_BUY'); ?></a></td>
    <td colspan="3" class="ar"><a href="?location=pubmarketsell"><?php echo lang('PUBMARKET_LINK_SELL'); ?></a></td></tr>
<tr><th class="al"><?php echo lang('COLUMN_UNIT'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_OWNED'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_AVAIL'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_PRICE'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_CANBUY'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_BUY'); ?></th></tr>
<?php
foreach ($types as $type)
{
	$canbuy = min(floor($emp1->e_cash / max($costs[$type], 1)), $amounts[$type]);
?>
<tr><td><?php echo lang($emp1->era->getData($type)); ?></td>
    <td class="ar"><?php echo number($emp1->getData('e_'. $type)); ?></td>
    <td class="ar"><?php echo number($amounts[$type]); ?></td>
    <td class="ar"><input type="hidden" name="price_<?php echo $type; ?>" value="<?php echo $costs[$type]; ?>" /><?php echo money($costs[$type]); ?></td>
    <td class="ar"><?php echo number($canbuy) .' '. copybutton('buy_'. $type, number($canbuy)); ?></td>
    <td class="ar"><input type="text" name="buy_<?php echo $type; ?>" id="buy_<?php echo $type; ?>" size="6" value="0" /></td></tr>
<?php
}
?>
<tr><td colspan="6" class="ac"><input type="hidden" name="action" value="buy" /><input type="submit" value="<?php echo lang('PUBMARKETBUY_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
page_footer();
?>
