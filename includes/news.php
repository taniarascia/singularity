<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: news.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

// Empire News
// Events with attachments
define('EMPNEWS_ATTACH_FIRST', 100);		// First attachment event, MUST be equal to the event below
define('EMPNEWS_ATTACH_MARKET_SELL', 100);	// 0:type, 1:amount, 2:paid, 3:earned (minus tax)
define('EMPNEWS_ATTACH_LOTTERY', 101);		// 0:winnings
define('EMPNEWS_ATTACH_MARKET_RETURN', 102);	// 0:type, 1:amount, 2:price, 3:returned
define('EMPNEWS_ATTACH_AID_SEND', 103);		// 0:convoy, 1:trparm, 2:trplnd, 3:trpfly, 4:trpsea, 5:cash, 6:runes, 7:food
define('EMPNEWS_ATTACH_AID_RETURN', 104);	// 0:intended to return, 1:actually returned
define('EMPNEWS_ATTACH_AID_SENDCLAN', 105);	// 0:convoy, 1:trparm, 2:trplnd, 3:trpfly, 4:trpsea, 5:cash, 6:runes, 7:food
define('EMPNEWS_ATTACH_LAST', 105);		// Last attachment event, MUST be equal to the event above

// Magic events

// Negative spell results indicate failure, positive for success (unless noted otherwise)
define('SPELLRESULT_NOEFFECT', 0);
define('SPELLRESULT_SHIELDED', 1);
define('SPELLRESULT_SUCCESS', 2);

define('EMPNEWS_MAGIC_SPY', 201);		// 0:result
define('EMPNEWS_MAGIC_BLAST', 202);		// 0:result
define('EMPNEWS_MAGIC_SHIELD', 203);		// unused
define('EMPNEWS_MAGIC_STORM', 204);		// 0:result, 1:food, 2:cash
define('EMPNEWS_MAGIC_RUNES', 205);		// 0:result, 1:runes
define('EMPNEWS_MAGIC_STRUCT', 206);		// 0:result, 1:buildings
define('EMPNEWS_MAGIC_FOOD', 207);		// unused
define('EMPNEWS_MAGIC_CASH', 208);		// unused
define('EMPNEWS_MAGIC_GATE', 209);		// unused
define('EMPNEWS_MAGIC_UNGATE', 210);		// unused
define('EMPNEWS_MAGIC_FIGHT', 211);		// 0:result (>0 = acres taken), 1:target trpwiz loss, 2:attacker trpwiz loss
define('EMPNEWS_MAGIC_STEAL', 212);		// 0:result, 1:cash
define('EMPNEWS_MAGIC_ADVANCE', 213);		// unused
define('EMPNEWS_MAGIC_REGRESS', 214);		// unused

// Military events
define('EMPNEWS_MILITARY_AID', 300);		// 0:empire protected
define('EMPNEWS_MILITARY_KILL', 301);		// no arguments
define('EMPNEWS_MILITARY_STANDARD', 302);	// 0:acres, 1:target trparm loss, 2:target trplnd loss, 3:target trpfly loss, 4:target trpsea loss,
						// 5:attacker trparm loss, 6:attacker trplnd loss, 7:attacker trpfly loss, 8:attacker trpsea loss,
define('EMPNEWS_MILITARY_SURPRISE', 303);	// 0:acres, 1:target trparm loss, 2:target trplnd loss, 3:target trpfly loss, 4:target trpsea loss,
						// 5:attacker trparm loss, 6:attacker trplnd loss, 7:attacker trpfly loss, 8:attacker trpsea loss,
define('EMPNEWS_MILITARY_ARM', 304);		// 0:acres, 1:target trparm loss, 2:attacker trparm loss
define('EMPNEWS_MILITARY_LND', 305);		// 0:acres, 1:target trplnd loss, 2:attacker trplnd loss
define('EMPNEWS_MILITARY_FLY', 306);		// 0:acres, 1:target trpfly loss, 2:attacker trpfly loss
define('EMPNEWS_MILITARY_SEA', 307);		// 0:acres, 1:target trpsea loss, 2:attacker trpsea loss

// Clan events
define('EMPNEWS_CLAN_CREATE', 400);		// no arguments
define('EMPNEWS_CLAN_DISBAND', 401);		// no arguments
define('EMPNEWS_CLAN_JOIN', 402);		// no arguments
define('EMPNEWS_CLAN_LEAVE', 403);		// no arguments
define('EMPNEWS_CLAN_REMOVE', 404);		// no arguments
define('EMPNEWS_CLAN_GRANT_LEADER', 405);	// no arguments
define('EMPNEWS_CLAN_INHERIT_LEADER', 406);	// no arguments
define('EMPNEWS_CLAN_GRANT_ASSISTANT', 407);	// no arguments
define('EMPNEWS_CLAN_REVOKE_ASSISTANT', 408);	// no arguments
define('EMPNEWS_CLAN_GRANT_MINISTER', 409);	// no arguments
define('EMPNEWS_CLAN_REVOKE_MINISTER', 410);	// no arguments
define('EMPNEWS_CLAN_WAR_START', 411);		// no arguments
define('EMPNEWS_CLAN_WAR_REQUEST', 412);	// no arguments
define('EMPNEWS_CLAN_WAR_STOP', 413);		// no arguments
define('EMPNEWS_CLAN_WAR_RETRACT', 414);	// no arguments
define('EMPNEWS_CLAN_WAR_REJECT', 415);		// no arguments
define('EMPNEWS_CLAN_WAR_GONE', 416);		// no arguments
define('EMPNEWS_CLAN_ALLY_REQUEST', 417);	// no arguments
define('EMPNEWS_CLAN_ALLY_START', 418);		// no arguments
define('EMPNEWS_CLAN_ALLY_STOP', 419);		// no arguments
define('EMPNEWS_CLAN_ALLY_RETRACT', 420);	// no arguments
define('EMPNEWS_CLAN_ALLY_DECLINE', 421);	// no arguments
define('EMPNEWS_CLAN_ALLY_GONE', 422);		// no arguments
define('EMPNEWS_CLAN_REVOKE_LEADER', 423);	// no arguments
define('EMPNEWS_CLAN_INVITE_TEMP', 424);	// no arguments
define('EMPNEWS_CLAN_INVITE_PERM', 425);	// no arguments
define('EMPNEWS_CLAN_UNINVITE_TEMP', 426);	// no arguments
define('EMPNEWS_CLAN_UNINVITE_PERM', 427);	// no arguments
define('EMPNEWS_CLAN_INVITE_DISBANDED', 428);	// no arguments

