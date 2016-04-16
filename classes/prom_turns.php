<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: prom_turns.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die('Access denied');

define('TURNS_NEED_NORMAL',	0x01);
define('TURNS_NEED_HOUR',	0x02);
define('TURNS_NEED_DAY',	0x04);
define('TURNS_NEED_FINAL',	0x08);

class prom_turns
{
	protected $time;

	public function __construct ()
	{
		$this->time = 0;
	}

	// Generate a log message, intended to be appended to a logfile
	protected function statecho ($msg, $type = TURN_EVENT)
	{
		global $db, $world;

		$time = explode(' ', microtime());
		if (defined('IN_TURNS'))
		{
			if ($this->time == 0)
				$ts = '----/--/-- --:--';
			else	$ts = date('Y/m/d H:i', $this->time);
			if ($type != TURN_END)
				printf("%s.%06d - [%s] - %s\n", date('Y/m/d H:i:s', $time[1]), $time[0] * 1000000, $ts, $msg);
		}
		elseif (TURNS_CRONLOG)
		{
			$q = $db->prepare('INSERT INTO '. TURNLOG_TABLE .' (turn_time,turn_ticks,turn_interval,turn_type,turn_text) VALUES (?,?,?,?,?)');
			$q->bindIntValue(1, $time[1]);
			$q->bindIntValue(2, $time[0] * 1000000);
			$q->bindIntValue(3, $this->time);
			$q->bindIntValue(4, $type);
			$q->bindStrValue(5, $msg);
			$q->execute() or warning('Failed to add entry to turn log', 1);
		}
	}

	// Perform any world variable adjustments
	public function adjWorld ()
	{
		global $db, $world;

		$this->statecho('Adjusting world variables');

		$q = $db->query('SELECT * FROM '. VAR_ADJUST_TABLE);
		$adjusts = $q->fetchAll();
		foreach ($adjusts as $adjust)
			$world->setData($adjust['v_name'], $world->getData($adjust['v_name']) + $adjust['v_offset']);
		$db->clearTable(VAR_ADJUST_TABLE);
	}

	// Pick a lottery ticket
	public function lottery ()
	{
		global $db, $world;

		$this->statecho('Running lottery');

		$numplayers = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE);
		$lotterynum = mt_rand(1, LOTTERY_MAXTICKETS * $numplayers);

		$jackpot = $world->lotto_current_jackpot;
		$lastjackpot = $world->lotto_yesterday_jackpot;

		// if the minimum jackpot gets increased, apply it now
		if ($lastjackpot > $jackpot)
			$lastjackpot = LOTTERY_JACKPOT;

		$world->lotto_last_picked = $lotterynum;
		$world->lotto_yesterday_jackpot = $jackpot;
		$world->lotto_jackpot_increase = $jackpot - $lastjackpot;

