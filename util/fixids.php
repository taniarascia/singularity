<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) 2001-2014 QMT Productions
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * $Id: fixids.php 1983 2014-10-01 15:18:43Z quietust $
 */

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('UTC');
ignore_user_abort(1);
set_time_limit(0);

// require CLI invocation
if (isset($_SERVER['REQUEST_URI']))
	die("Access denied");

// prevent recursion
if (defined('IN_GAME'))
	die("Access denied");

define('IN_GAME', TRUE);
define('IN_SCRIPT', TRUE);
// double dirname() here, since this script runs from a subdirectory
define('PROM_BASEDIR', dirname(dirname(__FILE__)) . '/');

require_once(PROM_BASEDIR .'config.php');
require_once(PROM_BASEDIR .'includes/constants.php');
require_once(PROM_BASEDIR .'includes/database.php');
require_once(PROM_BASEDIR .'includes/language.php');
require_once(PROM_BASEDIR .'includes/logging.php');
require_once(PROM_BASEDIR .'classes/prom_user.php');
require_once(PROM_BASEDIR .'classes/prom_vars.php');

$db = db_open(DB_TYPE, DB_SOCK, DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME);
if (!$db)
{
	echo "Database unavailable!\n";
	exit;
}

$world = new prom_vars();
$world->lock();
$db->lockGroup(ENT_USER);
if (!$db->acquireLocks(LOCK_SCRIPT))
	exit;

$world->load();
if (!isset($world->round_time_begin))
{
	echo "Game setup is not yet complete!\n";
	exit;
}

echo "Checking current IDs...\n";

$q = $db->query('SELECT hr_id,hr_allempires,hr_allclans FROM '. HISTORY_ROUND_TABLE .' ORDER BY hr_id ASC');
$fixes = array();
foreach ($q as $row)
{
	$max_clan = $db->queryCell('SELECT MAX(hc_id) FROM '. HISTORY_CLAN_TABLE .' WHERE hr_id = ?', array($row['hr_id']));
	$max_emp = $db->queryCell('SELECT MAX(he_id) FROM '. HISTORY_EMPIRE_TABLE .' WHERE hr_id = ?', array($row['hr_id']));
	if ($max_emp > $row['hr_allempires'])
	{
		echo "IDs have already been updated in this game - aborting.\n";
		exit;
	}
	$fixes[$row['hr_id']] = array('clan' => $max_clan, 'emp' => $max_emp);
}

function adjust_parm ($query, $adjust, $max, $parm)
{
	global $db;
	if ($adjust == 0)
		return;
	if ($adjust < $max)
	{
		$db->queryParam($query, array($adjust + $max, $parm));
		$db->queryParam($query, array(-$max, $parm));
	}
	else	$db->queryParam($query, array($adjust, $parm));
}

function adjust ($query, $adjust, $max)
{
	global $db;
	if ($adjust == 0)
		return;
	if ($adjust < $max)
	{
		$db->queryParam($query, array($adjust + $max));
		$db->queryParam($query, array(-$max));
	}
	else	$db->queryParam($query, array($adjust));
}

echo "Correcting IDs...\n";

$emp_adjust = 0;
$clan_adjust = 0;
foreach ($fixes as $round => $data)
{
	echo "Round $round - adjusting clans by $clan_adjust and empires by $emp_adjust\n";
	$max_clan = $data['clan'];
	$max_emp = $data['emp'];

	adjust_parm('UPDATE '. HISTORY_CLAN_TABLE .' SET hc_id = hc_id + ? WHERE hr_id = ?', $clan_adjust, $max_clan, $round);
	adjust_parm('UPDATE '. HISTORY_EMPIRE_TABLE .' SET hc_id = hc_id + ? WHERE hr_id = ? AND hc_id != 0', $clan_adjust, $max_clan, $round);
	$clan_adjust += $max_clan;

	adjust_parm('UPDATE '. HISTORY_EMPIRE_TABLE .' SET he_id = he_id + ? WHERE hr_id = ?', $emp_adjust, $max_emp, $round);
	$emp_adjust += $max_emp;
}

$max_clan = $db->queryCell('SELECT MAX(c_id) FROM '. CLAN_TABLE);
$max_emp = $db->queryCell('SELECT MAX(e_id) FROM '. EMPIRE_TABLE);

// these won't actually happen until the end
$db->setSequence(CLAN_TABLE, $max_clan + $clan_adjust);
$db->setSequence(EMPIRE_TABLE, $max_emp + $emp_adjust);

adjust('UPDATE '. CLAN_TABLE .' SET c_id = c_id + ?', $clan_adjust, $max_clan);
adjust('UPDATE '. CLAN_TABLE .' SET e_id_leader = e_id_leader + ? WHERE e_id_leader != 0', $emp_adjust, $max_emp);
adjust('UPDATE '. CLAN_TABLE .' SET e_id_asst = e_id_asst + ? WHERE e_id_asst != 0', $emp_adjust, $max_emp);
adjust('UPDATE '. CLAN_TABLE .' SET e_id_fa1 = e_id_fa1 + ? WHERE e_id_fa1 != 0', $emp_adjust, $max_emp);
adjust('UPDATE '. CLAN_TABLE .' SET e_id_fa2 = e_id_fa2 + ? WHERE e_id_fa2 != 0', $emp_adjust, $max_emp);

