<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: en-US.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$lang['en-US'] = array(
	// Display name for language (within Preferences)
	'LANG_ID'	=> 'English (United States)',
	// Language code as reported in <html> tag
	'LANG_CODE'	=> 'en-US',
	// Text direction as reported in <html> tag
	'LANG_DIR'	=> 'ltr',
	// Location of additional strings for this language (within lang directory)
	// as well as guide pages (in "guide/" directory within this path)
	'LANG_PATH'	=> 'en-US/',
	// Various language-dependent string formatting routines
	'FUNC_FORMAT_LIST'	=> 'en_us_format_list',
	'FUNC_FORMAT_LABEL'	=> 'en_us_format_label',
	'FUNC_FORMAT_NUMBER'	=> 'en_us_format_number',
	'FUNC_FORMAT_PRENUM'	=> 'en_us_format_prenum',
	'FUNC_FORMAT_MONEY'	=> 'en_us_format_money',
	'FUNC_FORMAT_PERCENT'	=> 'en_us_format_percent',
	'FUNC_FORMAT_DURATION'	=> 'en_us_format_duration',
	'FUNC_UNFORMAT_NUMBER'	=> 'en_us_unformat_number',
	'FUNC_TRUNCATE'		=> 'en_us_truncate',
);
function en_us_format_list ($list)
{
	if (count($list) == 0)
		return '';
	if (count($list) == 1)
		return array_shift($list);
	if (count($list) == 2)
		return array_shift($list) .' and '. array_shift($list);

	$out = '';
	while (count($list) > 1)
		$out .= array_shift($list) .', ';
	$out .= 'and '. array_shift($list);
	return $out;
}
function en_us_format_label ($label, $value = NULL)
{
	if (isset($value))
		return $label .': '. $value;
	else	return $label .':';
}
function en_us_format_number ($num)
{
	return number_format($num, 0, '.', ',');
}
function en_us_format_prenum ($num)
{
	return '#'. number_format($num, 0, '.', ',');
}
function en_us_format_money ($num)
{
	if ($num < 0)
		return '-$'. number_format(abs($num), 0, '.', ',');
	else	return '$'. number_format($num, 0, '.', ',');
}
function en_us_format_percent ($num, $decimal)
{
	return number_format($num, $decimal, '.', ',') .'%';
}
function en_us_format_duration ($num, $precision, $level_min, $level_max)
{
	$prefix = '';
	if ($num < 0)
	{
		$num = -$num;
		$prefix = '-';
	}
	$dur = array();
	if ($level_max < $level_min)
		$level_max = $level_min;
	if (($level_min <= DURATION_DAYS) && ($level_max >= DURATION_DAYS))
	{
		$divisor = 60 * 60 * 24;
		if ($level_min == DURATION_DAYS)
		{
			// only include fractions for the final token, and allow zero if it's the only token
			$x = round($num / $divisor, $precision);
			if ($x == 1)
				$dur[] = "$x day";
			elseif ($x || !count($dur))
				$dur[] = "$x days";
		}
		else
		{
			$x = floor($num / $divisor);
			if ($x == 1)
				$dur[] = "$x day";
			elseif ($x)
				$dur[] = "$x days";
			$num -= $x * $divisor;
		}
	}
	if (($level_min <= DURATION_HOURS) && ($level_max >= DURATION_HOURS))
	{
		$divisor = 60 * 60;
		if ($level_min == DURATION_HOURS)
		{
			$x = round($num / $divisor, $precision);
			if ($x == 1)
				$dur[] = "$x hour";
			elseif ($x || !count($dur))
				$dur[] = "$x hours";
		}
		else
		{
			$x = floor($num / $divisor);
			if ($x == 1)
				$dur[] = "$x hour";
			elseif ($x)
				$dur[] = "$x hours";
			$num -= $x * $divisor;
		}
	}
	if (($level_min <= DURATION_MINUTES) && ($level_max >= DURATION_MINUTES))
	{
		$divisor = 60;
		if ($level_min == DURATION_MINUTES)
		{
			$x = round($num / $divisor, $precision);
			if ($x == 1)
				$dur[] = "$x minute";
			elseif ($x || !count($dur))
				$dur[] = "$x minutes";
		}
		else
		{
			$x = floor($num / $divisor);
			if ($x == 1)
				$dur[] = "$x minute";
			elseif ($x)
				$dur[] = "$x minutes";
			$num -= $x * $divisor;
		}
	}
	if (($level_min <= DURATION_SECONDS) && ($level_max >= DURATION_SECONDS))
	{
		$divisor = 1;
		if ($level_min == DURATION_SECONDS)
		{
			$x = round($num / $divisor, $precision);
			if ($x == 1)
				$dur[] = "$x second";
			elseif ($x || !count($dur))
				$dur[] = "$x seconds";
		}
		else
		{
			$x = floor($num / $divisor);
			if ($x == 1)
				$dur[] = "$x second";
			elseif ($x)
				$dur[] = "$x seconds";
			$num -= $x * $divisor;
		}
	}
	return $prefix.implode(', ', $dur);
}
function en_us_unformat_number ($num)
{
	return str_replace(array('$', '#', '%', ','), '', $num);
}
function en_us_truncate ($str, $len)
{
	if (strlen($str) > $len)
		$str = substr($str, 0, $len - 3) .'...';
	return $str;
}
?>
