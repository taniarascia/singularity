<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: lottery.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'LOTTERY_TITLE';

page_header();

if (ROUND_FINISHED)
	unavailable(lang('LOTTERY_UNAVAILABLE_END'));
if (!ROUND_STARTED)
	unavailable(lang('LOTTERY_UNAVAILABLE_START'));

$tickcost = round($emp1->e_networth / log($emp1->e_networth, 25));

if ($action == 'ticket') do
{
	if (!isFormPost())
		break;
	if ($world->turns_next_daily > $world->round_time_end)
	{
		notice(lang('LOTTERY_END_ROUND'));
		break;
	}
	if ($emp1->e_cash < $tickcost)
	{
		notice(lang('LOTTERY_NOT_ENOUGH_MONEY'));
		break;
	}
	$tickcount = $db->queryCell('SELECT COUNT(*) FROM '. LOTTERY_TABLE .' WHERE e_id = ?', array($emp1->e_id));
	if ($tickcount >= LOTTERY_MAXTICKETS)
	{
		notice(lang('LOTTERY_TOO_MANY_TICKETS'));
		break;
	}

	$maxnum = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE);
	// really should improve this - ideally, pre-insert all valid tickets into the database
	// and then randomly select one that hasn't yet been purchased
	do
	{
		$ticknum = mt_rand(1, LOTTERY_MAXTICKETS * $maxnum);
	} while ($db->queryCell('SELECT COUNT(*) FROM '. LOTTERY_TABLE .' WHERE l_ticket = ? AND e_id > 0', array($ticknum)) > 0);

	$q = $db->prepare('INSERT INTO '. LOTTERY_TABLE .' (e_id,l_ticket,l_cash) VALUES (?,?,?)');
	$q->bindIntValue(1, $emp1->e_id);
	$q->bindIntValue(2, $ticknum);
	$q->bindIntValue(3, $tickcost);
	$q->execute() or warning('Failed to add lottery ticket', 0);

	$emp1->e_cash -= $tickcost;
	$world->adjust('lotto_current_jackpot', $tickcost);

	notice(lang('LOTTERY_COMPLETE', prenum($ticknum), money($tickcost)));
	logevent(varlist(array('ticknum', 'tickcost'), get_defined_vars()));

	// recalculate networth now, and display next ticket price
	$emp1->updateNet();
	$tickcost = round($emp1->e_networth / log($emp1->e_networth, 25));
} while (0);
notices();
$q = $db->prepare('SELECT * FROM '. LOTTERY_TABLE .' WHERE e_id = ?');
$q->bindIntValue(1, $emp1->e_id);
$q->execute() or warning('Failed to fetch lottery tickets', 0);
$tickets = $q->fetchAll();
$totaltickets = $db->queryCell('SELECT COUNT(*) FROM '. LOTTERY_TABLE .' WHERE e_id != 0');
$lottotime = $user1->customdate($world->turns_next_daily, 'g:ia');

echo lang('LOTTERY_DESC', $lottotime) ."<br />\n";
if ($world->turns_next_daily < $world->round_time_end)
{
	echo lang('LOTTERY_NEXTTICKET', money($tickcost)) ."<br />\n";
	echo lang('LOTTERY_MAXALLOWED', plural(LOTTERY_MAXTICKETS, 'TICKETS_SINGLE', 'TICKETS_PLURAL')) ."<br /><br />\n";
	echo '<b>'. lang('LOTTERY_CURJACKPOT') .'</b> '. money($world->lotto_current_jackpot) ."<br /><br />\n";
	echo lang('LOTTERY_TOTALBOUGHT') .' <span class="cneutral">'. $totaltickets .'</span><br />'."\n";
}

if ($world->lotto_last_winner)
{
	$emp_a = new prom_empire($world->lotto_last_winner);
	$emp_a->loadPartial();
	echo lang('LOTTERY_LAST_WINNER', prenum($world->lotto_last_picked), $emp_a, money($world->lotto_yesterday_jackpot));
}
elseif ($world->turns_next_daily > $world->round_time_end)
	echo lang('LOTTERY_LAST_NOWINNER_END', prenum($world->lotto_last_picked), money($world->lotto_current_jackpot));
else	echo lang('LOTTERY_LAST_NOWINNER', prenum($world->lotto_last_picked), money($world->lotto_jackpot_increase));
echo "<br />\n";
if ($world->turns_next_daily > $world->round_time_end)
	/* skip entirely */
	;
elseif (count($tickets))
{
	$ticklist = '';
	foreach ($tickets as $ticket)
	{
		if (strlen($ticklist))
			$ticklist .= lang('LOTTERY_TICKET_LISTSEP');
		$ticklist .= prenum($ticket['l_ticket']);
	}
	echo lang('LOTTERY_HAVE_TICKETS', $ticklist);
}
else	echo lang('LOTTERY_HAVE_NO_TICKETS');
echo "<br />\n";

if ($world->turns_next_daily > $world->round_time_end)
	echo lang('LOTTERY_END_ROUND') ."<br />\n";
elseif (count($tickets) < LOTTERY_MAXTICKETS)
{
	if ($emp1->e_cash < $tickcost)
		echo lang('LOTTERY_CANT_AFFORD') ."<br />\n";
	else
	{
?>
<form method="post" action="?location=lottery">
<div>
<input type="hidden" name="action" value="ticket" />
<input type="submit" value="<?php echo lang('LOTTERY_SUBMIT'); ?>" />
</div>
</form>
<?php
	}
}
page_footer();
?>
