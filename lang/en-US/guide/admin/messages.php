<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: messages.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

if ($adminflag & UFLAG_MOD)
	guidepage($topic, 'admin/messages', 'g_admin_messages', 'Messages');
function g_admin_messages ()
{
?>
<h2>Abuse Reports</h2>
<p>When users use the "Report" link on messages they receive, their messages will be sent to this mailbox, accessible to all Moderators and Administrators. Unread reports are displayed in bold.</p>
<p>Clicking on a report's subject line will open and display its details, including the reason why the user sent the report. To view the original message reported, click on the "In response to <b>this message</b>..." link at the top.</p>
<h2>Browse Messages</h2>
<p>This section allows Moderators and Administrators to browse through the contents of private messages sent within the game.</p>
<dl>
    <dt>Date Filter</dt>
        <dd>Messages can be optionally filtered down to those sent within a particular time span, specified as the number of hours prior to the current time.</dd>
    <dt>Source</dt>
        <dd>Entering an empire ID number will restrict the results to messages sent by the indicated empire. If left blank, messages from all empires will be shown.</dd>
    <dt>Destination</dt>
        <dd>Entering an empire ID number will restrict the results to messages sent to the indicated empire. If left blank, messages to all empires will be shown.</dd>
    <dt>Include Replies</dt>
        <dd>Checking this option will allow the above two options to be interpreted in either direction. When filtering by source or destination only, this will display all messages sent from or to the selected empire, while filtering on both will effectively capture an entire conversation between the two empires.</dd>
    <dt>String</dt>
        <dd>Specifying a string here will filter the results down to messages containing the specified text. If left blank, message text is left unfiltered.</dd>
</dl>
<p><b>Please use this feature responsibly.</b></p>
<?php
}
?>
