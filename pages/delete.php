<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: delete.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'DELETE_TITLE';

page_header();

if (!($emp1->e_flags & EFLAG_VALID))
	unavailable(lang('DELETE_MUST_VALIDATE'));

if ($action == 'delete') do
{
	if (!isFormPost())
		break;
	$delcode = getFormVar('delete_code');
	if ($delcode == '')
	{
		notice(lang('DELETE_NEED_VALCODE'));
		break;
	}
	if ($delcode != $emp1->e_valcode)
	{
		notice(lang('DELETE_BAD_VALCODE'));
		break;
	}
	$delreason = getFormVar('delete_reason');
	// leave the remaining 55 characters for stuff added by the turns script
	if (strlen($delreason) > 200)
	{
		notice(lang('DELETE_REASON_TOO_LONG'));
		break;
	}
	if ($delreason)
		$emp1->e_reason = $delreason;
	$emp1->e_killedby = $emp1->e_id;
	$emp1->setFlag(EFLAG_DELETE);
	$emp1->setFlag(EFLAG_NOTIFY);
	notice(lang('DELETE_COMPLETE'));
	if ($emp1->is_protected())
		notice(lang('DELETE_WAIT', duration(TURNS_FREQ * 60)));
	else	notice(lang('DELETE_WAIT', duration(IDLE_TIMEOUT_DELETE * 60 * 60 * 24)));
	notices();
	logevent();

	unavailable('<a href="?location=logout">'. lang('DELETE_LOGOUT') .'</a>');
} while (0);
notices();
?>
<form method="post" action="?location=delete">
<div>
<?php echo lang('DELETE_HEADER'); ?><br />
<?php echo lang('LABEL_VALCODE'); ?> <input type="text" name="delete_code" size="16" /><br />
<?php echo lang('DELETE_REASON'); ?><br />
<input type="text" name="delete_reason" size="80" maxlength="200" /><br />
<input type="hidden" name="action" value="delete" /><input type="submit" value="<?php echo lang('DELETE_SUBMIT'); ?>" />
</div>
</form>
<?php
page_footer();
?>