// Clan News
// Membership changes and related actions
define('CLANNEWS_MEMBER_CREATE', 100);		// e1:founder
define('CLANNEWS_MEMBER_JOIN', 101);		// e1:new member
define('CLANNEWS_MEMBER_LEAVE', 102);		// e1:former member
define('CLANNEWS_MEMBER_REMOVE', 103);		// e1:former member, e2:kicker
define('CLANNEWS_MEMBER_DEAD', 104);		// e1:dead member
define('CLANNEWS_MEMBER_SHARE', 105);		// e1:member
define('CLANNEWS_MEMBER_UNSHARE', 106);		// e1:member
define('CLANNEWS_MEMBER_INVITE_TEMP', 107);	// e1:inviter, e2:recipient
define('CLANNEWS_MEMBER_INVITE_PERM', 108);	// e1:inviter, e2:recipient
define('CLANNEWS_MEMBER_UNINVITE_TEMP', 109);	// e1:uninviter, e2:recipient
define('CLANNEWS_MEMBER_UNINVITE_PERM', 110);	// e1:uninviter, e2:recipient

// Permission changes
define('CLANNEWS_PERM_GRANT_LEADER', 200);	// e1:new leader, e2:grantor
define('CLANNEWS_PERM_REVOKE_LEADER', 201);	// e1:old leader, e2:grantor
define('CLANNEWS_PERM_GRANT_ASSISTANT', 202);	// e1:new asst, e2:grantor
define('CLANNEWS_PERM_REVOKE_ASSISTANT', 203);	// e1:old asst, e2:grantor
define('CLANNEWS_PERM_GRANT_MINISTER', 204);	// e1:new fa, e2:grantor
define('CLANNEWS_PERM_REVOKE_MINISTER', 205);	// e1:old fa, e2:grantor
define('CLANNEWS_PERM_ASSISTANT_INHERIT', 206);	// e1:new leader, e2:old leader
define('CLANNEWS_PERM_MINISTER_INHERIT', 207);	// e1:new leader, e2:old leader
define('CLANNEWS_PERM_MEMBER_INHERIT', 208);	// e1:new leader, e2:old leader

// Property changes
define('CLANNEWS_PROP_CHANGE_PASSWORD', 300);	// e1:changer
define('CLANNEWS_PROP_CHANGE_TITLE', 301);	// e1:changer
define('CLANNEWS_PROP_CHANGE_URL', 302);	// e1:changer
define('CLANNEWS_PROP_CHANGE_LOGO', 303);	// e1:changer

// Relation events, incoming
define('CLANNEWS_RECV_WAR_START', 400);		// e2:declarer, c2:opponent
define('CLANNEWS_RECV_WAR_REQUEST', 401);	// e2:declarer, c2:opponent
define('CLANNEWS_RECV_WAR_STOP', 402);		// e2:declarer, c2:opponent
define('CLANNEWS_RECV_WAR_RETRACT', 403);	// e2:declarer, c2:opponent
define('CLANNEWS_RECV_WAR_REJECT', 404);	// e2:declarer, c2:opponent
define('CLANNEWS_RECV_WAR_GONE', 405);		// c2:opponent
define('CLANNEWS_RECV_ALLY_REQUEST', 406);	// e2:declarer, c2:ally
define('CLANNEWS_RECV_ALLY_START', 407);	// e2:declarer, c2:ally
define('CLANNEWS_RECV_ALLY_STOP', 408);		// e2:declarer, c2:ally
define('CLANNEWS_RECV_ALLY_RETRACT', 409);	// e2:declarer, c2:ally
define('CLANNEWS_RECV_ALLY_DECLINE', 410);	// e2:declarer, c2:ally
define('CLANNEWS_RECV_ALLY_GONE', 411);		// c2:ally

// Relation events, outgoing
define('CLANNEWS_SEND_WAR_START', 500);		// e1:declarer, c2:opponent
define('CLANNEWS_SEND_WAR_REQUEST', 501);	// e1:declarer, c2:opponent
define('CLANNEWS_SEND_WAR_STOP', 502);		// e1:declarer, c2:opponent
define('CLANNEWS_SEND_WAR_RETRACT', 503);	// e1:declarer, c2:opponent
define('CLANNEWS_SEND_WAR_REJECT', 504);	// e1:declarer, c2:opponent
define('CLANNEWS_SEND_ALLY_REQUEST', 505);	// e1:declarer, c2:ally
define('CLANNEWS_SEND_ALLY_START', 506);	// e1:declarer, c2:ally
define('CLANNEWS_SEND_ALLY_STOP', 507);		// e1:declarer, c2:ally
define('CLANNEWS_SEND_ALLY_RETRACT', 508);	// e1:declarer, c2:ally
define('CLANNEWS_SEND_ALLY_DECLINE', 509);	// e1:declarer, c2:ally

