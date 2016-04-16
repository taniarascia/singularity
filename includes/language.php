<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: language.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

define('DURATION_SECONDS', 0);
define('DURATION_MINUTES', 1);
define('DURATION_HOURS', 2);
define('DURATION_DAYS', 3);

$lang = array();
// Load all available language pack definitions
foreach (glob(PROM_BASEDIR .'lang/*.php') as $langfile)
	require_once($langfile);
$lang_required_keys = array('LANG_ID', 'LANG_CODE', 'LANG_DIR', 'LANG_PATH');
// For each language pack defined, load all of its subfiles
foreach (array_keys($lang) as $lang_id)
{
	// make sure all of the vital properties are set in each language definition
	foreach ($lang_required_keys as $key)
		if (!array_key_exists($key, $lang[$lang_id]))
		{
			unset($lang[$lang_id]);
			continue 2;
		}
	foreach (glob(PROM_BASEDIR .'lang/'. $lang[$lang_id]['LANG_PATH'] .'*.php') as $langsubfile)
		require_once($langsubfile);
}

if (!isset($lang[BASE_LANGUAGE]))
	die('Base language pack not found');
if (!isset($lang[DEFAULT_LANGUAGE]))
	die('Default language pack not found');

$cur_lang = DEFAULT_LANGUAGE;
$lang_cache = array();

// Selects which language to use when generating the current page
// Returns TRUE if the language is available, FALSE if not
function setlanguage ($name)
{
	global $lang, $cur_lang, $lang_cache;
	if (isset($lang[$name]))
	{
		$cur_lang = $name;
		return TRUE;
	}
	$cur_lang = DEFAULT_LANGUAGE;
	$lang_cache = array();
	return FALSE;
}

// Substitutes the supplied string ID for the matching string in the currently selected language
// and substitutes parameters where appropriate
function lang ($id)
{
	global $lang, $cur_lang;
	if (isset($lang[$cur_lang][$id]))
		$str = $lang[$cur_lang][$id];
	elseif (isset($lang[DEFAULT_LANGUAGE][$id]))
		$str = $lang[DEFAULT_LANGUAGE][$id];
	elseif (isset($lang[BASE_LANGUAGE][$id]))
		$str = $lang[BASE_LANGUAGE][$id];
	else	$str = $cur_lang .'.'. $id;

	// if multiple parameters were passed, then substitute them into the string
	if (func_num_args() > 1)
	{
		$args = func_get_args();
		array_shift($args);
		// if you pass an array as the 1st parameter, treat it as a parameter list
		if (is_array($args[0]))
			$args = $args[0];
		// if any of the parameters happen to be additional string IDs, then substitute them directly
		foreach ($args as &$arg)
		{
			if (!is_string($arg))
				continue;
			if (isset($lang[$cur_lang][$arg]))
				$arg = $lang[$cur_lang][$arg];
			elseif (isset($lang[DEFAULT_LANGUAGE][$arg]))
				$arg = $lang[DEFAULT_LANGUAGE][$arg];
			elseif (isset($lang[BASE_LANGUAGE][$arg]))
				$arg = $lang[BASE_LANGUAGE][$arg];
		}
		$str = @vsprintf($str, $args);
		if (strlen($str) == 0)
		{
			$e = error_get_last();
			warning($e['message'], 1);
		}
	}
	return $str;
}

// Substitutes the supplied string ID for the matching string in the DEFAULT language
// and substitutes parameters where appropriate
function def_lang ($id)
{
	global $lang;
	if (isset($lang[DEFAULT_LANGUAGE][$id]))
		$str = $lang[DEFAULT_LANGUAGE][$id];
	if (isset($lang[BASE_LANGUAGE][$id]))
		$str = $lang[BASE_LANGUAGE][$id];
	else	$str = DEFAULT_LANGUAGE .'.'. $id;

	// if multiple parameters were passed, then substitute them into the string
	if (func_num_args() > 1)
	{
		$args = func_get_args();
		array_shift($args);
		// if you pass an array as the 1st parameter, treat it as a parameter list
		if (is_array($args[0]))
			$args = $args[0];
		// if any of the parameters happen to be additional string IDs, then substitute them directly
		foreach ($args as &$arg)
		{
			if (!is_string($arg))
				continue;
			if (isset($lang[DEFAULT_LANGUAGE][$arg]))
				$arg = $lang[DEFAULT_LANGUAGE][$arg];
			elseif (isset($lang[BASE_LANGUAGE][$arg]))
				$arg = $lang[BASE_LANGUAGE][$arg];
		}
		$str = @vsprintf($str, $args);
		if (strlen($str) == 0)
		{
			$e = error_get_last();
			warning($e['message'], 1);
		}
	}
	return $str;
}

// Substitutes the supplied string ID for the matching string in the BASE language
// and substitutes parameters where appropriate
function base_lang ($id)
{
	global $lang;
	if (isset($lang[BASE_LANGUAGE][$id]))
		$str = $lang[BASE_LANGUAGE][$id];
	else	$str = BASE_LANGUAGE .'.'. $id;

	// if multiple parameters were passed, then substitute them into the string
	if (func_num_args() > 1)
	{
		$args = func_get_args();
		array_shift($args);
		// if you pass an array as the 1st parameter, treat it as a parameter list
		if (is_array($args[0]))
			$args = $args[0];
		// if any of the parameters happen to be additional string IDs, then substitute them directly
		foreach ($args as &$arg)
		{
			if (!is_string($arg))
				continue;
			if (isset($lang[BASE_LANGUAGE][$arg]))
				$arg = $lang[BASE_LANGUAGE][$arg];
		}
		$str = @vsprintf($str, $args);
		if (strlen($str) == 0)
		{
			$e = error_get_last();
			warning($e['message'], 1);
		}
	}
	return $str;
}

