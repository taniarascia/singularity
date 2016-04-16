<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: clanforum.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'CLANFORUM_TITLE';

page_header();
require_once(PROM_BASEDIR .'includes/bbcode.php');

if (ROUND_FINISHED)
	unavailable(lang('CLANFORUM_UNAVAILABLE_END'));
if (!ROUND_STARTED)
	unavailable(lang('CLANFORUM_UNAVAILABLE_START'));
if ($emp1->is_protected())
	unavailable(lang('CLANFORUM_UNAVAILABLE_PROTECT'));
if ($emp1->e_flags & EFLAG_ADMIN)
	unavailable(lang('CLANFORUM_UNAVAILABLE_ADMIN'));
if (!CLAN_ENABLE)
	unavailable(lang('CLANFORUM_UNAVAILABLE_CONFIG'));

if ($emp1->c_id == 0)
	unavailable(lang('CLAN_NOT_MEMBER'));

// don't want to load this from page_header() since it'll end up locking it too (which is unnecessary)
$clan1 = new prom_clan($emp1->c_id);
$clan1->load();

// The clan leader and assistant leader are considered to be forum moderators
$is_moderator = in_array($emp1->e_id, array($clan1->e_id_leader, $clan1->e_id_asst));
$is_ranked = in_array($emp1->e_id, array($clan1->e_id_leader, $clan1->e_id_asst, $clan1->e_id_fa1, $clan1->e_id_fa2));

// Variables shared across multiple actions, used as defaults for fallthrough cases
$clan_id = $emp1->c_id;		// for logging purposes
$topic_id = 0;
$topic_subject = '';
$topic_flags = 0;
$post_id = 0;
$post_body = '';

// preset $post_id for the View action - other modes can fall back to View, and
// some of them zero out $post_id so it doesn't end up quoted in the Reply form
if ($action == 'view')
	$post_id = fixInputNum(getFormVar('post_id', $post_id));

