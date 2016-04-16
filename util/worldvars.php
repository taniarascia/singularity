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
 * $Id: worldvars.php 1983 2014-10-01 15:18:43Z quietust $
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
require_once(PROM_BASEDIR .'classes/prom_vars.php');

$db = db_open(DB_TYPE, DB_SOCK, DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME);
if (!$db)
{
	echo "Database unavailable!\n";
	exit;
}

function syntax ($mode = 'any')
{
	global $argv;
	echo "Syntax:";
	if ($mode == 'any')
	{
		echo "\n";
		$sep = "\t";
	}
	else	$sep = ' ';
	if (($mode == 'any') || ($mode == 'list'))
		echo $sep ."[php] $argv[0] list\n";
	if (($mode == 'any') || ($mode == 'add'))
		echo $sep ."[php] $argv[0] add <varname>\n";
	if (($mode == 'any') || ($mode == 'set'))
		echo $sep ."[php] $argv[0] set <varname> <value>\n";
	if (($mode == 'any') || ($mode == 'adjust'))
		echo $sep ."[php] $argv[0] adjust <varname> [-]<offset>\n";
	exit;
}

if ($argc < 2)
	syntax();

$mode = $argv[1];
$name = '';
$value = '';
switch ($mode)
{
case 'list':
	if ($argc != 2)
		syntax($mode);
	break;
case 'add':
	if ($argc != 3)
		syntax($mode);
	$name = $argv[2];
	break;
case 'set':
	if ($argc != 4)
		syntax($mode);
	$name = $argv[2];
	$value = $argv[3];
	break;
case 'adjust':
	if ($argc != 4)
		syntax($mode);
	$name = $argv[2];
	$value = $argv[3];
	break;
default:
	syntax();
}

$world = new prom_vars();
$world->lock();
if (!$db->acquireLocks(LOCK_SCRIPT))
	exit;

if (!$world->load())
{
	echo "Unable to read world variables!\n";
	exit;
}

switch ($mode)
{
case 'list':
	foreach ($world as $name => $value)
		echo "$name = '$value'\n";
	break;
case 'add':
	if ($world->create($name))
		echo "Adding world variable '$name'\n";
	break;
case 'set':
	if (isset($world->$name))
	{
		$oldval = $world->$name;
		$world->$name = $value;
		echo "Changing world variable '$name' from '$oldval' to '$value'\n";
	}
	else	echo "Specified world variable does not exist! Please create it first.\n";
	break;
case 'adjust':
	if (isset($world->$name))
	{
		$oldval = $world->$name;
		$value += $world->$name;
		$world->$name = $value;
		echo "Adjusting world variable '$name' from '$oldval' to '$value'\n";
	}
	else	echo "Specified world variable does not exist! Please create it first.\n";
	break;
}
$world->save();
if ($db->releaseLocks())
	echo "Operation completed successfully.\n";
?>
