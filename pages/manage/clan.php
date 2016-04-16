<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: clan.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'MANAGE_CLAN_TITLE';

$lock['clan1'] = -1;	// always lock the current clan
if (in_array($action, array('ally_request', 'ally_cancel', 'ally_accept', 'ally_deny', 'ally_stop', 'war_declare', 'war_request', 'war_resume', 'war_reject', 'war_stop')))
	$lock['clan2'] = fixInputNum(getFormVar('rel_clan'));
if ($action == 'ranks')
	$lock['emp2'] = fixInputNum(getFormVar('modify_rem'));
if ($action == 'invite')
	$lock['emp2'] = fixInputNum(getFormVar('invite_emp'));

page_header();

if (ROUND_FINISHED)
	unavailable(lang('MANAGE_CLAN_UNAVAILABLE_END'));
if (!ROUND_STARTED)
	unavailable(lang('MANAGE_CLAN_UNAVAILABLE_START'));
if ($emp1->is_protected())
	unavailable(lang('MANAGE_CLAN_UNAVAILABLE_PROTECT'));
if ($emp1->e_flags & EFLAG_ADMIN)
	unavailable(lang('MANAGE_CLAN_UNAVAILABLE_ADMIN'));
if (!CLAN_ENABLE)
	unavailable(lang('MANAGE_CLAN_UNAVAILABLE_CONFIG'));

if ($emp1->c_id == 0)
	unavailable(lang('CLAN_NOT_MEMBER'));
if (!in_array($emp1->e_id, array($clan1->e_id_leader, $clan1->e_id_asst, $clan1->e_id_fa1, $clan1->e_id_fa2)))
	unavailable(lang('MANAGE_CLAN_NEED_PERMISSION'));

