<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: setup.php 1983 2014-10-01 15:18:43Z quietust $
 */

error_reporting(E_ALL | E_STRICT);
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
define('IN_SETUP', TRUE);
define('PROM_BASEDIR', dirname(__FILE__) . '/');

// Make sure setup.php is running from the base folder
if (!file_exists(PROM_BASEDIR .'config.php') || !file_exists(PROM_BASEDIR .'index.php') || !file_exists(PROM_BASEDIR .'turns.php'))
	die("Access denied - this game has already been configured.");

require_once(PROM_BASEDIR .'config.php');
require_once(PROM_BASEDIR .'includes/constants.php');
require_once(PROM_BASEDIR .'includes/database.php');
require_once(PROM_BASEDIR .'includes/language.php');
require_once(PROM_BASEDIR .'includes/html.php');
require_once(PROM_BASEDIR .'includes/logging.php');
require_once(PROM_BASEDIR .'includes/misc.php');
require_once(PROM_BASEDIR .'classes/prom_vars.php');
require_once(PROM_BASEDIR .'classes/prom_user.php');
require_once(PROM_BASEDIR .'classes/prom_empire.php');

$db = db_open(DB_TYPE, DB_SOCK, DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME);
if (!$db)
	error_503('ERROR_TITLE', 'Unable to connect to database');

$action = getFormVar('action');

$html = new prom_html_compact();
$html->begin('Setup');

$username = getFormVar('admin_username');
$password = getFormVar('admin_password');
$password_verify = getFormVar('admin_password_verify');
$name = htmlspecialchars(getFormVar('admin_name'));
$email = htmlspecialchars(getFormVar('admin_email'));
$empname = htmlspecialchars(getFormVar('admin_empirename'));

// by default, start in 24-48 hours
$deftime = CUR_TIME - (CUR_TIME % 86400) + 86400 * 2;

$begin = getFormVar('round_begin', gmdate('Y/m/d H:i:s O', $deftime));
$closing = getFormVar('round_closing', gmdate('Y/m/d H:i:s O', $deftime + 86400 * 21));
$end = getFormVar('round_end', gmdate('Y/m/d H:i:s O', $deftime + 86400 * 28 - 1));

