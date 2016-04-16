<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: news.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'NEWS_TITLE';

page_header();
require_once(PROM_BASEDIR .'includes/magic.php');

$newslimit = 100;
$military = array(
	EMPNEWS_MILITARY_STANDARD => 'MILITARY_TYPE_STANDARD',
	EMPNEWS_MILITARY_SURPRISE => 'MILITARY_TYPE_SURPRISE',
	EMPNEWS_MILITARY_ARM => 'MILITARY_TYPE_ARM',
	EMPNEWS_MILITARY_LND => 'MILITARY_TYPE_LND',
	EMPNEWS_MILITARY_FLY => 'MILITARY_TYPE_FLY',
	EMPNEWS_MILITARY_SEA => 'MILITARY_TYPE_SEA'
);

if ($action == 'search') do
{
	$type = getFormVar('news_type');
	$which = getFormVar('news_which');
	$eid = fixInputNum(getFormVar('news_emp'));
	$cid = fixInputNum(getFormVar('news_clan'));

	$where = '';
	$parms = array();
	if ($type == 'emp')
	{
		if ($eid == 0)
			$where = '1=1';
		elseif ($which == 'attack')
		{
			$where = 'e_id_src = ?';
			$parms[] = $eid;
		}
		elseif ($which == 'defend')
		{
			$where = 'e_id_dst = ?';
			$parms[] = $eid;
		}
		elseif ($which == 'either')
		{
			$where = '(e_id_src = ? OR e_id_dst = ?)';
			$parms[] = $eid;
			$parms[] = $eid;
		}
		else
		{
			notice(lang('NEWS_INVALID_DATA'));
			break;
		}
	}
	elseif (CLAN_ENABLE && ($type == 'clan'))
	{
		if ($which == 'attack')
		{
			$where = 'c_id_src = ?';
			$parms[] = $cid;
		}
		elseif ($which == 'defend')
		{
			$where = 'c_id_dst = ?';
			$parms[] = $cid;
		}
		elseif ($which == 'either')
		{
			$where = '(c_id_src = ? OR c_id_dst = ?)';
			$parms[] = $cid;
			$parms[] = $cid;
		}
		else
		{
			notice(lang('NEWS_INVALID_DATA'));
			break;
		}
	}
	else
	{
		notice(lang('NEWS_INVALID_TYPE'));
		break;
	}
	$filterEvents = array(
		EMPNEWS_ATTACH_AID_SEND,

//		EMPNEWS_MAGIC_SPY,
		EMPNEWS_MAGIC_BLAST,
		EMPNEWS_MAGIC_STORM,
		EMPNEWS_MAGIC_RUNES,
		EMPNEWS_MAGIC_STRUCT,
		EMPNEWS_MAGIC_FIGHT,
		EMPNEWS_MAGIC_STEAL,

		EMPNEWS_MILITARY_KILL,
		EMPNEWS_MILITARY_STANDARD,
		EMPNEWS_MILITARY_SURPRISE,
		EMPNEWS_MILITARY_ARM,
		EMPNEWS_MILITARY_LND,
		EMPNEWS_MILITARY_FLY,
		EMPNEWS_MILITARY_SEA,
	);
	if ($user1->u_flags & (UFLAG_ADMIN | UFLAG_MOD))
		$filterEvents[] = EMPNEWS_ATTACH_AID_SENDCLAN;

	$sql = 'SELECT n_time, n_event, n_d0, e_id_src, c_id_src, e_id_dst, c_id_dst '.
		'FROM '. EMPIRE_NEWS_TABLE .' '.
		'WHERE '. $where .' AND (n_event IN '. sqlArgList($filterEvents) .')'.
		'ORDER BY n_id DESC';
	$sql = $db->setLimit($sql, $newslimit);
	$q = $db->prepare($sql);
	foreach ($filterEvents as $val)
		$parms[] = $val;
	$q->bindAllValues($parms);
	$q->execute() or warning('Failed to fetch news entries', 0);
	$news = $q->fetchAll();
?>
<h2><?php echo lang('NEWS_EVENTS_MATCHED', count($news)); ?></h2>
<table class="inputtable" style="width:90%">
<tr><th><?php echo lang('NEWS_LABEL_DATE'); ?></th>
    <th><?php echo lang('NEWS_LABEL_ATTACKER'); ?></th>
    <th><?php echo lang('NEWS_LABEL_EVENT'); ?></th>
    <th><?php echo lang('NEWS_LABEL_DEFENDER'); ?></th></tr>
<?php
	foreach ($news as $result)
	{
		$emp_a = prom_empire::cached_load($result['e_id_src']);
		$emp_b = prom_empire::cached_load($result['e_id_dst']);
		if ($result['c_id_src'])
			$clan_a = prom_clan::cached_load($result['c_id_src']);
		if ($result['c_id_dst'])
			$clan_b = prom_clan::cached_load($result['c_id_dst']);
?>
<tr><td><?php echo $user1->customdate($result['n_time']); ?></td>
    <td><?php echo $emp_a; if (CLAN_ENABLE && $result['c_id_src']) echo '<br />'. lang('NEWS_CLAN', $clan_a->c_name); ?></td>
    <td><?php
		switch ($result['n_event'])
		{
		case EMPNEWS_MILITARY_KILL:
			echo lang('NEWS_EVENT_KILLED');
			break;
		case EMPNEWS_MILITARY_STANDARD:
		case EMPNEWS_MILITARY_SURPRISE:
		case EMPNEWS_MILITARY_ARM:
		case EMPNEWS_MILITARY_LND:
		case EMPNEWS_MILITARY_FLY:
		case EMPNEWS_MILITARY_SEA:
			echo lang($military[$result['n_event']]) .'<br />';
			if ($result['n_d0'] > 0)
				echo lang('NEWS_EVENT_TOOK_ACRES', $result['n_d0']);
			else	echo lang('NEWS_EVENT_DEFENDED');
			break;
		
		case EMPNEWS_MAGIC_SPY:
		case EMPNEWS_MAGIC_BLAST:
		case EMPNEWS_MAGIC_STORM:
		case EMPNEWS_MAGIC_RUNES:
		case EMPNEWS_MAGIC_STRUCT:
		case EMPNEWS_MAGIC_STEAL:
			// convert event code to spell name, then translate it for the attacker's era
			echo lang($emp_a->era->getData('spell_'. $spells[$result['n_event']])).'<br />';
			if ($result['n_d0'] == SPELLRESULT_SUCCESS)
				echo lang('NEWS_EVENT_SPELL_SUCCESS');
			elseif ($result['n_d0'] == SPELLRESULT_SHIELDED)
				echo lang('NEWS_EVENT_SPELL_SHIELDED');
			else	// SPELLRESULT_NOEFFECT and less than zero
				echo lang('NEWS_EVENT_SPELL_FAILED');
			break;

		case EMPNEWS_MAGIC_FIGHT:
			// convert event code to spell name, then translate it for the attacker's era
			echo lang($emp_a->era->getData('spell_'. $spells[$result['n_event']])).'<br />';
			if ($result['n_d0'] < 0)
				echo lang('NEWS_EVENT_SPELL_FAILED');
			elseif ($result['n_d0'] == SPELLRESULT_NOEFFECT)
				echo lang('NEWS_EVENT_DEFENDED');
			else	echo lang('NEWS_EVENT_TOOK_ACRES', $result['n_d0']);
			break;

		case EMPNEWS_ATTACH_AID_SEND:
		case EMPNEWS_ATTACH_AID_SENDCLAN:
			echo lang('NEWS_EVENT_AID');
			break;

		default:
			echo lang('NEWS_EVENT_UNKNOWN');
			break;
		}
?></td>
    <td><?php echo $emp_b; if (CLAN_ENABLE && $result['c_id_dst']) echo '<br />'. lang('NEWS_CLAN', $clan_b->c_name); ?></td></tr>
<tr><td colspan="4"><hr /></td></tr>
<?php
		$emp_a = NULL;
		$emp_b = NULL;
		$clan_a = NULL;
		$clan_b = NULL;
	}
?>
</table>
<?php
} while (0);
notices();
?>
<form method="post" action="?location=news">
<table class="inputtable">
<tr><th class="al"><?php echo lang('NEWS_LABEL_SEARCHBY'); ?></th>
    <td><?php echo radiobutton('news_which', lang('NEWS_LABEL_ATTACKER'), 'attack') .' '. radiobutton('news_which', lang('NEWS_LABEL_DEFENDER'), 'defend') .' '. radiobutton('news_which', lang('NEWS_LABEL_EITHER'), 'either', TRUE); ?></td></tr>
<tr><th class="al"><?php echo radiobutton('news_type', lang('NEWS_LABEL_EMPIRE'), 'emp', TRUE); ?></th>
    <td><input type="text" name="news_emp" size="5" /></td></tr>
<?php
if (CLAN_ENABLE)
{
?>
<tr><th class="al"><?php echo radiobutton('news_type', lang('NEWS_LABEL_CLAN'), 'clan'); ?></th>
    <td><?php
$clanlist = array();
foreach ($cnames as $id => $name)
	$clanlist[$id] = $name;
echo optionlist('news_clan', $clanlist);
?></td></tr>
<?php
}
?>
<tr><td colspan="2" class="ac"><input type="hidden" name="action" value="search" /><input type="submit" value="<?php echo lang('NEWS_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
page_footer();
?>
