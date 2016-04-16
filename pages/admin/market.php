<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: market.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'ADMIN_MARKET_TITLE';

$needpriv = UFLAG_ADMIN;

if ($action == 'remove')
	$db->lockGroup(ENT_MARKET);

page_header();

$defcost = lookup('pubmkt_id_cost');
$types = lookup('pubmkt_id_name');

$return = getFormVar('return', 1);
if ($action == 'remove') do
{
	if (!isFormPost())
		break;
	$items = getFormArr('remove');
	if (count($items) == 0)
	{
		notice(lang('ADMIN_MARKET_ERROR_NOTHING'));
		break;
	}
	// fetch all checked market items
	$q = $db->prepare('SELECT * FROM '. MARKET_TABLE .' WHERE k_id IN '. sqlArgList($items));
	$q->bindAllValues($items);
	$q->execute();
	$markets = $q->fetchAll();
	// then handle returns for each one, if necessary
	$removed = 0;
	foreach ($markets as $market)
	{
		if ($return > 0)
		{
			$emp_b = prom_empire::cached_load($market['e_id']);
			$basecost = $defcost[$market['k_type']];
			if ($return == 2)
				$lost = 0;
			else	$lost = floor($market['k_amt'] * (min(max($market['k_price'] - $basecost, 0) / $basecost, 0.3) + 0.2));
			addEmpireNews(EMPNEWS_ATTACH_MARKET_RETURN, NULL, $emp_b, $market['k_type'], $market['k_amt'], $market['k_price'], $market['k_amt'] - $lost);
			$emp_b = NULL;
			// lost goods fund the jackpot at 20% of their base value
			if ($lost)
				$world->adjust('lotto_current_jackpot', round($lost * $basecost / 5));
		}
		$removed++;
	}

	// then delete the lot of them
	$q = $db->prepare('DELETE FROM '. MARKET_TABLE .' WHERE k_id IN '. sqlArgList($items));
	$q->bindAllValues($items);
	$q->execute();

	notice(lang('ADMIN_MARKET_COMPLETE', $removed));
	if (count($items) != $removed)
		notice(lang('ADMIN_MARKET_REMAINING', count($items) - $removed));
	logevent();
} while (0);
notices();

prom_session::initvar('admin_market_sortcol', 'eid');
prom_session::initvar('admin_market_sortdir', 'asc');
prom_session::initvar('admin_market_page', 1);

$sortcol = getFormVar('sortcol', $_SESSION['admin_market_sortcol']);
$sortdir = getFormVar('sortdir', $_SESSION['admin_market_sortdir']);
$curpage = fixInputNum(getFormVar('page', $_SESSION['admin_market_page']));
$per = 50;	// 50 rows per page

$total = $db->queryCell('SELECT COUNT(*) FROM '. MARKET_TABLE);
$pages = ceil($total / $per);

$sorttypes = array(
	'_default'	=> 'eid',
	'eid'		=> array('e_id {DIR}', 'e_id'),
	'type'		=> array('k_type {DIR}', 'k_type'),
	'amt'		=> array('k_amt {DIR}', 'k_amt'),
	'price'		=> array('k_price {DIR}', 'k_price'),
	'time'		=> array('k_time {DIR}', 'k_time'),
);
$sortby = parsesort($sortcol, $sortdir, $sorttypes);
$offset = parsepage($curpage, $total, $per);

$_SESSION['admin_market_sortcol'] = $sortcol;
$_SESSION['admin_market_sortdir'] = $sortdir;
$_SESSION['admin_market_page'] = $curpage;

$sortlink = '?location=admin/market&amp;';
?>
<form method="post" action="?location=admin/market">
<table>
<tr><th><?php echo sortlink(lang('COLUMN_ADMIN_EMPIREID'), $sortlink, $sortcol, $sortdir, 'eid', 'asc', $curpage); ?></th>
    <th><?php echo sortlink(lang('ADMIN_MARKET_COLUMN_TYPE'), $sortlink, $sortcol, $sortdir, 'type', 'asc', $curpage); ?></th>
    <th><?php echo sortlink(lang('ADMIN_MARKET_COLUMN_AMT'), $sortlink, $sortcol, $sortdir, 'amt', 'asc', $curpage); ?></th>
    <th><?php echo sortlink(lang('ADMIN_MARKET_COLUMN_PRICE'), $sortlink, $sortcol, $sortdir, 'price', 'asc', $curpage); ?></th>
    <th><?php echo sortlink(lang('ADMIN_MARKET_COLUMN_TIME'), $sortlink, $sortcol, $sortdir, 'time', 'asc', $curpage); ?></th>
    <th><?php echo lang('ADMIN_MARKET_COLUMN_REMOVE'); ?></th></tr>
<?php
$sql = 'SELECT * FROM '. MARKET_TABLE .' ORDER BY '. $sortby;
$sql = $db->setLimit($sql, $per, $offset);
$q = $db->query($sql) or warning('Failed to fetch market item list', 0);
$markets = $q->fetchAll();
foreach ($markets as $market)
{
	$time = CUR_TIME - $market['k_time'];
?>
<tr><td class="ar"><?php echo prenum($market['e_id']); ?></td>
    <td><?php echo lang($emp1->era->getData($types[$market['k_type']])); ?></td>
    <td class="ac"><?php echo number($market['k_amt']); ?></td>
    <td class="ac"><?php echo money($market['k_price']); ?></td>
    <td class="ar"><?php echo colornum($time, gmdate('z:H:i:s', abs($time)), 'cgood', 'cwarn', 'cgood', '+', '-', '+'); ?></td>
    <td class="ar"><?php echo checkbox('remove[]', '', $market['k_id'], FALSE, TRUE, 'market_'. $market['k_id']); ?></td></tr>
<?php
}
if ($pages > 0)
	echo '<tr><td colspan="6" class="ar">'. pagelist($curpage, $pages, $sortlink, $sortcol, $sortdir) .'</td></tr>';
?>
<tr><td colspan="6" class="ar">
        <input type="hidden" name="action" value="remove" />
	<a href="javascript:togglechecks('market')"><?php echo lang('ADMIN_MARKET_COLUMN_TOGGLE'); ?></a><br />
        <?php echo lang('ADMIN_MARKET_RETURN'); ?> <?php echo radiolist('return', array(0 => lang('ADMIN_MARKET_RETURN_NONE'), 1 => lang('ADMIN_MARKET_RETURN_SOME'), 2 => lang('ADMIN_MARKET_RETURN_ALL')), $return); ?><br />
        <input type="submit" value="<?php echo lang('ADMIN_MARKET_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
page_footer();
?>
