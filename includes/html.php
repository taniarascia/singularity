<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: html.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

require_once(PROM_BASEDIR .'classes/prom_html.php');

// Prints an error page intended to report that access is denied
function error_403 ($title, $description)
{
	if (substr(php_sapi_name(), 0, 3) == 'cgi')
		header('Status: 403 Forbidden');
	else	header($_SERVER["SERVER_PROTOCOL"] .' 403 Forbidden');
	error_die($title, $description);
}

// Prints an error page intended to report that the page does not exist
function error_404 ($title, $description)
{
	if (substr(php_sapi_name(), 0, 3) == 'cgi')
		header('Status: 404 Not Found');
	else	header($_SERVER["SERVER_PROTOCOL"] .' 404 Not Found');
	error_die($title, $description);
}

// Prints an error page intended to report that something is broken
function error_500 ($title, $description)
{
	if (substr(php_sapi_name(), 0, 3) == 'cgi')
		header('Status: 500 Internal Server Error');
	else	header($_SERVER["SERVER_PROTOCOL"] .' 500 Internal Server Error');
	error_die($title, $description);
}

// Prints an error page intended to report that something is temporarily offline
function error_503 ($title, $description)
{
	if (substr(php_sapi_name(), 0, 3) == 'cgi')
		header('Status: 503 Service Unavailable');
	else	header($_SERVER["SERVER_PROTOCOL"] .' 503 Service Unavailable');
	error_die($title, $description);
}

// Prints an error message within a compact HTML page and ends script execution.
// Should only be used with critical errors encountered before database locks have been established
function error_die ($title, $description)
{
	$html = new prom_html_compact();
	$html->begin($title);
	echo $description .'<br />'."\n";
	$html->end();
	exit;
}

// Prints a preformatted number enclosed within a <span> tag, colored according to its positivity/negativity.
function colornum ($amt, $fmt, $pcolor = 'cgood', $ncolor = 'cbad', $zcolor = 'cneutral', $psign = '+', $nsign = '-', $zsign = '')
{
	$out = '<span class="';
	if ($amt > 0)
		$out .= $pcolor .'">'. $psign;
	elseif ($amt < 0)
		$out .= $ncolor .'">'. $nsign;
	else	$out .= $zcolor .'">'. $zsign;
	$out .= $fmt.'</span>';
	return $out;
}

// Generates a link to sort a table by a particular column
// $url will have sort parameters appended directly, so it must end in "?" or "&amp;"
function sortlink ($title, $url, $cursort, $curdir, $newsort = '', $defdir = 'asc', $page = 0)
{
	if ($newsort != '')
	{
		$url = '<a href="'. $url .'sortcol='. $newsort .'&amp;sortdir=';
		if ($cursort == $newsort)
		{
			if ($curdir == 'asc')
				$url .= 'desc';
			else	$url .= 'asc';
		}
		else	$url .= $defdir;
	}
	else	$url = '<a href="'. $url .'sortcol='. $cursort .'&amp;sortdir='. $curdir;
	if ($page > 0)
		$url .= '&amp;page='. $page;
	$url .= '">'. $title .'</a>';
	if ($cursort == $newsort)
	{
		if ($curdir == 'desc')
			$url .= ' '. lang('HTML_SORT_DESCEND');
		else	$url .= ' '. lang('HTML_SORT_ASCEND');
	}
	return $url;
}

// Generates HTML for a dropdown list and optionally preselects a particular option
// Specify an array for $enabled to selectively disable individual options
function optionlist ($name, $options, $default = '', $enabled = TRUE)
{
	$out = '<select name="'. $name .'"';
	if (!is_array($enabled) && !$enabled)
		$out .= ' disabled="disabled"';
	$out .= '>';
	foreach ($options as $val => $label)
	{
		$out .= '<option value="'. $val .'"';
		if (is_array($enabled) && isset($enabled[$val]) && !$enabled[$val])
			$out .= ' disabled="disabled"';
		elseif ($val == $default)
			$out .= ' selected="selected"';
		$out .= '>'. $label .'</option>';
	}
	$out .= '</select>';
	return trim($out);
}

// Generates HTML for a set of radio buttons and optionally preselects a particular option
// Specify an array for $enabled to selectively disable individual options
function radiolist ($name, $options, $default = '', $enabled = TRUE)
{
	$out = '';
	foreach ($options as $val => $label)
	{
		$out .= '<label><input type="radio" name="'. $name .'" value="'. $val .'"';
		if ((!is_array($enabled) && !$enabled) || (is_array($enabled) && isset($enabled[$val]) && !$enabled[$val]))
			$out .= ' disabled="disabled"';
		elseif ($val == $default)
			$out .= ' checked="checked"';
		$out .= ' />'. $label .'</label> ';
	}
	return trim($out);
}

