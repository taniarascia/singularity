<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: aid.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'AID_TITLE';
if ($action == 'send')
	$lock['emp2'] = fixInputNum(getFormVar('aid_target'));

page_header();

if (ROUND_FINISHED)
	unavailable(lang('AID_UNAVAILABLE_END'));
if (!ROUND_STARTED)
	unavailable(lang('AID_UNAVAILABLE_START'));
if ($emp1->is_protected())
	unavailable(lang('AID_UNAVAILABLE_PROTECT'));
if ($emp1->e_flags & EFLAG_ADMIN)
	unavailable(lang('AID_UNAVAILABLE_ADMIN'));
if (!AID_ENABLE)
	unavailable(lang('AID_UNAVAILABLE_CONFIG'));

$types = lookup('list_aid');

$cansend = array();
foreach ($types as $type)
	$cansend[$type] = round($emp1->getData('e_'. $type) * 0.20);
$convoy = 2 * floor($emp1->e_networth / 10000);

if ($action == 'send') do
{
	if (!isFormPost())
		break;
	if ($lock['emp2'] == 0)
	{
		notice(lang('INPUT_EMPIRE_ID'));
		break;
	}
	if ($emp1->e_id == $emp2->e_id)
	{
		notice(lang('AID_SELF'));
		break;
	}
	if ($emp1->e_turns < 2)
	{
		notice(lang('AID_NEED_TURNS'));
		break;
	}
	if ($emp2->e_land == 0)
	{
		notice(lang('AID_TARGET_DEAD'));
		break;
	}
	if ($emp2->u_id == 0)
	{
		notice(lang('AID_TARGET_DELETED'));
		break;
	}
	if (($emp2->e_era != $emp1->e_era) && (!$emp1->effects->m_gate) && (!$emp2->effects->m_gate))
	{
		notice(lang('AID_TARGET_GATE'));
		break;
	}
	if ($emp2->e_flags & EFLAG_ADMIN)
	{
		notice(lang('AID_TARGET_ADMIN'));
		break;
	}
	if ($emp2->e_flags & EFLAG_DISABLE)
	{
		notice(lang('AID_TARGET_DISABLED'));
		break;
	}
	if ($emp2->is_protected())
	{
		notice(lang('AID_TARGET_PROTECTED'));
		break;
	}
	if ($emp2->is_vacation())
	{
		notice(lang('AID_TARGET_VACATION'));
		break;
	}

	// Threshold beyond which an empire doesn't need your aid
	$netmult_refuse = 10;
	$aid_cost = 1;

	if (CLAN_ENABLE && ($emp1->c_id != 0) && ($emp2->c_id != 0))
	{
		$clan_a = new prom_clan($emp1->c_id);

		$allies = $clan_a->getAllies();
		$wars = $clan_a->getWars();

		if (($emp1->c_id == $emp2->c_id) || in_array($emp2->c_id, $allies))
		{
			$netmult_refuse = 50;
			if (!ROUND_CLOSING)
				$aid_cost = 0;	// aid to allies is free, except during the final week
		}
		if (in_array($emp2->c_id, $wars))
		{
			notice(lang('AID_TARGET_WAR'));
			break;
		}
		$clan_a = NULL;
	}
	if ($aid_cost && $emp1->effects->m_sendaid >= (AID_MAXCREDITS - 1) * AID_DELAY)
	{
		notice(lang('AID_NEED_CREDITS'));
		break;
	}
	if ($emp2->e_networth > $emp1->e_networth * $netmult_refuse)
	{
		notice(lang('AID_TARGET_TOO_BIG'));
		break;
	}
	if ($cansend['trpsea'] < $convoy)
	{
		notice(lang('AID_NEED_CONVOY', $emp1->era->trpsea));
		break;
	}

	$send = array();
	foreach ($types as $type)
		$send[$type] = fixInputNum(getFormVar('send_'. $type));
	// must send at least the convoy size
	if ($send['trpsea'] < $convoy)
		$send['trpsea'] = $convoy;

	// preliminary checks - make sure values are valid, and make sure there's actually something to send
	foreach ($send as $type => $amount)
	{
		if ($amount == 0)
		{
			// delete empty slots to simplify check below
			unset($send[$type]);
			continue;
		}
		if ($amount > $cansend[$type])
		{
			if ($type == 'cash')
				notice(lang('AID_TOO_MUCH_CASH', money($cansend[$type])));
			else	notice(lang('AID_TOO_MUCH_UNIT', $emp1->era->getData($type), number($cansend[$type])));
			$send[$type] = $amount = $cansend[$type];
		}
	}
	// if the only thing being sent is ships with the quantity being the minimum convoy size, abort
	if ((count($send) == 1) && (isset($send['trpsea'])) && ($send['trpsea'] == $convoy))
	{
		notice(lang('AID_NO_CARGO'));
		break;
	}

	foreach ($send as $type => $amount)
	{
		$emp1->subData('e_'. $type, $amount);
		$emp2->addData('e_'. $type, $amount);
		// and update how much can be sent in the next shipment
		$cansend[$type] = round($emp1->getData('e_'. $type) * 0.20);
	}
	// refill empty slots for news report
	foreach ($types as $type)
		if (!isset($send[$type]))
			$send[$type] = 0;

	// flush notices so the above messages come through before the turn report
	notices();
	logevent(varlist(array('convoy', 'send'), get_defined_vars()));

	if (($emp1->c_id != 0) && ($emp1->c_id == $emp2->c_id))
		$eventcode = EMPNEWS_ATTACH_AID_SENDCLAN;
	else	$eventcode = EMPNEWS_ATTACH_AID_SEND;

	addEmpireNews($eventcode, $emp1, $emp2, $convoy, $send['trparm'], $send['trplnd'], $send['trpfly'], $send['trpsea'], $send['cash'], $send['runes'], $send['food']);
	$emp1->effects->m_sendaid += $aid_cost * AID_DELAY;
	$emp1->takeTurns(2, 'aid');
	notice(lang('AID_COMPLETE', number(max($convoy, $send['trpsea'])), $emp1->era->trpsea, $emp2));
	if ($send['trpsea'] < $convoy)
		notice(lang('AID_WILL_RETURN', number($convoy - $send['trpsea']), $emp1->era->trpsea));

	$emp1->updateNet();	// recalculate networth now
	$convoy = 2 * floor($emp1->e_networth / 10000);
} while (0);
notices();
?>
<?php echo lang('AID_HEADER', number($convoy), $emp1->era->trpsea, plural(max(0, AID_MAXCREDITS - ceil($emp1->effects->m_sendaid / AID_DELAY)), 'SHIPMENTS_SINGLE', 'SHIPMENTS_PLURAL')); ?><br />
<form method="post" action="?location=aid">
<table class="inputtable">
<tr><td colspan="3" class="ar"><?php echo lang('LABEL_EMPIRE_RECIPIENT'); ?></td>
    <td><input type="text" name="aid_target" size="6" value="<?php echo prenum($lock['emp2']); ?>" /></td></tr>
<tr><th class="al"><?php echo lang('COLUMN_UNIT'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_OWNED'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_CANSEND'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_SEND'); ?></th></tr>
<?php
foreach ($types as $type)
{
?>
<tr><td><?php if ($type == 'cash') echo lang('ROW_CASH'); else echo lang($emp1->era->getData($type)); ?></td>
    <td class="ar"><?php echo number($emp1->getData('e_'. $type)); ?></td>
    <td class="ar"><?php echo number($cansend[$type]) .' '. copybutton('send_'. $type, number($cansend[$type])); ?></td>
    <td class="ar"><input type="text" name="send_<?php echo $type; ?>" id="send_<?php echo $type; ?>" size="8" value="0" /></td></tr>
<?php
}
?>
<tr><td colspan="4" class="ac"><input type="hidden" name="action" value="send" /><input type="submit" value="<?php echo lang('AID_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
page_footer();
?>
