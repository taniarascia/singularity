<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: auth.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die('Access denied');

// Checks if the current session is valid
function checkAuth ($relogin = TRUE)
{
	global $user1, $emp1;

	if (!prom_session::check())
	{
		// user has no session cookie set
		return lang('ERROR_LOGIN_NO_SESSION') . ($relogin ? ('<br /><br />'. lang('ERROR_PLEASE_RELOGIN_CHECK')) : '');
	}

	prom_session::start();

	// load user data
	if (!prom_session::verify())
	{
		// session data got cleared out by cron job, or user/empire values have somehow been unset
		prom_session::kill();
		return lang('ERROR_LOGIN_OLD_SESSION') . ($relogin ? ('<br /><br />'. lang('ERROR_PLEASE_RELOGIN')) : '');
	}

	$user1 = new prom_user($_SESSION['user']);
	if (!$user1->load())
	{
		// user record isn't in the database? shouldn't be possible unless the admin broke something
		prom_session::kill();
		return lang('ERROR_LOGIN_NO_USER') . ($relogin ? ('<br /><br />'. lang('ERROR_PLEASE_RELOGIN')) : '');
	}

	// once the user is loaded and authenticated, pick their selected language
	setlanguage($user1->u_lang);

	$emp1 = new prom_empire($_SESSION['empire']);
	if (!$emp1->load())
	{
		// empire record isn't in the database? shouldn't be possible unless the admin broke something
		prom_session::kill();
		return lang('ERROR_LOGIN_NO_EMPIRE') . ($relogin ? ('<br /><br />'. lang('ERROR_PLEASE_RELOGIN')) : '');
	}

	if ($emp1->u_id == 0)
	{
		// empire has been unlinked
		prom_session::kill();
		return lang('ERROR_LOGIN_EMPIRE_DELETED') . ($relogin ? ('<br /><br />'. lang('ERROR_PLEASE_RELOGIN')) : '');
	}
	return '';
}

