<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: magic.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'MAGIC_TITLE';

if (($action == 'cast_friend') || ($action == 'cast_enemy'))
	$lock['emp2'] = fixInputNum(getFormVar('magic_target'));

page_header(); ?>

<br/><img src="/images/magic.jpg" style="max-width: 550px;"/>
<br/>

<?php

if (ROUND_FINISHED)
	unavailable(lang('MAGIC_UNAVAILABLE_END'));
if (!ROUND_STARTED)
	unavailable(lang('MAGIC_UNAVAILABLE_START'));

require_once(PROM_BASEDIR .'includes/magic.php');
loadSpells();

$spname_self = getFormVar('magic_spell_self');
$spname_friend = getFormVar('magic_spell_friend');
$spname_enemy = getFormVar('magic_spell_enemy');
$vary_friend = FALSE;
$vary_enemy = FALSE;

if ($action == 'cast_self') do
{
	if (!isFormPost())
		break;
	if (empty($spname_self))
	{
		notice(lang('INPUT_SPELL_NAME'));
		break;
	}

	$spell = getSpell($spname_self, $emp1);
	if (!($spell instanceof prom_spelltype_self))
	{
		notice(lang('MAGIC_SELF_ILLEGAL'));
		break;
	}
	if (!$spell->allow_self())
	{
		notice(lang('MAGIC_SELF_INVALID'));
		break;
	}
	if ($emp1->e_trpwiz == 0)
	{
		notice(lang('MAGIC_NEED_WIZARDS', $emp1->era->trpwiz));
		break;
	}
	$cost = $spell->cost_self();
	$turns = $spell->turns_self();
	if ($emp1->e_runes < $cost)
	{
		notice(lang('MAGIC_NEED_RUNES', $emp1->era->runes));
		break;
	}
	if ($emp1->e_turns < $turns)
	{
		notice(lang('MAGIC_NEED_TURNS'));
		break;
	}
	if ($emp1->e_health < 20)
	{
		notice(lang('MAGIC_NEED_HEALTH', $emp1->era->trpwiz));
		break;
	}
	$count = fixInputNum(getFormVar('magic_count'));
	if ($count == 0)
		$count = 1;
	echo lang('MAGIC_BEGIN', $emp1->era->trpwiz) .'<br />';
	for ($i = 0; $i < $count; $i++)
	{
		// Advance and Regress have complex conditions for allowing to cast, so check every time
		if (!$spell->allow_self())
		{
			notice(lang('MAGIC_SELF_INVALID_AGAIN'));
			break;
		}
		if ($emp1->e_runes < $cost)
		{
			notice(lang('MAGIC_NEED_RUNES_AGAIN', $emp1->era->runes));
			break;
		}
		if ($emp1->e_turns < $turns)
		{
			notice(lang('MAGIC_NEED_TURNS_AGAIN'));
			break;
		}
		if ($emp1->e_health < 20)
		{
			notice(lang('MAGIC_NEED_HEALTH_AGAIN', $emp1->era->trpwiz));
			break;
		}
		$emp1->e_runes -= $cost;
		$taken = $emp1->takeTurns($turns, 'magic', TRUE);
		// if we ran into trouble, abort with a special message
		if ($taken != $turns)
		{
			notice(lang('MAGIC_TROUBLE_ABORT', $emp1->era->trpwiz));
			logevent(varlist(array('spname_self', 'i', 'count', 'turns', 'cost', 'taken'), get_defined_vars()));
			break;
		}
		$result = $spell->cast_self();
		logevent(varlist(array('spname_self', 'i', 'count', 'turns', 'cost', 'taken', 'result'), get_defined_vars()));
		// if the spell blew up in our face, then stop
		if (!$result)
			break;
		// recalculate for next loop
		$cost = $spell->cost_self();
		$turns = $spell->turns_self();
	}
	if ($count > 1)
		notice(lang('MAGIC_SELF_COMPLETE_AGAIN'));
	else	notice(lang('MAGIC_SELF_COMPLETE'));
} while (0);

