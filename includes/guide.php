<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: guide.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die('Access denied');

require_once(PROM_BASEDIR .'includes/bbcode.php');

define('IN_GUIDE', TRUE);

// load era data for whatever era we've been told to use
$era = new prom_era($_era);

// define topics in table of contents
define('GUIDE_INTRO', 1);
define('GUIDE_INFO', 2);
define('GUIDE_TURNS', 3);
define('GUIDE_FINANCE', 4);
define('GUIDE_FOREIGN', 5);
define('GUIDE_MANAGE', 6);
define('GUIDE_ADMIN', 7);

// define names for each topics, as well as the order they'll be displayed in
$guidetoc_labels = array(
	GUIDE_INTRO => lang('GUIDE_INTRO'),
	GUIDE_INFO => lang('GUIDE_INFO'),
	GUIDE_TURNS => lang('GUIDE_TURNS'),
	GUIDE_FINANCE => lang('GUIDE_FINANCE'),
	GUIDE_FOREIGN => lang('GUIDE_FOREIGN'),
	GUIDE_MANAGE => lang('GUIDE_MANAGE'),
	GUIDE_ADMIN => lang('GUIDE_ADMIN'),
);

// generate an empty table of contents
$guidetoc = array();
foreach ($guidetoc_labels as $id => $label)
	$guidetoc[$id] = array();

// Adds a guide page to the table of contents, under the specified topic
function guidepage ($topic, $page, $func, $title, $subparm = '', $subval = '')
{
	global $guidetoc;
	$guidetoc[$topic][$page] = array('func' => $func, 'url' => guidelink($page, $title, $subparm, $subval));
}

// Constructs a URL to the given guide page
function guidelink ($section, $label, $subparm = '', $subval = '')
{
	global $page;
	$url = '<a href="?location='. $page .'&amp;section='. $section;
	if ($subparm)
	{
		if ($subval != '')
			$url .= '&amp;'. $subparm .'='. $subval;
		else	$url .= '#'.$subparm;
	}
	$url .= '">'. $label .'</a>';
	return $url;
}

// Attempts to load a file within the game guide for the currently selected language
// If it cannot be found (or if no guide path has been specified), use the default language instead
function guidefile ($topic, $file)
{
	$guidefile = langfile('guide/'. $file .'.php');
	if (!$guidefile)
	{
		warning('Unable to load guide file: '. $file, 1);
		return;
	}
	global $adminflag;
	require_once($guidefile);
}

// in-game pages which should redirect to other guide pages
$redirects = array('relogin' => 'main', 'game' => 'main', 'pguide' => 'guide');

guidefile(GUIDE_INTRO, 'misc/intro');
guidefile(GUIDE_INTRO, 'misc/buildings');
guidefile(GUIDE_INTRO, 'misc/units');
guidefile(GUIDE_INTRO, 'misc/races');
guidefile(GUIDE_INTRO, 'misc/eras');

guidefile(GUIDE_INFO, 'main');
guidefile(GUIDE_INFO, 'status');
guidefile(GUIDE_INFO, 'scores');
guidefile(GUIDE_INFO, 'graveyard');
guidefile(GUIDE_INFO, 'search');
guidefile(GUIDE_INFO, 'news');
guidefile(GUIDE_INFO, 'contacts');
guidefile(GUIDE_INFO, 'clanstats');

guidefile(GUIDE_TURNS, 'farm');
guidefile(GUIDE_TURNS, 'cash');
guidefile(GUIDE_TURNS, 'land');
guidefile(GUIDE_TURNS, 'build');
guidefile(GUIDE_TURNS, 'demolish');

guidefile(GUIDE_FINANCE, 'pvtmarketbuy');
guidefile(GUIDE_FINANCE, 'pvtmarketsell');
guidefile(GUIDE_FINANCE, 'pubmarketbuy');
guidefile(GUIDE_FINANCE, 'pubmarketsell');
guidefile(GUIDE_FINANCE, 'bank');
guidefile(GUIDE_FINANCE, 'lottery');

guidefile(GUIDE_FOREIGN, 'aid');
guidefile(GUIDE_FOREIGN, 'clan');
guidefile(GUIDE_FOREIGN, 'clanforum');
guidefile(GUIDE_FOREIGN, 'military');
guidefile(GUIDE_FOREIGN, 'magic');

guidefile(GUIDE_MANAGE, 'messages');
guidefile(GUIDE_MANAGE, 'manage/user');
guidefile(GUIDE_MANAGE, 'manage/empire');
guidefile(GUIDE_MANAGE, 'manage/clan');

guidefile(GUIDE_ADMIN, 'admin/users');
guidefile(GUIDE_ADMIN, 'admin/empires');
guidefile(GUIDE_ADMIN, 'admin/clans');
guidefile(GUIDE_ADMIN, 'admin/market');
guidefile(GUIDE_ADMIN, 'admin/messages');
guidefile(GUIDE_ADMIN, 'admin/permissions');
guidefile(GUIDE_ADMIN, 'admin/log');
guidefile(GUIDE_ADMIN, 'admin/round');
guidefile(GUIDE_ADMIN, 'admin/history');
guidefile(GUIDE_ADMIN, 'admin/empedit');

// if no admin pages are available, then remove the group from the TOC
if (!count($guidetoc[GUIDE_ADMIN]))
	unset($guidetoc[GUIDE_ADMIN]);
unset($topic);

// Table of Contents, automatically generated with all pages defined above
function g_guide ()
{
	global $guidetoc, $guidetoc_labels;
?>
<table class="guidetoc" style="width:80%">
<caption><?php echo lang('GUIDE_SELECT_TOPIC'); ?></caption>
<tr><?php
	foreach ($guidetoc as $id => $topic)
		echo '<th>'. $guidetoc_labels[$id] .'</th>';
?></tr>
<?php
	while (1)
	{
		$nonempty = 0;
		$out = '<tr>';
		foreach ($guidetoc as &$topic)
		{
			$row = array_shift($topic);
			if ($row == NULL)
				$out .= '<td></td>';
			else
			{
				$nonempty = 1;
				$out .= '<td>'. $row['url'] .'</td>';
			}
		}
		$out .= '</tr>';
		if ($nonempty)
			echo $out ."\n";
		else	break;
	}
?>
</table>
<?php
}

$section = getFormVar('section', 'guide');

// If the guide page is a redirect, follow it
if (isset($aliaspages[$section]))
	$section = $redirects[$section];
?>
<h1><?php echo lang('GUIDE_HEADER'); ?></h1>
<div class="guide">
<?php
$func = '';
// Look up the page's name in the table of contents to get its function name
foreach ($guidetoc as $topic)
	if (isset($topic[$section]))
	{
		$func = $topic[$section]['func'];
		break;
	}
// If the page wasn't found, then drop back to the table of contents
if ($func)
	echo guidelink('guide', lang('GUIDE_RETURN_TO_TOC'));
else	$func = 'g_guide';
$func();
?>
</div>
