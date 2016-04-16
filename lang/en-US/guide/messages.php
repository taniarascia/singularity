<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: messages.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'messages', 'g_messages', 'Your Mailbox');
function g_messages ()
{
?>
<h2>Your Mailbox</h2>
<p>This section allows you to send private messages to other empires, whether to exchange information, forge alliances, or taunt your foes.</p>
<p>From the Inbox, all messages you have received are listed in a table showing the message's Subject line, the empire who sent it, and the date it was sent. Unread messages have their subject line displayed in bold. The Sent Messages page will similarly display a list of all messages you have sent to other empires; here, a bold subject line indicates that the message has not yet been read by its recipient. If the empire who sent you a message has since been destroyed, its name will be displayed in italic.</p>
<p>To send a new message to another empire, enter its Empire ID number (as seen on the <?php echo guidelink('scores', 'Top Scores'); ?> page), enter a Subject line, and type your message in the area provided and press "Send".</p>
<p>From the Inbox and Sent Messages pages, clicking on a message's subject will display the selected message in full. If you are viewing an incoming message you have not yet replied to, a Reply form will also be displayed, as well as a "Quote" button to automatically prefill your message with your recipient's original message text. If the message you are viewing was a reply to another message, a link at the top of the message will allow you to view the original message.</p>
<p>Up to <?php echo MESSAGES_MAXCREDITS; ?> new messages can be sent at any given time, and one additional message can be sent every <?php echo duration(MESSAGES_DELAY); ?>. Replying to an existing message does not count toward this limit.</p>
<p>If you receive a message which you believe to be inappropriate or in violation of the game's rules, click the "Report" link to send a notification to the game's Moderators and Administrators. Violators may have their empires Silenced at the discretion of the Administration. The Report command should only be used as a last resort - whenever possible, please attempt to resolve disputes personally so as to not place excess burden on the Administration.</p>
<?php
	$examples = array('[quote]A quoted message body from another user.<br />Can be multiple lines long.[/quote]', '[center]Centered text[/center]', '[b]Bold text[/b]', '[i]Italic text[/i]', '[u]Underlined text[/u]', '[url]'. URL_HOMEPAGE .'[/url]', '[email]'. MAIL_VALIDATE .'[/email]', '[quote][center]Multiple [i]formatting [b]codes[/b][/i]<br />combined[/center][/quote]');
?>
<h3>Message Formatting</h3>
<table>
<?php
	foreach ($examples as $text)
	{
?>
<tr><td><?php echo $text; ?></td>
    <td><?php echo bbencode($text); ?></td></tr>
<?php
	}
?>
</table>
<p>Formatting tags cannot be nested within themselves (e.g. you can't put one quote inside another), and all tags must be nested in the correct order (e.g. "[b][i]some text[/b][/i]" is illegal, but "[b][i]some text[/i][/b]" is acceptable).</p>
<p>Also note that the [<b>quote</b>] and [<b>center</b>] tags cannot be contained within [<b>b</b>]old/[<b>i</b>]talic/[<b>u</b>]nderline, and [<b>url</b>]/[<b>email</b>] obviously cannot contain any tags themselves.</p>
<?php
}
?>