if ($action == 'cast_friend') do
{
	if (!isFormPost())
		break;
	if (!FRIEND_MAGIC_ENABLE)
	{
		notice(lang('MAGIC_FRIEND_DISABLED'));
		break;
	}
	// These checks are done here (rather than at the top of the page) so self-cast spells can still be used
	if ($emp1->is_protected())
	{
		notice(lang('MAGIC_FRIEND_UNAVAILABLE_PROTECT'));
		break;
	}
	if ($emp1->e_flags & EFLAG_ADMIN)
	{
		notice(lang('MAGIC_FRIEND_UNAVAILABLE_ADMIN'));
		break;
	}
	if ($lock['emp2'] == 0)
	{
		notice(lang('INPUT_EMPIRE_ID'));
		break;
	}
	if (empty($spname_friend))
	{
		notice(lang('INPUT_SPELL_NAME'));
		break;
	}
	if ($emp1->e_id == $emp2->e_id)
	{
		notice(lang('MAGIC_FRIEND_SELF'));
		break;
	}

	$spell = getSpell($spname_friend, $emp1, $emp2);
	if (!($spell instanceof prom_spelltype_friend))
	{
		notice(lang('MAGIC_FRIEND_ILLEGAL'));
		break;
	}
	if (!$spell->allow_friend())
	{
		notice(lang('MAGIC_FRIEND_INVALID'));
		break;
	}
	if ($emp1->e_trpwiz == 0)
	{
		notice(lang('MAGIC_NEED_WIZARDS', $emp1->era->trpwiz));
		break;
	}
	$confirm = fixInputBool(getFormVar('confirm'));
	if (($spell instanceof prom_spelltype_varycost) && !$confirm)
	{
		$vary_friend = TRUE;
		notice(lang('MAGIC_FRIEND_CONFIRM'));
		break;
	}
	$cost = $spell->cost_friend();
	$turns = $spell->turns_friend();
	if ($emp1->e_runes < $cost)
	{
		notice(lang('MAGIC_NEED_RUNES', $emp1->era->runes));
		break;
	}
	if ($emp1->e_turns < $turns)
	{
		notice(lang('MAGIC_NEED_TURNS'));
		break;
	}
	if ($emp2->e_land == 0)
	{
		notice(lang('MAGIC_FRIEND_DEAD'));
		break;
	}
	if ($emp2->u_id == 0)
	{
		notice(lang('MAGIC_FRIEND_DELETED'));
		break;
	}
	if (($emp2->e_era != $emp1->e_era) && (!$emp1->effects->m_gate) && (!$emp2->effects->m_gate))
	{
		notice(lang('MAGIC_FRIEND_NEED_GATE'));
		break;
	}
	if ($emp2->e_flags & EFLAG_ADMIN)
	{
		notice(lang('MAGIC_FRIEND_ADMIN'));
		break;
	}
	if ($emp2->e_flags & EFLAG_DISABLE)
	{
		notice(lang('MAGIC_FRIEND_DISABLED'));
		break;
	}
	if ($emp2->is_protected())
	{
		notice(lang('MAGIC_FRIEND_PROTECTED'));
		break;
	}
	if ($emp2->is_vacation())
	{
		notice(lang('MAGIC_FRIEND_VACATION'));
		break;
	}

	// Threshold beyond which an empire doesn't need your help
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
			notice(lang('MAGIC_FRIEND_REFUSE_WAR'));
			break;
		}
		$clan_a = NULL;
	}
	// Friendly spells should have some sort of limit - the aid limit should work nicely
	if ($aid_cost && $emp1->effects->m_sendaid >= AID_MAXCREDITS * AID_DELAY)
	{
		notice(lang('MAGIC_FRIEND_NEED_CREDITS'));
		break;
	}
	if ($emp2->e_networth > $emp1->e_networth * $netmult_refuse)
	{
		notice(lang('MAGIC_FRIEND_REFUSE_SIZE'));
		break;
	}

	echo lang('MAGIC_BEGIN', $emp1->era->trpwiz) .'<br />';

	$emp1->e_runes -= $cost;
	$taken = $emp1->taketurns($turns, 'magic', TRUE);
	// if we ran into trouble, abort with a special message
	if ($taken != $turns)
	{
		notice(lang('MAGIC_TROUBLE_ABORT', $emp1->era->trpwiz));
		logevent(varlist(array('spname_friend', 'turns', 'cost', 'taken'), get_defined_vars()));
		break;
	}
	$result = $spell->cast_friend();

	logevent(varlist(array('spname_friend', 'turns', 'cost', 'taken', 'result'), get_defined_vars()));

	notice(lang('MAGIC_FRIEND_COMPLETE'));
} while (0);