adjust('UPDATE '. EMPIRE_TABLE .' SET e_id = e_id + ?', $emp_adjust, $max_emp);
adjust('UPDATE '. EMPIRE_TABLE .' SET c_id = c_id + ? WHERE c_id != 0', $clan_adjust, $max_clan);
adjust('UPDATE '. EMPIRE_TABLE .' SET c_oldid = c_oldid + ? WHERE c_oldid != 0', $clan_adjust, $max_clan);
adjust('UPDATE '. EMPIRE_TABLE .' SET e_killedby = e_killedby + ? WHERE e_killedby != 0', $emp_adjust, $max_emp);
adjust('UPDATE '. EMPIRE_TABLE .' SET e_killclan = e_killclan + ? WHERE e_killclan != 0', $clan_adjust, $max_clan);

adjust('UPDATE '. CLAN_INVITE_TABLE .' SET c_id = c_id + ? WHERE c_id != 0', $clan_adjust, $max_clan);
adjust('UPDATE '. CLAN_INVITE_TABLE .' SET e_id_1 = e_id_1 + ? WHERE e_id_1 != 0', $emp_adjust, $max_emp);
adjust('UPDATE '. CLAN_INVITE_TABLE .' SET e_id_2 = e_id_2 + ? WHERE e_id_2 != 0', $emp_adjust, $max_emp);

adjust('UPDATE '. CLAN_MESSAGE_TABLE .' SET e_id = e_id + ? WHERE e_id != 0', $emp_adjust, $max_emp);

adjust('UPDATE '. CLAN_NEWS_TABLE .' SET c_id = c_id + ? WHERE c_id != 0', $clan_adjust, $max_clan);
adjust('UPDATE '. CLAN_NEWS_TABLE .' SET c_id_2 = c_id_2 + ? WHERE c_id_2 != 0', $clan_adjust, $max_clan);
adjust('UPDATE '. CLAN_NEWS_TABLE .' SET e_id_1 = e_id_1 + ? WHERE e_id_1 != 0', $emp_adjust, $max_emp);
adjust('UPDATE '. CLAN_NEWS_TABLE .' SET e_id_2 = e_id_2 + ? WHERE e_id_2 != 0', $emp_adjust, $max_emp);

adjust('UPDATE '. CLAN_RELATION_TABLE .' SET c_id_1 = c_id_1 + ? WHERE c_id_1 != 0', $clan_adjust, $max_clan);
adjust('UPDATE '. CLAN_RELATION_TABLE .' SET c_id_2 = c_id_2 + ? WHERE c_id_2 != 0', $clan_adjust, $max_clan);

adjust('UPDATE '. CLAN_TOPIC_TABLE .' SET c_id = c_id + ? WHERE c_id != 0', $clan_adjust, $max_clan);

adjust('UPDATE '. EMPIRE_EFFECT_TABLE .' SET e_id = e_id + ?', $emp_adjust, $max_emp);

adjust('UPDATE '. EMPIRE_MESSAGE_TABLE .' SET e_id_src = e_id_src + ? WHERE e_id_src != 0', $emp_adjust, $max_emp);
adjust('UPDATE '. EMPIRE_MESSAGE_TABLE .' SET e_id_dst = e_id_dst + ? WHERE e_id_dst != 0', $emp_adjust, $max_emp);

adjust('UPDATE '. EMPIRE_NEWS_TABLE .' SET e_id_src = e_id_src + ? WHERE e_id_src != 0', $emp_adjust, $max_emp);
adjust('UPDATE '. EMPIRE_NEWS_TABLE .' SET e_id_dst = e_id_dst + ? WHERE e_id_dst != 0', $emp_adjust, $max_emp);
adjust('UPDATE '. EMPIRE_NEWS_TABLE .' SET c_id_src = c_id_src + ? WHERE c_id_src != 0', $clan_adjust, $max_clan);
adjust('UPDATE '. EMPIRE_NEWS_TABLE .' SET c_id_dst = c_id_dst + ? WHERE c_id_dst != 0', $clan_adjust, $max_clan);

adjust_parm('UPDATE '. LOCK_TABLE .' SET lock_id = lock_id + ? WHERE lock_type = ?', $emp_adjust, $max_emp, ENT_EMPIRE);
adjust_parm('UPDATE '. LOCK_TABLE .' SET lock_id = lock_id + ? WHERE lock_type = ?', $clan_adjust, $max_clan, ENT_CLAN);

adjust('UPDATE '. LOTTERY_TABLE .' SET e_id = e_id + ? WHERE e_id != 0', $emp_adjust, $max_emp);

adjust('UPDATE '. MARKET_TABLE .' SET e_id = e_id + ? WHERE e_id != 0', $emp_adjust, $max_emp);

// clear all sessions
$db->clearTable(SESSION_TABLE);

if ($db->releaseLocks())
	echo "Done fixing empire/clan ID numbers.\n";
?>
