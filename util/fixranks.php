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
 * $Id: fixranks.php 1983 2014-10-01 15:18:43Z quietust $
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

echo "Correcting rankings...\n";

$q = $db->query('SELECT hr_id,MAX(he_rank) AS maxrank FROM '. HISTORY_EMPIRE_TABLE .' GROUP BY hr_id');
$maxranks = array();
foreach ($q as $row)
	$maxranks[$row['hr_id']] = $row['maxrank'];

$fixcount = 0;
$q1 = $db->query('SELECT u_id,u_numplays,u_avgrank,u_bestrank,u_sucplays FROM '. USER_TABLE);
$q2 = $db->prepare('SELECT hr_id,MIN(he_rank) AS myrank FROM '. HISTORY_EMPIRE_TABLE .' WHERE u_id = ? GROUP BY hr_id');
$q3 = $db->prepare('UPDATE '. USER_TABLE .' SET u_avgrank = ?, u_bestrank = ?, u_sucplays = ? WHERE u_id = ?');
foreach ($q1 as $user)
{
	if ($user['u_numplays'] == 0)
		continue;
	$myranks = array();
	foreach ($maxranks as $id => $val)
		$myranks[$id] = $val + 1;
	$q2->bindIntValue(1, $user['u_id']);
	$q2->execute();
	foreach ($q2 as $row)
		$myranks[$row['hr_id']] = $row['myrank'];
	$avgrank = 0;
	$bestrank = 0;
	$sucplays = 0;
	foreach (array_keys($maxranks) as $round)
	{
		if ($myranks[$round] > $maxranks[$round])
			continue;
		$thisrank = 1 - ($myranks[$round] - 1) / $maxranks[$round];
		$avgrank += $thisrank;
		if ($bestrank < $thisrank)
			$bestrank = $thisrank;
		$sucplays++;
	}
	if ($sucplays)
		$avgrank /= $sucplays;

	if ((abs($user['u_avgrank'] - $avgrank) > 0.001) || (abs($user['u_bestrank'] - $bestrank) > 0.001) || ($user['u_sucplays'] != $sucplays))
		$fixcount++;

	$q3->bindFltValue(1, $avgrank);
	$q3->bindFltValue(2, $bestrank);
	$q3->bindFltValue(3, $sucplays);
	$q3->bindIntValue(4, $user['u_id']);
	$q3->execute();
}
if ($db->releaseLocks())
	echo "Done fixing user rankings ($fixcount records modified).\n";
?>