if ($action == 'post') do
{
	if (!isFormPost())
		break;
	$subj = htmlspecialchars(getFormVar('topic_subject'));
	$body = htmlspecialchars(getFormVar('post_body'));
	$topic_flags = 0;
	if ($is_moderator)
	{
		if (fixInputBool(getFormVar('topic_sticky')))
			$topic_flags |= CTFLAG_STICKY;
		if (fixInputBool(getFormVar('topic_locked')))
			$topic_flags |= CTFLAG_LOCK;
	}

	// from this point, fall back to index
	$action = 'index';
	$post_body = $body;
	if (strlen($subj) > 255)
	{
		notice(lang('CLANFORUM_SUBJECT_TOO_LONG'));
		break;
	}
	if (lang_isset($subj))
	{
		notice(lang('CLANFORUM_SUBJECT_INVALID'));
		break;
	}
	$topic_subject = $subj;
	$encbody = bbencode($body);
	if (strlen($encbody) > 65535)
	{
		notice(lang('CLANFORUM_BODY_TOO_LONG'));
		break;
	}
	if (lang_isset($encbody))
	{
		notice(lang('CLANFORUM_BODY_INVALID'));
		break;
	}

	// post is going to be added, so restore the action for logging purposes
	$action = 'post';

	$q = $db->prepare('INSERT INTO '. CLAN_TOPIC_TABLE .' (c_id,ct_subject,ct_flags) VALUES (?,?,?)');
	$q->bindIntValue(1, $emp1->c_id);
	$q->bindStrValue(2, $topic_subject);
	$q->bindIntValue(3, $topic_flags);
	$q->execute() or warning('Failed to create topic', 0);
	$topic_id = $db->lastInsertId($db->getSequence(CLAN_TOPIC_TABLE));

	$q = $db->prepare('INSERT INTO '. CLAN_MESSAGE_TABLE .' (ct_id,e_id,cm_body,cm_time) VALUES (?,?,?,?)');
	$q->bindIntValue(1, $topic_id);
	$q->bindIntValue(2, $emp1->e_id);
	$q->bindStrValue(3, $encbody);
	$q->bindIntValue(4, CUR_TIME);
	$q->execute() or warning('Failed to post message', 0);
	$post_id = $db->lastInsertId($db->getSequence(CLAN_MESSAGE_TABLE));

	notice(lang('CLANFORUM_POST_COMPLETE'));
	logevent(varlist(array('clan_id', 'topic_id', 'post_id'), get_defined_vars()));
	$action = 'view';
	$post_id = 0;
	$post_body = '';
} while (0);
elseif ($action == 'reply') do
{
	if (!isFormPost())
		break;
	$topic_id = fixInputNum(getFormVar('topic_id', 0));
	$body = htmlspecialchars(getFormVar('post_body'));

	$q = $db->prepare('SELECT * FROM '. CLAN_TOPIC_TABLE .' WHERE ct_id = ? AND ct_flags & ? = 0');
	$q->bindIntValue(1, $topic_id);
	$q->bindIntValue(2, CTFLAG_DELETE);
	$q->execute() or warning('Failed to fetch topic', 0);
	$topic = $q->fetch();

	// default fallback to index
	$action = 'index';
	if (!$topic)
	{
		notice(lang('CLANFORUM_TOPIC_NOT_EXIST'));
		break;
	}
	if ($topic['c_id'] != $emp1->c_id)
	{
		notice(lang('CLANFORUM_TOPIC_WRONG_CLAN'));
		break;
	}

	// topic is okay, can fall back to view from here on in
	$action = 'view';
	// only moderators can post to locked threads
	if (($topic['ct_flags'] & CTFLAG_LOCK) && (!$is_moderator))
	{
		notice(lang('CLANFORUM_REPLY_LOCKED'));
		break;
	}
	// only privileged members can post to the news thread
	if (($topic['ct_flags'] & CTFLAG_NEWS) && (!$is_ranked))
	{
		notice(lang('CLANFORUM_REPLY_NEWS'));
		break;
	}

	$post_body = $body;
	$encbody = bbencode($body);
	if (strlen($encbody) > 65535)
	{
		notice(lang('CLANFORUM_BODY_TOO_LONG'));
		break;
	}
	if (lang_isset($encbody))
	{
		notice(lang('CLANFORUM_BODY_INVALID'));
		break;
	}

	// reply is going to be added, so restore the action for logging purposes
	$action = 'reply';

	$q = $db->prepare('INSERT INTO '. CLAN_MESSAGE_TABLE .' (ct_id,e_id,cm_body,cm_time) VALUES (?,?,?,?)');
	$q->bindIntValue(1, $topic_id);
	$q->bindIntValue(2, $emp1->e_id);
	$q->bindStrValue(3, $encbody);
	$q->bindIntValue(4, CUR_TIME);
	$q->execute() or warning('Failed to post message', 0);
	$post_id = $db->lastInsertId($db->getSequence(CLAN_MESSAGE_TABLE));

	notice(lang('CLANFORUM_REPLY_COMPLETE'));
	logevent(varlist(array('clan_id', 'topic_id', 'post_id'), get_defined_vars()));
	$action = 'view';
	$post_id = 0;
	$post_body = '';
} while (0);
elseif ($action == 'edit') do
{
	if (!isFormPost())
		break;
	$topic_id = fixInputNum(getFormVar('topic_id', 0));
	$post_id = fixInputNum(getFormVar('post_id', 0));
	$body = htmlspecialchars(getFormVar('post_body'));

	$q = $db->prepare('SELECT * FROM '. CLAN_TOPIC_TABLE .' WHERE ct_id = ? AND ct_flags & ? = 0');
	$q->bindIntValue(1, $topic_id);
	$q->bindIntValue(2, CTFLAG_DELETE);
	$q->execute() or warning('Failed to fetch topic', 0);
	$topic = $q->fetch();

	// default fallback to index
	$action = 'index';
	if (!$topic)
	{
		notice(lang('CLANFORUM_TOPIC_NOT_EXIST'));
		break;
	}
	if ($topic['c_id'] != $emp1->c_id)
	{
		notice(lang('CLANFORUM_TOPIC_WRONG_CLAN'));
		break;
	}

	// topic is okay, can fall back to view from here on in
	$action = 'view';
	// only moderators can edit posts in locked threads
	if (($topic['ct_flags'] & CTFLAG_LOCK) && (!$is_moderator))
	{
		notice(lang('CLANFORUM_EDIT_LOCKED'));
		// need to clear post ID to prevent message body from being quoted
		$post_id = 0;
		break;
	}
	// only privileged members can edit posts in the news thread
	if (($topic['ct_flags'] & CTFLAG_NEWS) && (!$is_ranked))
	{
		notice(lang('CLANFORUM_EDIT_NEWS'));
		$post_id = 0;
		break;
	}

	$firstpost = $db->queryCell('SELECT MIN(cm_id) FROM '. CLAN_MESSAGE_TABLE .' WHERE ct_id = ? AND cm_flags & ? = 0', array($topic_id, CMFLAG_DELETE));
	$lastpost = $db->queryCell('SELECT MAX(cm_id) FROM '. CLAN_MESSAGE_TABLE .' WHERE ct_id = ? AND cm_flags & ? = 0', array($topic_id, CMFLAG_DELETE));

	$q = $db->prepare('SELECT * FROM '. CLAN_MESSAGE_TABLE .' WHERE cm_id = ? AND cm_flags & ? = 0');
	$q->bindIntValue(1, $post_id);
	$q->bindIntValue(2, CMFLAG_DELETE);
	$q->execute() or warning('Failed to fetch post', 0);
	$post = $q->fetch();
	if (!$post)
	{
		notice(lang('CLANFORUM_MESSAGE_NOT_EXIST'));
		$post_id = 0;
		break;
	}
	// only moderators can edit other users' posts
	if (($post['e_id'] != $emp1->e_id) && (!$is_moderator))
	{
		notice(lang('CLANFORUM_EDIT_PERMISSION'));
		$post_id = 0;
		break;
	}
	// only moderators can edit posts after they've been replied to
	if (($post_id < $lastpost) && (!$is_moderator))
	{
		notice(lang('CLANFORUM_EDIT_TOO_LATE'));
		$post_id = 0;
		break;
	}

	// past this point, fall back to the edit form
	$action = 'edit_form';
	$post_body = $body;
	// are we editing the first post of the thread? if so, allow modifying subject and flags
	if ($post_id == $firstpost)
	{
		if (($is_moderator) && !($topic['ct_flags'] & CTFLAG_NEWS))
		{
			$topic_flags = 0;
			if (fixInputBool(getFormVar('topic_sticky')))
				$topic_flags |= CTFLAG_STICKY;
			if (fixInputBool(getFormVar('topic_locked')))
				$topic_flags |= CTFLAG_LOCK;
		}
		else	$topic_flags = $topic['ct_flags'];

		$subj = htmlspecialchars(getFormVar('topic_subject'));
		$topic_subject = $topic['ct_subject'];
		if (strlen($subj) > 255)
		{
			notice(lang('CLANFORUM_SUBJECT_TOO_LONG'));
			break;
		}
		if (lang_isset($subj))
		{
			notice(lang('CLANFORUM_SUBJECT_INVALID'));
			break;
		}
		$topic_subject = $subj;
	}

	$encbody = bbencode($body);
	if (strlen($encbody) > 65535)
	{
		notice(lang('CLANFORUM_BODY_TOO_LONG'));
		break;
	}
	if (lang_isset($encbody))
	{
		notice(lang('CLANFORUM_BODY_INVALID'));
		break;
	}

	// post is going to be edited, so restore the action for logging purposes
	$action = 'edit';

	$q = $db->prepare('UPDATE '. CLAN_MESSAGE_TABLE .' SET cm_body = ?, cm_time = ?, cm_flags = cm_flags | ? WHERE cm_id = ?');
	$q->bindStrValue(1, $encbody);
	$q->bindIntValue(2, ($post_id < $lastpost) ? $post['cm_time'] : CUR_TIME);
	$q->bindIntValue(3, CMFLAG_EDIT);
	$q->bindIntValue(4, $post_id);
	$q->execute() or warning('Failed to edit message', 0);

	if ($post_id == $firstpost)
	{
		$q = $db->prepare('UPDATE '. CLAN_TOPIC_TABLE .' SET ct_subject = ?, ct_flags = ? WHERE ct_id = ?');
		$q->bindStrValue(1, $topic_subject);
		$q->bindIntValue(2, $topic_flags);
		$q->bindIntValue(3, $topic_id);
		$q->execute() or warning('Failed to edit topic', 0);
		logevent(varlist(array('clan_id', 'topic_id', 'topic_flags'), get_defined_vars()));
	}

	notice(lang('CLANFORUM_EDIT_COMPLETE'));
	logevent(varlist(array('clan_id', 'topic_id', 'post_id'), get_defined_vars()));
	$action = 'view';
	$post_id = 0;
	$post_body = '';
} while (0);
elseif ($action == 'delete') do
{
	if (!isFormPost())
		break;
	$topic_id = fixInputNum(getFormVar('topic_id', 0));
	$post_id = fixInputNum(getFormVar('post_id', 0));

	$q = $db->prepare('SELECT * FROM '. CLAN_TOPIC_TABLE .' WHERE ct_id = ? AND ct_flags & ? = 0');
	$q->bindIntValue(1, $topic_id);
	$q->bindIntValue(2, CTFLAG_DELETE);
	$q->execute() or warning('Failed to fetch topic', 0);
	$topic = $q->fetch();

	// default fallback to index
	$action = 'index';
	if (!$topic)
	{
		notice(lang('CLANFORUM_TOPIC_NOT_EXIST'));
		break;
	}
	if ($topic['c_id'] != $emp1->c_id)
	{
		notice(lang('CLANFORUM_TOPIC_WRONG_CLAN'));
		break;
	}

	// topic is okay, can fall back to view from here on in
	$action = 'view';
	// only moderators can delete posts from locked threads
	if (($topic['ct_flags'] & CTFLAG_LOCK) && (!$is_moderator))
	{
		notice(lang('CLANFORUM_DELETE_LOCKED'));
		// need to clear post ID to prevent message body from being quoted
		$post_id = 0;
		break;
	}
	// only privileged members can delete posts from the news thread
	if (($topic['ct_flags'] & CTFLAG_NEWS) && (!$is_ranked))
	{
		notice(lang('CLANFORUM_DELETE_NEWS'));
		$post_id = 0;
		break;
	}

	$firstpost = $db->queryCell('SELECT MIN(cm_id) FROM '. CLAN_MESSAGE_TABLE .' WHERE ct_id = ? AND cm_flags & ? = 0', array($topic_id, CMFLAG_DELETE));
	$lastpost = $db->queryCell('SELECT MAX(cm_id) FROM '. CLAN_MESSAGE_TABLE .' WHERE ct_id = ? AND cm_flags & ? = 0', array($topic_id, CMFLAG_DELETE));

	$q = $db->prepare('SELECT * FROM '. CLAN_MESSAGE_TABLE .' WHERE cm_id = ? AND cm_flags & ? = 0');
	$q->bindIntValue(1, $post_id);
	$q->bindIntValue(2, CMFLAG_DELETE);
	$q->execute() or warning('Failed to fetch post', 0);
	$post = $q->fetch();
	if (!$post)
	{
		notice(lang('CLANFORUM_MESSAGE_NOT_EXIST'));
		$post_id = 0;
		break;
	}
	// only moderators can delete other users' posts
	if (($post['e_id'] != $emp1->e_id) && (!$is_moderator))
	{
		notice(lang('CLANFORUM_DELETE_PERMISSION'));
		$post_id = 0;
		break;
	}
	// only moderators can delete posts after they've been replied to
	if (($post_id < $lastpost) && (!$is_moderator))
	{
		notice(lang('CLANFORUM_DELETE_TOO_LATE'));
		$post_id = 0;
		break;
	}

	$killtopic = FALSE;
	// if you choose to delete the first post in the topic
	if ($post_id == $firstpost)
	{
		// then take the entire topic along with it
		$killtopic = TRUE;
		if ($topic['ct_flags'] & CTFLAG_NEWS)
		{
			// unless it's the News thread
			$killtopic = FALSE;
			if ($post_id == $lastpost)
			{
				// ...and if it's the ONLY post in the News thread, don't allow it at all
				notice(lang('CLANFORUM_DELETE_CANNOT_NEWS'));
				$post_id = 0;
				break;
			}
		}
	}

	// post is going to be deleted, so restore the action for logging purposes
	$action = 'delete';

	if ($killtopic)
	{
		$q = $db->prepare('UPDATE '. CLAN_TOPIC_TABLE .' SET ct_flags = ct_flags | ? WHERE ct_id = ?');
		$q->bindIntValue(1, CTFLAG_DELETE);
		$q->bindIntValue(2, $topic_id);
		$q->execute() or warning('Failed to delete topic', 0);

		$q = $db->prepare('UPDATE '. CLAN_MESSAGE_TABLE .' SET cm_flags = cm_flags | ? WHERE ct_id = ?');
		$q->bindIntValue(1, CMFLAG_DELETE);
		$q->bindIntValue(2, $topic_id);
		$q->execute() or warning('Failed to delete messages', 0);
		$post_id = 0;
		notice(lang('CLANFORUM_DELETE_TOPIC_COMPLETE'));
	}
	else
	{
		$q = $db->prepare('UPDATE '. CLAN_MESSAGE_TABLE .' SET cm_flags = cm_flags | ? WHERE cm_id = ?');
		$q->bindIntValue(1, CMFLAG_DELETE);
		$q->bindIntValue(2, $post_id);
		$q->execute() or warning('Failed to delete message', 0);
		notice(lang('CLANFORUM_DELETE_MESSAGE_COMPLETE'));
	}

	logevent(varlist(array('clan_id', 'topic_id', 'post_id', 'killtopic'), get_defined_vars()));

	if ($killtopic)
		$action = 'index';
	else	$action = 'view';
	$post_id = 0;
} while (0);
elseif (!in_array($action, array('view', 'edit_form', 'delete_form')))
	$action = 'index';