if ($action == 'ally_request') do
{
	if (!isFormPost())
		break;
	if ($lock['clan2'] == 0)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_CLAN'));
		break;
	}
	if ($clan2->c_members == 0)
	{
		notice(lang('MANAGE_CLAN_CLAN_IS_GONE'));
		break;
	}
	// make sure we don't already have a relation with this clan
	$relations = $clan1->listRelations(0, 0, RELATION_OUTBOUND | RELATION_INBOUND, TRUE);
	foreach ($relations as $rel)
	{
		// mutual alliance in either direction?
		if (in_array($clan1->c_id, array($rel['c_id_1'], $rel['c_id_2'])) &&
			in_array($clan2->c_id, array($rel['c_id_1'], $rel['c_id_2'])) &&
			($rel['cr_flags'] == CRFLAG_ALLY | CRFLAG_MUTUAL))
		{
			notice(lang('MANAGE_CLAN_ALLY_REQUEST_ALREADY_ALLY_ACCEPTED', $clan2->c_name));
			break 2;
		}
		// you have requested an alliance with them?
		if (($clan1->c_id == $rel['c_id_1']) && ($clan2->c_id == $rel['c_id_2']) && ($rel['cr_flags'] == CRFLAG_ALLY))
		{
			notice(lang('MANAGE_CLAN_ALLY_REQUEST_ALREADY_ALLY_REQUESTED', $clan2->c_name));
			break 2;
		}
		// they have requested an alliance with you?
		if (($clan1->c_id == $rel['c_id_2']) && ($clan2->c_id == $rel['c_id_1']) && ($rel['cr_flags'] == CRFLAG_ALLY))
		{
			notice(lang('MANAGE_CLAN_ALLY_REQUEST_ALREADY_ALLY_PENDING', $clan2->c_name));
			break 2;
		}

		// you have declared war with them?
		if (($clan1->c_id == $rel['c_id_1']) && ($clan2->c_id == $rel['c_id_2']) && ($rel['cr_flags'] == CRFLAG_WAR | CRFLAG_MUTUAL))
		{
			notice(lang('MANAGE_CLAN_ALLY_REQUEST_ALREADY_WAR_DECLARED_IN', $clan2->c_name));
			break 2;
		}
		// they have declared war with you?
		if (($clan1->c_id == $rel['c_id_2']) && ($clan2->c_id == $rel['c_id_1']) && ($rel['cr_flags'] == CRFLAG_WAR | CRFLAG_MUTUAL))
		{
			notice(lang('MANAGE_CLAN_ALLY_REQUEST_ALREADY_WAR_DECLARED_OUT', $clan2->c_name));
			break 2;
		}
		// you have declared war with them, then requested peace?
		if (($clan1->c_id == $rel['c_id_1']) && ($clan2->c_id == $rel['c_id_2']) && ($rel['cr_flags'] == CRFLAG_WAR))
		{
			notice(lang('MANAGE_CLAN_ALLY_REQUEST_ALREADY_WAR_REQUESTED_IN', $clan2->c_name));
			break 2;
		}
		// they have declared war with you, then requested peace?
		if (($clan1->c_id == $rel['c_id_2']) && ($clan2->c_id == $rel['c_id_1']) && ($rel['cr_flags'] == CRFLAG_WAR))
		{
			notice(lang('MANAGE_CLAN_ALLY_REQUEST_ALREADY_WAR_REQUESTED_OUT', $clan2->c_name));
			break 2;
		}
	}

	// count all outgoing (mutual or not), as well as incoming mutual
	$allycount = $db->queryCell('SELECT COUNT(*) FROM '. CLAN_RELATION_TABLE .' WHERE (c_id_1 = ? AND cr_flags & ? != 0) OR (c_id_2 = ? AND cr_flags = ?)', array($clan1->c_id, CRFLAG_ALLY, $clan1->c_id, CRFLAG_ALLY | CRFLAG_MUTUAL));
	if ($allycount == CLAN_MAXALLY)
	{
		notice(lang('MANAGE_CLAN_ALLY_REQUEST_TOO_MANY'));
		break;
	}
	$emp_a = new prom_empire($clan2->e_id_leader);
	$emp_a->loadPartial();
	addEmpireNews(EMPNEWS_CLAN_ALLY_REQUEST, $emp1, $emp_a, 0);
	addClanNews(CLANNEWS_SEND_ALLY_REQUEST, $clan1, $emp1, $clan2);
	addClanNews(CLANNEWS_RECV_ALLY_REQUEST, $clan2, NULL, $clan1, $emp1);
	$emp_a = NULL;
	notice(lang('MANAGE_CLAN_ALLY_REQUEST_COMPLETE', $clan2->c_name));
	logevent();
	$q = $db->prepare('INSERT INTO '. CLAN_RELATION_TABLE .' (c_id_1,c_id_2,cr_flags,cr_time) VALUES (?,?,?,?)');
	$q->bindIntValue(1, $clan1->c_id);
	$q->bindIntValue(2, $clan2->c_id);
	$q->bindIntValue(3, CRFLAG_ALLY);
	$q->bindIntValue(4, CUR_TIME);
	$q->execute() or warning('Failed to add clan relation', 0);
} while (0);
if ($action == 'ally_cancel') do
{
	if (!isFormPost())
		break;
	if ($lock['clan2'] == 0)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_CLAN'));
		break;
	}
	$relid = fixInputNum(getFormVar('rel_id'));
	$q = $db->prepare('SELECT * FROM '. CLAN_RELATION_TABLE .' WHERE cr_id = ?');
	$q->bindIntValue(1, $relid);
	$q->execute() or warning('Failed to fetch clan relation', 0);
	$rel = $q->fetch();
	if (!$rel)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_RELATION'));
		break;
	}
	// non-mutual, outbound
	if (($rel['cr_flags'] != CRFLAG_ALLY) || ($rel['c_id_1'] != $clan1->c_id) || ($rel['c_id_2'] != $clan2->c_id))
	{
		notice(lang('MANAGE_CLAN_INVALID_RELATION'));
		break;
	}
	if ($rel['cr_time'] >= CUR_TIME - 3600 * CLAN_MINRELATE)
	{
		notice(lang('MANAGE_CLAN_ALLY_CANCEL_TOO_SOON'));
		break;
	}
	$emp_a = new prom_empire($clan2->e_id_leader);
	$emp_a->loadPartial();
	addEmpireNews(EMPNEWS_CLAN_ALLY_RETRACT, $emp1, $emp_a, 0);
	addClanNews(CLANNEWS_SEND_ALLY_RETRACT, $clan1, $emp1, $clan2);
	addClanNews(CLANNEWS_RECV_ALLY_RETRACT, $clan2, NULL, $clan1, $emp1);
	$emp_a = NULL;
	notice(lang('MANAGE_CLAN_ALLY_CANCEL_COMPLETE', $clan2->c_name));
	logevent();
	$q = $db->prepare('DELETE FROM '. CLAN_RELATION_TABLE .' WHERE cr_id = ?');
	$q->bindIntValue(1, $relid);
	$q->execute() or warning('Failed to remove clan relation', 0);
} while (0);
if ($action == 'ally_accept') do
{
	if (!isFormPost())
		break;
	if ($lock['clan2'] == 0)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_CLAN'));
		break;
	}
	$relid = fixInputNum(getFormVar('rel_id'));
	$q = $db->prepare('SELECT * FROM '. CLAN_RELATION_TABLE .' WHERE cr_id = ?');
	$q->bindIntValue(1, $relid);
	$q->execute() or warning('Failed to fetch clan relation', 0);
	$rel = $q->fetch();
	if (!$rel)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_RELATION'));
		break;
	}
	// non-mutual, inbound
	if (($rel['cr_flags'] != CRFLAG_ALLY) || ($rel['c_id_1'] != $clan2->c_id) || ($rel['c_id_2'] != $clan1->c_id))
	{
		notice(lang('MANAGE_CLAN_INVALID_RELATION'));
		break;
	}
	$allycount1 = $db->queryCell('SELECT COUNT(*) FROM '. CLAN_RELATION_TABLE .' WHERE (c_id_1 = ? OR c_id_2 = ?) AND cr_flags = ?', array($clan1->c_id, $clan1->c_id, CRFLAG_ALLY | CRFLAG_MUTUAL));
	$allycount2 = $db->queryCell('SELECT COUNT(*) FROM '. CLAN_RELATION_TABLE .' WHERE (c_id_1 = ? OR c_id_2 = ?) AND cr_flags = ?', array($clan2->c_id, $clan2->c_id, CRFLAG_ALLY | CRFLAG_MUTUAL));
	if ($allycount1 == CLAN_MAXALLY)
	{
		notice(lang('MANAGE_CLAN_ALLY_ACCEPT_SELF_TOO_MANY'));
		break;
	}
	if ($allycount2 == CLAN_MAXALLY)
	{
		notice(lang('MANAGE_CLAN_ALLY_ACCEPT_OTHER_TOO_MANY'));
		break;
	}
	// no time limit here
	$emp_a = new prom_empire($clan2->e_id_leader);
	$emp_a->loadPartial();
	addEmpireNews(EMPNEWS_CLAN_ALLY_START, $emp1, $emp_a, 0);
	addClanNews(CLANNEWS_SEND_ALLY_START, $clan1, $emp1, $clan2);
	addClanNews(CLANNEWS_RECV_ALLY_START, $clan2, NULL, $clan1, $emp1);
	$emp_a = NULL;
	notice(lang('MANAGE_CLAN_ALLY_ACCEPT_COMPLETE', $clan2->c_name));
	logevent();
	$q = $db->prepare('UPDATE '. CLAN_RELATION_TABLE .' SET cr_flags =  ?, cr_time = ? WHERE cr_id = ?');
	$q->bindIntValue(1, CRFLAG_ALLY | CRFLAG_MUTUAL);
	$q->bindIntValue(2, CUR_TIME);
	$q->bindIntValue(3, $relid);
	$q->execute() or warning('Failed to modify clan relation', 0);
} while (0);
if ($action == 'ally_deny') do
{
	if (!isFormPost())
		break;
	if ($lock['clan2'] == 0)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_CLAN'));
		break;
	}
	$relid = fixInputNum(getFormVar('rel_id'));
	$q = $db->prepare('SELECT * FROM '. CLAN_RELATION_TABLE .' WHERE cr_id = ?');
	$q->bindIntValue(1, $relid);
	$q->execute() or warning('Failed to fetch clan relation', 0);
	$rel = $q->fetch();
	if (!$rel)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_RELATION'));
		break;
	}
	// non-mutual, inbound
	if (($rel['cr_flags'] != CRFLAG_ALLY) || ($rel['c_id_1'] != $clan2->c_id) || ($rel['c_id_2'] != $clan1->c_id))
	{
		notice(lang('MANAGE_CLAN_INVALID_RELATION'));
		break;
	}
	// no time limit here
	$emp_a = new prom_empire($clan2->e_id_leader);
	$emp_a->loadPartial();
	addEmpireNews(EMPNEWS_CLAN_ALLY_DECLINE, $emp1, $emp_a, 0);
	addClanNews(CLANNEWS_SEND_ALLY_DECLINE, $clan1, $emp1, $clan2);
	addClanNews(CLANNEWS_RECV_ALLY_DECLINE, $clan2, NULL, $clan1, $emp1);
	$emp_a = NULL;
	notice(lang('MANAGE_CLAN_ALLY_DENY_COMPLETE', $clan2->c_name));
	logevent();
	$q = $db->prepare('DELETE FROM '. CLAN_RELATION_TABLE .' WHERE cr_id = ?');
	$q->bindIntValue(1, $relid);
	$q->execute() or warning('Failed to remove clan relation', 0);
} while (0);
if ($action == 'ally_stop') do
{
	if (!isFormPost())
		break;
	$confirm = fixInputBool(getFormVar('confirm'));
	if (!$confirm)
	{
		notice(lang('MANAGE_CLAN_ALLY_STOP_NEED_CONFIRM'));
		break;
	}
	if ($lock['clan2'] == 0)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_CLAN'));
		break;
	}
	$relid = fixInputNum(getFormVar('rel_id'));
	$q = $db->prepare('SELECT * FROM '. CLAN_RELATION_TABLE .' WHERE cr_id = ?');
	$q->bindIntValue(1, $relid);
	$q->execute() or warning('Failed to fetch clan relation', 0);
	$rel = $q->fetch();
	if (!$rel)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_RELATION'));
		break;
	}
	// mutual
	if (($rel['cr_flags'] != (CRFLAG_ALLY | CRFLAG_MUTUAL)) || (!in_array($clan1->c_id, array($rel['c_id_1'], $rel['c_id_2']))) || (!in_array($clan2->c_id, array($rel['c_id_1'], $rel['c_id_2']))))
	{
		notice(lang('MANAGE_CLAN_INVALID_RELATION'));
		break;
	}
	if ($rel['cr_time'] >= CUR_TIME - 3600 * CLAN_MINRELATE)
	{
		notice(lang('MANAGE_CLAN_ALLY_STOP_TOO_SOON'));
		break;
	}
	$emp_a = new prom_empire($clan2->e_id_leader);
	$emp_a->loadPartial();
	addEmpireNews(EMPNEWS_CLAN_ALLY_STOP, $emp1, $emp_a, 0);
	addClanNews(CLANNEWS_SEND_ALLY_STOP, $clan1, $emp1, $clan2);
	addClanNews(CLANNEWS_RECV_ALLY_STOP, $clan2, NULL, $clan1, $emp1);
	$emp_a = NULL;
	notice(lang('MANAGE_CLAN_ALLY_STOP_COMPLETE', $clan2->c_name));
	logevent();
	$q = $db->prepare('DELETE FROM '. CLAN_RELATION_TABLE .' WHERE cr_id = ?');
	$q->bindIntValue(1, $relid);
	$q->execute() or warning('Failed to remove clan relation', 0);
} while (0);
if ($action == 'war_declare') do
{
	if (!isFormPost())
		break;
	if ($lock['clan2'] == 0)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_CLAN'));
		break;
	}
	if ($clan2->c_members == 0)
	{
		notice(lang('MANAGE_CLAN_CLAN_IS_GONE'));
		break;
	}
	// make sure we don't already have a relation with this clan
	$relations = $clan1->listRelations(0, 0, RELATION_OUTBOUND | RELATION_INBOUND, TRUE);
	foreach ($relations as $rel)
	{
		// mutual alliance in either direction?
		if (in_array($clan1->c_id, array($rel['c_id_1'], $rel['c_id_2'])) &&
			in_array($clan2->c_id, array($rel['c_id_1'], $rel['c_id_2'])) &&
			($rel['cr_flags'] == CRFLAG_ALLY | CRFLAG_MUTUAL))
		{
			notice(lang('MANAGE_CLAN_WAR_DECLARE_ALREADY_ALLY_ACCEPTED', $clan2->c_name));
			break 2;
		}
		// you have requested an alliance with them?
		if (($clan1->c_id == $rel['c_id_1']) && ($clan2->c_id == $rel['c_id_2']) && ($rel['cr_flags'] == CRFLAG_ALLY))
		{
			notice(lang('MANAGE_CLAN_WAR_DECLARE_ALREADY_ALLY_REQUESTED', $clan2->c_name));
			break 2;
		}
		// they have requested an alliance with you?
		if (($clan1->c_id == $rel['c_id_2']) && ($clan2->c_id == $rel['c_id_1']) && ($rel['cr_flags'] == CRFLAG_ALLY))
		{
			notice(lang('MANAGE_CLAN_WAR_DECLARE_ALREADY_ALLY_PENDING', $clan2->c_name));
			break 2;
		}

		// you have declared war with them?
		if (($clan1->c_id == $rel['c_id_1']) && ($clan2->c_id == $rel['c_id_2']) && ($rel['cr_flags'] == CRFLAG_WAR | CRFLAG_MUTUAL))
		{
			notice(lang('MANAGE_CLAN_WAR_DECLARE_ALREADY_WAR_DECLARED_IN', $clan2->c_name));
			break 2;
		}
		// they have declared war with you?
		if (($clan1->c_id == $rel['c_id_2']) && ($clan2->c_id == $rel['c_id_1']) && ($rel['cr_flags'] == CRFLAG_WAR | CRFLAG_MUTUAL))
		{
			notice(lang('MANAGE_CLAN_WAR_DECLARE_ALREADY_WAR_DECLARED_OUT', $clan2->c_name));
			break 2;
		}
		// you have declared war with them, then requested peace?
		if (($clan1->c_id == $rel['c_id_1']) && ($clan2->c_id == $rel['c_id_2']) && ($rel['cr_flags'] == CRFLAG_WAR))
		{
			notice(lang('MANAGE_CLAN_WAR_DECLARE_ALREADY_WAR_REQUESTED_IN', $clan2->c_name));
			break 2;
		}
		// they have declared war with you, then requested peace?
		if (($clan1->c_id == $rel['c_id_2']) && ($clan2->c_id == $rel['c_id_1']) && ($rel['cr_flags'] == CRFLAG_WAR))
		{
			notice(lang('MANAGE_CLAN_WAR_DECLARE_ALREADY_WAR_REQUESTED_OUT', $clan2->c_name));
			break 2;
		}
	}
	// count all outgoing
	$warcount = $db->queryCell('SELECT COUNT(*) FROM '. CLAN_RELATION_TABLE .' WHERE c_id_1 = ? AND cr_flags & ? != 0', array($clan1->c_id, CRFLAG_WAR));
	if ($warcount == CLAN_MAXWAR)
	{
		notice(lang('MANAGE_CLAN_WAR_DECLARE_TOO_MANY'));
		break;
	}
	$emp_a = new prom_empire($clan2->e_id_leader);
	$emp_a->loadPartial();
	addEmpireNews(EMPNEWS_CLAN_WAR_START, $emp1, $emp_a, 0);
	addClanNews(CLANNEWS_SEND_WAR_START, $clan1, $emp1, $clan2);
	addClanNews(CLANNEWS_RECV_WAR_START, $clan2, NULL, $clan1, $emp1);
	$emp_a = NULL;
	notice(lang('MANAGE_CLAN_WAR_DECLARE_COMPLETE', $clan2->c_name));
	logevent();
	$q = $db->prepare('INSERT INTO '. CLAN_RELATION_TABLE .' (c_id_1,c_id_2,cr_flags,cr_time) VALUES (?,?,?,?)');
	$q->bindIntValue(1, $clan1->c_id);
	$q->bindIntValue(2, $clan2->c_id);
	$q->bindIntValue(3, CRFLAG_WAR | CRFLAG_MUTUAL);
	$q->bindIntValue(4, CUR_TIME);
	$q->execute() or warning('Failed to add clan relation', 0);
} while (0);
if ($action == 'war_request') do
{
	if (!isFormPost())
		break;
	$confirm = fixInputBool(getFormVar('confirm'));
	if (!$confirm)
	{
		notice(lang('MANAGE_CLAN_WAR_REQUEST_NEED_CONFIRM'));
		break;
	}
	if ($lock['clan2'] == 0)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_CLAN'));
		break;
	}
	$relid = fixInputNum(getFormVar('rel_id'));
	$q = $db->prepare('SELECT * FROM '. CLAN_RELATION_TABLE .' WHERE cr_id = ?');
	$q->bindIntValue(1, $relid);
	$q->execute() or warning('Failed to fetch clan relation', 0);
	$rel = $q->fetch();
	if (!$rel)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_RELATION'));
		break;
	}
	// mutual, outbound
	if (($rel['cr_flags'] != (CRFLAG_WAR | CRFLAG_MUTUAL)) || ($rel['c_id_1'] != $clan1->c_id) || ($rel['c_id_2'] != $clan2->c_id))
	{
		notice(lang('MANAGE_CLAN_INVALID_RELATION'));
		break;
	}
	if ($rel['cr_time'] >= CUR_TIME - 3600 * CLAN_MINRELATE)
	{
		notice(lang('MANAGE_CLAN_WAR_REQUEST_TOO_SOON'));
		break;
	}
	$emp_a = new prom_empire($clan2->e_id_leader);
	$emp_a->loadPartial();
	addEmpireNews(EMPNEWS_CLAN_WAR_REQUEST, $emp1, $emp_a, 0);
	addClanNews(CLANNEWS_SEND_WAR_REQUEST, $clan1, $emp1, $clan2);
	addClanNews(CLANNEWS_RECV_WAR_REQUEST, $clan2, NULL, $clan1, $emp1);
	$emp_a = NULL;
	notice(lang('MANAGE_CLAN_WAR_REQUEST_COMPLETE', $clan2->c_name));
	logevent();
	$q = $db->prepare('UPDATE '. CLAN_RELATION_TABLE .' SET cr_flags =  ?, cr_time = ? WHERE cr_id = ?');
	$q->bindIntValue(1, CRFLAG_WAR);
	$q->bindIntValue(2, CUR_TIME);
	$q->bindIntValue(3, $relid);
	$q->execute() or warning('Failed to modify clan relation', 0);
} while (0);
if ($action == 'war_resume') do
{
	if (!isFormPost())
		break;
	if ($lock['clan2'] == 0)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_CLAN'));
		break;
	}
	$relid = fixInputNum(getFormVar('rel_id'));
	$q = $db->prepare('SELECT * FROM '. CLAN_RELATION_TABLE .' WHERE cr_id = ?');
	$q->bindIntValue(1, $relid);
	$q->execute() or warning('Failed to fetch clan relation', 0);
	$rel = $q->fetch();
	if (!$rel)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_RELATION'));
		break;
	}
	// non-mutual, outbound
	if (($rel['cr_flags'] != CRFLAG_WAR) || ($rel['c_id_1'] != $clan1->c_id) || ($rel['c_id_2'] != $clan2->c_id))
	{
		notice(lang('MANAGE_CLAN_INVALID_RELATION'));
		break;
	}
	if ($rel['cr_time'] >= CUR_TIME - 3600 * CLAN_MINRELATE)
	{
		notice(lang('MANAGE_CLAN_WAR_RESUME_TOO_SOON'));
		break;
	}
	$emp_a = new prom_empire($clan2->e_id_leader);
	$emp_a->loadPartial();
	addEmpireNews(EMPNEWS_CLAN_WAR_RETRACT, $emp1, $emp_a, 0);
	addClanNews(CLANNEWS_SEND_WAR_RETRACT, $clan1, $emp1, $clan2);
	addClanNews(CLANNEWS_RECV_WAR_RETRACT, $clan2, NULL, $clan1, $emp1);
	$emp_a = NULL;
	notice(lang('MANAGE_CLAN_WAR_RESUME_COMPLETE', $clan2->c_name));
	logevent();
	$q = $db->prepare('UPDATE '. CLAN_RELATION_TABLE .' SET cr_flags =  ?, cr_time = ? WHERE cr_id = ?');
	$q->bindIntValue(1, CRFLAG_WAR | CRFLAG_MUTUAL);
	$q->bindIntValue(2, CUR_TIME);
	$q->bindIntValue(3, $relid);
	$q->execute() or warning('Failed to modify clan relation', 0);
} while (0);
if ($action == 'war_restart') do
{
	if (!isFormPost())
		break;
	$confirm = fixInputBool(getFormVar('confirm'));
	if (!$confirm)
	{
		notice(lang('MANAGE_CLAN_WAR_REJECT_NEED_CONFIRM'));
		break;
	}
	if ($lock['clan2'] == 0)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_CLAN'));
		break;
	}
	$relid = fixInputNum(getFormVar('rel_id'));
	$q = $db->prepare('SELECT * FROM '. CLAN_RELATION_TABLE .' WHERE cr_id = ?');
	$q->bindIntValue(1, $relid);
	$q->execute() or warning('Failed to fetch clan relation', 0);
	$rel = $q->fetch();
	if (!$rel)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_RELATION'));
		break;
	}
	// non-mutual, inbound
	if (($rel['cr_flags'] != CRFLAG_WAR) || ($rel['c_id_1'] != $clan2->c_id) || ($rel['c_id_2'] != $clan1->c_id))
	{
		notice(lang('MANAGE_CLAN_INVALID_RELATION'));
		break;
	}
	// count all outgoing
	$warcount = $db->queryCell('SELECT COUNT(*) FROM '. CLAN_RELATION_TABLE .' WHERE c_id_1 = ? AND cr_flags & ? != 0', array($clan1->c_id, CRFLAG_WAR));
	if ($warcount == CLAN_MAXWAR)
	{
		notice(lang('MANAGE_CLAN_WAR_REJECT_TOO_MANY'));
		break;
	}
	$emp_a = new prom_empire($clan2->e_id_leader);
	$emp_a->loadPartial();
	addEmpireNews(EMPNEWS_CLAN_WAR_REJECT, $emp1, $emp_a, 0);
	addClanNews(CLANNEWS_SEND_WAR_REJECT, $clan1, $emp1, $clan2);
	addClanNews(CLANNEWS_RECV_WAR_REJECT, $clan2, NULL, $clan1, $emp1);
	$emp_a = NULL;
	notice(lang('MANAGE_CLAN_WAR_REJECT_COMPLETE', $clan2->c_name));
	logevent();
	$q = $db->prepare('UPDATE '. CLAN_RELATION_TABLE .' SET c_id_1 = ?, c_id_2 = ?, cr_flags =  ?, cr_time = ? WHERE cr_id = ?');
	$q->bindIntValue(1, $clan1->c_id);
	$q->bindIntValue(2, $clan2->c_id);
	$q->bindIntValue(3, CRFLAG_WAR | CRFLAG_MUTUAL);
	$q->bindIntValue(4, CUR_TIME);
	$q->bindIntValue(5, $relid);
	$q->execute() or warning('Failed to modify clan relation', 0);
} while (0);
if ($action == 'war_stop') do
{
	if (!isFormPost())
		break;
	if ($lock['clan2'] == 0)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_CLAN'));
		break;
	}
	$relid = fixInputNum(getFormVar('rel_id'));
	$q = $db->prepare('SELECT * FROM '. CLAN_RELATION_TABLE .' WHERE cr_id = ?');
	$q->bindIntValue(1, $relid);
	$q->execute() or warning('Failed to fetch clan relation', 0);
	$rel = $q->fetch();
	if (!$rel)
	{
		notice(lang('MANAGE_CLAN_NO_SUCH_RELATION'));
		break;
	}
	// non-mutual, inbound
	if (($rel['cr_flags'] != CRFLAG_WAR) || ($rel['c_id_1'] != $clan2->c_id) || ($rel['c_id_2'] != $clan1->c_id))
	{
		notice(lang('MANAGE_CLAN_INVALID_RELATION'));
		break;
	}
	// no time limit here
	$emp_a = new prom_empire($clan2->e_id_leader);
	$emp_a->loadPartial();
	addEmpireNews(EMPNEWS_CLAN_WAR_STOP, $emp1, $emp_a, 0);
	addClanNews(CLANNEWS_SEND_WAR_STOP, $clan1, $emp1, $clan2);
	addClanNews(CLANNEWS_RECV_WAR_STOP, $clan2, NULL, $clan1, $emp1);
	$emp_a = NULL;
	notice(lang('MANAGE_CLAN_WAR_STOP_COMPLETE', $clan2->c_name));
	logevent();
	$q = $db->prepare('DELETE FROM '. CLAN_RELATION_TABLE .' WHERE cr_id = ?');
	$q->bindIntValue(1, $relid);
	$q->execute() or warning('Failed to remove clan relation', 0);
} while (0);
if ($action == 'password') do
{
	if (!isFormPost())
		break;
	if (!in_array($emp1->e_id, array($clan1->e_id_leader, $clan1->e_id_asst)))
	{
		notice(lang('MANAGE_CLAN_PASSWORD_NEED_PERMISSION'));
		break;
	}
	$newpass = getFormVar('new_password');
	$chkpass = getFormVar('new_password_verify');
	if (empty($newpass))
	{
		notice(lang('INPUT_NEED_PASSWORD'));
		break;
	}
	if ($newpass != $chkpass)
	{
		notice(lang('INPUT_PASSWORD_MISMATCH'));
		break;
	}
	$clan1->setPassword($newpass);
	addClanNews(CLANNEWS_PROP_CHANGE_PASSWORD, $clan1, $emp1);
	notice(lang('MANAGE_CLAN_PASSWORD_COMPLETE'));
	logevent();
} while (0);
if ($action == 'logo') do
{
	if (!isFormPost())
		break;
	if (!in_array($emp1->e_id, array($clan1->e_id_leader, $clan1->e_id_asst)))
	{
		notice(lang('MANAGE_CLAN_LOGO_NEED_PERMISSION'));
		break;
	}
	$newlogo = htmlspecialchars(getFormVar('new_logo'));
	if (strlen($newlogo) > 255)
	{
		notice(lang('MANAGE_CLAN_LOGO_TOO_LONG'));
		break;
	}
	if ((strlen($newlogo) > 0) && !validate_url($newlogo))
	{
		notice(lang('MANAGE_CLAN_LOGO_INVALID'));
		break;
	}
	$clan1->c_pic = $newlogo;
	addClanNews(CLANNEWS_PROP_CHANGE_LOGO, $clan1, $emp1);
	notice(lang('MANAGE_CLAN_LOGO_COMPLETE'));
	logevent(varlist(array('newlogo'), get_defined_vars()));
} while (0);
if ($action == 'title') do
{
	if (!isFormPost())
		break;
	if (!in_array($emp1->e_id, array($clan1->e_id_leader, $clan1->e_id_asst)))
	{
		notice(lang('MANAGE_CLAN_TITLE_NEED_PERMISSION'));
		break;
	}
	$newtitle = htmlspecialchars(getFormVar('new_title'));
	if (strlen($newtitle) == 0)
	{
		notice(lang('MANAGE_CLAN_TITLE_REQUIRED'));
		break;
	}
	if (strlen($newtitle) > 255)
	{
		notice(lang('MANAGE_CLAN_TITLE_TOO_LONG'));
		break;
	}
	if (lang_isset($newtitle))
	{
		notice(lang('MANAGE_CLAN_TITLE_INVALID'));
		break;
	}
	$clan1->c_title = $newtitle;
	addClanNews(CLANNEWS_PROP_CHANGE_TITLE, $clan1, $emp1);
	notice(lang('MANAGE_CLAN_TITLE_COMPLETE'));
	logevent(varlist(array('newtitle'), get_defined_vars()));
}  while (0);
if ($action == 'url') do
{
	if (!isFormPost())
		break;
	if (!in_array($emp1->e_id, array($clan1->e_id_leader, $clan1->e_id_asst)))
	{
		notice(lang('MANAGE_CLAN_URL_NEED_PERMISSION'));
		break;
	}
	$newurl = htmlspecialchars(getFormVar('new_url'));
	if (strlen($newurl) > 255)
	{
		notice(lang('MANAGE_CLAN_URL_TOO_LONG'));
		break;
	}
	if ((strlen($newurl) > 0) && !validate_url($newurl))
	{
		notice(lang('MANAGE_CLAN_URL_INVALID'));
		break;
	}
	$clan1->c_url = $newurl;
	addClanNews(CLANNEWS_PROP_CHANGE_URL, $clan1, $emp1);
	notice(lang('MANAGE_CLAN_URL_COMPLETE'));
	logevent(varlist(array('newurl'), get_defined_vars()));
} while (0);
if ($action == 'ranks') do
{
	if (!isFormPost())
		break;
	if (!in_array($emp1->e_id, array($clan1->e_id_leader, $clan1->e_id_asst)))
	{
		notice(lang('MANAGE_CLAN_RANKS_NEED_PERMISSION'));
		break;
	}
	if ($lock['emp2'] != 0) do
	{
		if ($emp2->c_id != $clan1->c_id)
		{
			notice(lang('MANAGE_CLAN_RANKS_REMOVE_NOT_IN_CLAN'));
			break;
		}
		if (in_array($emp2->e_id, array($clan1->e_id_leader, $clan1->e_id_asst, $clan1->e_id_fa1, $clan1->e_id_fa2)))
		{
			notice(lang('MANAGE_CLAN_RANKS_REMOVE_IS_SPECIAL'));
			break;
		}
		addEmpireNews(EMPNEWS_CLAN_REMOVE, $emp1, $emp2, 0);
		addClanNews(CLANNEWS_MEMBER_REMOVE, $clan1, $emp2, NULL, $emp1);
		$emp2->c_id = 0;
		$emp2->effects->m_clan = 3600 * CLAN_MINREJOIN;
		$emp2->e_sharing = 0;
		$emp2->save();	// save clan affiliation, prevent them from being assigned a rank below
		$clan1->c_members--;
		notice(lang('MANAGE_CLAN_RANKS_REMOVE_COMPLETE', $emp2));
	} while (0);
	$leader = fixInputNum(getFormVar('modify_ldr'));
	$asst = fixInputNum(getFormVar('modify_ast'));
	$fa1 = fixInputNum(getFormVar('modify_fa1'));
	$fa2 = fixInputNum(getFormVar('modify_fa2'));

	// do we have any privileges to change?
	if (($leader == $clan1->e_id_leader) && ($asst == $clan1->e_id_asst) && ($fa1 == $clan1->e_id_fa1) && ($fa2 == $clan1->e_id_fa2))
		break;

	logevent(varlist(array('leader', 'asst', 'fa1', 'fa2'), get_defined_vars()));

	if (($emp1->e_id != $clan1->e_id_leader) && ($leader != $clan1->e_id_leader))
	{
		notice(lang('MANAGE_CLAN_RANKS_CHANGE_LEADER_PERMISSION'));
		break;
	}
	if (($emp1->e_id != $clan1->e_id_leader) && ($asst != $clan1->e_id_asst))
	{
		notice(lang('MANAGE_CLAN_RANKS_CHANGE_ASST_PERMISSION'));
		break;
	}

	// first, if any ranks are changing, remove whoever was there before
	if (($clan1->e_id_asst) && ($clan1->e_id_asst != $asst))
	{
		$emp_a = new prom_empire($clan1->e_id_asst);
		$emp_a->loadPartial();
		addEmpireNews(EMPNEWS_CLAN_REVOKE_ASSISTANT, $emp1, $emp_a, 0);
		addClanNews(CLANNEWS_PERM_REVOKE_ASSISTANT, $clan1, $emp_a, NULL, $emp1);
		$clan1->e_id_asst = 0;
		notice(lang('MANAGE_CLAN_RANKS_ASST_REMOVED', $emp_a, $clan1->c_name));
		logevent("remove asst $clan1->e_id_asst");
		$emp_a = NULL;
	}
	if (($clan1->e_id_fa1) && ($clan1->e_id_fa1 != $fa1))
	{
		$emp_a = new prom_empire($clan1->e_id_fa1);
		$emp_a->loadPartial();
		addEmpireNews(EMPNEWS_CLAN_REVOKE_MINISTER, $emp1, $emp_a, 0);
		addClanNews(CLANNEWS_PERM_REVOKE_MINISTER, $clan1, $emp_a, NULL, $emp1);
		$clan1->e_id_fa1 = 0;
		notice(lang('MANAGE_CLAN_RANKS_FA_REMOVED', $emp_a, $clan1->c_name));
		logevent("remove fa1 $clan1->e_id_fa1");
		$emp_a = NULL;
	}
	if (($clan1->e_id_fa2) && ($clan1->e_id_fa2 != $fa2))
	{
		$emp_a = new prom_empire($clan1->e_id_fa2);
		$emp_a->loadPartial();
		addEmpireNews(EMPNEWS_CLAN_REVOKE_MINISTER, $emp1, $emp_a, 0);
		addClanNews(CLANNEWS_PERM_REVOKE_MINISTER, $clan1, $emp_a, NULL, $emp1);
		$clan1->e_id_fa2 = 0;
		notice(lang('MANAGE_CLAN_RANKS_FA_REMOVED', $emp_a, $clan1->c_name));
		logevent("remove fa2 $clan1->e_id_fa2");
		$emp_a = NULL;
	}
	// gotta do this one last, since this never actually goes empty
	if ($clan1->e_id_leader != $leader) do
	{
		if (!$leader)
		{
			notice(lang('MANAGE_CLAN_RANKS_NEED_LEADER'));
			break;
		}
		if (in_array($leader, array($clan1->e_id_asst, $clan1->e_id_fa1, $clan1->e_id_fa2)))
		{
			notice(lang('MANAGE_CLAN_RANKS_CONFLICT'));
			break;
		}
		$emp_a = new prom_empire($leader);
		if (!$emp_a->loadPartial())
		{
			$emp_a = NULL;
			notice(lang('MANAGE_CLAN_RANKS_LEADER_GONE'));
			break;
		}
		if ($emp_a->c_id != $clan1->c_id)
		{
			$emp_a = NULL;
			notice(lang('MANAGE_CLAN_RANKS_WRONG_CLAN'));
			break;
		}
		logevent("change leader $clan1->e_id_leader");
		$clan1->e_id_leader = $leader;
		addEmpireNews(EMPNEWS_CLAN_GRANT_LEADER, $emp1, $emp_a, 0);
		addClanNews(CLANNEWS_PERM_REVOKE_LEADER, $clan1, $emp1, NULL, $emp1);
		addClanNews(CLANNEWS_PERM_GRANT_LEADER, $clan1, $emp_a, NULL, $emp1);
		notice(lang('MANAGE_CLAN_RANKS_LEADER_ADDED', $emp_a, $clan1->c_name));
		$emp_a = NULL;
	} while (0);
	// then, assign all of the new positions
	if (($clan1->e_id_asst != $asst) && ($asst)) do
	{
		if (in_array($asst, array($clan1->e_id_leader, $clan1->e_id_fa1, $clan1->e_id_fa2)))
		{
			notice(lang('MANAGE_CLAN_RANKS_CONFLICT'));
			break;
		}
		$emp_a = new prom_empire($asst);
		if (!$emp_a->loadPartial())
		{
			$emp_a = NULL;
			notice(lang('MANAGE_CLAN_RANKS_ASST_GONE'));
			break;
		}
		if ($emp_a->c_id != $clan1->c_id)
		{
			$emp_a = NULL;
			notice(lang('MANAGE_CLAN_RANKS_WRONG_CLAN'));
			break;
		}
		$clan1->e_id_asst = $asst;
		addEmpireNews(EMPNEWS_CLAN_GRANT_ASSISTANT, $emp1, $emp_a, 0);
		addClanNews(CLANNEWS_PERM_GRANT_ASSISTANT, $clan1, $emp_a, NULL, $emp1);
		notice(lang('MANAGE_CLAN_RANKS_ASST_ADDED', $emp_a, $clan1->c_name));
		logevent("asst");
		$emp_a = NULL;
	} while (0);
	if (($clan1->e_id_fa1 != $fa1) && ($fa1)) do
	{
		if (in_array($fa1, array($clan1->e_id_leader, $clan1->e_id_asst, $clan1->e_id_fa2)))
		{
			notice(lang('MANAGE_CLAN_RANKS_CONFLICT'));
			break;
		}
		$emp_a = new prom_empire($fa1);
		if (!$emp_a->loadPartial())
		{
			$emp_a = NULL;
			notice(lang('MANAGE_CLAN_RANKS_FA_GONE'));
			break;
		}
		if ($emp_a->c_id != $clan1->c_id)
		{
			$emp_a = NULL;
			notice(lang('MANAGE_CLAN_RANKS_WRONG_CLAN'));
			break;
		}
		$clan1->e_id_fa1 = $fa1;
		addEmpireNews(EMPNEWS_CLAN_GRANT_MINISTER, $emp1, $emp_a, 0);
		addClanNews(CLANNEWS_PERM_GRANT_MINISTER, $clan1, $emp_a, NULL, $emp1);
		notice(lang('MANAGE_CLAN_RANKS_FA_ADDED', $emp_a, $clan1->c_name));
		logevent("fa1");
		$emp_a = NULL;
	} while (0);
	if (($clan1->e_id_fa2 != $fa2) && ($fa2)) do
	{
		if (in_array($fa2, array($clan1->e_id_leader, $clan1->e_id_asst, $clan1->e_id_fa1)))
		{
			notice(lang('MANAGE_CLAN_RANKS_CONFLICT'));
			break;
		}
		$emp_a = new prom_empire($fa2);
		if (!$emp_a->loadPartial())
		{
			$emp_a = NULL;
			notice(lang('MANAGE_CLAN_RANKS_FA_GONE'));
			break;
		}
		if ($emp_a->c_id != $clan1->c_id)
		{
			$emp_a = NULL;
			notice(lang('MANAGE_CLAN_RANKS_WRONG_CLAN'));
			break;
		}
		$clan1->e_id_fa2 = $fa2;
		addEmpireNews(EMPNEWS_CLAN_GRANT_MINISTER, $emp1, $emp_a, 0);
		addClanNews(CLANNEWS_PERM_GRANT_MINISTER, $clan1, $emp_a, NULL, $emp1);
		notice(lang('MANAGE_CLAN_RANKS_FA_ADDED', $emp_a, $clan1->c_name));
		logevent("fa2");
		$emp_a = NULL;
	} while (0);
} while (0);
if ($action == 'invite') do
{
	if (!isFormPost())
		break;
	if ($lock['emp2'] == 0)
	{
		notice(lang('INPUT_EMPIRE_ID'));
		break;
	}
	if ($emp2->e_land == 0)
	{
		notice(lang('MANAGE_CLAN_INVITE_DEAD'));
		break;
	}
	if ($emp2->u_id == 0)
	{
		notice(lang('MANAGE_CLAN_INVITE_DELETED'));
		break;
	}
	if ($emp2->e_flags & EFLAG_ADMIN)
	{
		notice(lang('MANAGE_CLAN_INVITE_ADMIN'));
		break;
	}
	if ($emp2->e_flags & EFLAG_DISABLE)
	{
		notice(lang('MANAGE_CLAN_INVITE_DISABLED'));
		break;
	}
	$is_perm = fixInputBool(getFormVar('invite_perm'));
	if ($is_perm && !in_array($emp1->e_id, array($clan1->e_id_leader, $clan1->e_id_asst)))
	{
		notice(lang('MANAGE_CLAN_INVITE_PERM_NEED_PERMISSION'));
		break;
	}
	if (!$is_perm)
	{
		if ($emp2->c_id == $clan1->c_id)
		{
			notice(lang('MANAGE_CLAN_INVITE_ALREADY_MEMBER'));
			break;
		}
		if ($emp2->c_id != 0)
		{
			notice(lang('MANAGE_CLAN_INVITE_WRONG_CLAN'));
			break;
		}
	}
	$q = $db->prepare('SELECT * FROM '. CLAN_INVITE_TABLE .' WHERE c_id = ? AND e_id_2 = ?');
	$q->bindIntValue(1, $clan1->c_id);
	$q->bindIntValue(2, $emp2->e_id);
	$q->execute();
	$invites = $q->fetchAll();
	$upgrade = count($invites);
	if ($upgrade > 0)
	{
		$invite = $invites[0];
		if ($invite['ci_flags'] & CIFLAG_PERM)
		{
			notice(lang('MANAGE_CLAN_INVITE_ALREADY_PERM'));
			break;
		}
		elseif (!$is_perm)
		{
			notice(lang('MANAGE_CLAN_INVITE_ALREADY_INVITED'));
			break;
		}
		// empire is not whitelisted, attempting to add whitelist entry
		$q = $db->prepare('UPDATE '. CLAN_INVITE_TABLE .' SET e_id_1 = ?, ci_flags = ?, ci_time = ? WHERE ci_id = ?');
		$q->bindIntValue(1, $emp1->e_id);
		$q->bindIntValue(2, CIFLAG_PERM);
		$q->bindIntValue(3, CUR_TIME);
		$q->bindIntValue(4, $invite['ci_id']);
		$q->execute() or warning('Failed to upgrade clan invite to whitelist', 0);
		addEmpireNews(EMPNEWS_CLAN_INVITE_PERM, $emp1, $emp2, 0);
		addClanNews(CLANNEWS_MEMBER_INVITE_PERM, $clan1, $emp1, NULL, $emp2);
		notice(lang('MANAGE_CLAN_INVITE_COMPLETE_PERM', $emp2, $clan1->c_name));
		logevent(varlist(array('is_perm', 'upgrade'), get_defined_vars()));
	}
	else
	{
		$q = $db->prepare('INSERT INTO '. CLAN_INVITE_TABLE .' (c_id,e_id_1,e_id_2,ci_flags,ci_time) VALUES (?,?,?,?,?)');
		$q->bindIntValue(1, $clan1->c_id);
		$q->bindIntValue(2, $emp1->e_id);
		$q->bindIntValue(3, $emp2->e_id);
		$q->bindIntValue(4, $is_perm ? CIFLAG_PERM : 0);
		$q->bindIntValue(5, CUR_TIME);
		$q->execute() or warning('Failed to add clan invite', 0);
		if ($is_perm)
		{
			addEmpireNews(EMPNEWS_CLAN_INVITE_PERM, $emp1, $emp2, 0);
			addClanNews(CLANNEWS_MEMBER_INVITE_PERM, $clan1, $emp1, NULL, $emp2);
			notice(lang('MANAGE_CLAN_INVITE_COMPLETE_PERM', $emp2, $clan1->c_name));
		}
		else
		{
			addEmpireNews(EMPNEWS_CLAN_INVITE_TEMP, $emp1, $emp2, 0);
			addClanNews(CLANNEWS_MEMBER_INVITE_TEMP, $clan1, $emp1, NULL, $emp2);
			notice(lang('MANAGE_CLAN_INVITE_COMPLETE_TEMP', $emp2, $clan1->c_name));
		}
		logevent(varlist(array('is_perm', 'upgrade'), get_defined_vars()));
	}
} while (0);
if ($action == 'uninvite') do
{
	if (!isFormPost())
		break;
	$inv_id = fixInputNum(getFormVar('uninvite_id'));
	$q = $db->prepare('SELECT * FROM '. CLAN_INVITE_TABLE .' WHERE ci_id = ?');
	$q->bindIntValue(1, $inv_id);
	$q->execute() or warning('Failed to fetch clan invite', 0);
	$invites = $q->fetchAll();
	if (count($invites) == 0)
	{
		notice(lang('MANAGE_CLAN_UNINVITE_NOT_EXIST'));
		break;
	}
	$invite = $invites[0];
	if ($invite['c_id'] != $clan1->c_id)
	{
		notice(lang('MANAGE_CLAN_UNINVITE_WRONG_CLAN'));
		break;
	}
	$is_perm = fixInputBool($invite['ci_flags'] & CIFLAG_PERM);
	if (($is_perm) && !in_array($emp1->e_id, array($clan1->e_id_leader, $clan1->e_id_asst)))
	{
		notice(lang('MANAGE_CLAN_UNINVITE_PERM_NEED_PERMISSION'));
		break;
	}
	$q = $db->prepare('DELETE FROM '. CLAN_INVITE_TABLE .' WHERE ci_id = ?');
	$q->bindIntValue(1, $inv_id);
	$q->execute() or warning('Failed to delete clan invite', 0);
	$emp_a = new prom_empire($invite['e_id_2']);
	$emp_a->loadPartial();
	if ($is_perm)
	{
		addEmpireNews(EMPNEWS_CLAN_UNINVITE_PERM, $emp1, $emp_a, 0);
		addClanNews(CLANNEWS_MEMBER_UNINVITE_PERM, $clan1, $emp1, NULL, $emp_a);
	}
	else
	{
		addEmpireNews(EMPNEWS_CLAN_UNINVITE_TEMP, $emp1, $emp_a, 0);
		addClanNews(CLANNEWS_MEMBER_UNINVITE_TEMP, $clan1, $emp1, NULL, $emp_a);
	}
	notice(lang('MANAGE_CLAN_UNINVITE_COMPLETE', $emp_a));
	$emp_a = NULL;
	logevent(varlist(array('is_perm'), get_defined_vars()));
} while (0);
notices();

