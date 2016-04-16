<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: permissions.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

// Checks if the specified IP address is banned
// Supports IPv4 and IPv6
function check_banned_ip ($ip)
{
	if (preg_match('/(?:\d{1,3}\.){3}\d{1,3}/', $ip))
		return check_banned($ip, PERM_IPV4);
	// fairly rough, but should suffice for detecting the difference between ipv4 and ipv6
	if (preg_match('/:?([0-9A-F]{0,4}:){1,7}[0-9A-F]{1,4}/i', $ip))
		return check_banned($ip, PERM_IPV6);
	return NULL;
}

// Checks if the specified email address is banned
function check_banned_email ($email)
{
	return check_banned($email, PERM_EMAIL);
}

// Checks all relevant permission entries in the database to determine if an address is banned or not
// Matching entries will have their hit dates (and hit counts for bans) updated
// Type must match one of the permission flag values
function check_banned ($address, $type)
{
	global $db;

	$perms = $db->prepare('SELECT * FROM '. PERMISSION_TABLE .' WHERE p_type & ? = ? AND (p_expire > ? OR p_expire = 0) ORDER BY p_id ASC');
	$perms->bindIntValue(1, PERM_MASK);
	$perms->bindIntValue(2, $type);
	$perms->bindIntValue(3, CUR_TIME);
	$perms->execute() or error_500('ERROR_TITLE', 'Failed to read from permission table!');

	$bans = array();
	$excepts = array();
	foreach ($perms as $perm)
	{
		if ($perm['p_type'] & PERM_EXCEPT)
			$excepts[] = $perm;
		else	$bans[] = $perm;
	}

	$banned = FALSE;
	$excepted = FALSE;
	foreach ($bans as $ban)
	{
		if ($type == PERM_IPV4)
			$match = match_ipv4($address, $ban['p_criteria']);
		if ($type == PERM_IPV6)
			$match = match_ipv6($address, $ban['p_criteria']);
		if ($type == PERM_EMAIL)
			$match = match_mail($address, $ban['p_criteria']);
		if ($match)
		{
			$banned = TRUE;
			break;
		}
	}
	if ($banned)
	{
		foreach ($excepts as $except)
		{
			if ($type == PERM_IPV4)
				$match = match_ipv4($address, $except['p_criteria']);
			if ($type == PERM_IPV6)
				$match = match_ipv6($address, $except['p_criteria']);
			if ($type == PERM_EMAIL)
				$match = match_mail($address, $except['p_criteria']);
			if ($match)
			{
				$excepted = TRUE;
				break;
			}
		}
	}

	if ($excepted)
	{
		$q = $db->prepare('UPDATE '. PERMISSION_TABLE .' SET p_lasthit = ? WHERE p_id = ?');
		$q->bindIntValue(1, CUR_TIME);
		$q->bindIntValue(2, $except['p_id']);
		$q->execute();
		return NULL;
	}
	elseif ($banned)
	{
		$q = $db->prepare('UPDATE '. PERMISSION_TABLE .' SET p_lasthit = ?, p_hitcount = p_hitcount + 1 WHERE p_id = ?');
		$q->bindIntValue(1, CUR_TIME);
		$q->bindIntValue(2, $ban['p_id']);
		$q->execute();
		return $ban;
	}
	else	return NULL;
}

// Checks if a given IPv4 address matches the specified criteria
// Criteria must consist of an IP address and a network mask, separated by a slash
// Example: "192.168.1.0/255.255.255.0"
function match_ipv4 ($ip, $criteria)
{
	list($match, $mask) = explode('/', $criteria);
	return ((ip2long($ip) & ip2long($mask)) == (ip2long($match) & ip2long($mask)));
}

// Checks if a given IPv6 address matches the specified criteria
// Criteria must consist of an IP address and a CIDR suffix, separated by a slash
// Example: "2001:0db8:1234::/48"
function match_ipv6 ($ip, $criteria)
{
	// Strip off zone index, if present
	if (strpos($ip, '%') !== FALSE)
		list($ip) = explode('%', $ip);
	list($match, $net) = explode('/', $criteria);
	// Expand both addresses into 32-character hex strings, no colons
	$ip = ipv6_expand($ip);
	$match = ipv6_expand($match);
	// Match whole number of characters
	if (substr($ip, 0, floor($net / 4)) != substr($match, 0, floor($net / 4)))
		return FALSE;
	if (($net % 4) == 0)
		return TRUE;
	// Match partial characters
	$ip = hexdec(substr($ip, floor($net / 4), 1));
	$match = hexdec(substr($match, floor($net / 4), 1));
	$mask = 0xF & (0xF << (4 - ($net % 4)));
	return (($ip & $mask) == ($match & $mask));
}

// Checks if a given email address matches the specified criteria
// Criteria must consist of an email address containing optional "*" wildcards
// Example: "*@hotmail.com"
function match_mail ($email, $criteria)
{
	$regex = '/^'. str_replace('\*', '.*?', preg_quote($criteria)) .'$/';
	return preg_match($regex, $email);
}

// Expands an IPv6 address into a full 32-character hexadecimal string, no colons
function ipv6_expand ($ip)
{
	$address = explode(':', $ip);
	// check for hybrid IPv6/IPv4 "compatible address" notation
	$last = array_pop($address);
	if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $last))
	{
		array_push($address, dechex((ip2long($last) >> 16) & 0xFFFF));
		array_push($address, dechex(ip2long($last) & 0xFFFF));
	}
	else	array_push($address, $last);
	$out = '';
	$midpoint = array_search('', $address);
	// address starts with '::'
	if ($midpoint === 0)
	{
		array_shift($address);
		array_shift($address);
		for ($i = 0; $i < 8 - count($address); $i++)
			$out .= '0000';
		foreach ($address as $data)
			$out .= str_pad($data, 4, '0', STR_PAD_LEFT);
	}
	// address ends with '::'
	elseif (($midpoint == count($address) - 2) && ($address[$midpoint + 1] === ''))
	{
		array_pop($address);
		array_pop($address);
		foreach ($address as $data)
			$out .= str_pad($data, 4, '0', STR_PAD_LEFT);
		for ($i = count($address); $i < 8; $i++)
			$out .= '0000';
	}
	// address possibly contains '::' somewhere in the middle
	else
	{
		foreach ($address as $data)
		{
			if ($data === '')
			{
				for ($i = 0; $i < 8 - count($address) + 1; $i++)
					$out .= '0000';
			}
			else	$out .= str_pad($data, 4, '0', STR_PAD_LEFT);
		}
	}
	return $out;
}
?>