notices();

if ($action == 'edit_form') do
{
	$topic_id = fixInputNum(getFormVar('topic_id', $topic_id));
	$post_id = fixInputNum(getFormVar('post_id', $post_id));

	$q = $db->prepare('SELECT * FROM '. CLAN_TOPIC_TABLE .' WHERE ct_id = ? AND ct_flags & ? = 0');
	$q->bindIntValue(1, $topic_id);
	$q->bindIntValue(2, CTFLAG_DELETE);
	$q->execute() or warning('Failed to fetch topic', 0);
	$topic = $q->fetch();

	$action = 'index';
	if (!$topic)
	{
		notice(lang('CLANFORUM_TOPIC_NOT_EXIST'));
		break;
	}
	if ($topic['c_id'] != $emp1->c_id)
	{
		notice(lang('CLANFORUM_TOPIC_WRONG_CLAN'));
		break;
	}
	$action = 'view';
	// only moderators can edit posts in locked threads
	if (($topic['ct_flags'] & CTFLAG_LOCK) && (!$is_moderator))
	{
		notice(lang('CLANFORUM_EDIT_LOCKED'));
		$post_id = 0;
		break;
	}
	// only privileged members can edit posts in the news thread
	if (($topic['ct_flags'] & CTFLAG_NEWS) && (!$is_ranked))
	{
		notice(lang('CLANFORUM_EDIT_NEWS'));
		$post_id = 0;
		break;
	}

	$firstpost = $db->queryCell('SELECT MIN(cm_id) FROM '. CLAN_MESSAGE_TABLE .' WHERE ct_id = ? AND cm_flags & ? = 0', array($topic_id, CMFLAG_DELETE));
	$lastpost = $db->queryCell('SELECT MAX(cm_id) FROM '. CLAN_MESSAGE_TABLE .' WHERE ct_id = ? AND cm_flags & ? = 0', array($topic_id, CMFLAG_DELETE));

	$q = $db->prepare('SELECT cm.*,e.e_name,e.c_id FROM '. CLAN_MESSAGE_TABLE .' cm LEFT OUTER JOIN '. EMPIRE_TABLE .' e USING (e_id) WHERE cm_id = ? AND cm_flags & ? = 0');
	$q->bindIntValue(1, $post_id);
	$q->bindIntValue(2, CMFLAG_DELETE);
	$q->execute() or warning('Failed to fetch post', 0);
	$post = $q->fetch();
	if (!$post)
	{
		notice(lang('CLANFORUM_MESSAGE_NOT_EXIST'));
		$post_id = 0;
		break;
	}
	// only moderators can edit other users' posts
	if (($post['e_id'] != $emp1->e_id) && (!$is_moderator))
	{
		notice(lang('CLANFORUM_EDIT_PERMISSION'));
		$post_id = 0;
		break;
	}
	// only moderators can edit posts after they've been replied to
	if (($post_id < $lastpost) && (!$is_moderator))
	{
		notice(lang('CLANFORUM_EDIT_TOO_LATE'));
		$post_id = 0;
		break;
	}
	$action = 'edit_form';

?>
<h2><a href="?location=clanforum"><?php echo lang('CLANFORUM_LINK_INDEX'); ?></a><?php
	echo lang('CLANFORUM_SUBJECT_SEP');
	if ($topic['ct_flags'] & CTFLAG_NEWS)
		echo lang('CLANFORUM_SUBJECT_NEWS');
	elseif ($topic['ct_subject'])
		echo $topic['ct_subject'];
	else	echo lang('MESSAGES_LABEL_NO_SUBJECT');
?></h2>
<table>
<tr><th><?php echo lang('CLANFORUM_COLUMN_AUTHOR'); ?></th><th><?php echo lang('CLANFORUM_COLUMN_MESSAGE'); ?></th></tr>
<?php
	$emp_a = new prom_empire($post['e_id']);
	$emp_a->initdata($post);
?>
<tr><td><?php echo $emp_a; ?><br /><?php
	if ($emp_a->c_id != $topic['c_id'])
		echo lang('CLANFORUM_NONMEMBER_LABEL');
	elseif ($emp_a->e_id == $clan1->e_id_leader)
		echo lang('CLAN_LEADER_LABEL');
	elseif ($emp_a->e_id == $clan1->e_id_asst)
		echo lang('CLAN_ASSISTANT_LABEL');
	elseif (($emp_a->e_id == $clan1->e_id_fa1) || ($emp_a->e_id == $clan1->e_id_fa2))
		echo lang('CLANFORUM_FA_LABEL');
	else	echo lang('CLANFORUM_MEMBER_LABEL');
?><br /><br /><?php echo $user1->customdate($post['cm_time']); ?></td><td><?php echo lang('CLANFORUM_LABEL_EDIT'); ?><br />
<form method="post" action="?location=clanforum">
<div>
<?php
	if (($post_id == $firstpost) && !($topic['ct_flags'] & CTFLAG_NEWS))
	{
		if (!strlen($topic_subject))
			$topic_subject = $topic['ct_subject'];
?>
<?php echo lang('LABEL_SUBJECT'); ?> <input type="text" name="topic_subject" size="40" value="<?php echo $topic_subject; ?>" /><br />
<?php
	}
	if (!strlen($post_body))
		$post_body = bbdecode($post['cm_body']);
?>
<textarea name="post_body" rows="15" cols="76"><?php echo $post_body; ?></textarea><br />
<?php
	if (($post_id == $firstpost) && ($is_moderator) && !($topic['ct_flags'] & CTFLAG_NEWS))
	{
		echo checkbox('topic_sticky', lang('CLANFORUM_FLAG_STICKY'), 1, ($topic['ct_flags'] & CTFLAG_STICKY)) .'<br />';
		echo checkbox('topic_locked', lang('CLANFORUM_FLAG_LOCKED'), 1, ($topic['ct_flags'] & CTFLAG_LOCK)) .'<br />';
	}
?>
</div>
<div class="ac"><input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>" /><input type="hidden" name="post_id" value="<?php echo $post['cm_id']; ?>" /><input type="hidden" name="action" value="edit" /><input type="submit" value="<?php echo lang('CLANFORUM_EDIT_SUBMIT'); ?>" /> <a href="?location=clanforum&amp;action=view&amp;topic_id=<?php echo $topic_id; ?>"><?php echo lang('CLANFORUM_EDIT_CANCEL'); ?></a></div>
</form>
</td></tr>
</table>
<?php
} while (0);
if ($action == 'delete_form') do
{
	$topic_id = fixInputNum(getFormVar('topic_id', $topic_id));
	$post_id = fixInputNum(getFormVar('post_id', $post_id));

	$q = $db->prepare('SELECT * FROM '. CLAN_TOPIC_TABLE .' WHERE ct_id = ? AND ct_flags & ? = 0');
	$q->bindIntValue(1, $topic_id);
	$q->bindIntValue(2, CTFLAG_DELETE);
	$q->execute() or warning('Failed to fetch topic', 0);
	$topic = $q->fetch();

	// default fallback to index
	$action = 'index';
	if (!$topic)
	{
		notice(lang('CLANFORUM_TOPIC_NOT_EXIST'));
		break;
	}
	if ($topic['c_id'] != $emp1->c_id)
	{
		notice(lang('CLANFORUM_TOPIC_WRONG_CLAN'));
		break;
	}

	// topic is okay, can fall back to view from here on in
	$action = 'view';
	// only moderators can delete posts from locked threads
	if (($topic['ct_flags'] & CTFLAG_LOCK) && (!$is_moderator))
	{
		notice(lang('CLANFORUM_DELETE_LOCKED'));
		// need to clear post ID to prevent message body from being quoted
		$post_id = 0;
		break;
	}
	// only privileged members can delete posts from the news thread
	if (($topic['ct_flags'] & CTFLAG_NEWS) && (!$is_ranked))
	{
		notice(lang('CLANFORUM_DELETE_NEWS'));
		$post_id = 0;
		break;
	}

	$firstpost = $db->queryCell('SELECT MIN(cm_id) FROM '. CLAN_MESSAGE_TABLE .' WHERE ct_id = ? AND cm_flags & ? = 0', array($topic_id, CMFLAG_DELETE));
	$lastpost = $db->queryCell('SELECT MAX(cm_id) FROM '. CLAN_MESSAGE_TABLE .' WHERE ct_id = ? AND cm_flags & ? = 0', array($topic_id, CMFLAG_DELETE));

	$q = $db->prepare('SELECT cm.*,e.e_name,e.c_id FROM '. CLAN_MESSAGE_TABLE .' cm LEFT OUTER JOIN '. EMPIRE_TABLE .' e USING (e_id) WHERE cm_id = ? AND cm_flags & ? = 0');
	$q->bindIntValue(1, $post_id);
	$q->bindIntValue(2, CMFLAG_DELETE);
	$q->execute() or warning('Failed to fetch post', 0);
	$post = $q->fetch();
	if (!$post)
	{
		notice(lang('CLANFORUM_MESSAGE_NOT_EXIST'));
		$post_id = 0;
		break;
	}
	// only moderators can delete other users' posts
	if (($post['e_id'] != $emp1->e_id) && (!$is_moderator))
	{
		notice(lang('CLANFORUM_DELETE_PERMISSION'));
		$post_id = 0;
		break;
	}
	// only moderators can delete posts after they've been replied to
	if (($post_id < $lastpost) && (!$is_moderator))
	{
		notice(lang('CLANFORUM_DELETE_TOO_LATE'));
		$post_id = 0;
		break;
	}

	$killtopic = FALSE;
	// if you choose to delete the first post in the topic
	if ($post_id == $firstpost)
	{
		// then take the entire topic along with it
		$killtopic = TRUE;
		if ($topic['ct_flags'] & CTFLAG_NEWS)
		{
			// unless it's the News thread
			$killtopic = FALSE;
			if ($post_id == $lastpost)
			{
				// ...and if it's the ONLY post in the News thread, don't allow it at all
				notice(lang('CLANFORUM_DELETE_CANNOT_NEWS'));
				$post_id = 0;
				break;
			}
		}
	}

	$action = 'delete_form';
?>
<h2><a href="?location=clanforum"><?php echo lang('CLANFORUM_LINK_INDEX'); ?></a><?php echo lang('CLANFORUM_SUBJECT_SEP'); ?><?php if ($topic['ct_flags'] & CTFLAG_NEWS) echo lang('CLANFORUM_SUBJECT_NEWS'); elseif ($topic['ct_subject']) echo $topic['ct_subject']; else echo lang('MESSAGES_LABEL_NO_SUBJECT'); ?></h2>
<table>
<tr><th><?php echo lang('CLANFORUM_COLUMN_AUTHOR'); ?></th><th><?php echo lang('CLANFORUM_COLUMN_MESSAGE'); ?></th></tr>
<?php
	$emp_a = new prom_empire($post['e_id']);
	$emp_a->initdata($post);
?>
<tr><td><?php echo $emp_a; ?><br /><?php
	if ($emp_a->c_id != $topic['c_id'])
		echo lang('CLANFORUM_NONMEMBER_LABEL');
	elseif ($emp_a->e_id == $clan1->e_id_leader)
		echo lang('CLAN_LEADER_LABEL');
	elseif ($emp_a->e_id == $clan1->e_id_asst)
		echo lang('CLAN_ASSISTANT_LABEL');
	elseif (($emp_a->e_id == $clan1->e_id_fa1) || ($emp_a->e_id == $clan1->e_id_fa2))
		echo lang('CLANFORUM_FA_LABEL');
	else	echo lang('CLANFORUM_MEMBER_LABEL');
?><br /><br /><?php echo $user1->customdate($post['cm_time']); ?></td><td><?php echo str_replace("\n", '<br />', $post['cm_body']); ?></td></tr>
<tr><td></td><td>
<form method="post" action="?location=clanforum">
<div class="ac">
<?php
	if ($killtopic)
		echo lang('CLANFORUM_DELETE_PROMPT_MESSAGE') .'<br />';
	else	echo lang('CLANFORUM_DELETE_PROMPT_TOPIC') .'<br />';
?>
<input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>" /><input type="hidden" name="post_id" value="<?php echo $post_id; ?>" /><input type="hidden" name="action" value="delete" /><input type="submit" value="<?php echo lang('COMMON_YES'); ?>" /> <a href="?location=clanforum&amp;action=view&amp;topic_id=<?php echo $topic_id; ?>"><?php echo lang('COMMON_NO'); ?></a>
</div>
</form>
</td></tr>
</table>
<?php
} while (0);
if ($action == 'view') do
{
	$topic_id = fixInputNum(getFormVar('topic_id', $topic_id));

	$q = $db->prepare('SELECT * FROM '. CLAN_TOPIC_TABLE .' WHERE ct_id = ? AND ct_flags & ? = 0');
	$q->bindIntValue(1, $topic_id);
	$q->bindIntValue(2, CTFLAG_DELETE);
	$q->execute() or warning('Unable to retrieve thread summary', 0);
	$topic = $q->fetch();

	if (!$topic)
	{
		notice(lang('CLANFORUM_TOPIC_NOT_EXIST'));
		$action = 'index';
		break;
	}
	if ($topic['c_id'] != $emp1->c_id)
	{
		notice(lang('CLANFORUM_TOPIC_WRONG_CLAN'));
		$action = 'index';
		break;
	}

	$q = $db->prepare('SELECT cm.*,e.e_name,e.c_id FROM '. CLAN_MESSAGE_TABLE .' cm LEFT OUTER JOIN '. EMPIRE_TABLE .' e USING (e_id) WHERE ct_id = ? AND cm_flags & ? = 0 ORDER BY cm_id ASC');
	$q->bindIntValue(1, $topic_id);
	$q->bindIntValue(2, CMFLAG_DELETE);
	$q->execute() or warning('Unable to retrieve thread contents', 0);
	$posts = $q->fetchAll();

	$firstpost = $posts[0]['cm_id'];
	$lastpost = $posts[count($posts) - 1]['cm_id'];

	// if it's locked, you need to be a moderator; if it's the News thread, you need any special rank
	$can_post = ((!($topic['ct_flags'] & CTFLAG_LOCK) || ($is_moderator)) && (!($topic['ct_flags'] & CTFLAG_NEWS) || ($is_ranked)));
?>
<h2><a href="?location=clanforum"><?php echo lang('CLANFORUM_LINK_INDEX'); ?></a><?php
	echo lang('CLANFORUM_SUBJECT_SEP');
	if ($topic['ct_flags'] & CTFLAG_NEWS)
		echo lang('CLANFORUM_SUBJECT_NEWS');
	elseif ($topic['ct_subject'])
		echo $topic['ct_subject'];
	else	echo lang('MESSAGES_LABEL_NO_SUBJECT');
?></h2>
<table>
<tr><th><?php echo lang('CLANFORUM_COLUMN_AUTHOR'); ?></th><th><?php echo lang('CLANFORUM_COLUMN_MESSAGE'); ?></th></tr>
<?php
	foreach ($posts as $post)
	{
		$emp_a = new prom_empire($post['e_id']);
		$emp_a->initdata($post);
?>
<tr><td><?php echo $emp_a; ?><br /><?php
		if ($emp_a->c_id != $topic['c_id'])
			echo lang('CLANFORUM_NONMEMBER_LABEL');
		elseif ($emp_a->e_id == $clan1->e_id_leader)
			echo lang('CLAN_LEADER_LABEL');
		elseif ($emp_a->e_id == $clan1->e_id_asst)
			echo lang('CLAN_ASSISTANT_LABEL');
		elseif (($emp_a->e_id == $clan1->e_id_fa1) || ($emp_a->e_id == $clan1->e_id_fa2))
			echo lang('CLANFORUM_FA_LABEL');
		else	echo lang('CLANFORUM_MEMBER_LABEL');
?><br /><br /><?php echo $user1->customdate($post['cm_time']); ?></td>
    <td><?php echo str_replace("\n", '<br />', $post['cm_body']); ?><br /><br /><?php
		if ($can_post)
		{
			if ((($post['e_id'] == $emp1->e_id) && ($post['cm_id'] == $lastpost)) || ($is_moderator))
			{
?><a href="?location=clanforum&amp;action=edit_form&amp;topic_id=<?php echo $topic_id; ?>&amp;post_id=<?php echo $post['cm_id']; ?>"><?php echo lang('CLANFORUM_SUBMIT_EDIT'); ?></a><?php
				if (!(($topic['ct_flags'] & CTFLAG_NEWS) && ($post['cm_id'] == $firstpost) && ($post['cm_id'] == $lastpost)))
				{
?> <a href="?location=clanforum&amp;action=delete_form&amp;topic_id=<?php echo $topic_id; ?>&amp;post_id=<?php echo $post['cm_id']; ?>"><?php echo lang('CLANFORUM_SUBMIT_DELETE'); ?></a><?php
				}
			}
?> <a href="?location=clanforum&amp;action=view&amp;topic_id=<?php echo $topic_id; ?>&amp;post_id=<?php echo $post['cm_id']; ?>"><?php echo lang('CLANFORUM_SUBMIT_QUOTE'); ?></a><?php
		}
?></td></tr>
<tr><td colspan="2"><hr /></td></tr>
<?php
		if ($post['cm_id'] == $post_id)
			$post_body = '[quote]'. preg_replace('/\[quote\].*?\[\/quote\]/s', '', bbdecode($post['cm_body'])) .'[/quote]';
	}
	if ($can_post)
	{
?>
<tr><td><?php echo lang('CLANFORUM_LABEL_REPLY'); ?></td><td>
<form method="post" action="?location=clanforum">
<div><textarea name="post_body" rows="15" cols="76"><?php echo $post_body; ?></textarea></div>
<div class="ac"><input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>" /><input type="hidden" name="action" value="reply" /><input type="submit" value="<?php echo lang('CLANFORUM_REPLY_SUBMIT'); ?>" /></div>
</form>
</td></tr>
<?php
	}
?>
</table>
<?php
} while (0);
notices();