// Checks for authentication, locks all necessary entities, then displays the page header
function page_header ()
{
	global $title, $needpriv, $page, $action;
	global $entities, $lock;
	global $world, $user1, $user2, $emp1, $emp2, $clan1, $clan2;
	global $cnames, $races, $eras;
	global $html, $shortmain;
	global $lang, $styles;

	if (($user1->u_flags & $needpriv) != $needpriv)
		error_403('ERROR_TITLE_ACCESS', lang('ERROR_LOGIN_PAGE_PERMISSION'));

	// List of entities to lock (and subsequently save at the end of the page)
	$entities = array();

	$lock['emp1'] = $emp1->e_id;
	$entities[] = $emp1;

	// This MUST be located here, since we want this to happen before the actual locks are applied
	if (($page == 'main') && ($action == 'setuser')) do
	{
		// Until we're certain that we're actually processing this event,
		// clear the event name so main.php won't try to finish handling it
		$action = '';
		if (!isFormPost())
			break;
		$newemp = fixInputNum(getFormVar('setuser_id'));
		if (!$newemp)
		{
			notice(lang('MAIN_SETUSER_BAD_EMPIRE'));
			break;
		}
		// don't bother switching if it's the same empire
		if ($newemp == $_SESSION['empire'])
			break;
		// can use
		$emp_a = new prom_empire($newemp);
		if (!$emp_a->load())
		{
			$emp_a = NULL;
			notice(lang('ERROR_LOGIN_NO_EMPIRE'));
			break;
		}
		if ($emp_a->u_id == 0)
		{
			$emp_a = NULL;
			notice(lang('ERROR_LOGIN_EMPIRE_DELETED'));
			break;
		}
		if ($emp_a->e_flags & EFLAG_DELETE)
		{
			$emp_a = NULL;
			notice(lang('ERROR_LOGIN_EMPIRE_DELETE_MARKED'));
			break;
		}
		if (($emp_a->u_id != $user1->u_id) && !($user1->u_flags & UFLAG_ADMIN))
		{
			$emp_a = NULL;
			notice(lang('ERROR_LOGIN_EMPIRE_PERMISSION'));
			break;
		}
		// okay to set it back now
		$action = 'setuser';
		$entities[] = $emp_a;
		$_SESSION['empire'] = $newemp;
		notice(lang('MAIN_SETUSER_COMPLETE', $emp_a));
		logevent(varlist(array('newemp'), get_defined_vars()));
		// reorder empires - 1 == current, 2 == old (cannot use with $lock['emp2'], but that's not a problem)
		$emp2 = $emp1;
		$emp1 = $emp_a;
		$emp_a = NULL;
		// update locks
		$lock['emp1'] = $emp1->e_id;
		$lock['emp2'] = $emp2->e_id;
	} while (0);
	// "elseif" so this case won't trigger at the same time as setuser (which has already taken emp2)
	elseif ($lock['emp2']) do
	{
		if ($lock['emp2'] == $emp1->e_id)
		{
			// copy pointer to empire record, but don't add it to the lock list
			$emp2 = $emp1;
			break;
		}
		$emp2 = new prom_empire($lock['emp2']);
		if (!$emp2->load())
		{
			$lock['emp2'] = 0;
			break;
		}
		if (isFormPost())
			$entities[] = $emp2;
	} while (0);

	if ($lock['user1'] == -1)
	{
		$lock['user1'] = $user1->u_id;
		if (isFormPost())
			$entities[] = $user1;
	}
	else	$lock['user1'] = 0;

	if ($lock['user2'] == -1)
	{
		if ($lock['emp2'])
			$lock['user2'] = $emp2->u_id;
		else	$lock['user2'] = 0;
	}

	if ($lock['user2']) do
	{
		if ($lock['user2'] == $user1->u_id)
		{
			// copy user record pointer, then add to lock list if it isn't already there
			$user2 = $user1;
			if (($lock['user2'] != $lock['user1']) && isFormPost())
				$entities[] = $user2;
			break;
		}
		$user2 = new prom_user($lock['user2']);
		if (!$user2->load())
		{
			$lock['user2'] = 0;
			break;
		}
		if (isFormPost())
			$entities[] = $user2;
	} while (0);

	if ($lock['clan1'] == -1)		// special case to lock one's own clan, needed for clan management
		$lock['clan1'] = $emp1->c_id;	// (since it isn't known until the empire is loaded)

	if ($lock['clan1']) do
	{
		$clan1 = new prom_clan($lock['clan1']);
		if (!$clan1->load())
		{
			$lock['clan1'] = 0;
			break;
		}
		if (isFormPost())
			$entities[] = $clan1;
	} while (0);

	if ($lock['clan2'] == -1)
	{
		if ($lock['emp2'])
			$lock['clan2'] = $emp2->c_id;
		else	$lock['clan2'] = 0;
	}

	if ($lock['clan2']) do
	{
		// don't allow locking the same clan twice
		if ($lock['clan1'] == $lock['clan2'])
		{
			$lock['clan2'] = 0;
			break;
		}
		$clan2 = new prom_clan($lock['clan2']);
		if (!$clan2->load())
		{
			$lock['clan2'] = 0;
			break;
		}
		if (isFormPost())
			$entities[] = $clan2;
	} while (0);

	if ($lock['world'] == -1)
	{
		$lock['world'] = 1;
		if (isFormPost())
			$entities[] = $world;
	}
	else	$lock['world'] = 0;

	db_lockentities($entities, $user1->u_id);

	// This is located here so style changes take effect immediately (rather than on the next page load)
	if (($page == 'manage/user') && ($action == 'style')) do
	{
		$action = '';
		if (!isFormPost())
			break;
		$style = getFormVar('style_new');
		if (!isset($styles[$style]))
		{
			notice(lang('MANAGE_USER_STYLE_ERROR'));
			break;
		}
		$action = 'style';
		$user1->u_style = $style;
		notice(lang('MANAGE_USER_STYLE_COMPLETE'));
		logevent(varlist(array('style'), get_defined_vars()));
	} while (0);
	// This is located here for mostly the same reason as the above section
	if (($page == 'manage/user') && ($action == 'language')) do
	{
		$action = '';
		if (!isFormPost())
			break;
		$newlang = getFormVar('lang_new');
		if (!isset($lang[$newlang]))
		{
			notice(lang('MANAGE_USER_LANGUAGE_INVALID'));
			break;
		}
		$action = 'language';
		$user1->u_lang = $newlang;
		// reset language so the current page loads properly
		setlanguage($user1->u_lang);
		notice(lang('MANAGE_USER_LANGUAGE_COMPLETE'));
		logevent(varlist(array('newlang'), get_defined_vars()));
	} while (0);

	// auto-update idle time, unless they're in a NOTIFY state (and they're being touched by the real owner)
	if (!($emp1->e_flags & EFLAG_NOTIFY) && ($emp1->u_id == $user1->u_id))
		$emp1->e_idle = CUR_TIME;

	if (CLAN_ENABLE)
		$cnames = prom_clan::getNames();
	$races = prom_race::getNames();
	$eras = prom_era::getNames();

	$html = new prom_html_full($user1, $emp1);
	$html->begin($title);
?>
<span style="font-size:x-large"><?php echo GAME_TITLE; ?></span><br /><?php echo lang('HEADER_LOGGED_IN_AS', $user1, $emp1); ?><br />
<?php
	if (defined('TXT_NEWS'))
		echo lang('HEADER_GAME_NEWS') . TXT_NEWS ."<br />\n";

	// Don't bother displaying guide/refresh links when already in the guide
	if ($page != 'guide')
		echo '<br /><a href="?location=guide&amp;section='. $page .'">'. lang('HEADER_GUIDE') .'</a> - <a href="?location='. $page .'">'. lang('HEADER_REFRESH') .'</a><br />';
	else	echo '<br /><br />';

	$emp1->printStatsBar();
	$emp1->giveNews();

	if (($emp1->e_vacation > 0) && ($emp1->e_land == 0))
		$emp1->e_vacation = 0;	// user sets vacation but gets killed before being locked

	$shortmain = FALSE;

	// If you are on vacation, display the notice on all pages EXCEPT the following:
	if (($emp1->e_vacation > 0) && (!in_array($page, array('guide','status','scores','graveyard','messages','manage/empire','manage/user','delete'))))
	{
		if ($emp1->is_vacation_done())
		{
			echo lang('VACATION_LOCKED', duration($emp1->vacation_hours_since_start() * 60 * 60)) . lang('VACATION_CAN_UNLOCK');
?>
<form method="post" action="?location=manage/empire">
<div><input type="hidden" name="action" value="unvacation" /><input type="submit" value="<?php echo lang('VACATION_UNLOCK_SUBMIT'); ?>" /></div>
</form>
<?php
		}
		elseif ($emp1->is_vacation())
			echo lang('VACATION_LOCKED', duration($emp1->vacation_hours_since_start() * 60 * 60)) . lang('VACATION_CANNOT_UNLOCK', duration($emp1->vacation_hours_until_limit() * 60 * 60));
		else	echo lang('VACATION_NOT_LOCKED', duration($emp1->vacation_hours_since_lock() * 60 * 60), duration($emp1->vacation_hours_until_start() * 60 * 60));
		// if on the main page, allow the rest of it to be displayed, otherwise end it early
		if ($page == 'main')
			$shortmain = TRUE;
		else	unavailable('');
	}
	
	// If you are dead, display the notice (and halt further execution) on all pages EXCEPT the following:
	if (($emp1->e_land == 0) && (!in_array($page, array('guide','scores','graveyard','news','messages','manage/user'))))
	{
		if ($emp1->e_flags & EFLAG_NOTIFY)
			echo lang('DEAD_NOTIFIED');
		else
		{
			// only set notify for the actual owner
			if ($emp1->u_id == $user1->u_id)
				$emp1->setFlag(EFLAG_NOTIFY);
			echo lang('DEAD_UNNOTIFIED');
		}

		printEmpireNews($emp1);
		if ($world->turns_next < $world->round_time_closing)
			echo lang('DEAD_DELETE_NOTICE', duration($world->turns_next - CUR_TIME));
		else	echo lang('DEAD_DELETE_NOTICE_END', duration($world->turns_next - CUR_TIME));

		// don't halt execution if the empire is being accessed from a different user account (i.e. an admin)
		if ($emp1->u_id == $user1->u_id)
		{
			if ($page == 'main')
				$shortmain = TRUE;
			else	unavailable('');
		}
	}

	if (!($emp1->e_flags & EFLAG_VALID))
	{
		$skip = 0;
		// If the user needs to validate, display the notice (and halt further execution) on all pages EXCEPT the following:
		if (VALIDATE_REQUIRE && ($emp1->e_turnsused >= TURNS_VALIDATE) && (!in_array($page, array('validate','revalidate','delete','messages','scores','search'))))
		{
			if (VALIDATE_ALLOW)
			{
				if ($emp1->e_flags & EFLAG_NOTIFY)
					echo lang('UNVALIDATED_NOTIFIED');
				else
				{		
					// only set notify for the actual owner
					if ($emp1->u_id == $user1->u_id)
						$emp1->setFlag(EFLAG_NOTIFY);
					echo lang('UNVALIDATED_UNNOTIFIED');
				}
?>
<form method="post" action="?location=revalidate"><div><input type="hidden" name="action" value="resend" /><input type="submit" value="<?php echo lang('REVALIDATE_SUBMIT'); ?>" /></div></form>
<?php
			}
			else	echo lang('UNVALIDATED_DISALLOWED');
			if ($emp1->u_id == $user1->u_id)
				$skip = 1;
		}
		// always display validation prompt for unvalidated users
		// so they can validate ahead of time
		if (VALIDATE_ALLOW && !in_array($page, array('validate','revalidate','delete')))
		{
?>
<form method="post" action="?location=validate">
<table class="inputtable">
<tr><td><?php echo lang('LABEL_VALCODE'); ?></td>
    <td class="ar"><input type="text" size="32" name="valcode"<?php if ($user1->u_flags & UFLAG_MOD) echo ' value="'. $emp1->e_valcode .'"'; ?> /></td></tr>
<tr><th colspan="2" class="ac"><input type="hidden" name="action" value="validate" /><input type="submit" value="<?php echo lang('VALIDATE_SUBMIT'); ?>" /></th></tr>
</table>
</form>
<?php
		}
		if ($skip)
		{
			if ($page == 'main')
				$shortmain = TRUE;
			else	unavailable('');
		}
	}
	
	if ($emp1->e_flags & EFLAG_DISABLE)
	{
		$reason = $emp1->e_reason;
		if (!$reason)
		{
			if ($emp1->e_flags & EFLAG_MULTI)
				$reason = lang('DISABLED_DEFAULT_REASON_MULTI');
			else	$reason = lang('DISABLED_DEFAULT_REASON');
		}
		if ($emp1->e_killedby)
		{
			$emp_a = new prom_empire($emp1->e_killedby);
			$emp_a->load();
			$user_a = new prom_user($emp_a->u_id);
			$user_a->load();
			echo lang('DISABLED_BY_ADMIN', $emp_a, $reason, $user_a->u_email, urlencode(lang('DISABLED_EMAIL_SUBJECT', GAME_TITLE, $emp1)));
			$emp_a = NULL;
			$user_a = NULL;
		}
		else	echo lang('DISABLED_BY_SCRIPT', $reason, MAIL_ADMIN, urlencode(lang('DISABLED_EMAIL_SUBJECT', GAME_TITLE, $emp1)));
		echo lang('DISABLED_EMAIL_REMINDER', GAME_TITLE);
		if ($emp1->u_id == $user1->u_id)
		{
			if ($page == 'main')
				$shortmain = TRUE;
			else	unavailable('');
		}
	}

	if ($emp1->e_flags & EFLAG_DELETE)
	{
		if ($page == 'main')
		{
			notice(lang('HEADER_EMPIRE_DELETED'));
			$shortmain = TRUE;
		}
		else	unavailable(lang('HEADER_EMPIRE_DELETED'));
	}

	// make sure the Online flag is set (unless the empire is being controlled by someone else)
	// but only after the round has started
	if (ROUND_STARTED && ($emp1->u_id == $user1->u_id))
		$emp1->setFlag(EFLAG_ONLINE);
}

// Ends a page started by page_header()
function page_footer ()
{
	global $entities, $db, $emp1, $html;

	foreach ($entities as $ent)
		if ($ent->locked())
			$ent->save();
	$db->releaseLocks();

	$emp1->printStatsBar();

	$html->end();
}

// Prints a message and gracefully ends the current action.
// Use this for pages that need to exit early after having already locked the player record
function unavailable ($description)
{
	echo $description .'<br />';

	page_footer();
	exit;
}
?>
