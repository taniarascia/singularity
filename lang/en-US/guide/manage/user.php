<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: user.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'manage/user', 'g_manage_user', 'Managing your Account');
function g_manage_user ()
{
	global $adminflag;
?>
<h2>Managing your User Account</h2>
<p>From this page, you may change several settings which apply to your user account - these settings will persist between empires and between rounds.</p>
<?php	if ($adminflag == UFLAG_ADMIN) { ?>
<p>Note that if you use the "<?php echo lang('MAIN_SETUSER_SUBMIT'); ?>" functionality to assume the identity of an empire owned by another user, these functions will <i>not</i> allow you to alter their user account - they will always apply to your own account instead. If you wish to modify someone else's user account, go to <?php echo guidelink('admin/users', 'User Account Administration'); ?>.</p>
<?php	} ?>
<h3>Theme</h3>
<p>Depending on the game's setup, multiple color schemes may be available for you to choose from. Simply select the one you wish to use and press "<?php echo lang('MANAGE_USER_STYLE_SUBMIT'); ?>". The new style should take effect immediately.</p>
<h3>Change Password</h3>
<p>If you believe your password has been compromised (or if you simply haven't changed it in a while), you may change your account password here - simply enter your desired new password twice.</p>
<h3>Timezone</h3>
<p>All in-game times are typically tracked in the time zone in which the game's server itself is located - if you are located in a different time zone and wish to display times as they would be in your area, simply select your time zone from the dropdown list and press "<?php echo lang('MANAGE_USER_TIMEZONE_SUBMIT'); ?>".</p>
<p>Each time this page is loaded, the current time will be displayed in your configured timezone. Note that Daylight Savings Time is not detected - when your local time switches to and from DST, you will need to update your account configuration accordingly.</p>
<h3>Date Format</h3>
<p>Here you may define how in-game dates are displayed. Simply type in a date specification (see the <a href="http://www.php.net/date" rel="external">date()</a> documentation for a full list of formatting codes) and press "<?php echo lang('MANAGE_USER_DATEFORMAT_SUBMIT'); ?>".</p>
<p>Note that format specifications involving the name of the current time zone (i.e. "e" and "T") cannot be used, as they will always display "UTC".</p>
<h3>Language</h3>
<p>By default, QM Promisance is available in English only, but if your server administrator has installed any additional language packs, you may select one here and play the game in a different language.</p>
<?php
}
?>