if ($action == 'index')
{
	$topics = array();
	$q = $db->prepare('SELECT ct.*,cm.*,e.e_name FROM '. CLAN_TOPIC_TABLE .' ct LEFT OUTER JOIN '. CLAN_MESSAGE_TABLE .' cm ON (cm.cm_id = (SELECT MAX(x.cm_id) FROM '. CLAN_MESSAGE_TABLE .' x WHERE x.ct_id = ct.ct_id AND cm_flags & ? = 0)) LEFT OUTER JOIN '. EMPIRE_TABLE .' e USING (e_id) WHERE ct.c_id = ? AND ct_flags & ? = ? ORDER BY cm_time DESC');
	$q->bindIntValue(1, CMFLAG_DELETE);
	$q->bindIntValue(2, $emp1->c_id);
	$q->bindIntValue(3, CTFLAG_NEWS);
	$q->bindIntValue(4, CTFLAG_NEWS);
	$q->execute() or warning('Unable to retrieve news topic', 0);
	$topics[] = $q->fetch();

	$q->bindIntValue(3, CTFLAG_DELETE | CTFLAG_STICKY | CTFLAG_NEWS);
	$q->bindIntValue(4, CTFLAG_STICKY);
	$q->execute() or warning('Unable to retrieve sticky topics', 0);
	$stickytopics = $q->fetchAll();
	foreach ($stickytopics as $topic)
		$topics[] = $topic;

	$q->bindIntValue(3, CTFLAG_DELETE | CTFLAG_STICKY | CTFLAG_NEWS);
	$q->bindIntValue(4, 0);
	$q->execute() or warning('Unable to retrieve normal topics', 0);
	$normaltopics = $q->fetchAll();
	foreach ($normaltopics as $topic)
		$topics[] = $topic;

	$authors = array();
	$q = $db->prepare('SELECT ct.ct_id,cm.e_id,e.e_name FROM '. CLAN_TOPIC_TABLE .' ct LEFT OUTER JOIN '. CLAN_MESSAGE_TABLE .' cm ON (cm.cm_id = (SELECT MIN(x.cm_id) FROM '. CLAN_MESSAGE_TABLE .' x WHERE x.ct_id = ct.ct_id AND cm_flags & ? = 0)) LEFT OUTER JOIN '. EMPIRE_TABLE .' e USING (e_id) WHERE ct.c_id = ? AND ct_flags & ? = 0');
	$q->bindIntValue(1, CMFLAG_DELETE);
	$q->bindIntValue(2, $emp1->c_id);
	$q->bindIntValue(3, CTFLAG_DELETE);
	$q->execute() or warning('Unable to retrieve topic authors', 0);
	foreach ($q as $data)
		$authors[$data['ct_id']] = $data;

	$replies = array();
	$q = $db->prepare('SELECT ct_id,COUNT(*) AS ct_replies FROM '. CLAN_MESSAGE_TABLE .' WHERE cm_flags & ? = 0 GROUP BY ct_id');
	$q->bindIntValue(1, CMFLAG_DELETE);
	$q->execute() or warning('Unable to retrieve reply counts', 0);
	foreach ($q as $data)
		$replies[$data['ct_id']] = $data['ct_replies'] - 1;
?>
<table class="inputtable">
<tr><th colspan="2"><?php echo lang('CLANFORUM_COLUMN_TOPICS'); ?></th>
    <th><?php echo lang('CLANFORUM_COLUMN_AUTHOR'); ?></th>
    <th><?php echo lang('CLANFORUM_COLUMN_REPLIES'); ?></th>
    <th><?php echo lang('CLANFORUM_COLUMN_LASTPOST'); ?></th></tr>
<?php
	foreach ($topics as $topic)
	{
		$emp_a = new prom_empire($topic['e_id']);
		$emp_a->initdata($topic);

		$emp_b = new prom_empire($authors[$topic['ct_id']]['e_id']);
		$emp_b->initdata($authors[$topic['ct_id']]);
?>
<tr><td><?php
		if ($topic['ct_flags'] & CTFLAG_NEWS)
			echo lang('CLANFORUM_ICON_NEWS');
		if ($topic['ct_flags'] & CTFLAG_STICKY)
			echo lang('CLANFORUM_ICON_STICKY');
		if ($topic['ct_flags'] & CTFLAG_LOCK)
			echo lang('CLANFORUM_ICON_LOCKED');
?></td>
    <td><a href="?location=clanforum&amp;action=view&amp;topic_id=<?php echo $topic['ct_id']; ?>"><?php
		if ($topic['ct_flags'] & CTFLAG_NEWS)
			echo lang('CLANFORUM_SUBJECT_NEWS');
		elseif ($topic['ct_subject'])
			echo $topic['ct_subject'];
		else	echo lang('MESSAGES_LABEL_NO_SUBJECT');
?></a></td>
    <td><?php echo $emp_b; ?></td>
    <td class="ac"><?php echo $replies[$topic['ct_id']]; ?></td>
    <td><?php echo $emp_a; ?><br /><?php echo $user1->customdate($topic['cm_time']); ?></td></tr>
<?php
		$emp_b = NULL;
		$emp_a = NULL;
	}
?>
</table>
<form method="post" action="?location=clanforum">
<div>
<input type="hidden" name="action" value="post" />
<?php echo lang('LABEL_SUBJECT'); ?> <input type="text" name="topic_subject" size="40" value="<?php echo $topic_subject; ?>" /><br />
<textarea rows="15" cols="76" name="post_body"><?php echo $post_body; ?></textarea><?php
	if ($is_moderator)
	{
		echo '<br />'. checkbox('topic_sticky', lang('CLANFORUM_FLAG_STICKY'), 1);
		echo '<br />'. checkbox('topic_locked', lang('CLANFORUM_FLAG_LOCKED'), 1);
	}
?></div>
<div class="ac"><input type="submit" value="<?php echo lang('CLANFORUM_POST_SUBMIT'); ?>" /></div>
</form>
<?php
}
page_footer();
?>
