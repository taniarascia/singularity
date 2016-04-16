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
 * $Id: index.php 1983 2014-10-01 15:18:43Z quietust $
 */

date_default_timezone_set('UTC');
ignore_user_abort(1);
set_time_limit(0);

// don't allow CLI invocation
if (!isset($_SERVER['REQUEST_URI']))
	die("Access denied");

// prevent recursion
if (defined('IN_GAME'))
	die("Access denied");

define('IN_GAME', TRUE);
define('PROM_BASEDIR', dirname(__FILE__) . '/');

// Don't allow accessing anything while setup.php is being run
if (file_exists(PROM_BASEDIR .'setup.php'))
	die("Access denied");

require_once(PROM_BASEDIR .'config.php');
require_once(PROM_BASEDIR .'includes/constants.php');
require_once(PROM_BASEDIR .'includes/database.php');
require_once(PROM_BASEDIR .'includes/language.php');
require_once(PROM_BASEDIR .'includes/html.php');
require_once(PROM_BASEDIR .'includes/logging.php');
require_once(PROM_BASEDIR .'includes/misc.php');
require_once(PROM_BASEDIR .'includes/news.php');
require_once(PROM_BASEDIR .'includes/permissions.php');
require_once(PROM_BASEDIR .'classes/prom_vars.php');
require_once(PROM_BASEDIR .'classes/prom_user.php');
require_once(PROM_BASEDIR .'classes/prom_empire.php');
require_once(PROM_BASEDIR .'classes/prom_clan.php');
require_once(PROM_BASEDIR .'classes/prom_session.php');
require_once(PROM_BASEDIR .'classes/prom_turns.php');
require_once(PROM_BASEDIR .'includes/auth.php');

// Valid in-game pages - can be specified for 'location' parameter to load corresponding PHP file
// Values denote any special requirements for loading the page
$valid_locations = array(
	// 0 - does not require referer or session
	'login' => 0,
	'count' => 0,
	'signup' => 0,
	'topempires' => 0,
	'topclans' => 0,
	'topplayers' => 0,
	'history' => 0,
	'pguide' => 0, 
	'playerstats' => 0,
	'credits' => 0,
	'relogin' => 0, // redirect from login page load; redirects don't set referer, and this could be a bookmark

	// 1 - requires referer from any site
	'game' => 1, // redirect from login page submission; redirects don't set referer

	// 2 - requires referer from in-game, also requires active session
	'main' => 2, // both 'relogin' and 'game' redirect to here
	'banner' => 2,
	'guide' => 2,
	'messages' => 2,
	'validate' => 2,
	'revalidate' => 2,

	// Information
	'status' => 2,
	'scores' => 2,
	'graveyard' => 2,
	'search' => 2,
	'news' => 2,
	'contacts' => 2,
	'clanstats' => 2,

	// Use Turns
	'farm' => 2,
	'cash' => 2,
	'land' => 2,
	'build' => 2,
	'demolish' => 2,

	// Finances
	'pvtmarketbuy' => 2,
	'pvtmarketsell' => 2,
	'pubmarketbuy' => 2,
	'pubmarketsell' => 2,
	'bank' => 2,
	'lottery' => 2,

	// Foreign Affairs
	'aid' => 2,
	'clan' => 2,
	'clanforum' => 2,
	'military' => 2,
	'magic' => 2,

	// Management
	'manage/user' => 2,
	'manage/empire' => 2,
	'manage/clan' => 2,
	'delete' => 2,

	// Administration
	'admin/users' => 2,
	'admin/empires' => 2,
	'admin/clans' => 2,
	'admin/market' => 2,
	'admin/messages' => 2,
	'admin/history' => 2,
	'admin/round' => 2,
	'admin/log' => 2,
	'admin/permissions' => 2,
	'admin/empedit' => 2,

	// Logout
	'logout' => 2,
);

function validate_location ($page)
{
	global $valid_locations;
	if (!isset($valid_locations[$page]))
		return 'error.badpage';

	// pages that work from anywhere
	switch ($valid_locations[$page])
	{
	case 0: // no referer needed
		// special case - go to "login" page when you still have an active session
		if ($page == 'relogin')
			$page = 'main';
		return $page;
		break;
	case 1: // need referer
		if (!isset($_SERVER['HTTP_REFERER']))
			return 'error.noref';
		if ($page == 'game')
			$page = 'main';
		return $page;
		break;
	case 2: // need in-game referer
		if (!isset($_SERVER['HTTP_REFERER']))
			return 'error.noref';
		if (!stristr($_SERVER['HTTP_REFERER'], URL_BASE))
			return 'error.badref';
		return $page;
		break;
	}
	return 'error.badpage';
}

$db = db_open(DB_TYPE, DB_SOCK, DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME);
if (!$db)
	error_503('ERROR_TITLE', lang('ERROR_DATABASE_OFFLINE'));

set_error_handler('error_handler', E_WARNING | E_NOTICE | E_STRICT | E_RECOVERABLE_ERROR);

// load world variables
$world = new prom_vars();
if (!$world->load())
	error_500('ERROR_TITLE', 'Unable to read world variables!');
if (!$world->check())
	error_503('ERROR_TITLE', 'Game setup is not yet complete.');

// If we're configured for cronless turn updates, check them now
$turns = new prom_turns();
if (!TURNS_CRONTAB)
	$turns->doUpdate();

