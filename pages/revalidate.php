<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: revalidate.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

// if user validation isn't permitted, return to the main page
if (!VALIDATE_ALLOW)
	redirect(URL_BASE .'?location=game');

$title = 'REVALIDATE_TITLE';

page_header();

if ($emp1->effects->m_revalidate)
{
	notice(lang('REVALIDATE_TOO_SOON', duration($emp1->effects->m_revalidate)));
	notices();
	echo '<a href="?location=main">'. lang('REVALIDATE_LINK_MAIN') .'</a>';
}
elseif (($action == 'resend') && isFormPost()) do
{
	$mailerror = $emp1->sendValidationMail($user1);
	if ($mailerror)
		notice(lang('REVALIDATE_ERROR', $mailerror));
	else	notice(lang('REVALIDATE_SUCCESS', $user1->u_email));
	notices();
	logevent();
	echo '<a href="?location=main">'. lang('REVALIDATE_LINK_MAIN') .'</a>';
} while (0);
else
{
?>
<?php echo lang('REVALIDATE_HEADER'); ?>
<form method="post" action="?location=revalidate"><div><input type="hidden" name="action" value="resend" /><input type="submit" value="<?php echo lang('REVALIDATE_SUBMIT'); ?>" /></div></form>
<?php
}
page_footer();
?>
