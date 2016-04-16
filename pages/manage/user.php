<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: user.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'MANAGE_USER_TITLE';

$lock['user1'] = -1;
page_header();

// Account Settings
// The action handlers for 'style' and 'language' are located within page_header()
// so that their updates can take effect immediately
if ($action == 'password') do
{
	if (!isFormPost())
		break;
	$newpass = getFormVar('password_new');
	$chkpass = getFormVar('password_verify');
	if (!strlen($newpass))
	{
		notice(lang('INPUT_NEED_PASSWORD'));
		break;
	}
	if ($newpass != $chkpass)
	{
		notice(lang('INPUT_PASSWORD_MISMATCH'));
		break;
	}
	$user1->setPassword($newpass);
	notice(lang('MANAGE_USER_PASSWORD_COMPLETE'));
	logevent();
} while (0);
if ($action == 'timezone') do
{
	if (!isFormPost())
		break;
	$newzone = fixInputNumSigned(getFormVar('zone_new'));
	$user1->u_timezone = $newzone;
	notice(lang('MANAGE_USER_TIMEZONE_COMPLETE'));
	logevent(varlist(array('newzone'), get_defined_vars()));
} while (0);
if ($action == 'dateformat') do
{
	if (!isFormPost())
		break;
	$newformat = getFormVar('dateformat_new');
	if (!strlen($newformat))
		$newformat = DEFAULT_DATEFORMAT;
	if (strlen($newformat) > 64)
	{
		notice(lang('INPUT_DATEFORMAT_TOO_LONG'));
		break;
	}
	$user1->u_dateformat = $newformat;
	notice(lang('MANAGE_USER_DATEFORMAT_COMPLETE'));
	logevent(varlist(array('newformat'), get_defined_vars()));
} while (0);
notices();
?>
<h2><?php echo lang('MANAGE_USER_HEADER'); ?></h2>
<table style="width:100%">
<tr><td class="ac" style="width:50%">
        <form method="post" action="?location=manage/user">
        <table class="inputtable">
        <tr><th><?php echo lang('MANAGE_USER_STYLE_LABEL'); ?></th></tr>
<?php
foreach ($styles as $name => $style)
{
?>
        <tr><td><?php echo radiobutton('style_new', $style['name'], $name, ($user1->u_style == $name)); ?></td></tr>
<?php
}
?>
        <tr><th><input type="hidden" name="action" value="style" /><input type="submit" value="<?php echo lang('MANAGE_USER_STYLE_SUBMIT'); ?>" /></th></tr>
        </table>
        </form>
    </td>
    <td class="ac" style="width:50%">
        <form method="post" action="?location=manage/user">
        <table class="inputtable">
        <tr><th colspan="2"><?php echo lang('MANAGE_USER_PASSWORD_LABEL'); ?></th></tr>
        <tr><td><?php echo lang('LABEL_PASSWORD_NEW'); ?></td>
            <td><input type="password" name="password_new" size="8" /></td></tr>
        <tr><td><?php echo lang('LABEL_PASSWORD_VERIFY'); ?></td>
            <td><input type="password" name="password_verify" size="8" /></td></tr>
        <tr><th colspan="2"><input type="hidden" name="action" value="password" /><input type="submit" value="<?php echo lang('MANAGE_USER_PASSWORD_SUBMIT'); ?>" /></th></tr>
        </table>
        </form>
    </td></tr>
<tr><td class="ac">
        <form method="post" action="?location=manage/user">
        <table class="inputtable">
        <tr><td><?php echo lang('MANAGE_USER_TIMEZONE_LABEL'); ?></td>
            <td><?php
$zonelist = array();
foreach ($timezones as $offset => $name)
	$zonelist[$offset] = $name;
echo optionlist('zone_new', $zonelist, $user1->u_timezone);
?></td></tr>
        <tr><td><?php echo lang('MANAGE_USER_TIMEZONE_SAMPLE'); ?></td><td><?php echo $user1->customdate(CUR_TIME); ?></td></tr>
        <tr><th colspan="2"><input type="hidden" name="action" value="timezone" /><input type="submit" value="<?php echo lang('MANAGE_USER_TIMEZONE_SUBMIT'); ?>" /></th></tr>
        </table>
        </form>
    </td>
    <td class="ac">
        <form method="post" action="?location=manage/user">
        <table class="inputtable">
        <tr><td><?php echo lang('MANAGE_USER_LANGUAGE_LABEL'); ?></td>
            <td><?php
$langlist = array();
foreach ($lang as $id => $data)
	$langlist[$id] = $data['LANG_ID'];
echo optionlist('lang_new', $langlist, $user1->u_lang);
?></td></tr>
        <tr><th colspan="2"><input type="hidden" name="action" value="language" /><input type="submit" value="<?php echo lang('MANAGE_USER_LANGUAGE_SUBMIT'); ?>" /></th></tr>
        </table>
        </form>
    </td></tr>
<tr><td class="ac">
        <form method="post" action="?location=manage/user">
        <table class="inputtable">
        <tr><td><?php echo lang('MANAGE_USER_DATEFORMAT_LABEL'); ?></td>
            <td><input type="text" name="dateformat_new" value="<?php echo htmlspecialchars($user1->u_dateformat); ?>" size="16" /></td></tr>
        <tr><td colspan="2"><?php echo lang('MANAGE_USER_DATEFORMAT_EXPLAIN'); ?></td></tr>
        <tr><th colspan="2"><input type="hidden" name="action" value="dateformat" /><input type="submit" value="<?php echo lang('MANAGE_USER_DATEFORMAT_SUBMIT'); ?>" /></th></tr>
        </table>
        </form>
    </td>
    <td class="ac">
    </td></tr>
</table>
<?php
page_footer();
?>
