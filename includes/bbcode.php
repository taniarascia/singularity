<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: bbcode.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

// Encodes any BBcode found inside a message
function bbencode ($message)
{
	// add padding to beginning so we can use strpos() more easily
	$message = ' '. $message;

	// if there aren't any square brackets, there's nothing to do
	if (!(strpos($message, '[') && strpos($message, ']')))
		return trim($message);

	// Parse the message to build a tag tree
	$msg = bbparse($message);
	return trim($msg);
}

// Worker function - locates complete BBcode tags and formats their contents
// Works recursively so we can ensure that we never nest elements improperly
// Do NOT call this function directly
function bbparse ($message, $inline = FALSE)
{
	$msg = '';
	while (1)
	{
		if (strlen($message) == 0)
			break;
		$match = array();
		preg_match('/\[([^\/][^\]]*?)\]/', $message, $match, PREG_OFFSET_CAPTURE);
		// did we find any tags?
		if (count($match) == 0)
		{
			// if not, dump out the rest of the message as plain text
			$msg .= $message;
			break;
		}
		$tag = $match[1][0];
		// we found a tag - is it one we recognize?
		if (!in_array($tag, array('url', 'email', 'b', 'i', 'u', 'center', 'quote')))
		{
			$msg .= substr($message, 0, $match[1][1]);
			$message = substr($message, $match[1][1]);
			continue;
		}
		// try to grab the contents of the tag
		// note that this will not handle nesting of a tag within itself
		$match2 = array();
		preg_match('/\['. $tag .'\](.*?)\[\/'. $tag .'\]/s', $message, $match2, PREG_OFFSET_CAPTURE);

		if (count($match2) == 0)
		{
			$msg .= substr($message, 0, $match[1][1]);
			$message = substr($message, $match[1][1]);
			continue;
		}
		$before = substr($message, 0, $match2[0][1]);
		$after = substr($message, $match2[0][1] + strlen($match2[0][0]));
		$contents = $match2[1][0];

		$msg .= $before;
		$msg .= bbenctag($tag, $contents, $inline);
		$message = $after;
	}
	return $msg;
}

// Worker function - formats the contents of a single tag (and possibly looks for other tags inside it)
// Do NOT call this function directly
function bbenctag ($tag, $contents, $inline)
{
	switch ($tag)
	{
	case 'url':
		$contents = trim($contents);
		// [url]http://www.example.com/path[/url] (no whitespace)
		$count = 0;
		$data = preg_replace('/^([a-z]+?:\/\/){1}([^\s]*?)$/i', '<a href="\1\2" rel="external"><!--url-->\1\2<!--/url--></a>', $contents, -1, $count);
		if ($count)
			break;

		// [url]www.example.com/path[/url] (no whitespace)
		$data = preg_replace('/^([^\s]*?)$/i', '<a href="http://\1" rel="external"><!--url-->\1<!--/url--></a>', $contents, -1, $count);
		if ($count)
			break;

		// there was whitespace - fail it
		$data = '[url]'. $contents .'[/url]';
		break;

	case 'email':
		$contents = trim($contents);
		$count = 0;
		// [email]user@example.com[/url] (no whitespace)
		$data = preg_replace('/^([^\s]*?)$/i', '<a href="mailto:\1"><!--email-->\1<!--/email--></a>', $contents, -1, $count);
		if ($count)
			break;

		// there was whitespace - fail it
		$data = '[email]'. $contents .'[/email]';
		break;

	case 'b':
		$data = '<span style="font-weight:bold"><!--b-->'. bbparse($contents, TRUE) .'<!--/b--></span>';
		break;

	case 'i':
		$data = '<span style="font-style:italic"><!--i-->'. bbparse($contents, TRUE) .'<!--/i--></span>';
		break;

	case 'u':
		$data = '<span style="text-decoration:underline"><!--u-->'. bbparse($contents, TRUE) .'<!--/u--></span>';
		break;

	case 'center':
		if ($inline)
		{
			$data = '[center]'. $contents .'[/center]';
			break;
		}
		$data = '<div class="ac"><!--center-->'. bbparse($contents) .'<!--/center--></div>';
		break;

	case 'quote':
		if ($inline)
		{
			$data = '[quote]'. $contents .'[/quote]';
			break;
		}
		$data = '<table style="border:0;font-size:smaller;margin-left:20px"><tr><td>Quote:<hr /></td></tr><tr><td><!--quote-->'. bbparse($contents) .'<!--/quote--></td></tr><tr><td><hr /></td></tr></table>';
		break;
	}
	return $data;
}

// Decodes any HTML found inside a message and turns it back into BBcode
function bbdecode ($message)
{
	// strip out all links - they'll still have the bbcode comments inside so we can restore the tags below
	$message = preg_replace('/<a href=".*?" rel="external">(.*?)<\/a>/', '\\1', $message);

	$from = array(
		'<table style="border:0;font-size:smaller;margin-left:20px"><tr><td>Quote:<hr /></td></tr><tr><td><!--quote-->', '<!--/quote--></td></tr><tr><td><hr /></td></tr></table>',
		'<div class="ac"><!--center-->', '<!--/center--></div>',
		'<span style="text-decoration:underline"><!--u-->', '<!--/u--></span>',
		'<span style="font-style:italic"><!--i-->', '<!--/i--></span>',
		'<span style="font-weight:bold"><!--b-->', '<!--/b--></span>',
		'<!--email-->', '<!--/email-->',
		'<!--url-->', '<!--/url-->',
	);
	$to = array('[quote]', '[/quote]', '[center]', '[/center]', '[u]', '[/u]', '[i]', '[/i]', '[b]', '[/b]', '[email]', '[/email]', '[url]', '[/url]');

	$message = str_replace($from, $to, $message);
	return $message;
}
?>