$allies1 = $clan1->listRelations(CRFLAG_ALLY | CRFLAG_MUTUAL, CRFLAG_ALLY | CRFLAG_MUTUAL, RELATION_OUTBOUND | RELATION_INBOUND, TRUE);
$allies2 = $clan1->listRelations(CRFLAG_ALLY | CRFLAG_MUTUAL, CRFLAG_ALLY, RELATION_OUTBOUND, TRUE);
$allies3 = $clan1->listRelations(CRFLAG_ALLY | CRFLAG_MUTUAL, CRFLAG_ALLY, RELATION_INBOUND, TRUE);
$wars1 = $clan1->listRelations(CRFLAG_WAR | CRFLAG_MUTUAL, CRFLAG_WAR | CRFLAG_MUTUAL, RELATION_OUTBOUND, TRUE);
$wars2 = $clan1->listRelations(CRFLAG_WAR | CRFLAG_MUTUAL, CRFLAG_WAR | CRFLAG_MUTUAL, RELATION_INBOUND, TRUE);
$wars3 = $clan1->listRelations(CRFLAG_WAR | CRFLAG_MUTUAL, CRFLAG_WAR, RELATION_OUTBOUND, TRUE);
$wars4 = $clan1->listRelations(CRFLAG_WAR | CRFLAG_MUTUAL, CRFLAG_WAR, RELATION_INBOUND, TRUE);