class prom_news_placeholder
{
	public $e_id;
	public $c_id;
	public function __construct($eid = 0, $cid = 0)
	{
		$this->e_id = $eid;
		$this->c_id = $cid;
	}
}

function addEmpireNews ($event, $src, $dst, $d0 = 0, $d1 = 0, $d2 = 0, $d3 = 0, $d4 = 0, $d5 = 0, $d6 = 0, $d7 = 0, $d8 = 0)
{
	global $db;

	$q = $db->prepare('INSERT INTO '. EMPIRE_NEWS_TABLE .' (n_time,e_id_src,c_id_src,e_id_dst,c_id_dst,n_event,n_d0,n_d1,n_d2,n_d3,n_d4,n_d5,n_d6,n_d7,n_d8) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
	$q->bindIntValue(1, CUR_TIME);
	$q->bindIntValue(2, $src ? $src->e_id : 0);
	$q->bindIntValue(3, $src ? $src->c_id : 0);
	$q->bindIntValue(4, $dst->e_id);
	$q->bindIntValue(5, $dst->c_id);
	$q->bindIntValue(6, $event);
	$q->bindIntValue(7, $d0);
	$q->bindIntValue(8, $d1);
	$q->bindIntValue(9, $d2);
	$q->bindIntValue(10, $d3);
	$q->bindIntValue(11, $d4);
	$q->bindIntValue(12, $d5);
	$q->bindIntValue(13, $d6);
	$q->bindIntValue(14, $d7);
	$q->bindIntValue(15, $d8);
	$q->execute() or warning('Failed to add empire news entry', 1);
}

function addClanNews ($event, $c1, $e1 = NULL, $c2 = NULL, $e2 = NULL)
{
	global $db;

	$q = $db->prepare('INSERT INTO '. CLAN_NEWS_TABLE .' (cn_time,c_id,e_id_1,c_id_2,e_id_2,cn_event) VALUES (?,?,?,?,?,?)');
	$q->bindIntValue(1, CUR_TIME);
	$q->bindIntValue(2, $c1->c_id);
	$q->bindIntValue(3, $e1 ? $e1->e_id : 0);
	$q->bindIntValue(4, $c2 ? $c2->c_id : 0);
	$q->bindIntValue(5, $e2 ? $e2->e_id : 0);
	$q->bindIntValue(6, $event);
	$q->execute() or warning('Failed to add clan news entry', 1);
}

// Prints a report of recent events for the specified empire
function printEmpireNews ($emp_a, $allnews = 0)
{
	global $db;
	$mkttype = lookup('pubmkt_id_name');

	$q = $db->prepare('SELECT n_id, n_time, e_id_src, c_id_src, e_id_dst, c_id_dst, '.
		'n_event, n_d0, n_d1, n_d2, n_d3, n_d4, n_d5, n_d6, n_d7, n_d8 '.
		'FROM '. EMPIRE_NEWS_TABLE .' '.
		'WHERE e_id_dst = ? AND n_time > ? AND n_flags & ? = 0 ORDER BY n_id ASC');
	$q->bindIntValue(1, $emp_a->e_id);
	$q->bindIntValue(2, CUR_TIME - 86400 * 7);
	$q->bindIntValue(3, $allnews ? 0 : NFLAG_READ);
	$q->execute() or warning("Failed to retrieve news for empire $emp_a->e_id", 1);
	$news = $q->fetchAll();
	if (count($news) == 0)
		return 0;
	$lastid = 0;

	$newsdata = array();
	foreach ($news as $event)
	{
		$entry = array();
		$lastid = $event['n_id'];

		$date = duration(CUR_TIME - $event['n_time'], 1, DURATION_HOURS);
		$entry['date'] = lang('EMPNEWS_DATE_FORMAT', $date);

		if ($event['e_id_src'])
			$emp_b = prom_empire::cached_load($event['e_id_src']);
		if ($event['c_id_src'])
			$clan_a = prom_clan::cached_load($event['c_id_src']);
		if ($event['c_id_dst'])
			$clan_b = prom_clan::cached_load($event['c_id_dst']);

		switch ($event['n_event'])
		{
		case EMPNEWS_ATTACH_MARKET_SELL:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('EMPNEWS_ATTACH_MARKET_SELL', number($event['n_d1']), $emp_a->era->getData($mkttype[$event['n_d0']]), money($event['n_d3']));
			$newsdata[] = $entry;
			break;
		case EMPNEWS_ATTACH_LOTTERY:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('EMPNEWS_ATTACH_LOTTERY', money($event['n_d0']));
			$newsdata[] = $entry;
			break;
		case EMPNEWS_ATTACH_MARKET_RETURN:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('EMPNEWS_ATTACH_MARKET_RETURN', number($event['n_d1']), $emp_a->era->getData($mkttype[$event['n_d0']]), money($event['n_d2']), number($event['n_d3']));
			$newsdata[] = $entry;
			break;
		case EMPNEWS_ATTACH_AID_SEND:
		case EMPNEWS_ATTACH_AID_SENDCLAN:
			$list = array();
			if ($event['n_d1']) $list[] = number($event['n_d1']) .' '. lang($emp_a->era->trparm);
			if ($event['n_d2']) $list[] = number($event['n_d2']) .' '. lang($emp_a->era->trplnd);
			if ($event['n_d3']) $list[] = number($event['n_d3']) .' '. lang($emp_a->era->trpfly);
			if ($event['n_d4']) $list[] = number($event['n_d4']) .' '. lang($emp_a->era->trpsea);
			if ($event['n_d5']) $list[] = money($event['n_d5']);
			if ($event['n_d6']) $list[] = number($event['n_d6']) .' '. lang($emp_a->era->runes);
			if ($event['n_d7']) $list[] = number($event['n_d7']) .' '. lang($emp_a->era->food);
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('EMPNEWS_ATTACH_AID_SEND', $emp_b, number($event['n_d0']), $emp_a->era->trpsea);
			$entry['desc2'] = commalist($list);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_ATTACH_AID_RETURN:
			if ($event['n_d0'] == $event['n_d1'])
			{
				$entry['class'] = 'cgood';
				$entry['desc'] = lang('EMPNEWS_ATTACH_AID_RETURN_ALL', number($event['n_d0']), $emp_a->era->trpsea, $emp_b);
			}
			elseif ($event['n_d1'] > 0)
			{
				$entry['class'] = 'cwarn';
				$entry['desc'] = lang('EMPNEWS_ATTACH_AID_RETURN_SOME', number($event['n_d1']), number($event['n_d0']), $emp_a->era->trpsea, $emp_b);
			}
			else
			{
				$entry['class'] = 'cbad';
				$entry['desc'] = lang('EMPNEWS_ATTACH_AID_RETURN_NONE', number($event['n_d0']), $emp_a->era->trpsea, $emp_b);
			}
			$newsdata[] = $entry;
			break;
		case EMPNEWS_MAGIC_SPY:
			if ($event['n_d0'] < 0)
			{
				$entry['class'] = 'cwarn';
				$entry['desc'] = lang('EMPNEWS_MAGIC_SPY_FAILED', $emp_b);
			}
			elseif ($event['n_d0'] == SPELLRESULT_SUCCESS)
			{
				$entry['class'] = 'cbad';
				$entry['desc'] = lang('EMPNEWS_MAGIC_SPY_SUCCESS');
			}
			$newsdata[] = $entry;
			break;
		case EMPNEWS_MAGIC_BLAST:
			if ($event['n_d0'] < 0)
			{
				$entry['class'] = 'cwarn';
				$entry['desc'] = lang('EMPNEWS_MAGIC_BLAST_FAILED', $emp_b);
			}
			elseif ($event['n_d0'] == SPELLRESULT_SUCCESS)
			{
				$entry['class'] = 'cbad';
				$entry['desc'] = lang('EMPNEWS_MAGIC_BLAST_SUCCESS', $emp_b);
			}
			elseif ($event['n_d0'] == SPELLRESULT_SHIELDED)
			{
				$entry['class'] = 'cbad';
				$entry['desc'] = lang('EMPNEWS_MAGIC_BLAST_SHIELDED', $emp_b);
			}
			$newsdata[] = $entry;
			break;
		case EMPNEWS_MAGIC_STORM:
			if ($event['n_d0'] < 0)
			{
				$entry['class'] = 'cwarn';
				$entry['desc'] = lang('EMPNEWS_MAGIC_STORM_FAILED', $emp_b);
			}
			elseif ($event['n_d0'] == SPELLRESULT_SUCCESS)
			{
				$entry['class'] = 'cbad';
				$entry['desc'] = lang('EMPNEWS_MAGIC_STORM_SUCCESS', number($event['n_d1']), $emp_a->era->food, money($event['n_d2']));
			}
			elseif ($event['n_d0'] == SPELLRESULT_SHIELDED)
			{
				$entry['class'] = 'cbad';
				$entry['desc'] = lang('EMPNEWS_MAGIC_STORM_SHIELDED', number($event['n_d1']), $emp_a->era->food, money($event['n_d2']));
			}
			$newsdata[] = $entry;
			break;
		case EMPNEWS_MAGIC_RUNES:
			if ($event['n_d0'] < 0)
			{
				$entry['class'] = 'cwarn';
				$entry['desc'] = lang('EMPNEWS_MAGIC_RUNES_FAILED', $emp_b, $emp_a->era->runes);
			}
			elseif ($event['n_d0'] == SPELLRESULT_SUCCESS)
			{
				$entry['class'] = 'cbad';
				$entry['desc'] = lang('EMPNEWS_MAGIC_RUNES_SUCCESS', number($event['n_d1']), $emp_a->era->runes);
			}
			elseif ($event['n_d0'] == SPELLRESULT_SHIELDED)
			{
				$entry['class'] = 'cbad';
				$entry['desc'] = lang('EMPNEWS_MAGIC_RUNES_SHIELDED', number($event['n_d1']), $emp_a->era->runes);
			}
			$newsdata[] = $entry;
			break;
		case EMPNEWS_MAGIC_STRUCT:
			if ($event['n_d0'] < 0)
			{
				$entry['class'] = 'cwarn';
				$entry['desc'] = lang('EMPNEWS_MAGIC_STRUCT_FAILED', $emp_b);
			}
			elseif ($event['n_d0'] == SPELLRESULT_SUCCESS)
			{
				$entry['class'] = 'cbad';
				$entry['desc'] = lang('EMPNEWS_MAGIC_STRUCT_SUCCESS');
			}
			elseif ($event['n_d0'] == SPELLRESULT_SHIELDED)
			{
				$entry['class'] = 'cbad';
				$entry['desc'] = lang('EMPNEWS_MAGIC_STRUCT_SHIELDED');
			}
			elseif ($event['n_d0'] == SPELLRESULT_NOEFFECT)
			{
				$entry['class'] = 'cwarn';
				$entry['desc'] = lang('EMPNEWS_MAGIC_STRUCT_NOEFFECT');
			}
			$newsdata[] = $entry;
			break;
		case EMPNEWS_MAGIC_FIGHT:
			if ($event['n_d0'] < 0)
			{
				$entry['class'] = 'cwarn';
				$entry['desc'] = lang('EMPNEWS_MAGIC_FIGHT_FAILED', $emp_b, $emp_a->era->trpwiz);
			}
			else
			{
				if ($event['n_d0'])
					$result = lang('EMPNEWS_MAGIC_FIGHT_DEFEATED', number($event['n_d0']));
				else	$result = lang('EMPNEWS_MAGIC_FIGHT_DEFENDED');
				if ($event['n_d1'])
					$result .= '<br />'. lang('EMPNEWS_MAGIC_FIGHT_LOSSES_YOU', number($event['n_d1']), $emp_a->era->trpwiz);
				if ($event['n_d2'])
					$result .= '<br />'. lang('EMPNEWS_MAGIC_FIGHT_LOSSES_ENEMY', number($event['n_d2']), $emp_b->era->trpwiz);
				$entry['class'] = 'cbad';
				$entry['desc'] = lang('EMPNEWS_MAGIC_FIGHT_SUCCESS', $emp_b, $emp_b->era->trpwiz);
				$entry['desc2'] = $result;
			}
			$newsdata[] = $entry;
			break;
		case EMPNEWS_MAGIC_STEAL:
			if ($event['n_d0'] < 0)
			{
				$entry['class'] = 'cwarn';
				$entry['desc'] = lang('EMPNEWS_MAGIC_STEAL_FAILED', $emp_b);
			}
			elseif ($event['n_d0'] == SPELLRESULT_SUCCESS)
			{
				$entry['class'] = 'cbad';
				$entry['desc'] = lang('EMPNEWS_MAGIC_STEAL_SUCCESS', money($event['n_d1']));
			}
			elseif ($event['n_d0'] == SPELLRESULT_SHIELDED)
			{
				$entry['class'] = 'cbad';
				$entry['desc'] = lang('EMPNEWS_MAGIC_STEAL_SHIELDED', money($event['n_d1']));
			}
			$newsdata[] = $entry;
			break;
		case EMPNEWS_MILITARY_AID:
			$emp_c = prom_empire::cached_load($event['n_d0']);
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('EMPNEWS_MILITARY_AID', $emp_c, $emp_b);
			$newsdata[] = $entry;
			$emp_c = NULL;
			break;
		case EMPNEWS_MILITARY_KILL:
			$entry['class'] = 'cbad';
			$entry['desc'] = lang('EMPNEWS_MILITARY_KILL', $emp_b);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_MILITARY_STANDARD:
		case EMPNEWS_MILITARY_SURPRISE:
			$list = array();
			if ($event['n_d1']) $list[] = number($event['n_d1']) .' '. lang($emp_a->era->trparm);
			if ($event['n_d2']) $list[] = number($event['n_d2']) .' '. lang($emp_a->era->trplnd);
			if ($event['n_d3']) $list[] = number($event['n_d3']) .' '. lang($emp_a->era->trpfly);
			if ($event['n_d4']) $list[] = number($event['n_d4']) .' '. lang($emp_a->era->trpsea);
			if ($event['n_d0'])
				$result = lang('EMPNEWS_MILITARY_DEFEATED', number($event['n_d0']));
			else	$result = lang('EMPNEWS_MILITARY_DEFENDED');
			if (count($list))
				$result .= '<br />'. lang('EMPNEWS_MILITARY_LOSSES_YOU_MULTIPLE', commalist($list));

			$list = array();
			if ($event['n_d5']) $list[] = number($event['n_d5']) .' '. lang($emp_b->era->trparm);
			if ($event['n_d6']) $list[] = number($event['n_d6']) .' '. lang($emp_b->era->trplnd);
			if ($event['n_d7']) $list[] = number($event['n_d7']) .' '. lang($emp_b->era->trpfly);
			if ($event['n_d8']) $list[] = number($event['n_d8']) .' '. lang($emp_b->era->trpsea);
			if (count($list))
				$result .= '<br />'. lang('EMPNEWS_MILITARY_LOSSES_ENEMY_MULTIPLE', commalist($list));
			$entry['class'] = 'cbad';
			if ($event['n_event'] == EMPNEWS_MILITARY_SURPRISE)
				$entry['desc'] = lang('EMPNEWS_MILITARY_SURPRISE', $emp_b);
			else	$entry['desc'] = lang('EMPNEWS_MILITARY_STANDARD', $emp_b);
			$entry['desc2'] = $result;
			$newsdata[] = $entry;
			break;
		case EMPNEWS_MILITARY_ARM:
		case EMPNEWS_MILITARY_LND:
		case EMPNEWS_MILITARY_FLY:
		case EMPNEWS_MILITARY_SEA:
			switch ($event['n_event'])
			{
			case EMPNEWS_MILITARY_ARM:	$unit = 'trparm';	break;
			case EMPNEWS_MILITARY_LND:	$unit = 'trplnd';	break;
			case EMPNEWS_MILITARY_FLY:	$unit = 'trpfly';	break;
			case EMPNEWS_MILITARY_SEA:	$unit = 'trpsea';	break;
			}
			if ($event['n_d0'])
				$result = lang('EMPNEWS_MILITARY_DEFEATED', number($event['n_d0']));
			else	$result = lang('EMPNEWS_MILITARY_DEFENDED');

			if ($event['n_d1'])
				$result .= '<br />'. lang('EMPNEWS_MILITARY_LOSSES_YOU_SINGLE', number($event['n_d1']), $emp_a->era->getData($unit));
			if ($event['n_d2'])
				$result .= '<br />'. lang('EMPNEWS_MILITARY_LOSSES_ENEMY_SINGLE', number($event['n_d2']), $emp_b->era->getData($unit));
			$entry['class'] = 'cbad';
			$entry['desc'] = lang('EMPNEWS_MILITARY_UNIT', $emp_b, $emp_b->era->getData($unit));
			$entry['desc2'] = $result;
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_CREATE:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('EMPNEWS_CLAN_CREATE', $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_DISBAND:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('EMPNEWS_CLAN_DISBAND', $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_JOIN:
			$entry['class'] = 'cgood';
			if ($event['e_id_src'] == $event['e_id_dst'])
				$entry['desc'] = lang('EMPNEWS_CLAN_JOIN_SELF', $clan_b->c_name);
			else	$entry['desc'] = lang('EMPNEWS_CLAN_JOIN_OTHER', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_LEAVE:
			$entry['class'] = 'cwarn';
			if ($event['e_id_src'] == $event['e_id_dst'])
				$entry['desc'] = lang('EMPNEWS_CLAN_LEAVE_SELF', $clan_b->c_name);
			else	$entry['desc'] = lang('EMPNEWS_CLAN_LEAVE_OTHER', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_REMOVE:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('EMPNEWS_CLAN_REMOVE', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_GRANT_LEADER:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('EMPNEWS_CLAN_GRANT_LEADER', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_INHERIT_LEADER:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('EMPNEWS_CLAN_INHERIT_LEADER', $clan_b->c_name, $emp_b);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_REVOKE_LEADER:	// only performed by Administrators
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('EMPNEWS_CLAN_REVOKE_LEADER', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_GRANT_ASSISTANT:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('EMPNEWS_CLAN_GRANT_ASSISTANT', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_REVOKE_ASSISTANT:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('EMPNEWS_CLAN_REVOKE_ASSISTANT', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_GRANT_MINISTER:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('EMPNEWS_CLAN_GRANT_MINISTER', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_REVOKE_MINISTER:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('EMPNEWS_CLAN_REVOKE_MINISTER', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_WAR_START:
			$entry['class'] = 'cbad';
			$entry['desc'] = lang('EMPNEWS_CLAN_WAR_START', $emp_b, $clan_a->c_name, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_WAR_REQUEST:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('EMPNEWS_CLAN_WAR_REQUEST', $emp_b, $clan_a->c_name, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_WAR_STOP:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('EMPNEWS_CLAN_WAR_STOP', $emp_b, $clan_a->c_name, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_WAR_RETRACT:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('EMPNEWS_CLAN_WAR_RETRACT', $emp_b, $clan_a->c_name, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_WAR_REJECT:
			$entry['class'] = 'cbad';
			$entry['desc'] = lang('EMPNEWS_CLAN_WAR_REJECT', $emp_b, $clan_a->c_name, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_WAR_GONE:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('EMPNEWS_CLAN_WAR_GONE', $clan_a->c_name, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_ALLY_REQUEST:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('EMPNEWS_CLAN_ALLY_REQUEST', $emp_b, $clan_a->c_name, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_ALLY_START:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('EMPNEWS_CLAN_ALLY_START', $emp_b, $clan_a->c_name, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_ALLY_STOP:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('EMPNEWS_CLAN_ALLY_STOP', $emp_b, $clan_a->c_name, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_ALLY_RETRACT:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('EMPNEWS_CLAN_ALLY_RETRACT', $emp_b, $clan_a->c_name, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_ALLY_DECLINE:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('EMPNEWS_CLAN_ALLY_DECLINE', $emp_b, $clan_a->c_name, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_ALLY_GONE:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('EMPNEWS_CLAN_ALLY_GONE', $clan_a->c_name, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_INVITE_TEMP:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('EMPNEWS_CLAN_INVITE_TEMP', $emp_b, $clan_a->c_name, duration(CLAN_INVITE_TIME * 60 * 60));
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_INVITE_PERM:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('EMPNEWS_CLAN_INVITE_PERM', $emp_b, $clan_a->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_UNINVITE_TEMP:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('EMPNEWS_CLAN_UNINVITE_TEMP', $emp_b, $clan_a->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_UNINVITE_PERM:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('EMPNEWS_CLAN_UNINVITE_PERM', $emp_b, $clan_a->c_name);
			$newsdata[] = $entry;
			break;
		case EMPNEWS_CLAN_INVITE_DISBANDED:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('EMPNEWS_CLAN_INVITE_DISBANDED', $clan_a->c_name);
			$newsdata[] = $entry;
			break;
		}
		$emp_b = NULL;
		$clan_a = NULL;
		$clan_b = NULL;
	}
	if (count($newsdata) > 0)
	{
?>
<table class="inputtable" border="1">
<tr><th><?php echo lang('EMPNEWS_COLUMN_TIME'); ?></th>
    <th colspan="2"><?php echo lang('EMPNEWS_COLUMN_EVENT'); ?></th></tr>
<?php
		foreach ($newsdata as $event)
		{
			if (isset($event['desc2']))
			{
?>
<tr style="vertical-align:top"><th><?php echo $event['date']; ?></th>
    <td><span class="<?php echo $event['class']; ?>"><?php echo $event['desc']; ?></span></td><td><?php echo $event['desc2']; ?></td></tr>
<?php
			}
			else
			{
?>
<tr style="vertical-align:top"><th><?php echo $event['date']; ?></th>
    <td colspan="2"><span class="<?php echo $event['class']; ?>"><?php echo $event['desc']; ?></span></td></tr>
<?php
			}
		}
?>
</table>
<?php
	}

	return $lastid;
}

// Prints a report of recent events for the specified clan
function printClanNews ($clan_a)
{
	global $db;
	$q = $db->prepare('SELECT cn_id,cn_time,e_id_1,e_id_2,c_id_2,cn_event '.
		'FROM '. CLAN_NEWS_TABLE .' '.
		'WHERE c_id = ? ORDER BY cn_id ASC');
	$q->bindIntValue(1, $clan_a->c_id);
	$q->execute() or warning("Failed to retrieve news for clan $clan_a->c_id", 1);
	$news = $q->fetchAll();
	if (count($news) == 0)
		return 0;
	$lastid = 0;

	$newsdata = array();
	foreach ($news as $event)
	{
		$entry = array();
		$lastid = $event['cn_id'];

		$date = duration(CUR_TIME - $event['cn_time'], 1, DURATION_HOURS);
		$entry['date'] = lang('EMPNEWS_DATE_FORMAT', $date);

		if ($event['e_id_1'])
			$emp_a = prom_empire::cached_load($event['e_id_1']);
		if ($event['e_id_2'])
			$emp_b = prom_empire::cached_load($event['e_id_2']);
		if ($event['c_id_2'])
			$clan_b = prom_clan::cached_load($event['c_id_2']);

		switch ($event['cn_event'])
		{
		case CLANNEWS_MEMBER_CREATE:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_MEMBER_CREATE', $emp_a);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_MEMBER_JOIN:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_MEMBER_JOIN', $emp_a);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_MEMBER_LEAVE:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('CLANNEWS_MEMBER_LEAVE', $emp_a);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_MEMBER_REMOVE:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('CLANNEWS_MEMBER_REMOVE', $emp_a, $emp_b);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_MEMBER_DEAD:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('CLANNEWS_MEMBER_DEAD', $emp_a);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_MEMBER_SHARE:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_MEMBER_SHARE', $emp_a);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_MEMBER_UNSHARE:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('CLANNEWS_MEMBER_UNSHARE', $emp_a);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_MEMBER_INVITE_TEMP:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_MEMBER_INVITE_TEMP', $emp_a, $emp_b);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_MEMBER_INVITE_PERM:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_MEMBER_INVITE_PERM', $emp_a, $emp_b);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_MEMBER_UNINVITE_TEMP:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('CLANNEWS_MEMBER_UNINVITE_TEMP', $emp_a, $emp_b);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_MEMBER_UNINVITE_PERM:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('CLANNEWS_MEMBER_UNINVITE_PERM', $emp_a, $emp_b);
			$newsdata[] = $entry;
			break;

		case CLANNEWS_PERM_GRANT_LEADER:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_PERM_GRANT_LEADER', $emp_a, $emp_b);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_PERM_REVOKE_LEADER:
			$entry['class'] = 'cwarn';
			if ($emp_a->e_id == $emp_b->e_id)
				$entry['desc'] = lang('CLANNEWS_PERM_REVOKE_LEADER_SELF', $emp_a);
			else	$entry['desc'] = lang('CLANNEWS_PERM_REVOKE_LEADER', $emp_a, $emp_b);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_PERM_GRANT_ASSISTANT:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_PERM_GRANT_ASSISTANT', $emp_a, $emp_b);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_PERM_REVOKE_ASSISTANT:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('CLANNEWS_PERM_REVOKE_ASSISTANT', $emp_a, $emp_b);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_PERM_GRANT_MINISTER:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_PERM_GRANT_MINISTER', $emp_a, $emp_b);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_PERM_REVOKE_MINISTER:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('CLANNEWS_PERM_REVOKE_MINISTER', $emp_a, $emp_b);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_PERM_ASSISTANT_INHERIT:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_PERM_ASSISTANT_INHERIT', $emp_a, $emp_b);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_PERM_MINISTER_INHERIT:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_PERM_MINISTER_INHERIT', $emp_a, $emp_b);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_PERM_MEMBER_INHERIT:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_PERM_MEMBER_INHERIT', $emp_a, $emp_b);
			$newsdata[] = $entry;
			break;

		case CLANNEWS_PROP_CHANGE_PASSWORD:
			$entry['class'] = 'cneutral';
			$entry['desc'] = lang('CLANNEWS_PROP_CHANGE_PASSWORD', $emp_a);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_PROP_CHANGE_TITLE:
			$entry['class'] = 'cneutral';
			$entry['desc'] = lang('CLANNEWS_PROP_CHANGE_TITLE', $emp_a);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_PROP_CHANGE_URL:
			$entry['class'] = 'cneutral';
			$entry['desc'] = lang('CLANNEWS_PROP_CHANGE_URL', $emp_a);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_PROP_CHANGE_LOGO:
			$entry['class'] = 'cneutral';
			$entry['desc'] = lang('CLANNEWS_PROP_CHANGE_LOGO', $emp_a);
			$newsdata[] = $entry;
			break;

		case CLANNEWS_RECV_WAR_START:
			$entry['class'] = 'cbad';
			$entry['desc'] = lang('CLANNEWS_RECV_WAR_START', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_RECV_WAR_REQUEST:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_RECV_WAR_REQUEST', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_RECV_WAR_STOP:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_RECV_WAR_STOP', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_RECV_WAR_RETRACT:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('CLANNEWS_RECV_WAR_RETRACT', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_RECV_WAR_REJECT:
			$entry['class'] = 'cbad';
			$entry['desc'] = lang('CLANNEWS_RECV_WAR_REJECT', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_RECV_WAR_GONE:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_RECV_WAR_GONE', $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_RECV_ALLY_REQUEST:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_RECV_ALLY_REQUEST', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_RECV_ALLY_START:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_RECV_ALLY_START', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_RECV_ALLY_STOP:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('CLANNEWS_RECV_ALLY_STOP', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_RECV_ALLY_RETRACT:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('CLANNEWS_RECV_ALLY_RETRACT', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_RECV_ALLY_DECLINE:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('CLANNEWS_RECV_ALLY_DECLINE', $emp_b, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_RECV_ALLY_GONE:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('CLANNEWS_RECV_ALLY_GONE', $clan_b->c_name);
			$newsdata[] = $entry;
			break;

		case CLANNEWS_SEND_WAR_START:
			$entry['class'] = 'cbad';
			$entry['desc'] = lang('CLANNEWS_SEND_WAR_START', $emp_a, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_SEND_WAR_REQUEST:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_SEND_WAR_REQUEST', $emp_a, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_SEND_WAR_STOP:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_SEND_WAR_STOP', $emp_a, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_SEND_WAR_RETRACT:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('CLANNEWS_SEND_WAR_RETRACT', $emp_a, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_SEND_WAR_REJECT:
			$entry['class'] = 'cbad';
			$entry['desc'] = lang('CLANNEWS_SEND_WAR_REJECT', $emp_a, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_SEND_ALLY_REQUEST:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_SEND_ALLY_REQUEST', $emp_a, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_SEND_ALLY_START:
			$entry['class'] = 'cgood';
			$entry['desc'] = lang('CLANNEWS_SEND_ALLY_START', $emp_a, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_SEND_ALLY_STOP:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('CLANNEWS_SEND_ALLY_STOP', $emp_a, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_SEND_ALLY_RETRACT:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('CLANNEWS_SEND_ALLY_RETRACT', $emp_a, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		case CLANNEWS_SEND_ALLY_DECLINE:
			$entry['class'] = 'cwarn';
			$entry['desc'] = lang('CLANNEWS_SEND_ALLY_DECLINE', $emp_a, $clan_b->c_name);
			$newsdata[] = $entry;
			break;
		}
		$emp_a = NULL;
		$emp_b = NULL;
		$clan_b = NULL;
	}
	if (count($newsdata) > 0)
	{
?>
<table class="inputtable" border="1">
<tr><th><?php echo lang('CLANNEWS_COLUMN_TIME'); ?></th>
    <th colspan="2"><?php echo lang('CLANNEWS_COLUMN_EVENT'); ?></th></tr>
<?php
		foreach ($newsdata as $event)
		{
?>
<tr style="vertical-align:top"><th><?php echo $event['date']; ?></th>
    <td colspan="2"><span class="<?php echo $event['class']; ?>"><?php echo $event['desc']; ?></span></td></tr>
<?php
		}
?>
</table>
<?php
	}

	return $lastid;
}

// Prints a report of aid shipments sent between clan members
function printClanAid ($clan_a)
{
	global $emp1;
	global $db;

	$q = $db->prepare('SELECT n_id, n_time, e_id_src, e_id_dst, '.
		'n_event, n_d0, n_d1, n_d2, n_d3, n_d4, n_d5, n_d6, n_d7, n_d8 '.
		'FROM '. EMPIRE_NEWS_TABLE .' '.
		'WHERE c_id_src = ? AND n_event = ? ORDER BY n_id ASC');
	$q->bindIntValue(1, $clan_a->c_id);
	$q->bindIntValue(2, EMPNEWS_ATTACH_AID_SENDCLAN);
	$q->execute() or warning("Failed to retrieve news for aid sent within $clan_a->c_id", 1);
	$news = $q->fetchAll();
	if (count($news) == 0)
		return 0;
	$lastid = 0;

	$newsdata = array();
	foreach ($news as $event)
	{
		$entry = array();
		$lastid = $event['n_id'];

		$date = duration(CUR_TIME - $event['n_time'], 1, DURATION_HOURS);
		$entry['date'] = lang('EMPNEWS_DATE_FORMAT', $date);

		if ($event['e_id_src'])
			$emp_a = prom_empire::cached_load($event['e_id_src']);
		if ($event['e_id_dst'])
			$emp_b = prom_empire::cached_load($event['e_id_dst']);

		$entry['src'] = $emp_a;
		$entry['dest'] = $emp_b;
		$entry['trparm'] = number($event['n_d1']);
		$entry['trplnd'] = number($event['n_d2']);
		$entry['trpfly'] = number($event['n_d3']);
		$entry['trpsea'] = number($event['n_d4']);
		$entry['cash'] = money($event['n_d5']);
		$entry['runes'] = number($event['n_d6']);
		$entry['food'] = number($event['n_d7']);
		$newsdata[] = $entry;

		$emp_a = NULL;
		$emp_b = NULL;
	}
	if (count($newsdata) > 0)
	{
?>
<table class="inputtable" border="1">
<tr><th><?php echo lang('MESSAGES_COLUMN_DATE'); ?></th><th><?php echo lang('MESSAGES_COLUMN_INBOX'); ?></th><th><?php echo lang('MESSAGES_COLUMN_OUTBOX'); ?></th><th><?php echo lang($emp1->era->trparm); ?></th><th><?php echo lang($emp1->era->trplnd); ?></th><th><?php echo lang($emp1->era->trpfly); ?></th><th><?php echo lang($emp1->era->trpsea); ?></th><th><?php echo lang('ROW_CASH'); ?></th><th><?php echo lang($emp1->era->runes); ?></th><th><?php echo lang($emp1->era->food); ?></th></tr>
<?php
		foreach ($newsdata as $event)
		{
?>
<tr><td><?php echo $event['date']; ?></td>
    <td><?php echo $event['src']; ?></td>
    <td><?php echo $event['dest']; ?></td>
    <td><?php echo $event['trparm']; ?></td>
    <td><?php echo $event['trplnd']; ?></td>
    <td><?php echo $event['trpfly']; ?></td>
    <td><?php echo $event['trpsea']; ?></td>
    <td><?php echo $event['cash']; ?></td>
    <td><?php echo $event['runes']; ?></td>
    <td><?php echo $event['food']; ?></td></tr>
<?php
		}
?>
</table>
<?php
	}

	return $lastid;
}
?>
