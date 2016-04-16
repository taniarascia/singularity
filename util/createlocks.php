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
 * $Id: createlocks.php 1983 2014-10-01 15:18:43Z quietust $
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
require_once(PROM_BASEDIR .'includes/logging.php');

$db = db_open(DB_TYPE, DB_SOCK, DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME);
if (!$db)
{
	echo "Database unavailable!\n";
	exit;
}

echo "Building list of locks to create...\n";
$q = $db->query('SELECT u_id FROM '. USER_TABLE);
$users = $q->fetchAll();

$q = $db->query('SELECT e_id FROM '. EMPIRE_TABLE);
$empires = $q->fetchAll();

$q = $db->query('SELECT c_id FROM '. CLAN_TABLE);
$clans = $q->fetchAll();

echo "Flushing and recreating locks table...";
if (!$db->beginTransaction())
{
	echo "Unable to begin transaction!\n";
	exit;
}
$db->query('DELETE FROM '. LOCK_TABLE);
foreach ($users as $row)
	$db->createLock(ENT_USER, $row['u_id']);
foreach ($empires as $row)
	$db->createLock(ENT_EMPIRE, $row['e_id']);
foreach ($clans as $row)
	$db->createLock(ENT_CLAN, $row['c_id']);
$db->createLock(ENT_VARS, 1);
$db->createLock(ENT_MARKET, 1);
if (!$db->commit())
{
	echo "Unable to commit transaction!\n";
	exit;
}
echo "done!\n";
?>