		$win = $db->queryCell('SELECT e_id FROM '. LOTTERY_TABLE .' WHERE e_id > 0 AND l_ticket = ?', array($lotterynum));
		if ($win)
		{
			$emp_b = prom_empire::cached_load($win);
			addEmpireNews(EMPNEWS_ATTACH_LOTTERY, NULL, $emp_b, $jackpot);
			$this->statecho('- Lottery results: ticket '. prenum($lotterynum) .' held by '. $emp_b .', '. money($jackpot) .' won.');
			logmsg_turns('Lottery results: ticket '. prenum($lotterynum) .' held by '. $emp_b .', '. money($jackpot) .' won.');
			$world->lotto_current_jackpot = LOTTERY_JACKPOT;
			$emp_b = NULL;
		}
		else
		{
			$this->statecho('- Lottery results: ticket '. prenum($lotterynum) .' not held, no winner.');
			logmsg_turns('Lottery results: ticket '. prenum($lotterynum) .' not held, no winner.');
			$win = 0;
		}
		$world->lotto_last_winner = $win;
		$db->clearTable(LOTTERY_TABLE);
	}

	// Perform standard daily events - pick lottery, and give points based on networth
	public function update_daily ()
	{
		global $db, $world;

		$this->statecho('Performing daily events');

		$this->lottery();

		if (SCORE_ENABLE)
			$db->queryParam('UPDATE '. EMPIRE_TABLE .' SET e_score = e_score + SQRT(e_networth / 1250000) WHERE e_vacation = 0 AND e_flags & ? = 0 AND u_id != 0', array(EFLAG_DISABLE));
	}

	// Perform standard hourly events
	public function update_hourly ()
	{
		global $db, $world;

		$this->statecho('Performing hourly events');

		// Various counters
		$db->query('UPDATE '. EMPIRE_TABLE .' SET e_vacation = e_vacation + 1 WHERE e_vacation > 0 AND u_id != 0');

		if (MAX_ATTACKS > 0)
		{
			$db->query('UPDATE '. EMPIRE_TABLE .' SET e_attacks = e_attacks + 1 WHERE e_attacks < 0 AND u_id != 0');
			$db->query('UPDATE '. EMPIRE_TABLE .' SET e_attacks = e_attacks + 1 WHERE e_attacks < 0 AND u_id != 0');
		}

		// the vacation check here MUST match the behavior in prom_empire::is_vacation_done()
		if (CUR_TIME >= $world->round_time_closing)
			$db->queryParam('UPDATE '. EMPIRE_TABLE .' SET e_vacation = 0, e_idle = ? WHERE e_vacation >= ? AND u_id != 0', array(CUR_TIME, VACATION_START + VACATION_LIMIT + 1));

		// clean up expired effects
		$db->queryParam('DELETE FROM '. EMPIRE_EFFECT_TABLE .' WHERE ef_name LIKE ? AND ef_value <= ?', array(EMPIRE_EFFECT_TIME .'%', CUR_TIME));
		$db->queryParam('DELETE FROM '. EMPIRE_EFFECT_TABLE .' WHERE ef_name LIKE ? AND ef_value <= 0', array(EMPIRE_EFFECT_TURN .'%'));
		$db->queryParam('DELETE FROM '. EMPIRE_EFFECT_TABLE .' WHERE ef_name LIKE ? AND ef_value = 0', array(EMPIRE_EFFECT_PERM .'%'));
	}

	// Perform standard turn update events
	public function update_normal ()
	{
		global $db, $world;

		$this->statecho('Performing normal events');

		// Give turns, overflowing into stored turns if necessary
		$db->queryParam('UPDATE '. EMPIRE_TABLE .' SET e_turns = e_turns + ? + LEAST(e_storedturns, ?), e_storedturns = e_storedturns - LEAST(e_storedturns, ?) WHERE e_vacation = 0 AND e_flags & ? = 0 AND u_id != 0', array(TURNS_COUNT, TURNS_UNSTORE, TURNS_UNSTORE, EFLAG_DISABLE));
		$db->queryParam('UPDATE '. EMPIRE_TABLE .' SET e_storedturns = LEAST(?, e_storedturns + e_turns - ?), e_turns = ? WHERE e_turns > ? AND u_id != 0', array(TURNS_STORED, TURNS_MAXIMUM, TURNS_MAXIMUM, TURNS_MAXIMUM));

		// Reduce maximum private market sell percentage (by 1% base, up to 2% if the player has nothing but bldcash)
		$db->query('UPDATE '. EMPIRE_TABLE .' SET e_mktperarm = e_mktperarm - LEAST(e_mktperarm, 100 * (1 + e_bldcash / e_land)) WHERE u_id != 0 AND e_land != 0');
		$db->query('UPDATE '. EMPIRE_TABLE .' SET e_mktperlnd = e_mktperlnd - LEAST(e_mktperlnd, 100 * (1 + e_bldcash / e_land)) WHERE u_id != 0 AND e_land != 0');
		$db->query('UPDATE '. EMPIRE_TABLE .' SET e_mktperfly = e_mktperfly - LEAST(e_mktperfly, 100 * (1 + e_bldcash / e_land)) WHERE u_id != 0 AND e_land != 0');
		$db->query('UPDATE '. EMPIRE_TABLE .' SET e_mktpersea = e_mktpersea - LEAST(e_mktpersea, 100 * (1 + e_bldcash / e_land)) WHERE u_id != 0 AND e_land != 0');

		// Refill private market based on bldcost (except for food, which uses bldfood)
		$db->query('UPDATE '. EMPIRE_TABLE .' SET e_mktarm = e_mktarm + 8 * (e_land + e_bldcost) WHERE e_mktarm / 250 < e_land + 2 * e_bldcost AND u_id != 0');
		$db->query('UPDATE '. EMPIRE_TABLE .' SET e_mktlnd = e_mktlnd + 5 * (e_land + e_bldcost) WHERE e_mktlnd / 200 < e_land + 2 * e_bldcost AND u_id != 0');
		$db->query('UPDATE '. EMPIRE_TABLE .' SET e_mktfly = e_mktfly + 3 * (e_land + e_bldcost) WHERE e_mktfly / 180 < e_land + 2 * e_bldcost AND u_id != 0');
		$db->query('UPDATE '. EMPIRE_TABLE .' SET e_mktsea = e_mktsea + 2 * (e_land + e_bldcost) WHERE e_mktsea / 150 < e_land + 2 * e_bldcost AND u_id != 0');
		$db->query('UPDATE '. EMPIRE_TABLE .' SET e_mktfood = e_mktfood + 50 * (e_land + e_bldfood) WHERE e_mktfood / 2000 < e_land + 2 * e_bldfood AND u_id != 0');

		// Various other counters
		if (MAX_ATTACKS > 0)
			$db->query('UPDATE '. EMPIRE_TABLE .' SET e_attacks = e_attacks - 1 WHERE e_attacks > 0 AND u_id != 0');
		if (CLAN_ENABLE)
			$db->query('UPDATE '. EMPIRE_TABLE .' SET e_sharing = e_sharing - 1 WHERE e_sharing > 0 AND u_id != 0');

		// clean up expired clan invites
		if (CLAN_ENABLE)
			$db->queryParam('DELETE FROM '. CLAN_INVITE_TABLE .' WHERE ci_flags & ? = 0 AND ci_time <= ?', array(CIFLAG_PERM, CUR_TIME - CLAN_INVITE_TIME * 3600));
	}

	// Clean stuff out of the public market
	public function cleanMarket ()
	{
		global $db, $world;

		if (PUBMKT_MAXTIME < 0)
			return;

		$this->statecho('Cleaning market');

		$q = $db->queryParam('SELECT * FROM '. MARKET_TABLE .' WHERE k_time <= ?', array(CUR_TIME - 3600 * PUBMKT_MAXTIME));
		$markets = $q->fetchAll();
		$defcost = lookup('pubmkt_id_cost');
		foreach ($markets as $market)	// send them back
		{
			$emp_b = prom_empire::cached_load($market['e_id']);
			$basecost = $defcost[$market['k_type']];
			$lost = floor($market['k_amt'] * (min(max($market['k_price'] - $basecost, 0) / $basecost, 0.3) + 0.2));
			addEmpireNews(EMPNEWS_ATTACH_MARKET_RETURN, NULL, $emp_b, $market['k_type'], $market['k_amt'], $market['k_price'], $market['k_amt'] - $lost);
			$emp_b = NULL;
			// lost goods fund the jackpot at 20% of their base value
			$world->lotto_current_jackpot += round($lost * $basecost / 5);
		}
		$db->queryParam('DELETE FROM '. MARKET_TABLE .' WHERE k_time <= ?', array(CUR_TIME - 3600 * PUBMKT_MAXTIME));	// and delete them
	}

	// Subroutine of removeFromClan() - inherit clan leadership from another member
	protected function inheritClan ($emp_a, $clan_a, $emp_b)
	{
		global $db, $world;

		$event = CLANNEWS_PERM_MEMBER_INHERIT;
		$clan_a->e_id_leader = $emp_b->e_id;
		if ($clan_a->e_id_fa1 == $emp_b->e_id)
		{
			$clan_a->e_id_fa1 = 0;
			$event = CLANNEWS_PERM_MINISTER_INHERIT;
		}
		if ($clan_a->e_id_fa2 == $emp_b->e_id)
		{
			$clan_a->e_id_fa2 = 0;
			$event = CLANNEWS_PERM_MINISTER_INHERIT;
		}

		addEmpireNews(EMPNEWS_CLAN_INHERIT_LEADER, $emp_a, $emp_b, 0);
		addClanNews($event, $clan_a, $emp_b, NULL, $emp_a);
	}
	// Subroutine of cleanEmpires() - remove an empire from its clan, transferring ownership if necessary
	protected function removeFromClan ($emp_a)
	{
		global $db, $world;

		$reason = '';
		$clan_a = new prom_clan($emp_a->c_id);
		$clan_a->load();
		if ($clan_a->e_id_leader == $emp_a->e_id)
		{	// transfer ownership if possible
			$reason .= 'clan leader, ';
			// first candidate is the assistant leader, alive or not - if he's dead, it'll transfer again shortly
			if ($clan_a->e_id_asst)
			{
				$clan_a->e_id_leader = $clan_a->e_id_asst;
				$clan_a->e_id_asst = 0;

				$emp_b = prom_empire::cached_load($clan_a->e_id_leader);
				addEmpireNews(EMPNEWS_CLAN_INHERIT_LEADER, $emp_a, $emp_b, 0);
				addClanNews(CLANNEWS_PERM_ASSISTANT_INHERIT, $clan_a, $emp_b, NULL, $emp_a);
				$reason .= 'to asst '. $emp_b .', ';
				$emp_b = NULL;
			}
			// next, try to find the strongest member who isn't dead, deleted, or disabled
			elseif ($found = $db->queryCell('SELECT e_id FROM '. EMPIRE_TABLE .' WHERE c_id = ? AND e_id != ? AND e_flags & ? = 0 AND e_land > 0 ORDER BY e_networth DESC', array($clan_a->c_id, $emp_a->e_id, EFLAG_DELETE | EFLAG_DISABLE)))
			{
				$emp_b = prom_empire::cached_load($found);
				$this->inheritClan($emp_a, $clan_a, $emp_b);
				$reason .= 'to member '. $emp_b .', ';
				$emp_b = NULL;
			}
			// if everybody's dead, then pick one of them anyways (as long as they aren't disabled)
			elseif ($found = $db->queryCell('SELECT e_id FROM '. EMPIRE_TABLE .' WHERE c_id = ? AND e_id != ? AND e_flags & ? = 0 ORDER BY e_networth DESC', array($clan_a->c_id, $emp_a->e_id, EFLAG_DISABLE)))
			{
				$emp_b = prom_empire::cached_load($found);
				$this->inheritClan($emp_a, $clan_a, $emp_b);
				$reason .= 'to member '. $emp_b .' (also dead), ';
				$emp_b = NULL;
			}
			// last resort, NEED to choose a leader, even if it's disabled
			elseif ($found = $db->queryCell('SELECT e_id FROM '. EMPIRE_TABLE .' WHERE c_id = ? AND e_id != ? ORDER BY e_networth DESC', array($clan_a->c_id, $emp_a->e_id)))
			{
				$emp_b = prom_empire::cached_load($found);
				$this->inheritClan($emp_a, $clan_a, $emp_b);
				$reason .= 'to member '. $emp_b .' (DISABLED), ';
				$emp_b = NULL;
			}
			// by now, said empire is guaranteed to be the last member, so the clan will be deleted in a moment
			else
			{
				$clan_a->e_id_leader = 0;
				$reason .= 'abandoned, ';

				// clean up any pending invites
				$q = $db->queryParam('SELECT e_id_2 FROM '. CLAN_INVITE_TABLE .' WHERE c_id = ?', array($clan_a->c_id));
				$invs = $q->fetchAll();
				foreach ($invs as $inv)
				{
					$emp_b = new prom_empire($inv['e_id_2']);
					$emp_b->loadPartial();
					addEmpireNews(EMPNEWS_CLAN_INVITE_DISBANDED, $emp_a, $emp_b, 0);
					$emp_b = NULL;
				}
				$db->queryParam('DELETE FROM '. CLAN_INVITE_TABLE .' WHERE c_id = ?', array($clan_a->c_id));
			}
		}
		elseif ($clan_a->e_id_asst == $emp_a->e_id)
		{
			$clan_a->e_id_asst = 0;
			$reason .= 'clan asst, ';
		}
		elseif ($clan_a->e_id_fa1 == $emp_a->e_id)
		{
			$clan_a->e_id_fa1 = 0;
			$reason .= 'clan fa1, ';
		}
		elseif ($clan_a->e_id_fa2 == $emp_a->e_id)
		{
			$clan_a->e_id_fa2 = 0;
			$reason .= 'clan fa2, ';
		}
		$clan_a->c_members--;
		$clan_a->save();
		$clan_a = NULL;

		$emp_a->c_oldid = $emp_a->c_id;
		$emp_a->c_id = 0;		// remove from clan
		return $reason;
	}

	// Cleanup unneeded empire entries
	public function cleanEmpires ()
	{
		global $db, $world;

		$this->statecho('Unlinking empires');

		$checks = array();
		$values = array(EFLAG_ADMIN); // corresponds to "e_flags & ? = 0"

		// not yet prompted for validation
		$checks[] = '(e_flags & ? = 0 AND e_idle < ?)';
		$values[] = EFLAG_VALID | EFLAG_NOTIFY;
		$values[] = CUR_TIME - 86400 * IDLE_TIMEOUT_NEW;

		// stalled at validation prompt
		$checks[] = '(e_flags & ? = ? AND e_idle < ?)';
		$values[] = EFLAG_VALID | EFLAG_NOTIFY;
		$values[] = EFLAG_NOTIFY;
		$values[] = CUR_TIME - 86400 * IDLE_TIMEOUT_VALIDATE;

		// not disabled
		$checks[] = '(e_flags & ? = 0 AND e_vacation = 0 AND e_land > 0 AND e_idle < ?)';
		$values[] = EFLAG_DISABLE;
		$values[] = CUR_TIME - 86400 * IDLE_TIMEOUT_ABANDON;

		// dead
		$checks[] = '(e_land = 0 AND (e_flags & ? = ? OR e_idle < ?))';
		$values[] = EFLAG_NOTIFY;
		$values[] = EFLAG_NOTIFY;
		$values[] = CUR_TIME - 86400 * IDLE_TIMEOUT_KILLED;

		// marked for deletion (immediate if under protection)
		// the protection check here MUST match the behavior in prom_empire::is_protected()
		$checks[] = '(e_flags & ? = ? AND (e_turnsused <= ? OR e_idle < ?))';
		$values[] = EFLAG_DELETE;
		$values[] = EFLAG_DELETE;
		$values[] = TURNS_PROTECTION;
		$values[] = CUR_TIME - 86400 * IDLE_TIMEOUT_DELETE;

		$q = $db->queryParam('SELECT e_id FROM '. EMPIRE_TABLE .' WHERE u_id != 0 AND e_flags & ? = 0 AND ('. implode(' OR ', $checks) .')', $values);
		$emps = $q->fetchAll();

		foreach ($emps as $row)
		{
			$emp_a = new prom_empire($row['e_id']);
			$emp_a->load();
			$this->statecho('- Unlinking empire '. $emp_a);
			$reason = '';
			if (CLAN_ENABLE && $emp_a->c_id)
				$reason .= $this->removeFromClan($emp_a);

			// delete messages sent to user
			$db->queryParam('UPDATE '. EMPIRE_MESSAGE_TABLE .' SET m_flags = m_flags | ? WHERE e_id_dst = ?', array(MFLAG_DELETE, $emp_a->e_id));
			// flag all messages sent from user
			$db->queryParam('UPDATE '. EMPIRE_MESSAGE_TABLE .' SET m_flags = m_flags | ? WHERE e_id_src = ?', array(MFLAG_DEAD, $emp_a->e_id));

			// nuke any of the user's items on the market
			$db->queryParam('DELETE FROM '. MARKET_TABLE .' WHERE e_id = ?', array($emp_a->e_id));
			// nuke any lottery tickets
			$db->queryParam('DELETE FROM '. LOTTERY_TABLE .' WHERE e_id = ?', array($emp_a->e_id));

			if ($emp_a->e_flags & EFLAG_DELETE)
			{
				if ($emp_a->e_killedby == $emp_a->e_id)
				{
					$reason .= 'deleted';
					if ($emp_a->e_reason)
						$emp_a->e_reason = 'Self-deleted: '. $emp_a->e_reason;
					else	$emp_a->e_reason = 'Self-deleted (no reason)';
				}
				else
				{
					$reason .= 'nuked';
					if ($emp_a->e_reason)
						$emp_a->e_reason = 'Deleted by admin: '. $emp_a->e_reason;
					else	$emp_a->e_reason = 'Deleted by admin (no reason)';
				}
			}
			elseif (($emp_a->e_flags & (EFLAG_VALID | EFLAG_NOTIFY)) == 0)
			{
				$reason .= 'ignored';
				$emp_a->e_reason = 'Auto-deleted, never used enough turns to require validation';
				$emp_a->e_killedby = 0;
			}
			elseif (($emp_a->e_flags & (EFLAG_VALID | EFLAG_NOTIFY)) == EFLAG_NOTIFY)
			{
				$reason .= 'unvalidated';
				$emp_a->e_reason = 'Auto-deleted, failed to validate';
				$emp_a->e_killedby = 0;
			}
			elseif ($emp_a->e_land == 0)
			{
				$reason .= 'killed';
				$emp_a->e_reason = 'Killed';
			}
			elseif ((($emp_a->e_flags & EFLAG_DISABLE) == 0) && ($emp_a->e_vacation == 0))
			{
				$reason .= 'abandoned';
				$emp_a->e_reason = 'Auto-deleted, left idle for an excessive duration';
				$emp_a->e_killedby = 0;
			}
			else
			{
				$reason .= 'indeterminate';
				$emp_a->e_reason = 'Unknown deletion reason';
				$emp_a->e_killedby = 0;
			}

			$emp_a->u_oldid = $emp_a->u_id;
			$emp_a->u_id = 0;		// unlink from user
			$emp_a->save();
			$this->statecho('- Empire '. $emp_a .' unlinked - '. $reason);
			logmsg_turns('Empire '. $emp_a .' unlinked - '. $reason);
			$emp_a = NULL;
		}
	}

	// Cleanup unneeded clan entries
	public function cleanClans ()
	{
		global $db, $world;

		if (!CLAN_ENABLE)
			return;

		$this->statecho('Removing clans');

		// need a dummy record here so we can record the clan of origin
		$emp_a = new prom_news_placeholder();
		$q = $db->query('SELECT c_id FROM '. CLAN_TABLE .' WHERE c_members = 0');
		$clans = $q->fetchAll();
		foreach ($clans as $row)
		{	// remove all associations with empty clans and make clan invisible to game
			$clan_a = new prom_clan($row['c_id']);
			$clan_a->load();
			$this->statecho('- Removing clan '. $clan_a);
			// can't actually delete the row, since news table entries will still refer to it
			$clan_a->c_members = -1;
			$emp_a->c_id = $clan_a->c_id;	// assign clan ID to dummy record

			$q1 = $db->queryParam('SELECT e_id_leader AS e_id,cr_flags,c_id FROM '. CLAN_RELATION_TABLE .' LEFT OUTER JOIN '. CLAN_TABLE .' ON (c_id_2 = c_id) WHERE c_id_1 = ?', array($clan_a->c_id));
			$q2 = $db->queryParam('SELECT e_id_leader AS e_id,cr_flags,c_id FROM '. CLAN_RELATION_TABLE .' LEFT OUTER JOIN '. CLAN_TABLE .' ON (c_id_1 = c_id) WHERE c_id_2 = ?', array($clan_a->c_id));
			$rels = array_merge($q1->fetchAll(), $q2->fetchAll());
			foreach ($rels as $rel)
			{
				// if two clans are disbanded simultaneously and they had relations with each other, skip the news reports
				if ($rel['e_id'] == 0)
					continue;
				$emp_b = prom_empire::cached_load($rel['e_id']);
				$clan_b = new prom_news_placeholder(0, $rel['c_id']);
				if ($rel['cr_flags'] & CRFLAG_ALLY)
				{
					$empevent = EMPNEWS_CLAN_ALLY_GONE;
					$clanevent = CLANNEWS_RECV_ALLY_GONE;
				}
				else
				{
					$empevent = EMPNEWS_CLAN_WAR_GONE;
					$clanevent = CLANNEWS_RECV_WAR_GONE;
				}
				addEmpireNews($empevent, $emp_a, $emp_b, 0);
				addClanNews($clanevent, $clan_b, NULL, $clan_a);
				$clan_b = NULL;
				$emp_b = NULL;
			}
			$db->queryParam('DELETE FROM '. CLAN_RELATION_TABLE .' WHERE c_id_1 = ? OR c_id_2 = ?', array($clan_a->c_id, $clan_a->c_id));
			$clan_a->save();
			$this->statecho('- Clan '. $clan_a .' removed.');
			logmsg_turns('Clan '. $clan_a .' removed.');
			$clan_a = NULL;
		}
		$emp_a = NULL;
	}

	// Update empire rankings
	public function updateRanks ()
	{
		global $db, $world;

		$this->statecho('Updating ranks');
		$db->query('UPDATE '. EMPIRE_TABLE .' SET e_rank = 0 WHERE u_id = 0');
		// If score tracking is enabled, rank players by their score instead of their networth
		if (SCORE_ENABLE)
			$q = $db->query('SELECT e_id FROM '. EMPIRE_TABLE .' WHERE u_id != 0 ORDER BY e_score DESC, e_networth DESC');
		else	$q = $db->query('SELECT e_id FROM '. EMPIRE_TABLE .' WHERE u_id != 0 ORDER BY e_networth DESC');
		$emps = $q->fetchAll();
		$urank = 0;
		$q = $db->prepare('UPDATE '. EMPIRE_TABLE .' SET e_rank = ? WHERE e_id = ?');
		foreach ($emps as $emp)
		{
			$urank++;
			$q->bindIntValue(1, $urank);
			$q->bindIntValue(2, $emp['e_id']);
			$q->execute();
		}
	}

	// Check if it's okay to end the round early
	public function checkEndEarly ()
	{
		global $db, $world;

		// check to see if it's okay to end the round early
		if (CUR_TIME >= $world->round_time_closing)
		{
			$numplayers = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id != 0 AND e_flags & ? = 0', array(EFLAG_ADMIN));
			if ($numplayers == 1)
			{
				$this->statecho('Only one survivor remains - ending round.');
				logmsg_turns('Ending round early, only one survivor remaining');
				$world->round_time_end = CUR_TIME;
			}
		}
	}

	// Mark empires as offline
	public function flushSessions ()
	{
		global $db, $world;

		$this->statecho('Flushing sessions');
		// after 3 turns updates of being idle (or if they've been unlinked), set empires as offline
		$db->queryParam('UPDATE '. EMPIRE_TABLE .' SET e_flags = e_flags & ? WHERE e_idle < ? OR u_id = 0', array(~EFLAG_ONLINE, CUR_TIME - 3 * 60 * TURNS_FREQ));
	}

	// Check what processing actually needs to be done
	public function needsUpdate (&$interval = NULL)
	{
		global $db, $world;

		// don't trigger if any turns haven't yet been scheduled
		if ((!$world->turns_next) || (!$world->turns_next_hourly) || (!$world->turns_next_daily))
		{
			if (isset($interval))
				$interval = 0;
			return 0;
		}

		// find out the next thing that needs to be run
		$nextinterval = min(CUR_TIME, $world->round_time_end, $world->turns_next_daily, $world->turns_next_hourly, $world->turns_next);

		$updates = 0;
		if ($nextinterval == $world->turns_next)
			$updates |= TURNS_NEED_NORMAL;
		if ($nextinterval == $world->turns_next_hourly)
			$updates |= TURNS_NEED_HOUR;
		if ($nextinterval == $world->turns_next_daily)
			$updates |= TURNS_NEED_DAY;
		if ($nextinterval == $world->round_time_end)
			$updates |= TURNS_NEED_FINAL;
		
		if (isset($interval))
			$interval = $nextinterval;
		return $updates;
	}

	// Perform actual turn updates
	public function doUpdate ()
	{
		global $db, $world;

		// first off, check if we need to update turns
		if (!$this->needsUpdate())
			return;

		$this->time = min($world->turns_next_daily, $world->turns_next_hourly, $world->turns_next);

		$this->statecho('Beginning turn run', TURN_START);

		$this->statecho('Locking entities');
		$db->lockAll();
		if (!$db->acquireLocks(LOCK_TURNS))
		{
			$this->statecho('Lock failed, aborting turn run', TURN_ABORT);
			return;
		}

		// it's possible that a turns update was already in progress while we were waiting on the lock
		// so reload world vars and double-check that we still need to do this
		$world->load();
		$nextinterval = 0;
		if (!$this->needsUpdate($nextinterval))
		{
			// if so, then release locks and exit out
			$db->releaseLocks();
			$this->statecho('Turn run aborted', TURN_ABORT);
			return;
		}

		// Special actions to perform on the very first turns run
		if ($nextinterval == $world->round_time_begin + min(TURNS_OFFSET, TURNS_OFFSET_HOURLY, TURNS_OFFSET_DAILY) * 60)
		{
			$this->statecho('Round has begun!');
			logmsg_turns('Beginning round');
			// reset everybody's idle time so they won't expire below
			$db->queryParam('UPDATE '. EMPIRE_TABLE .' SET e_idle = ?', array(CUR_TIME));
		}

		$this->adjWorld();

		// Give out turns and update other stuff as necessary
		while ($updates = $this->needsUpdate())
		{
			if ($updates & TURNS_NEED_DAY)
			{
				$this->time = $world->turns_next_daily;
				$this->update_daily();
				$world->turns_next_daily += 60 * 60 * 24;
			}
			if ($updates & TURNS_NEED_HOUR)
			{
				$this->time = $world->turns_next_hourly;
				$this->update_hourly();
				$world->turns_next_hourly += 60 * 60;
			}
			if ($updates & TURNS_NEED_NORMAL)
			{
				$this->time = $world->turns_next;
				$this->update_normal();
				$world->turns_next += 60 * TURNS_FREQ;
			}
			// exit out once we hit the end of the round
			if ($updates & TURNS_NEED_FINAL)
				break;
		}

		$this->cleanMarket();
		$this->cleanEmpires();
		$this->cleanClans();
		$this->updateRanks();
		$this->checkEndEarly();
		$this->flushSessions();

		// Special actions to perform at the end of the round
		if ($updates & TURNS_NEED_FINAL)
		{
			$this->statecho('Round has ended!');
			logmsg_turns('Ending round');
			// mark everybody as offline
			$db->queryParam('UPDATE '. EMPIRE_TABLE .' SET e_flags = e_flags & ?', array(~EFLAG_ONLINE));
			// unschedule all turn updates
			$world->turns_next_daily = 0;
			$world->turns_next_hourly = 0;
			$world->turns_next = 0;
			$this->time = 0;
		}

		$world->save();
		$db->releaseLocks();
		$this->statecho('', TURN_END);
	}
}
?>