$q = $db->prepare('SELECT c_id,c_name FROM '. CLAN_TABLE .' WHERE c_members > 0 AND c_id != ? ORDER BY c_id ASC');
$q->bindIntValue(1, $clan1->c_id);
$q->execute() or warning('Failed to fetch clan list', 0);
$allclans = $q->fetchAll();

if ($clan1->c_url)
{
?><a href="<?php echo $clan1->c_url; ?>" rel="external"><?php
}
if ($clan1->c_pic)
{
?><img src="<?php echo $clan1->c_pic; ?>" style="border:0" alt="<?php echo lang('CLAN_LINK_LABEL', $clan1->c_title); ?>" /><?php
}
elseif ($clan1->c_url)
	echo lang('CLAN_LINK_LABEL', $clan1->c_title);
if ($clan1->c_url)
{
?></a><?php
}
?>
<br />
<table style="background-color:#1F1F1F">
<tr><th class="era<?php echo $emp1->e_era; ?>"><?php echo lang('MANAGE_CLAN_HEADER', $clan1->c_name); ?></th></tr>
</table>
<?php
// All special ranks can change relations
?>
<h3><?php echo lang('CLAN_RELATIONS_HEADER', $clan1->c_title); ?></h3>
<table class="inputtable">
<tr><th style="width:50%"><span class="cgood"><?php echo lang('CLAN_ALLY_LABEL'); ?></span><br /><?php echo lang('CLAN_ALLY_DESC'); ?></th>
    <th style="width:50%"><span class="cbad"><?php echo lang('CLAN_WAR_LABEL'); ?></span><br /><?php echo lang('CLAN_WAR_DESC'); ?></th></tr>