// Generates HTML for a checkbox with optional label
function checkbox ($name, $label = '', $value = 1, $checked = FALSE, $enabled = TRUE, $id = NULL)
{
	$out = '';
	if ($label)
		$out .= '<label>';
	$out .= '<input type="checkbox" name="'. $name .'" value="'. $value .'"';
	if ($checked)
		$out .= ' checked="checked"';
	if (!$enabled)
		$out .= ' disabled="disabled"';
	if ($id)
		$out .= ' id="'. $id .'"';
	$out .= ' />';
	if ($label)
		$out .= $label .'</label>';
	return trim($out);
}

// Generates HTML for a radio button with optional label
function radiobutton ($name, $label = '', $value = 1, $checked = FALSE, $enabled = TRUE)
{
	$out = '';
	if ($label)
		$out .= '<label>';
	$out .= '<input type="radio" name="'. $name .'" value="'. $value .'"';
	if ($checked)
		$out .= ' checked="checked"';
	if (!$enabled)
		$out .= ' disabled="disabled"';
	$out .= ' />';
	if ($label)
		$out .= $label .'</label>';
	return trim($out);
}

// Generates HTML for a button to set a form field to a particular value
function copybutton ($id, $value)
{
	return '<input type="button" value="'. lang('HTML_BUTTON_COPY') .'" onclick="'. $id .'.value=\''. $value .'\'"/>';
}

// Generates a list of page links for the specified content
function pagelist ($curpage, $maxpage, $sortlink, $sortcol, $sortdir)
{
	$out = lang('COMMON_PAGES_LABEL') .' ';
	if ($curpage > 1)
		$out .= sortlink(lang('COMMON_PAGES_PREV'), $sortlink, $sortcol, $sortdir, '', '', $curpage - 1) .' ';
	for ($i = 1; $i <= $maxpage; $i++)
	{
		if ((($i > 1) && ($i < $curpage - 3)) || (($i > $curpage + 3) && ($i < $maxpage)))
			continue;
		if ($i == $curpage)
			$out .= '<b>'. $i .'</b>';
		else	$out .= sortlink($i, $sortlink, $sortcol, $sortdir, '', '', $i);
		if ((($i == 1) && ($curpage > 5)) || (($i == $curpage + 3) && ($curpage + 4 < $maxpage)))
			$out .= lang('COMMON_PAGES_GAP');
		elseif ($i != $maxpage)
			$out .= lang('COMMON_PAGES_SEP');
	}
	if ($curpage < $maxpage)
		$out .= ' '.sortlink(lang('COMMON_PAGES_NEXT'), $sortlink, $sortcol, $sortdir, '', '', $curpage + 1);
	return $out;
}

// Parse sort parameters into a suitable ORDER BY query segment, correcting any invalid inputs in the process
function parsesort (&$sortcol, &$sortdir, $sorttypes)
{
	if ($sortdir == 'asc')
		$dir = 'ASC';
	else
	{
		$sortdir = 'desc';
		$dir = 'DESC';
	}
	if ((!isset($sorttypes[$sortcol])) || ($sortcol == '_default'))
		$sortcol = $sorttypes['_default'];
	$sortby = str_replace('{DIR}', $dir, $sorttypes[$sortcol][0]);

	return $sortby;
}

// Parse page number into row offset, correcting any invalid input in the process
function parsepage (&$page, $maxentries, $pagelen)
{
	$offset = ($page - 1) * $pagelen;	// initial guess
	if ($offset > $maxentries)
		$page = ceil(max(1, $maxentries) / $pagelen);
	if ($offset < 0)
		$page = 1;
	$offset = ($page - 1) * $pagelen;	// in case the page number was recalculated

	return $offset;
}

function submenu ($menu)
{
	global $page;
	$opened = false;
	$out = '';
	foreach ($menu as $item)
	{
		$out .= '<li>';
		if (isset($item['location']))
		{
			$item['url'] = '?location='. $item['location'];
			if ($item['location'] == $page)
				$opened = true;
		}
		if (isset($item['extra']))
			$item['extra'] = ' '. $item['extra'];
		else	$item['extra'] = '';

		if (isset($item['url']))
			$out .= '<a href="'. $item['url'] . '"'. $item['extra'] .'>'. $item['label'] .'</a>';
		else	$out .= $item['label'];

		if (isset($item['submenu']))
			$out .= submenu($item['submenu']);
		$out .= '</li>'."\n";
	}
	if ($opened)
		$out = '<ul class="menusel">'. $out .'</ul>';
	else	$out = '<ul>'. $out .'</ul>';
	return $out;
}
?>
