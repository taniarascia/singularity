<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: misc.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

// Special variables used by functions in this file
$notices = '';		// Notices accumulated using notice() and displayed using notices()

// Sends mail - returns 0 for success, >0 for error
function prom_mail ($to, $subj, $msg)
{
	return !mail($to, $subj, $msg, 'From: '. lang('EMAIL_FROM') .' <'. MAIL_VALIDATE .">\nX-Mailer: PHP/". phpversion());
}

// checks if a form is being submitted via GET or POST
function isFormPost ()
{
	return ($_SERVER['REQUEST_METHOD'] == 'POST');
}

// Converts non-breaking spaces into normal ones
function remove_nbsp ($str)
{
	return str_replace(html_entity_decode('&nbsp;', ENT_COMPAT, 'UTF-8'), ' ', $str);
}

// fetches a field from the posted form, trims whitespace
function getFormVar ($var, $default = '')
{
	if (isset($_REQUEST[$var]))
		return trim(remove_nbsp($_REQUEST[$var]));
	else	return $default;
}

// fetches an array from the posted form
function getFormArr ($var, $default = array())
{
	if (isset($_REQUEST[$var]))
		return $_REQUEST[$var];
	else	return $default;
}

// remove any special punctuation (thousands separators), allow positive integers only
function fixInputNum ($num)
{
	$result = floor(unformat_number($num));
	if ($result > 0)
		return $result;
	else	return 0;
}

// remove any special punctuation (thousands separators), allow positive or negative integers
function fixInputNumSigned ($num)
{
	$result = floor(unformat_number($num));
	return $result;
}

// restrict input to a boolean value
function fixInputBool ($bool)
{
	if ($bool)
		return TRUE;
	else	return FALSE;
}

// Builds a string "(?,?,?,...,?)" for a given array of parameters
function sqlArgList ($args, $fill = '?')
{
	return '('. implode(',', array_fill(0, count($args), $fill)) .')';
}

// Returns one of several frequently used lookup tables
function lookup ($id)
{
	static $lookup = array(
		// unit lists of various types
		'list_mil'	=> array('trparm', 'trplnd', 'trpfly', 'trpsea'),
		'list_mkt'	=> array('trparm', 'trplnd', 'trpfly', 'trpsea', 'food'),
		'list_aid'	=> array('trparm', 'trplnd', 'trpfly', 'trpsea', 'cash', 'runes', 'food'),

		// private market property lookups
		'pvtmkt_name_id'	=> array('trparm' => 'e_mktarm', 'trplnd' => 'e_mktlnd', 'trpfly' => 'e_mktfly', 'trpsea' => 'e_mktsea', 'food' => 'e_mktfood'),
		'pvtmkt_name_limit'	=> array('trparm' => 'e_mktperarm', 'trplnd' => 'e_mktperlnd', 'trpfly' => 'e_mktperfly', 'trpsea' => 'e_mktpersea'),
		'pvtmkt_name_cost'	=> array('trparm' => PVTM_TRPARM, 'trplnd' => PVTM_TRPLND, 'trpfly' => PVTM_TRPFLY, 'trpsea' => PVTM_TRPSEA, 'food' => PVTM_FOOD),

		// public market property lookups
		'pubmkt_name_id'	=> array('trparm' => MARKET_TRPARM, 'trplnd' => MARKET_TRPLND, 'trpfly' => MARKET_TRPFLY, 'trpsea' => MARKET_TRPSEA, 'food' => MARKET_FOOD),
		'pubmkt_id_name'	=> array(MARKET_TRPARM => 'trparm', MARKET_TRPLND => 'trplnd', MARKET_TRPFLY => 'trpfly', MARKET_TRPSEA => 'trpsea', MARKET_FOOD => 'food'),
		'pubmkt_id_cost'	=> array(MARKET_TRPARM => PVTM_TRPARM, MARKET_TRPLND => PVTM_TRPLND, MARKET_TRPFLY => PVTM_TRPFLY, MARKET_TRPSEA => PVTM_TRPSEA, MARKET_FOOD => PVTM_FOOD),
	);

	if (!isset($lookup[$id]))
	{
		warning('Attempted to fetch undefined lookup table '. $id, 1);
		return NULL;
	}

	$data = $lookup[$id];
	return $data;
}

// Generates a gaussian random number within a particular range
// Mean defaults to the center of the range
// Standard deviation defaults to 1/6th of the range (such that only 0.3% of values will get clipped)
function gauss_rand ($min, $max, $dev = 0, $mean = 0)
{
	if ($mean == 0)
		$mean = ($max + $min) / 2;
	if ($dev == 0)
		$dev = ($max - $min) / 6;

	$randmax = mt_getrandmax();

	while (1)
	{
		do
		{
			$x1 = mt_rand();
			$x2 = mt_rand();
		} while ($x1 == 0);

		$x1 /= $randmax;
		$x2 /= $randmax;

		// can also change cos() into sin(), but that's not necessary
		$y1 = sqrt(-2 * log($x1)) * cos(2 * M_PI * $x2);

		$val = $y1 * $dev + $mean;
		// if the value is out of range, reroll
		if (($val >= $min) && ($val <= $max))
			break;
	}
	return $val;
}
// Shortcut for always getting an integer value
function gauss_intrand ($min, $max, $dev = 0, $mean = 0)
{
	return intval(gauss_rand($min, $max, $dev, $mean));
}

function notice ($msg)
{
	global $notices;
	if (strlen($notices) > 0)
		$notices .= "<br />\n";
	$notices .= $msg;
}

function notices ($style = 0)
{
	global $notices;
	if (!empty($notices))
	{
		switch ($style)
		{
		case 2:
			echo '<h4 class="cwarn">'. $notices .'</h4>';
			break;
		case 1:
			echo '<h4>'. $notices .'</h4>';
			break;
		case 0:
		default:
			echo $notices .'<hr />';
			break;
		}
		$notices = '';
	}
}

function redirect ($newurl)
{
	header('Location: '. $newurl);
	exit;
}

function validate_email ($email)
{
	return preg_match('/[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/i', $email);
}

function validate_url ($url)
{
	return preg_match('/https?:\/\/(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(\/[^\s]*)?/i', $url);
}

require_once(PROM_BASEDIR .'includes/PasswordHash.php');

function enc_password ($pass)
{
	$pwh = new PasswordHash(10, FALSE);
	return $pwh->HashPassword($pass);
}

function chk_password ($pass, $hash)
{
	$pwh = new PasswordHash(10, FALSE);
	return $pwh->CheckPassword($pass, $hash);
}

?>