<?php
	$allies = array();
	foreach ($allies1 as $id => $rel)
	{
		$txt = '<b>'. $cnames[$id] .'</b><br />'. lang('MANAGE_CLAN_RELATION_STATUS', 'MANAGE_CLAN_RELATION_STATUS_ALLY_MUTUAL', $user1->customdate($rel['cr_time']))
			.'<form method="post" action="?location=manage/clan"><div><input type="hidden" name="action" value="ally_stop" />'
			.'<input type="hidden" name="rel_id" value="'. $rel['cr_id'] .'" /><input type="hidden" name="rel_clan" value="'. $id .'" />'
			.'<input type="submit" value="'. lang('MANAGE_CLAN_ALLY_STOP_SUBMIT') .'" /><br />'. checkbox('confirm', lang('MANAGE_CLAN_ALLY_STOP_CONFIRM')) .'</div></form>';
		$allies[] = $txt;
	}
	foreach ($allies2 as $id => $rel)
	{
		$txt = '<b>'. $cnames[$id] .'</b><br />'. lang('MANAGE_CLAN_RELATION_STATUS', 'MANAGE_CLAN_RELATION_STATUS_ALLY_OUTBOUND', $user1->customdate($rel['cr_time']))
			.'<form method="post" action="?location=manage/clan"><div><input type="hidden" name="action" value="ally_cancel" />'
			.'<input type="hidden" name="rel_id" value="'. $rel['cr_id'] .'" /><input type="hidden" name="rel_clan" value="'. $id .'" />'
			.'<input type="submit" value="'. lang('MANAGE_CLAN_ALLY_CANCEL_SUBMIT') .'" /></div></form>';
		$allies[] = $txt;
	}
	foreach ($allies3 as $id => $rel)
	{
		$txt = '<b>'. $cnames[$id] .'</b><br />'. lang('MANAGE_CLAN_RELATION_STATUS', 'MANAGE_CLAN_RELATION_STATUS_ALLY_INBOUND', $user1->customdate($rel['cr_time']))
			.'<form method="post" action="?location=manage/clan"><div><input type="hidden" name="action" value="ally_accept" />'
			.'<input type="hidden" name="rel_id" value="'. $rel['cr_id'] .'" /><input type="hidden" name="rel_clan" value="'. $id .'" />'
			.'<input type="submit" value="'. lang('MANAGE_CLAN_ALLY_ACCEPT_SUBMIT') .'" /></div></form>'
			.'<form method="post" action="?location=manage/clan"><div><input type="hidden" name="action" value="ally_deny" />'
			.'<input type="hidden" name="rel_id" value="'. $rel['cr_id'] .'" /><input type="hidden" name="rel_clan" value="'. $id .'" />'
			.'<input type="submit" value="'. lang('MANAGE_CLAN_ALLY_DENY_SUBMIT') .'" /></div></form>';
		$allies[] = $txt;
	}
	if (count($allies1) + count($allies2) < CLAN_MAXALLY)
	{
		$txt = '<form method="post" action="?location=manage/clan"><div>'. lang('MANAGE_CLAN_ALLY_REQUEST_LABEL') .' ';
		$clanlist = array();
		$clanlist[0] = '';
		foreach ($allclans as $clan)
		{
			$id = $clan['c_id'];
			$name = $clan['c_name'];
			if (isset($allies1[$id]) || isset($allies2[$id]) || isset($allies3[$id]) || isset($wars1[$id]) || isset($wars2[$id]) || isset($wars3[$id]) || isset($wars4[$id]))
				continue;
			$clanlist[$id] = $name;
		}
		$txt .= optionlist('rel_clan', $clanlist);
		$txt .= '<br /><input type="hidden" name="action" value="ally_request" /><input type="submit" value="'. lang('MANAGE_CLAN_ALLY_REQUEST_SUBMIT') .'" /></div></form>';
		for ($i = count($allies1) + count($allies2); $i < CLAN_MAXALLY; $i++)
			$allies[] = $txt;
	}

	$wars = array();
	foreach ($wars1 as $id => $rel)
	{
		$txt = '<b>'. $cnames[$id] .'</b><br />'. lang('MANAGE_CLAN_RELATION_STATUS', 'MANAGE_CLAN_RELATION_STATUS_WAR_MUTUAL_OUTBOUND', $user1->customdate($rel['cr_time']))
			.'<form method="post" action="?location=manage/clan"><div><input type="hidden" name="action" value="war_request" />'
			.'<input type="hidden" name="rel_id" value="'. $rel['cr_id'] .'" /><input type="hidden" name="rel_clan" value="'. $id .'" />'
			.'<input type="submit" value="'. lang('MANAGE_CLAN_WAR_REQUEST_SUBMIT') .'" /><br />'. checkbox('confirm', lang('MANAGE_CLAN_WAR_REQUEST_CONFIRM')) .'</div></form>';
		$wars[] = $txt;
	}
	foreach ($wars2 as $id => $rel)
	{
		$txt = '<b>'. $cnames[$id] .'</b><br />'. lang('MANAGE_CLAN_RELATION_STATUS', 'MANAGE_CLAN_RELATION_STATUS_WAR_MUTUAL_INBOUND', $user1->customdate($rel['cr_time']));
		$wars[] = $txt;
	}
	foreach ($wars3 as $id => $rel)
	{
		$txt = '<b>'. $cnames[$id] .'</b><br />'. lang('MANAGE_CLAN_RELATION_STATUS', 'MANAGE_CLAN_RELATION_STATUS_WAR_OUTBOUND', $user1->customdate($rel['cr_time']))
			.'<form method="post" action="?location=manage/clan"><div><input type="hidden" name="action" value="war_resume" />'
			.'<input type="hidden" name="rel_id" value="'. $rel['cr_id'] .'" /><input type="hidden" name="rel_clan" value="'. $id .'" />'
			.'<input type="submit" value="'. lang('MANAGE_CLAN_WAR_RESUME_SUBMIT') .'" /></div></form>';
		$wars[] = $txt;
	}
	foreach ($wars4 as $id => $rel)
	{
		$txt = '<b>'. $cnames[$id] .'</b><br />'. lang('MANAGE_CLAN_RELATION_STATUS', 'MANAGE_CLAN_RELATION_STATUS_WAR_INBOUND', $user1->customdate($rel['cr_time']))
			.'<form method="post" action="?location=manage/clan"><div><input type="hidden" name="action" value="war_stop" />'
			.'<input type="hidden" name="rel_id" value="'. $rel['cr_id'] .'" /><input type="hidden" name="rel_clan" value="'. $id .'" />'
			.'<input type="submit" value="'. lang('MANAGE_CLAN_WAR_STOP_SUBMIT') .'" /></div></form>'
			.'<form method="post" action="?location=manage/clan"><div><input type="hidden" name="action" value="war_restart" />'
			.'<input type="hidden" name="rel_id" value="'. $rel['cr_id'] .'" /><input type="hidden" name="rel_clan" value="'. $id .'" />'
			.'<input type="submit" value="'. lang('MANAGE_CLAN_WAR_RESTART_SUBMIT') .'" /><br />'. checkbox('confirm', lang('MANAGE_CLAN_WAR_RESTART_CONFIRM')) .'</div></form>';
		$wars[] = $txt;
	}
	if (count($wars1) + count($wars3) < CLAN_MAXWAR)
	{
		$txt = '<form method="post" action="?location=manage/clan"><div>'. lang('MANAGE_CLAN_WAR_DECLARE_LABEL') .' ';
		$clanlist = array();
		$clanlist[0] = '';
		foreach ($allclans as $clan)
		{
			$id = $clan['c_id'];
			$name = $clan['c_name'];
			if (isset($allies1[$id]) || isset($allies2[$id]) || isset($allies3[$id]) || isset($wars1[$id]) || isset($wars2[$id]) || isset($wars3[$id]) || isset($wars4[$id]))
				continue;
			$clanlist[$id] = $name;
		}
		$txt .= optionlist('rel_clan', $clanlist);
		$txt .= '<br /><input type="hidden" name="action" value="war_declare" /><input type="submit" value="'. lang('MANAGE_CLAN_WAR_DECLARE_SUBMIT') .'" /></div></form>';
		for ($i = count($wars1) + count($wars3); $i < CLAN_MAXWAR; $i++)
			$wars[] = $txt;
	}

	$lines = max(count($allies), count($wars));
	for ($i = 0; $i < $lines; $i++)
	{
		echo '<tr>';
		if (isset($allies[$i]))
			echo '<td class="ac">'. $allies[$i] .'</td>';
		else	echo '<td></td>';
		if (isset($wars[$i]))
			echo '<td class="ac">'. $wars[$i] .'</td>';
		else	echo '<td></td>';
		echo '</tr>'."\n";
	}