// define constants based on round start/end times
if (CUR_TIME < $world->round_time_begin)
{	// pre-registration
	define('ROUND_SIGNUP', TRUE);
	define('ROUND_STARTED', FALSE);
	define('ROUND_CLOSING', FALSE);
	define('ROUND_FINISHED', FALSE);
	define('TXT_TIMENOTICE', lang('ROUND_WILL_BEGIN', gmdate(lang('ROUND_WILL_BEGIN_FORMAT'), $world->round_time_begin - CUR_TIME)));
}
elseif (CUR_TIME < $world->round_time_closing)
{	// normal gameplay
	define('ROUND_SIGNUP', TRUE);
	define('ROUND_STARTED', TRUE);
	define('ROUND_CLOSING', FALSE);
	define('ROUND_FINISHED', FALSE);
}
elseif (CUR_TIME < $world->round_time_end)
{	// final week (or so)
	define('ROUND_SIGNUP', FALSE);
	define('ROUND_STARTED', TRUE);
	define('ROUND_CLOSING', TRUE);
	define('ROUND_FINISHED', FALSE);
	define('TXT_TIMENOTICE', lang('ROUND_WILL_END', gmdate(lang('ROUND_WILL_END_FORMAT'), $world->round_time_end - CUR_TIME)));
}
else
{	// end of round
	define('ROUND_SIGNUP', FALSE);
	define('ROUND_STARTED', FALSE);
	define('ROUND_CLOSING', FALSE);
	define('ROUND_FINISHED', TRUE);
	define('TXT_TIMENOTICE', lang('ROUND_HAS_ENDED'));
}

if ($ban = check_banned_ip($_SERVER['REMOTE_ADDR']))
{
	$ban_message = lang('YOU_ARE_BANNED',
		gmdate(lang('COMMON_TIME_FORMAT'), $ban['p_createtime']),
		($ban['p_reason']) ? $ban['p_reason'] : lang('BANNED_NO_REASON'),
		($ban['p_expire'] == 0) ? lang('BANNED_PERMANENT') : lang('BANNED_EXPIRES', gmdate(lang('COMMON_TIME_FORMAT'), $ban['p_expire'])),
		MAIL_ADMIN);

	error_403('ERROR_TITLE_ACCESS', $ban_message);
}

// Special variables parsed by page_header()
// Page title ("Promisance - whatever")
$title = '';

// Set to combination of UFLAG_MOD/UFLAG_ADMIN to indicate USER privileges required to load page
$needpriv = 0;

// Add entries to this array to request entities to be loaded and locked.
$lock = array('emp1' => 0, 'emp2' => 0, 'user1' => 0, 'user2' => 0, 'clan1' => 0, 'clan2' => 0, 'world' => 0);
// Set to an entity number, or use -1 (for non-empires) to determine the ID automatically from the loaded empire
// If loading an entity fails, its value will be reset to 0
// Setting 'emp1' has no effect - it exists for logging purposes and is automatically set to the current empire ID
// Setting 'user1' or 'world' to anything other than -1 has no effect - they only allow auto-detection.
// Additional locks (for special purposes) can be requested directly from $db

$triedpage = getFormVar('location', 'login');
$page = validate_location($triedpage);
$action = getFormVar('action');

// if they tried entering a really really long action, truncate it and log a warning
if (strlen($action) > 64)
{
	$oldaction = $action;
	$action = substr($action, 0, 64);
	logmsg(E_USER_NOTICE, 'action overflowed: '. $oldaction);
}

$errchk = explode('.', $page);
if ($errchk[0] == 'error')
{
	$message = '<table><tr><th>'. lang('SECURITY_TITLE') .'</th></tr><tr><td>'. lang('SECURITY_DESC') .'<br />';
	$error_args = array('triedpage', 'action');
	$logmsg = $triedpage;
	if ($errchk[1] == 'badpage')
		$message .= lang('SECURITY_BADPAGE', htmlspecialchars($triedpage)) .'<br />';
	elseif ($errchk[1] == 'badref')
	{
		$referer = $_SERVER['HTTP_REFERER'];
		$message .= lang('SECURITY_BADREF', htmlspecialchars($triedpage), htmlspecialchars($referer), URL_BASE) .'<br />';
		$error_args[] = 'referer';
	}
	elseif ($errchk[1] == 'noref')
		$message .= lang('SECURITY_NOREF', htmlspecialchars($triedpage)) .'<br />';
	else	$message .= lang('SECURITY_UNKNOWN', htmlspecialchars($triedpage), $errchk[1]) .'<br />';
	$message .= lang('SECURITY_INSTRUCT', URL_BASE, MAIL_ADMIN) .'</td></tr></table>';

	logmsg(E_USER_ERROR, varlist($error_args, get_defined_vars()));

	if ($errchk[1] == 'badpage')
		error_404('ERROR_TITLE', $message);
	else	error_403('ERROR_TITLE', $message);
}

// check if destination page requires an active session
if ($valid_locations[$page] == 2)
{
	// prevent logout page from suggesting to log back in again
	if ($page == 'logout')
		$auth = checkAuth(FALSE);
	else	$auth = checkAuth();
	if ($auth != '')
		error_403('ERROR_TITLE_ACCESS', $auth);
}

require_once(PROM_BASEDIR .'pages/'. $page .'.php');
// Do not put anything after this line, since it may not be executed.
?>