if ($action == 'cast_enemy') do
{
	if (!isFormPost())
		break;
	// These checks are done here (rather than at the top of the page) so self-cast spells can still be used
	if ($emp1->is_protected())
	{
		notice(lang('MAGIC_ENEMY_UNAVAILABLE_PROTECT'));
		break;
	}
	if ($emp1->e_flags & EFLAG_ADMIN)
	{
		notice(lang('MAGIC_ENEMY_UNAVAILABLE_ADMIN'));
		break;
	}
	if ($lock['emp2'] == 0)
	{
		notice(lang('INPUT_EMPIRE_ID'));
		break;
	}
	if (empty($spname_enemy))
	{
		notice(lang('INPUT_SPELL_NAME'));
		break;
	}
	if ($emp1->e_id == $emp2->e_id)
	{
		notice(lang('MAGIC_ENEMY_SELF'));
		break;
	}

	$spell = getSpell($spname_enemy, $emp1, $emp2);
	if (!($spell instanceof prom_spelltype_enemy))
	{
		notice(lang('MAGIC_ENEMY_ILLEGAL'));
		break;
	}
	if (!$spell->allow_enemy())
	{
		notice(lang('MAGIC_ENEMY_INVALID'));
		break;
	}
	if ($emp1->e_trpwiz == 0)
	{
		notice(lang('MAGIC_NEED_WIZARDS', $emp1->era->trpwiz));
		break;
	}
	$confirm = fixInputBool(getFormVar('confirm'));
	if (($spell instanceof prom_spelltype_varycost) && !$confirm)
	{
		$vary_enemy = TRUE;
		notice(lang('MAGIC_ENEMY_CONFIRM'));
		break;
	}
	$cost = $spell->cost_enemy();
	$turns = $spell->turns_enemy();
	if ($emp1->e_runes < $cost)
	{
		notice(lang('MAGIC_NEED_RUNES', $emp1->era->runes));
		break;
	}
	if ($emp1->e_turns < $turns)
	{
		notice(lang('MAGIC_NEED_TURNS'));
		break;
	}
	if ($emp1->e_health < 20)
	{
		notice(lang('MAGIC_NEED_HEALTH', $emp1->era->trpwiz));
		break;
	}
	if ($emp2->e_land == 0)
	{
		notice(lang('MAGIC_ENEMY_DEAD'));
		break;
	}
	if ($emp2->u_id == 0)
	{
		notice(lang('MAGIC_ENEMY_DELETED'));
		break;
	}
	if (($emp2->e_era != $emp1->e_era) && (!$emp1->effects->m_gate) && (!$emp2->effects->m_gate))
	{
		notice(lang('MAGIC_ENEMY_NEED_GATE'));
		break;
	}
	if ($emp2->e_flags & EFLAG_ADMIN)
	{
		notice(lang('MAGIC_ENEMY_ADMIN'));
		break;
	}
	if ($emp2->e_flags & EFLAG_DISABLE)
	{
		notice(lang('MAGIC_ENEMY_DISABLED'));
		break;
	}
	if ($emp2->is_protected())
	{
		notice(lang('MAGIC_ENEMY_PROTECTED'));
		break;
	}
	if ($emp2->is_vacation())
	{
		notice(lang('MAGIC_ENEMY_VACATION'));
		break;
	}

	// Is this spell an act of war?
	$warflag = 0;

	if (CLAN_ENABLE)
	{
		// Threshold for outright refusal to attack
		$netmult_refuse = 10;
		// Threshold for desertion
		$netmult_desert = 2.5;
	}
	else
	{
		// if no clans, can never refuse to attack
		$netmult_refuse = 1000000;
		$netmult_desert = 5;
	}

	if (CLAN_ENABLE && ($emp1->c_id != 0) && ($emp2->c_id != 0))
	{
		if ($emp1->c_id == $emp2->c_id)
		{
			notice(lang('MAGIC_ENEMY_REFUSE_CLAN'));
			break;
		}

		$clan_a = new prom_clan($emp1->c_id);
		$clan_a->load();

		$allies = $clan_a->getAllies();
		$wars = $clan_a->getWars();

		if (in_array($emp2->c_id, $allies))
		{
			notice(lang('MAGIC_ENEMY_REFUSE_ALLY'));
			break;
		}
		if (in_array($emp2->c_id, $wars))
		{
			$warflag = 1;
			$netmult_refuse = 30;
		}
		$clan_a = NULL;
	}

	if (!($spell instanceof prom_spelltype_spy))
	{
		$alive = TRUE;
		if (($emp2->e_flags & EFLAG_DELETE) || ($emp2->e_networth == 0))
			$alive = FALSE;		// deleted empires cannot defend themselves

		if (($alive) && ($emp2->e_networth < $emp1->e_networth / $netmult_refuse))
		{
			notice(lang('MAGIC_ENEMY_REFUSE_SMALL', $emp1->era->trpwiz));
			break;
		}
		if (($alive) && ($emp2->e_networth > $emp1->e_networth * $netmult_refuse))
		{
			notice(lang('MAGIC_ENEMY_REFUSE_LARGE', $emp1->era->trpwiz));
			break;
		}
		if (($warflag == 0) && ($alive))
		{
			if ((MAX_ATTACKS > 0) && ($emp1->e_attacks >= 2 * MAX_ATTACKS))
			{
				notice(lang('MAGIC_ENEMY_ATTACK_LIMIT', $emp1->era->trpwiz, duration(2 * TURNS_FREQ * 60)));
				break;
			}
			$revolt = 0;
			if ($emp2->e_networth < $emp1->e_networth / $netmult_desert)
			{	// Shame is less powerful than fear
				echo '<div class="cwarn">'. lang('MAGIC_ENEMY_DESERT_SMALL', $emp1->era->trpwiz) .'</div>';
				$revolt = ($emp1->e_networth / $emp2->e_networth) / 125;
			}
			if ($emp2->e_networth > $emp1->e_networth * $netmult_desert)
			{
				echo '<div class="cwarn">'. lang('MAGIC_ENEMY_DESERT_LARGE', $emp1->era->trpwiz) .'</div>';
				$revolt = ($emp2->e_networth / $emp1->e_networth) / 100;
			}
			// without clans, half losses from revolting
			if (!CLAN_ENABLE)
				$revolt *= 0.5;
			// limit to 10% loss
			if ($revolt > 0.1)
				$revolt = 0.1;
			$wizloss = ceil($emp1->e_trpwiz * $revolt);
			$emp1->e_trpwiz -= $wizloss;
		}
		if ((MAX_ATTACKS > 0) && ($warflag == 0) && ($alive))
		{
			$emp1->e_attacks += 2;
			$emp2->e_attacks--;
		}
		$emp1->e_health -= 6;
	}

	echo lang('MAGIC_BEGIN', $emp1->era->trpwiz) .'<br />';

	$emp1->e_runes -= $cost;
	if ($warflag)
		$taken = $emp1->taketurns($turns, 'war', TRUE);
	else	$taken = $emp1->taketurns($turns, 'magic', TRUE);
	// if we ran into trouble, abort with a special message
	if ($taken != $turns)
	{
		notice(lang('MAGIC_TROUBLE_ABORT', $emp1->era->trpwiz));
		logevent(varlist(array('spname_enemy', 'turns', 'cost', 'taken'), get_defined_vars()));
		break;
	}
	$result = $spell->cast_enemy();

	logevent(varlist(array('spname_enemy', 'turns', 'cost', 'taken', 'result'), get_defined_vars()));

	notice(lang('MAGIC_ENEMY_COMPLETE'));
} while (0);