// Locates a file within a language directory
function langfile ($file)
{
	$langfile = PROM_BASEDIR .'lang/'. lang('LANG_PATH') . $file;
	if (!is_file($langfile))
		$langfile = PROM_BASEDIR .'lang/'. def_lang('LANG_PATH') . $file;
	if (!is_file($langfile))
		$langfile = PROM_BASEDIR .'lang/'. base_lang('LANG_PATH') . $file;
	if (!is_file($langfile))
		return NULL;
	return $langfile;
}

// Checks if text is equal to a string defined in any language pack
// Intended for preventing creation of clans named "None"
function lang_equals_any ($compare, $id)
{
	global $lang;
	foreach ($lang as &$curlang)
	{
		if (!isset($curlang[$id]))
			continue;
		if ($curlang[$id] == $compare)
			return TRUE;
	}
	return FALSE;
}

// Determines if the specified string is a defined string ID
// Intended for usage in user input validation (to prevent users from entering a string ID as their nickname, clan title, etc.)
function lang_isset ($id)
{
	global $lang;
	foreach ($lang as &$curlang)
	{
		if (isset($curlang[$id]))
			return TRUE;
	}
	return FALSE;
}

// pluralize a string, with the (comma-formatted) number substituted into the string if requested
// singular/plural forms may be literal strings or language-specific string IDs
function plural ($num, $sing, $plur, $zero = '')
{
	global $lang, $cur_lang;
	if ($num == 1)
		$str = $sing;
	elseif ($zero)
		$str = $zero;
	else	$str = $plur;

	if (isset($lang[$cur_lang][$str]))
		$str = $lang[$cur_lang][$str];
	elseif (isset($lang[DEFAULT_LANGUAGE][$str]))
		$str = $lang[DEFAULT_LANGUAGE][$str];
	elseif (isset($lang[BASE_LANGUAGE][$str]))
		$str = $lang[BASE_LANGUAGE][$str];

	$str = @sprintf($str, number($num));
	if (strlen($str) == 0)
	{
		$e = error_get_last();
		warning($e['message'], 1);
	}
	return $str;
}

// Substitutes the supplied string ID for the matching string in the currently selected language
// Intended for retrieving language-specific functions
// Resulting function name is cached for faster access until the language is switched
function lang_func ($id, $default = 'lang_unavailable')
{
	global $lang, $cur_lang, $lang_cache;

	if (isset($lang_cache[$id]))
		return $lang_cache[$id];

	if (isset($lang[$cur_lang][$id]))
		$str = $lang[$cur_lang][$id];
	elseif (isset($lang[DEFAULT_LANGUAGE][$id]))
		$str = $lang[DEFAULT_LANGUAGE][$id];
	elseif (isset($lang[BASE_LANGUAGE][$id]))
		$str = $lang[BASE_LANGUAGE][$id];
	else	$str = $default;

	if ($str != $default)
		$lang_cache[$id] = $str;

	return $str;
}

function lang_unavailable ()
{
	warning('Language-specific function not available', 1);
}

// Takes a list and separates it with commas and spaces, including "and" before the last entry
// If there are only 2 entries in the list, no commas are used
function commaList ($list)
{
	$func = lang_func('FUNC_FORMAT_LIST');
	return $func($list);
}

// Formats the specified text as a label to be immediately followed by a value
// Label text may be a string ID
function label ($label, $value = NULL)
{
	global $lang, $cur_lang;

	$func = lang_func('FUNC_FORMAT_LABEL');

	if (isset($lang[$cur_lang][$label]))
		$label = $lang[$cur_lang][$label];
	elseif (isset($lang[DEFAULT_LANGUAGE][$label]))
		$label = $lang[DEFAULT_LANGUAGE][$label];
	elseif (isset($lang[BASE_LANGUAGE][$label]))
		$label = $lang[BASE_LANGUAGE][$label];

	return $func($label, $value);
}

// Formats the specified text as an ordinary number with thousands separators
function number ($num)
{
	$func = lang_func('FUNC_FORMAT_NUMBER');
	return $func($num);
}

// Formats the specified text as a number with a number-sign prefix or suffix
function prenum ($num)
{
	$func = lang_func('FUNC_FORMAT_PRENUM');
	return $func($num);
}

// Formats the specified text as currency
function money ($num)
{
	$func = lang_func('FUNC_FORMAT_MONEY');
	return $func($num);
}

// Formats the specified text as a percentage
function percent ($num, $decimal = 0)
{
	$func = lang_func('FUNC_FORMAT_PERCENT');
	return $func($num, $decimal);
}

// Formats a number of seconds as "N days, N hours, N minutes, N seconds"
// Precision controls number of decimal places for last token
function duration ($num, $precision = 0, $min_level = DURATION_SECONDS, $max_level = DURATION_DAYS)
{
	$func = lang_func('FUNC_FORMAT_DURATION');
	return $func($num, $precision, $min_level, $max_level);
}

// Removes all number formatting from the specified text
function unformat_number ($num)
{
	$func = lang_func('FUNC_UNFORMAT_NUMBER');
	return $func($num);
}

// Truncates a string down to a particular length, if necessary
function truncate ($str, $len)
{
	$func = lang_func('FUNC_TRUNCATE');
	return $func($str, $len);
}
?>