?>
<tr><td colspan="2" class="ac"><?php echo lang('MANAGE_CLAN_RELATION_WARNING', CLAN_MINRELATE); ?></td></tr>
</table>
<br />
<?php
// Only leader and assistant can change ranks and other clan properties (or view the empire status of other members, if enabled)
if (in_array($emp1->e_id, array($clan1->e_id_leader, $clan1->e_id_asst)))
{
	$q = $db->prepare('SELECT e_id,e_name,e_sharing,e_rank,e_networth FROM '. EMPIRE_TABLE .' WHERE c_id = ?');
	$q->bindIntValue(1, $clan1->c_id);
	$q->execute() or warning('Failed to fetch clan member list', 0);
	$emps = $q->fetchAll();
	$members = array();
	foreach ($emps as $data)
	{
		$emp_a = new prom_empire($data['e_id']);
		$emp_a->initdata($data);
		$members[] = $emp_a;
		$emp_a = NULL;
	}
?>
<?php echo lang('CLAN_MEMBERS_HEADER', $clan1->c_title, $clan1->c_members); ?><br /><br />
<form method="post" action="?location=manage/clan">
<table class="inputtable">
<caption><b><?php echo lang('CLAN_MEMBERS_LABEL'); ?></b></caption>
<tr><th><?php echo lang('CLAN_LEADER_LABEL'); ?></th>
    <th><?php echo lang('CLAN_ASSISTANT_LABEL'); ?></th>
    <th colspan="2"><?php echo lang('CLAN_FA_LABEL'); ?></th>
    <th><?php echo lang('COLUMN_EMPIRE'); ?></th>
    <th><?php echo lang('COLUMN_NETWORTH'); ?></th>
    <th><?php echo lang('COLUMN_RANK'); ?></th>
    <th><?php echo lang('COLUMN_SHARING'); ?></th>
    <th><?php echo lang('MANAGE_CLAN_RELATION_REMOVE'); ?></th></tr>
<?php
	foreach ($members as $emp_a)
	{
?>
<tr><td class="ac"><?php echo radiobutton('modify_ldr', '', $emp_a->e_id, ($emp_a->e_id == $clan1->e_id_leader), ($clan1->e_id_leader == $emp1->e_id)); ?></td>
    <td class="ac"><?php echo radiobutton('modify_ast', '', $emp_a->e_id, ($emp_a->e_id == $clan1->e_id_asst), ($clan1->e_id_leader == $emp1->e_id)); ?></td>
    <td class="ac"><?php echo radiobutton('modify_fa1', '', $emp_a->e_id, ($emp_a->e_id == $clan1->e_id_fa1)); ?></td>
    <td class="ac"><?php echo radiobutton('modify_fa2', '', $emp_a->e_id, ($emp_a->e_id == $clan1->e_id_fa2)); ?></td>
    <td class="ac"><?php if (CLAN_VIEW_STAT) echo '<a href="?location=status&amp;empire='. $emp_a->e_id .'">'. $emp_a .'</a>'; else echo $emp_a; ?></td>
    <td class="ar"><?php echo money($emp_a->e_networth); ?></td>
    <td class="ar"><?php echo prenum($emp_a->e_rank); ?></td>
    <td class="ac"><span class=<?php if ($emp_a->e_sharing) echo '"cgood">'. lang('COMMON_YES'); else echo '"cbad">'. lang('COMMON_NO'); ?></span></td>
    <td class="ac"><?php echo radiobutton('modify_rem', '', $emp_a->e_id, FALSE, !in_array($emp_a->e_id, array($clan1->e_id_leader, $clan1->e_id_asst, $clan1->e_id_fa1, $clan1->e_id_fa2))); ?></td></tr>
<?php
	}
?>
<tr><td class="ac"></td>
    <td class="ac"><?php echo radiobutton('modify_ast', '', 0, ($clan1->e_id_asst == 0), ($clan1->e_id_leader == $emp1->e_id)); ?></td>
    <td class="ac"><?php echo radiobutton('modify_fa1', '', 0, ($clan1->e_id_fa1 == 0)); ?></td>
    <td class="ac"><?php echo radiobutton('modify_fa2', '', 0, ($clan1->e_id_fa2 == 0)); ?></td>
    <td class="ac" colspan="4"><?php if ($clan1->e_id_leader != $emp1->e_id) echo '<input type="hidden" name="modify_ldr" value="'. $clan1->e_id_leader .'" /><input type="hidden" name="modify_ast" value="'. $clan1->e_id_asst .'" />'; ?><input type="hidden" name="action" value="ranks" /><input type="submit" value="Modify/Remove Members" /></td>
    <td class="ac"><?php echo radiobutton('modify_rem', '', 0, TRUE); ?></td></tr>
</table>
</form>
<br />
<table class="inputtable">
<tr><th><?php echo lang('MANAGE_CLAN_PASSWORD_LABEL'); ?></th>
    <td><form method="post" action="?location=manage/clan"><div>
        <?php echo lang('LABEL_PASSWORD_NEW'); ?> <input type="password" name="new_password" size="8" /><br />
        <?php echo lang('LABEL_PASSWORD_VERIFY'); ?> <input type="password" name="new_password_verify" size="8" /><br />
        <input type="hidden" name="action" value="password" /><input type="submit" value="<?php echo lang('MANAGE_CLAN_PASSWORD_SUBMIT'); ?>" />
        </div></form></td></tr>
<tr><th><?php echo lang('MANAGE_CLAN_TITLE_LABEL'); ?></th>
    <td><form method="post" action="?location=manage/clan"><div>
        <input type="text" name="new_title" value="<?php echo $clan1->c_title; ?>" size="32" /><br />
        <input type="hidden" name="action" value="title" /><input type="submit" value="<?php echo lang('MANAGE_CLAN_TITLE_SUBMIT'); ?>" />
        </div></form></td></tr>
<tr><th><?php echo lang('MANAGE_CLAN_LOGO_LABEL'); ?></th>
    <td><form method="post" action="?location=manage/clan"><div>
        <input type="text" name="new_logo" value="<?php echo $clan1->c_pic; ?>" size="32" /><br />
        <input type="hidden" name="action" value="logo" /><input type="submit" value="<?php echo lang('MANAGE_CLAN_LOGO_SUBMIT'); ?>" />
        </div></form></td></tr>
<tr><th><?php echo lang('MANAGE_CLAN_URL_LABEL'); ?></th>
    <td><form method="post" action="?location=manage/clan"><div>
        <input type="text" name="new_url" value="<?php echo $clan1->c_url; ?>" size="32" /><br />
        <input type="hidden" name="action" value="url" /><input type="submit" value="<?php echo lang('MANAGE_CLAN_URL_SUBMIT'); ?>" />
        </div></form></td></tr>
</table>
<br />
<?php
	$emp_a = NULL;
}
?>
<form method="post" action="?location=manage/clan">
<table class="inputtable">
<tr><td><?php echo lang('MANAGE_CLAN_INVITE_LABEL', '<input type="text" name="invite_emp" size="4" value="'. prenum(0) .'" />'); ?></td></tr>
<tr><td><?php echo checkbox('invite_perm', lang('MANAGE_CLAN_INVITE_PERM_LABEL'), 1, FALSE, in_array($emp1->e_id, array($clan1->e_id_leader, $clan1->e_id_asst))); ?></td></tr>
<tr><th><input type="hidden" name="action" value="invite" /><input type="submit" value="<?php echo lang('MANAGE_CLAN_INVITE_SUBMIT'); ?>" /></th></tr>
</table>
</form>
<br />
<?php
$q = $db->prepare('SELECT ci_id,ci_flags,ci_time,e_id_1,e_id_2 FROM '. CLAN_INVITE_TABLE .' WHERE c_id = ?');
$q->bindIntValue(1, $clan1->c_id);
$q->execute() or warning('Failed to fetch clan invite list', 0);
$invites = $q->fetchAll();