notices();
?>
<form method="post" action="?location=magic">
<table class="inputtable">
<tr><th><?php echo lang('MAGIC_SELF_LABEL', '<input type="text" name="magic_count" value="1" size="3" />'); ?></th></tr>
<tr><td><?php
$spelllist = array();
$spelllenable = array();
$spelllist[''] = lang('MAGIC_SPELL_SELECT');
$spelllenable[''] = TRUE;
foreach ($spells as $i)
{
	$spell = getSpell($i, $emp1);
	if ($spell instanceof prom_spelltype_self)
	{
		$spelllist[$i] = lang('MAGIC_SPELL_LABEL', $emp1->era->getData('spell_'. $i), number($spell->cost_self()), $emp1->era->runes, plural($spell->turns_self(), 'TURNS_SINGLE', 'TURNS_PLURAL'));
		$spellenable[$i] = $spell->allow_self();
	}
	$spell = NULL;
}
echo optionlist('magic_spell_self', $spelllist, $spname_self, $spellenable);
?></td></tr>
<tr><td class="ac"><input type="hidden" name="action" value="cast_self" /><input type="submit" value="<?php echo lang('MAGIC_SELF_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
if (FRIEND_MAGIC_ENABLE && (!$emp1->is_protected()) && !($emp1->e_flags & EFLAG_ADMIN))
{
?>
<hr />
<form method="post" action="?location=magic">
<table class="inputtable">
<tr><th colspan="2"><?php echo lang('MAGIC_FRIEND_LABEL', '<input type="text" name="magic_target" value="'. prenum($lock['emp2']) .'" size="5" />'); ?></th></tr>
<tr><td><?php
	$spelllist = array();
	$spelllist[''] = lang('MAGIC_SPELL_SELECT');
	foreach ($spells as $i)
	{
		$spell = getSpell($i, $emp1);
		if ($spell instanceof prom_spelltype_friend)
		{
			if ($spell instanceof prom_spelltype_varycost)
				$spelllist[$i] = lang('MAGIC_SPELL_LABEL_VARY', $emp1->era->getData('spell_'. $i));
			else	$spelllist[$i] = lang('MAGIC_SPELL_LABEL', $emp1->era->getData('spell_'. $i), number($spell->cost_friend()), $emp1->era->runes, plural($spell->turns_friend(), 'TURNS_SINGLE', 'TURNS_PLURAL'));
		}
		$spell = NULL;
	}
	echo optionlist('magic_spell_friend', $spelllist, $spname_friend);
?>
</td></tr>
<tr><td class="ac"><input type="hidden" name="action" value="cast_friend" /><input type="submit" value="<?php echo lang('MAGIC_FRIEND_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
	if ($vary_friend)
	{
?>
<form method="post" action="?location=magic">
<table class="inputtable">
<tr><th colspan="2"><?php echo lang('MAGIC_FRIEND_VERIFY_LABEL', prenum($lock['emp2'])); ?></th></tr>
<tr><td><?php
		$spell = getSpell($spname_friend, $emp1, $emp2);
		echo lang('MAGIC_SPELL_LABEL', $emp1->era->getData('spell_'. $i), number($spell->cost_friend()), $emp1->era->runes, plural($spell->turns_friend(), 'TURNS_SINGLE', 'TURNS_PLURAL'));
?>
</td></tr>
<tr><td class="ac"><input type="hidden" name="action" value="cast_friend" /><input type="hidden" name="magic_target" value="<?php echo $lock['emp2']; ?>" /><input type="hidden" name="magic_spell_friend" value="<?php echo $spname_friend; ?>" /><input type="hidden" name="confirm" value="1" /><input type="submit" value="<?php echo lang('MAGIC_FRIEND_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
	}
}
if ((!$emp1->is_protected()) && !($emp1->e_flags & EFLAG_ADMIN))
{
?>
<hr />
<form method="post" action="?location=magic">
<table class="inputtable">
<tr><th colspan="2"><?php echo lang('MAGIC_ENEMY_LABEL', '<input type="text" name="magic_target" value="'. prenum($lock['emp2']) .'" size="5" />'); ?></th></tr>
<tr><td><?php
	$spelllist = array();
	$spelllist[''] = lang('MAGIC_SPELL_SELECT');
	foreach ($spells as $i)
	{
		$spell = getSpell($i, $emp1);
		if ($spell instanceof prom_spelltype_enemy)
		{
			if ($spell instanceof prom_spelltype_varycost)
				$spelllist[$i] = lang('MAGIC_SPELL_LABEL_VARY', $emp1->era->getData('spell_'. $i));
			else	$spelllist[$i] = lang('MAGIC_SPELL_LABEL', $emp1->era->getData('spell_'. $i), number($spell->cost_enemy()), $emp1->era->runes, plural($spell->turns_enemy(), 'TURNS_SINGLE', 'TURNS_PLURAL'));
		}
		$spell = NULL;
	}
	echo optionlist('magic_spell_enemy', $spelllist, $spname_enemy);
?></td></tr>
<tr><td class="ac"><input type="hidden" name="action" value="cast_enemy" /><input type="submit" value="<?php echo lang('MAGIC_ENEMY_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
	if ($vary_enemy)
	{
?>
<form method="post" action="?location=magic">
<table class="inputtable">
<tr><th colspan="2"><?php echo lang('MAGIC_ENEMY_VERIFY_LABEL', prenum($lock['emp2'])); ?></th></tr>
<tr><td><?php
		$spell = getSpell($spname_enemy, $emp1, $emp2);
		echo lang('MAGIC_SPELL_LABEL', $emp1->era->getData('spell_'. $i), number($spell->cost_enemy()), $emp1->era->runes, plural($spell->turns_enemy(), 'TURNS_SINGLE', 'TURNS_PLURAL'));
?>
</td></tr>
<tr><td class="ac"><input type="hidden" name="action" value="cast_enemy" /><input type="hidden" name="magic_target" value="<?php echo $lock['emp2']; ?>" /><input type="hidden" name="magic_spell_enemy" value="<?php echo $spname_enemy; ?>" /><input type="hidden" name="confirm" value="1" /><input type="submit" value="<?php echo lang('MAGIC_ENEMY_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
	}
}
$effects = $emp1->effects->getDescriptions();
if (count($effects))
{
	echo '<i>'. lang('EFFECT_HEADER') ."</i>\n";
	echo '<table class="inputtable">';
	echo '<tr><th>'. lang('COLUMN_EFFECT_NAME') .'</th><th>'. lang('COLUMN_EFFECT_DESC') .'</th><th>'. lang('COLUMN_EFFECT_DURATION') ."</th></tr>\n";
	foreach ($effects as $effect)
		echo '<tr><td>'. $effect['name'] .'</td><td>'. $effect['desc'] .'</td><td>'. $effect['duration'] .'</td></tr>'."\n";
	echo "</table>\n";
}
page_footer();
?>
