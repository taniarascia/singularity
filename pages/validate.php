<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: validate.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

// if the page wasn't submitted using the form button (or user validation isn't permitted),
// return to the main page
if (($action != 'validate') || (!VALIDATE_ALLOW))
	redirect(URL_BASE .'?location=game');

$title = 'VALIDATE_TITLE';

$lock['user1'] = -1;
page_header();

do
{
	if (!isFormPost())
		break;
	$valcode = getFormVar('valcode');

	if ($emp1->e_flags & EFLAG_VALID)
	{
		notice(lang('VALIDATE_ALREADY'));
		break;
	}
	if ($emp1->e_valcode != $valcode)
	{
		notice(lang('VALIDATE_INCORRECT'));
		break;
	}
	$emp1->setFlag(EFLAG_VALID);
	$emp1->clrFlag(EFLAG_NOTIFY);
	// validate the user account as well, so we know the address did work at one point
	// but only if the empire is being validated by the account that created it
	if ($user1->u_id == $emp1->u_id)
		$user1->setFlag(UFLAG_VALID);
	notice(lang('VALIDATE_COMPLETE'));
	logevent();
} while (0);
notices();
?>
<a href="?location=main"><?php echo lang('VALIDATE_LINK_MAIN'); ?></a>
<?php
page_footer();
?>
