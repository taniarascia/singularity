<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: signup.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die('Access denied');

if (!ROUND_SIGNUP)
	error_403('ERROR_TITLE', lang('ERROR_SIGNUP_CLOSED'));

if (SIGNUP_CLOSED_USER && SIGNUP_CLOSED_EMPIRE)
	error_403('ERROR_TITLE', lang('ERROR_SIGNUP_DISABLED'));

$username = getFormVar('signup_username');
$password = getFormVar('signup_password');
$password_verify = getFormVar('signup_password_verify');
$name = htmlspecialchars(getFormVar('signup_name'));
$email = getFormVar('signup_email');
$email_verify = getFormVar('signup_email_verify');
$language = getFormVar('signup_lang');
$empname = htmlspecialchars(getFormVar('signup_empirename'));
$race = getFormVar('signup_race', RACE_HUMAN);

if (!setlanguage($language))
	$language = $cur_lang;

$races = prom_race::getNames();

// bounced from the login page, so prefill the username
$reg = fixInputBool(getFormVar('registered'));
if ($reg && !$username)
	$username = $reg;

$html = new prom_html_compact();
$acct_created = 0;
if ($action == 'signup') do
{
	if (!isFormPost())
		break;

	// Part 1 - Do cursory checks on all necessary inputs
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
	if (lang_isset($username))
	{
		notice(lang('INPUT_BAD_USERNAME'));
		break;
	}
	// Only check empire inputs if they're actually available
	if (!SIGNUP_CLOSED_EMPIRE)
	{
		if (empty($empname))
		{
			notice(lang('INPUT_NEED_EMPIRE'));
			break;
		}
		if (empty($race))
		{
			notice(lang('SIGNUP_NEED_RACE'));
			break;
		}
		if (strlen($empname) > 255)
		{
			notice(lang('INPUT_EMPIRE_TOO_LONG'));
			break;
		}
		if (lang_isset($empname))
		{
			notice(lang('INPUT_BAD_EMPIRE'));
			break;
		}
		if (!prom_race::exists($race))
		{
			notice(lang('SIGNUP_RACE_INVALID'));
			break;
		}
	}

	// Part 2 - Create or verify login info
	$user = new prom_user();
	if ($user->findName($username))
	{
		// The user has registered here once before
		if (SIGNUP_CLOSED_EMPIRE)
		{
			notice(lang('SIGNUP_EMPIRE_CLOSED', 'SIGNUP_HEADER_EXTRA'));
			break;
		}
		$user->load();
		if (!$user->checkPassword($password))
		{
			// If they typed the password twice, assume they were trying to create a new account
			// otherwise, assume they were trying to use an existing account
			if (strlen($password_verify))
				notice(lang('INPUT_USERNAME_IN_USE'));
			else
			{
				notice(lang('INPUT_INCORRECT_PASSWORD'));
				logmsg(E_USER_NOTICE, 'failed (password) - '. $username);
			}
			break;
		}
		if ($user->u_flags & UFLAG_DISABLE)
		{
			notice(lang('SIGNUP_ACCOUNT_DISABLED'));
			logmsg(E_USER_NOTICE, 'failed (disabled) - '. $username);
			break;
		}
		if ($user->u_flags & UFLAG_CLOSED)
		{
			notice(lang('SIGNUP_ACCOUNT_CLOSED'));
			logmsg(E_USER_NOTICE, 'failed (closed) - '. $username);
			break;
		}
		$lock['user1'] = $user->u_id;
		db_lockentities(array($user), $user->u_id);	// user exists, password OK, so lock it
	}
	else
	{
		if (SIGNUP_CLOSED_USER)
		{
			notice(lang('SIGNUP_USER_CLOSED', 'SIGNUP_HEADER_EXTRA'));
			break;
		}
		// Try to create a new account for the user
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
		if (lang_isset($name))
		{
			notice(lang('INPUT_BAD_NICKNAME'));
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
		if ($ban = check_banned_email($email))
		{
			notice(lang('SIGNUP_EMAIL_BANNED', ($ban['p_reason']) ? $ban['p_reason'] : lang('BANNED_NO_REASON')));
			break;
		}
		if ($password != $password_verify)
		{
			notice(lang('INPUT_PASSWORD_MISMATCH'));
			break;
		}
		if ($email != $email_verify)
		{
			notice(lang('INPUT_EMAIL_MISMATCH'));
			break;
		}
		if ($db->queryCell('SELECT COUNT(*) FROM '. USER_TABLE .' WHERE u_email = ?', array($email)) > 0)
		{
			notice(lang('INPUT_EMAIL_IN_USE'));
			break;
		}
		// We need to have at least one lock acquired in order to safely create a user account
		$db->lockSingle(ENT_VARS, 1);
		$db->acquireLocks(LOCK_NEW);
		$user->create($username, $password, $name, $email, $language);
		$acct_created = 1;
		$user->save();
		$lock['user1'] = $user->u_id;
		logevent(varlist(array('username', 'name', 'email'), get_defined_vars()));
		if (SIGNUP_CLOSED_EMPIRE)
		{
			$db->releaseLocks();
?>
<br /><?php echo lang('SIGNUP_CANNOT_CONTINUE'); ?><br /><br />
<?php
			$html->end();
			exit;
		}
	}

	// Part 3 - Attempt to create an empire for the user
	// At this point, the user record is locked (and possibly mid-transaction), so we MUST release locks if we encounter any problems below

	$empires = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id = ?', array($user->u_id));
	if ($empires >= EMPIRES_PER_USER)
	{
		notice(lang('SIGNUP_NO_MORE_EMPIRES'));
		$db->releaseLocks();
		break;
	}
	if ($db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE e_name = ?', array($empname)) > 0)
	{
		notice(lang('INPUT_EMPIRE_IN_USE'));
		$db->releaseLocks();
		break;
	}

	// Everything is good - proceed and create the empire

	$emp = new prom_empire();
	$emp->create($user, $empname, $race);
	$lock['emp1'] = $emp->e_id;

	// establish login session with this empire selected
	prom_session::start();
	$_SESSION['user'] = $user->u_id;
	$_SESSION['empire'] = $emp->e_id;

	$user->u_lastip = $_SERVER['REMOTE_ADDR'];
	$user->u_lastdate = CUR_TIME;

	// only set them online if the round has actually started
	if (ROUND_STARTED)
		$emp->setFlag(EFLAG_ONLINE);

	// commit to database
	$user->save();
	$emp->save();
	$db->releaseLocks();

	$html->begin('SIGNUP_TITLE');
	echo lang('SIGNUP_COMPLETE', $emp, $user->u_email) .'<br />';
	if ($empires > 0)
		echo lang('SIGNUP_MULTIPLE') .'<br />';
?>
<br /><a href="?location=game"><?php echo lang('SIGNUP_CONTINUE'); ?></a><br /><br />
<?php
	if (VALIDATE_ALLOW)
	{
		$mailerror = $emp->sendValidationMail($user);
		if ($mailerror)
			echo '<div class="cwarn">'. lang('SIGNUP_MAILERROR', $mailerror) .'</div>';
	}
	$html->end();
	logevent(varlist(array('username', 'empname', 'race', 'accounts', 'mailerror'), get_defined_vars()));
	exit;
} while (0);
$html->begin('SIGNUP_TITLE');
?>
<h2><?php echo lang('SIGNUP_HEADER'); ?></h2>
<div style="max-width: 800px;margin:auto;">
<?php
if ($reg)
	echo lang('SIGNUP_WELCOME_BACK', GAME_TITLE) .'<br />';
else	echo lang('SIGNUP_WELCOME_FIRST', GAME_TITLE, GRAVEYARD_DISCLOSE ? 'SIGNUP_WELCOME_DISCLOSE' : 'SIGNUP_WELCOME_NO_DISCLOSE') .'<br /><br />';
?>

<?php
if (VALIDATE_ALLOW) echo '<h3>'. lang('SIGNUP_VALIDATION_REMINDER', MAIL_VALIDATE) .'</h3>';
// if account creation was successful but empire creation failed
if ($acct_created) echo '<h4 class="cwarn">'. lang('SIGNUP_USER_BUT_NOT_EMPIRE') .'<b><br />';
notices(2);
?>
<h4><?php
if (SIGNUP_CLOSED_USER)
	echo lang('SIGNUP_USER_CLOSED_REMINDER', 'SIGNUP_HEADER_EXTRA');
elseif (SIGNUP_CLOSED_EMPIRE)
	echo lang('SIGNUP_EMPIRE_CLOSED_REMINDER', 'SIGNUP_HEADER_EXTRA');
else	echo lang('SIGNUP_REPLAY_REMINDER');
?></h4>
<form method="post" action="?location=signup">
<table class="inputtable">
<tr><th colspan="2"><?php echo lang('SIGNUP_ACCOUNT_INFO'); ?></th></tr>
<tr><th class="ar"><?php echo lang('LABEL_USERNAME'); ?></th>
    <td><input type="text" name="signup_username" value="<?php echo htmlspecialchars($username); ?>" size="8" maxlength="24" /></td></tr>
<tr><th class="ar"><?php echo lang('LABEL_PASSWORD'); ?></th>
    <td><input type="password" name="signup_password" size="8" /></td></tr>
<?php
if (!SIGNUP_CLOSED_USER)
{
?>
<tr><th class="ar">* <?php echo lang('LABEL_PASSWORD_VERIFY'); ?></th>
    <td><input type="password" name="signup_password_verify" size="8" /></td></tr>
<tr><th class="ar">* <?php echo lang('LABEL_NICKNAME'); ?></th>
    <td><input type="text" name="signup_name" value="<?php echo $name; ?>" size="24" /></td></tr>
<tr><th class="ar">* <?php echo lang('LABEL_EMAIL'); ?></th>
    <td><input type="text" name="signup_email" value="<?php echo htmlspecialchars($email); ?>" size="24" /></td></tr>
<tr><th class="ar">* <?php echo lang('LABEL_EMAIL_VERIFY'); ?></th>
    <td><input type="text" name="signup_email_verify" size="24" /></td></tr>
<tr><th class="ar">* <?php echo lang('SIGNUP_LANGUAGE'); ?></th>
    <td><?php
$langlist = array();
foreach (array_keys($lang) as $lang_id)
	$langlist[$lang_id] = $lang[$lang_id]['LANG_ID'];
echo optionlist('signup_lang', $langlist, $language);
?></td></tr>
<tr><td colspan="2" style="font-size:small;text-align:center"><?php echo lang('SIGNUP_PRIVACY'); ?></td></tr>
<?php
}
?>
<?php
if (!SIGNUP_CLOSED_EMPIRE)
{
?>
<tr><th colspan="2"><?php echo lang('SIGNUP_EMPIRE_INFO'); ?></th></tr>
<tr><th class="ar"><?php echo lang('LABEL_EMPIRE'); ?></th>
    <td><input type="text" name="signup_empirename" value="<?php echo $empname; ?>" size="24" maxlength="32" /></td></tr>
<tr><th class="ar"><?php echo lang('LABEL_RACE'); ?></th>
    <td><?php
$racelist = array();
foreach ($races as $rid => $rname)
	$racelist[$rid] = $rname;
echo optionlist('signup_race', $racelist, $race);
?> <a href="?location=pguide&amp;section=races"><?php echo lang('SIGNUP_RACE_DETAILS'); ?></a></td></tr>
<?php
}
?>
<tr><td colspan="2" class="ac"><input type="hidden" name="action" value="signup" /><input type="submit" value="<?php echo lang('SIGNUP_SUBMIT'); ?>" /></td></tr>
</table>
</form>
</div>
<b><?php echo lang('SIGNUP_WARN_RULES'); ?></b><br /><br />
<table class="inputtable">
<caption style="font-size:large;font-weight:bold"><?php echo lang('SIGNUP_HEADER_RULES'); ?></caption>
<tr><th style="font-size:large"><?php echo lang('SIGNUP_HEADER_MULTIS'); ?></th></tr>
<tr><td class="ac"><?php echo lang('SIGNUP_RULES_MULTIS', plural(EMPIRES_PER_USER, 'SIGNUP_MULTIS_SINGLE', 'SIGNUP_MULTIS_PLURAL')); ?></td></tr>
<tr><th style="font-size:large"><?php echo lang('SIGNUP_HEADER_USE'); ?></th></tr>
<tr><td class="ac"><?php echo lang('SIGNUP_RULES_USE'); ?></td></tr>
<tr><th style="font-size:large"><?php echo lang('SIGNUP_HEADER_SUPPORT'); ?></th></tr>
<tr><td class="ac"><?php echo lang('SIGNUP_RULES_SUPPORT', GAME_TITLE, MAIL_ADMIN); ?></td></tr>
<tr><th style="font-size:large"><?php echo lang('SIGNUP_HEADER_EXTRA'); ?></th></tr>
<tr><td class="ac"><?php echo TXT_RULES; ?></td></tr>
</table><br />
<?php
$html->end();
?>
