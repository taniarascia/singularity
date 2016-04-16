<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: banner.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

function giveBonusTurns ()
{
	global $db, $lock;
	global $user1, $emp1;

	if (!ROUND_STARTED)
		return 0;	// don't give turns if the game isn't running

	// page_header() isn't called here, so we have to do all of this manually

	// if they're not eligible, then don't bother acquiring a lock
	if ($emp1->e_vacation > 0)
		return 0;	// can't claim turns when on vacation
	if ($emp1->e_flags & EFLAG_DISABLE)
		return 0;	// or when disabled
	if ($emp1->effects->m_freeturns)
		return 0;	// already got free turns

	$turnbonus = ceil(60 / TURNS_FREQ) * TURNS_COUNT;

	// update $lock for logging purposes
	$lock['emp1'] = $emp1->e_id;
	db_lockentities(array($emp1), $user1->u_id);

	// check again, just in case they clicked twice in a row
	if ($emp1->effects->m_freeturns)
	{
		// abort, but make sure to release the locks
		$db->releaseLocks();
		return 0;
	}

	$emp1->effects->m_freeturns = 86400;
	$emp1->e_turns += $turnbonus;
	if ($emp1->e_turns > TURNS_MAXIMUM)
	{
		$emp1->e_storedturns += $emp1->e_turns - TURNS_MAXIMUM;
		$emp1->e_turns = TURNS_MAXIMUM;
		if ($emp1->e_storedturns > TURNS_STORED)
			$emp1->e_storedturns = TURNS_STORED;
	}
	$emp1->save();	// give 1 hour worth of turns and reset timer
	$db->releaseLocks();
	return $turnbonus;
}

$dest = 'game';	// In the event of failure, jump back to this page

if (!isFormPost())
	$action = '';

if ($action == 'click')
{
	$id = fixInputNum(getFormVar('banner_id'));
	if (isset($banners[$id]))
	{
		if (BONUS_TURNS)
			$turns = giveBonusTurns();
		$url = $banners[$id]['url'];
		if ($banners[$id]['ismap'])
		{
			$click_x = fixInputNum(getFormVar('banner_img_x'));
			$click_y = fixInputNum(getFormVar('banner_img_y'));
			$url .= '?'. $click_x .','. $click_y;
		}
		logevent(varlist(array('turns', 'id'), get_defined_vars()));
		redirect($url);
	}
}
elseif ($action == 'bonus')
{
	if (count($banners) == 0)
	{
		if (BONUS_TURNS)
			$turns = giveBonusTurns();
		logevent(varlist(array('turns'), get_defined_vars()));
		$dest = getFormVar('bonus_return', 'game');
	}
}
redirect(URL_BASE .'?location='. urlencode($dest));
?>