if (count($invites) > 0)
{
?>
<table class="inputtable">
<tr><th><?php echo lang('MANAGE_CLAN_UNINVITE_EMPIRE_LABEL'); ?></th><th><?php echo lang('MANAGE_CLAN_UNINVITE_INVITER_LABEL'); ?></th><th><?php echo lang('MANAGE_CLAN_UNINVITE_TYPE_LABEL'); ?></th><th><?php echo lang('MANAGE_CLAN_UNINVITE_DATE_LABEL'); ?></th><th><?php echo lang('MANAGE_CLAN_UNINVITE_ACTION_LABEL'); ?></th></tr>
<?php
	foreach ($invites as $invite)
	{
		$emp_a = prom_empire::cached_load($invite['e_id_1']);
		$emp_b = prom_empire::cached_load($invite['e_id_2']);
?>
<tr><td><?php echo $emp_b; ?></td>
    <td><?php echo $emp_a; ?></td>
    <td><?php if ($invite['ci_flags'] & CIFLAG_PERM) echo lang('MANAGE_CLAN_UNINVITE_TYPE_PERM'); else echo lang('MANAGE_CLAN_UNINVITE_TYPE_TEMP'); ?></td>
    <td><?php echo $user1->customdate($invite['ci_time']); ?></td>
    <td><?php
		// only the leader and assistant leader can remove whitelist entries
		if (in_array($emp1->e_id, array($clan1->e_id_leader, $clan1->e_id_asst)) || (!($invite['ci_flags'] & CIFLAG_PERM)))
			echo '<form method="post" action="?location=manage/clan"><div><input type="hidden" name="uninvite_id" value="'. $invite['ci_id'] .'" /><input type="hidden" name="action" value="uninvite" /><input type="submit" value="'. lang('MANAGE_CLAN_UNINVITE_SUBMIT') .'" /></div></form>';
?></td></tr>
<?php
		$emp_b = NULL;
		$emp_a = NULL;
	}
?>
</table>
<br />
<?php
}
// Finally, print event log
printClanNews($clan1);

if (CLAN_VIEW_AID)
	printClanAid($clan1);

page_footer();
?>
