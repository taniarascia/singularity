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
 * $Id: checklang.php 1983 2014-10-01 15:18:43Z quietust $
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
require_once(PROM_BASEDIR .'includes/language.php');

if (count($lang) == 1)
{
	echo "Only one language pack is defined - nothing to check!\n";
	return;
}

function checkguide ($defbase, $curbase, $dir, $checkbase)
{
	global $found, $total;
	foreach (glob($defbase . $dir .'*', GLOB_MARK) as $filename)
	{
		if (is_dir($filename))
		{
			checkguide($defbase, $curbase, str_replace($defbase, '', $filename), $checkbase);
			continue;
		}
		$total++;
		$relname = $dir . basename($filename);
		if (!is_file($curbase . $relname))
		{
			if ($checkbase)
				echo "Guide file '$relname' does not exist!\n";
			else	echo "Unexpected guide file '$relname' encountered!\n";
			continue;
		}
		$found++;
	}
}

echo "Comparing all installed language packs against base pack '". BASE_LANGUAGE ."'...\n\n";

foreach ($lang as $id => &$data)
{
	if ($id == BASE_LANGUAGE)
		continue;
	echo "Checking language pack '$id'...\n\n";
	$found = $total = 0;
	foreach (array_keys($lang[BASE_LANGUAGE]) as $key)
	{
		$total++;
		if (!isset($data[$key]))
		{
			echo "String '$key' is not defined!  Value in base pack '". BASE_LANGUAGE ."' is '". base_lang($key) ."'\n";
			continue;
		}
		if ((substr($key, 0, 5) == 'FUNC_') && (!function_exists($data[$key])))
		{
			echo "Function '". $data[$key] ."' associated with '$key' is not defined!\n";
			continue;
		}
		$found++;
	}
	echo "* $found/$total strings found.\n\n";

	$found = $total = 0;
	foreach (array_keys($data) as $key)
	{
		$total++;
		if (!isset($lang[BASE_LANGUAGE][$key]))
		{
			echo "Unexpected string '$key' encountered!\n";
			continue;
		}
		$found++;
	}
	echo "* ". ($total - $found) ." unexpected strings found.\n\n";

	$found = $total = 0;
	checkguide(PROM_BASEDIR .'lang/'. base_lang('LANG_PATH') .'guide/', PROM_BASEDIR .'lang/'. $data['LANG_PATH'] .'guide/', '', TRUE);
	echo "* $found/$total guide files found.\n\n";

	$found = $total = 0;
	checkguide(PROM_BASEDIR .'lang/'. $data['LANG_PATH'] .'guide/', PROM_BASEDIR .'lang/'. base_lang('LANG_PATH') .'guide/', '', FALSE);
	echo "* ". ($total - $found) ." unexpected guide files found.\n\n";

	echo "\n";
}

echo "Language pack scan complete.\n";
?>