if ($action == 'submit') do
{
	if (!isFormPost())
		break;
	if (empty($username))
	{
		notice(lang('INPUT_NEED_USERNAME'));
		break;
	}
	if (empty($password))
	{
		notice(lang('INPUT_NEED_PASSWORD'));
		break;
	}
	if (strlen($username) > 255)
	{
		notice(lang('INPUT_USERNAME_TOO_LONG'));
		break;
	}
	if (empty($name))
	{
		notice(lang('INPUT_NEED_NICKNAME'));
		break;
	}
	if (strlen($name) > 255)
	{
		notice(lang('INPUT_NICKNAME_TOO_LONG'));
		break;
	}
	if (!validate_email($email))
	{
		notice(lang('INPUT_NEED_EMAIL'));
		break;
	}
	if (strlen($email) > 255)
	{
		notice(lang('INPUT_EMAIL_TOO_LONG'));
		break;
	}
	if ($password != $password_verify)
	{
		notice(lang('INPUT_PASSWORD_MISMATCH'));
		break;
	}
	if (empty($empname))
	{
		notice(lang('INPUT_NEED_EMPIRE'));
		break;
	}
	if (strlen($empname) > 255)
	{
		notice(lang('INPUT_EMPIRE_TOO_LONG'));
		break;
	}

	$_begin = strtotime($begin);
	$_closing = strtotime($closing);
	$_end = strtotime($end);

	if ($_begin == FALSE)
	{
		notice(lang('SETUP_BAD_START'));
		break;
	}
	if ($_closing == FALSE)
	{
		notice(lang('SETUP_BAD_COOLDOWN'));
		break;
	}
	if ($_end == FALSE)
	{
		notice(lang('SETUP_BAD_END'));
		break;
	}

	if ($_begin < CUR_TIME)
	{
		notice(lang('SETUP_START_IN_FUTURE'));
		break;
	}

	if ($_begin >= $_closing)
	{
		notice(lang('SETUP_COOLDOWN_AFTER_START'));
		break;
	}

	if ($_closing + (VACATION_START + VACATION_LIMIT) * 3600 >= $_end)
	{
		notice(lang('SETUP_COOLDOWN_LENGTH', ceil((VACATION_START + VACATION_LIMIT) / 24)));
		break;
	}

	// Load the database initialization script and run it
	$schema = explode(';', file_get_contents(PROM_BASEDIR .'install/prom.'. $db->getAttribute(PDO::ATTR_DRIVER_NAME)));
	foreach ($schema as $stmt)
	{
		// translate table names
		$stmt = strtr($stmt, $tables);
		// remove any comments
		$stmt = preg_replace('/--.*$/m', '', $stmt);
		// and discard the query if it's empty (i.e. end of the file)
		$stmt = trim($stmt);
		if (!$stmt)
			continue;
		if (!$db->query($stmt))
		{
			notice(lang('SETUP_ERROR_DATABASE_INIT', $stmt));
			break 2;
		}
	}
	$db->createLock(ENT_VARS, 1);
	$db->createLock(ENT_MARKET, 1);

	// lock world variables - if this fails, something has gone horribly wrong
	$world = new prom_vars();
	$world->lock();
	if (!$db->acquireLocks(LOCK_NEW))
	{
		notice(lang('SETUP_ERROR_LOCK_WORLD'));
		break;
	}
	$world->load();

	// create world variables
	$world->create('lotto_current_jackpot');
	$world->create('lotto_yesterday_jackpot');
	$world->create('lotto_last_picked');
	$world->create('lotto_last_winner');
	$world->create('lotto_jackpot_increase');
	$world->create('round_time_begin');
	$world->create('round_time_closing');
	$world->create('round_time_end');
	$world->create('turns_next');
	$world->create('turns_next_hourly');
	$world->create('turns_next_daily');

	$world->lotto_current_jackpot = LOTTERY_JACKPOT;
	$world->lotto_yesterday_jackpot = LOTTERY_JACKPOT;
	$world->lotto_last_picked = 0;
	$world->lotto_last_winner = 0;
	$world->lotto_jackpot_increase = 0;

	$world->round_time_begin = $_begin;
	$world->round_time_closing = $_closing;
	$world->round_time_end = $_end;

	$world->turns_next = $_begin + TURNS_OFFSET * 60;
	$world->turns_next_hourly = $_begin + TURNS_OFFSET_HOURLY * 60;
	$world->turns_next_daily = $_begin + TURNS_OFFSET_DAILY * 60;

	// create user account
	$user = new prom_user();
	$user->create($username, $password, $name, $email, DEFAULT_LANGUAGE);
	$user->u_flags = UFLAG_ADMIN | UFLAG_MOD;
	$user->u_lastip = $_SERVER['REMOTE_ADDR'];

	$emp = new prom_empire();
	$emp->create($user, $empname, RACE_HUMAN);
	$emp->e_flags = EFLAG_ADMIN | EFLAG_VALID;

	$user->save();
	$emp->save();
	$world->save();
	$db->releaseLocks();

	notice(lang('SETUP_COMPLETE'));
	notices();
	$html->end();
	exit;
} while (0);
?>
<h2><?php echo lang('SETUP_TITLE'); ?></h2>
<p><?php echo lang('SETUP_DESC'); ?></p>
<?php
notices(2);
?>
<form method="post" action="setup.php">
<table class="inputtable">
<tr><th colspan="2"><?php echo lang('SETUP_ADMIN'); ?></th></tr>
<tr><th class="ar"><?php echo lang('LABEL_USERNAME'); ?></th>
    <td><input type="text" name="admin_username" value="<?php echo htmlspecialchars($username); ?>" size="8" maxlength="24" /></td></tr>
<tr><th class="ar"><?php echo lang('LABEL_PASSWORD'); ?></th>
    <td><input type="password" name="admin_password" size="8" /></td></tr>
<tr><th class="ar"><?php echo lang('LABEL_PASSWORD_VERIFY'); ?></th>
    <td><input type="password" name="admin_password_verify" size="8" /></td></tr>
<tr><th class="ar"><?php echo lang('LABEL_NICKNAME'); ?></th>
    <td><input type="text" name="admin_name" value="<?php echo $name; ?>" size="24" /></td></tr>
<tr><th class="ar"><?php echo lang('LABEL_EMAIL'); ?></th>
    <td><input type="text" name="admin_email" value="<?php echo $email; ?>" size="24" /></td></tr>
<tr><th class="ar"><?php echo lang('LABEL_EMPIRE'); ?></th>
    <td><input type="text" name="admin_empirename" value="<?php echo $empname; ?>" size="24" maxlength="32" /></td></tr>
<tr><th colspan="2"><hr /></th></tr>
<tr><th colspan="2"><?php echo lang('SETUP_SETTINGS'); ?></th></tr>
<tr><th class="ar"><?php echo lang('LABEL_ROUND_START'); ?></th>
    <td><input type="text" name="round_begin" value="<?php echo $begin; ?>" size="24" /></td></tr>
<tr><th class="ar"><?php echo lang('LABEL_ROUND_COOLDOWN'); ?></th>
    <td><input type="text" name="round_closing" value="<?php echo $closing; ?>" size="24" /></td></tr>
<tr><th class="ar"><?php echo lang('LABEL_ROUND_END'); ?></th>
    <td><input type="text" name="round_end" value="<?php echo $end; ?>" size="24" /></td></tr>
<tr><th colspan="2"><hr /></th></tr>
<tr><td colspan="2" class="ac"><input type="hidden" name="action" value="submit" /><input type="submit" value="<?php echo lang('SETUP_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
$html->end();
?>
