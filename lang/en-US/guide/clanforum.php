<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: clanforum.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

if (CLAN_ENABLE)
	guidepage($topic, 'clanforum', 'g_clanforum', 'Clan Forum');
function g_clanforum()
{
	global $era;
?>
<h2>Clan Forum</h2>
<p>All clans are given access to a forum in which members can discuss their strategies and plan collaborative activities.</p>
<p>See <?php echo guidelink('messages', 'Messages'); ?> for a full list of formatting codes allowed in forum posts.</p>
<h3>Index</h3>
<p>From the forum index, a list of all available threads is presented. Clicking on a thread's subject line will display all of its posts on a new page.</p>
<p>The first thread shown in the list is always the <b><?php echo lang('CLANFORUM_SUBJECT_NEWS'); ?></b> thread, marked with a "<?php echo lang('CLANFORUM_ICON_NEWS'); ?>". The latest message posted to this thread will be displayed on the Main page of all clan members.</p>
<p>Next, any sticky threads will be listed, marked with a "<?php echo lang('CLANFORUM_ICON_STICKY'); ?>" and ordered descending by the last date a reply was posted. Below Sticky threads, all other threads are listed in chronological order by latest post.</p>
<p>Locked threads, marked with a "<?php echo lang('CLANFORUM_ICON_LOCKED'); ?>", can only be posted to by the clan Leader and Assistant Leader, henceforth referred to as Moderators.</p>
<p>Any clan member can create a new thread by filling out the form at the bottom of the page.</p>
<h3>Thread View</h3>
<p>Moderators are permitted to edit and delete posts made by any user - other clan members can only edit or delete their own posts, and only if nobody has yet replied to them.</p>
<p>Editing the first post in a thread allows editing the thread's Subject, and Moderators are also allowed to mark (or unmark) the thread as Sticky or Locked.</p>
<p>Deleting the first post in a thread will delete the entire thread. If you are editing the <?php echo lang('CLANFORUM_SUBJECT_NEWS'); ?> thread, however, only the first post will be deleted, as deleting this thread is not allowed.</p>
<p>Clicking the <b><?php echo lang('CLANFORUM_SUBMIT_QUOTE'); ?></b> link will copy the text of the corresponding message into the Reply form at the bottom of the page and enclose it in quote tags.</p>
<p>Only the clan Leader, Assistant Leader, and Ministers of Foreign Affairs are permitted to post in the <?php echo lang('CLANFORUM_SUBJECT_NEWS'); ?> thread.</p>
<?php
}
?>
