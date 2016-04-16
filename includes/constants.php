<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: constants.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

// Entity type IDs, used for locking
define('ENT_USER',	1);	// User account
define('ENT_EMPIRE',	2);	// Empire
define('ENT_CLAN',	3);	// Clan
define('ENT_VARS',	4);	// World variables
define('ENT_MARKET',	5);	// Public market items

// Permission flags
define('PERM_EXCEPT',	0x01);	// Permission entry is an exception rather than a ban
define('PERM_IPV4',	0x00);	// Permission specifies an IPv4 address+mask
define('PERM_EMAIL',	0x02);	// Permission specifies an email address mask
define('PERM_IPV6',	0x04);	// Permission specifies an IPv6 address+mask
define('PERM_MASK',	0x06);	// Bitmask for permission types

// Lock owner IDs for special functions - used only for potential logging purposes
define('LOCK_SCRIPT',	2147483643);	// Utility script
define('LOCK_HISTORY',	2147483644);	// Record history
define('LOCK_RESET',	2147483645);	// Round reset
define('LOCK_NEW',	2147483646);	// New entity creation
define('LOCK_TURNS',	2147483647);	// Turns script

// User flags
define('UFLAG_MOD',	0x01);	// User has Moderator privileges (can set/clear multi and disabled flags, can browse empire messages)
define('UFLAG_ADMIN',	0x02);	// User has Administrator privileges (can grant/revoke privileges, delete/rename empires, login as anyone, edit clans)
define('UFLAG_DISABLE',	0x04);	// User account is disabled, cannot create new empires (but can still login to existing ones)
define('UFLAG_VALID',	0x08);	// User account's email address has been validated at least once
define('UFLAG_CLOSED',	0x10);	// User account has been voluntarily closed, cannot create new empires or login to existing ones
define('UFLAG_WATCH',	0x20);	// User account is suspected of abuse

// Empire flags
//define('EFLAG_MOD',	0);		// Unused
define('EFLAG_ADMIN',	0x0002);	// Empire is owned by moderator/administrator and cannot interact with other empires
define('EFLAG_DISABLE',	0x0004);	// Empire is disabled
define('EFLAG_VALID',	0x0008);	// Empire has submitted their validation code
define('EFLAG_DELETE',	0x0010);	// Empire is flagged for deletion
define('EFLAG_MULTI',	0x0020);	// Empire is one of multiple accounts being accessed from the same location (legally or not)
define('EFLAG_NOTIFY',	0x0040);	// Empire is in a notification state and cannot perform actions (and will not update idle time)
define('EFLAG_ONLINE',	0x0080);	// Empire is currently logged in
define('EFLAG_SILENT',	0x0100);	// Empire is prohibited from sending private messages to non-Administrators
define('EFLAG_LOGGED',	0x0200);	// All actions performed by empire are logged with a special event code

// Empire message flags
define('MFLAG_DELETE',	0x01);	// Message has been deleted
define('MFLAG_READ',	0x02);	// Message has been read
define('MFLAG_REPLY',	0x04);	// Message has been replied to
define('MFLAG_REPORT',	0x08);	// Message has been reported for abuse
define('MFLAG_DEAD',	0x10);	// Message sender is dead

// Empire news flags
define('NFLAG_READ',	0x01);	// News item has been read
define('NFLAG_LOCK',	0x02);	// News item is currently being processed
define('NFLAG_GOTTEN',	0x04);	// Items attached to the news message have been received

// Clan relation flags
define('CRFLAG_MUTUAL',	0x01);	// Clan relation is mutual - set to complete an alliance, clear to stop a war
define('CRFLAG_ALLY',	0x02);	// Clan relation describes an alliance
define('CRFLAG_WAR',	0x04);	// Clan relation describes a war

// Clan forum thread flags
define('CTFLAG_NEWS',	0x01);	// Topic contains News postings for the clan, visible on main page
define('CTFLAG_STICKY',	0x02);	// Topic is sticky and appears in bold at the top of the list
define('CTFLAG_LOCK',	0x04);	// Topic has been locked - normal members may not post
define('CTFLAG_DELETE',	0x08);	// Topic has been deleted

// Clan forum message flags
define('CMFLAG_EDIT',	0x01);	// Post has been edited
define('CMFLAG_DELETE',	0x02);	// Post has been deleted

// Clan invite flags
define('CIFLAG_PERM',	0x01);	// Clan invitation is permanent, effectively a whitelist entry

// History round flags
define('HRFLAG_CLANS',	0x01);	// Round had clans enabled
define('HRFLAG_SCORE',	0x02);	// Round ranked empires by score rather than networth

// History empire flags
define('HEFLAG_PROTECT',	0x01);	// Empire was protected, whether vacation or newly registered
define('HEFLAG_ADMIN',	EFLAG_ADMIN);	// Empire was owned by a moderator/administrator

// Turn log entry types
define('TURN_EVENT',	0);	// Normal turn log entry
define('TURN_START',	1);	// Start of a turn run
define('TURN_END',	2);	// End of a turn run
define('TURN_ABORT',	3);	// Turn run was aborted due to there being nothing to do

// Public market item types
define('MARKET_TRPARM',	0);
define('MARKET_TRPLND',	1);
define('MARKET_TRPFLY',	2);
define('MARKET_TRPSEA',	3);
define('MARKET_FOOD',	4);

// Database table names
define('CLAN_TABLE', TABLE_PREFIX .'clan');
define('CLAN_INVITE_TABLE', TABLE_PREFIX .'clan_invite');
define('CLAN_MESSAGE_TABLE', TABLE_PREFIX .'clan_message');
define('CLAN_NEWS_TABLE', TABLE_PREFIX .'clan_news');
define('CLAN_RELATION_TABLE', TABLE_PREFIX .'clan_relation');
define('CLAN_TOPIC_TABLE', TABLE_PREFIX .'clan_topic');
define('EMPIRE_TABLE', TABLE_PREFIX .'empire');
define('EMPIRE_EFFECT_TABLE', TABLE_PREFIX .'empire_effect');
define('EMPIRE_MESSAGE_TABLE', TABLE_PREFIX .'empire_message');
define('EMPIRE_NEWS_TABLE', TABLE_PREFIX .'empire_news');
define('HISTORY_CLAN_TABLE', TABLE_PREFIX .'history_clan');
define('HISTORY_EMPIRE_TABLE', TABLE_PREFIX .'history_empire');
define('HISTORY_ROUND_TABLE', TABLE_PREFIX .'history_round');
define('LOCK_TABLE', TABLE_PREFIX .'locks');
define('LOG_TABLE', TABLE_PREFIX .'log');
define('LOTTERY_TABLE', TABLE_PREFIX .'lottery');
define('MARKET_TABLE', TABLE_PREFIX .'market');
define('PERMISSION_TABLE', TABLE_PREFIX .'permission');
define('SESSION_TABLE', TABLE_PREFIX .'session');
define('TURNLOG_TABLE', TABLE_PREFIX .'turnlog');
define('USER_TABLE', TABLE_PREFIX .'users');
define('VAR_TABLE', TABLE_PREFIX .'var');
define('VAR_ADJUST_TABLE', TABLE_PREFIX .'var_adjust');

// Lookup table for translating table name token to actual table name (for setup.php)
$tables = array();
$tables['{CLAN}'] = CLAN_TABLE;
$tables['{CLAN_INVITE}'] = CLAN_INVITE_TABLE;
$tables['{CLAN_MESSAGE}'] = CLAN_MESSAGE_TABLE;
$tables['{CLAN_NEWS}'] = CLAN_NEWS_TABLE;
$tables['{CLAN_RELATION}'] = CLAN_RELATION_TABLE;
$tables['{CLAN_TOPIC}'] = CLAN_TOPIC_TABLE;
$tables['{EMPIRE}'] = EMPIRE_TABLE;
$tables['{EMPIRE_EFFECT}'] = EMPIRE_EFFECT_TABLE;
$tables['{EMPIRE_MESSAGE}'] = EMPIRE_MESSAGE_TABLE;
$tables['{EMPIRE_NEWS}'] = EMPIRE_NEWS_TABLE;
$tables['{HISTORY_CLAN}'] = HISTORY_CLAN_TABLE;
$tables['{HISTORY_EMPIRE}'] = HISTORY_EMPIRE_TABLE;
$tables['{HISTORY_ROUND}'] = HISTORY_ROUND_TABLE;
$tables['{LOCK}'] = LOCK_TABLE;
$tables['{LOG}'] = LOG_TABLE;
$tables['{LOTTERY}'] = LOTTERY_TABLE;
$tables['{MARKET}'] = MARKET_TABLE;
$tables['{PERMISSION}'] = PERMISSION_TABLE;
$tables['{SESSION}'] = SESSION_TABLE;
$tables['{TURNLOG}'] = TURNLOG_TABLE;
$tables['{USER}'] = USER_TABLE;
$tables['{VAR}'] = VAR_TABLE;
$tables['{VAR_ADJUST}'] = VAR_ADJUST_TABLE;

// Lookup table for translating table name to sequence name (where applicable)
$sequences = array();
$sequences[CLAN_TABLE] = CLAN_TABLE .'_seq';
$sequences[CLAN_INVITE_TABLE] = CLAN_INVITE_TABLE .'_seq';
$sequences[CLAN_MESSAGE_TABLE] = CLAN_MESSAGE_TABLE .'_seq';
$sequences[CLAN_NEWS_TABLE] = CLAN_NEWS_TABLE .'_seq';
$sequences[CLAN_RELATION_TABLE] = CLAN_RELATION_TABLE .'_seq';
$sequences[CLAN_TOPIC_TABLE] = CLAN_TOPIC_TABLE .'_seq';
$sequences[EMPIRE_TABLE] = EMPIRE_TABLE .'_seq';
$sequences[EMPIRE_MESSAGE_TABLE] = EMPIRE_MESSAGE_TABLE .'_seq';
$sequences[EMPIRE_NEWS_TABLE] = EMPIRE_NEWS_TABLE .'_seq';
$sequences[HISTORY_ROUND_TABLE] = HISTORY_ROUND_TABLE .'_seq';
$sequences[LOG_TABLE] = LOG_TABLE .'_seq';
$sequences[MARKET_TABLE] = MARKET_TABLE .'_seq';
$sequences[PERMISSION_TABLE] = PERMISSION_TABLE .'_seq';
$sequences[TURNLOG_TABLE] = TURNLOG_TABLE .'_seq';
$sequences[USER_TABLE] = USER_TABLE .'_seq';

// World variables that must be defined in order for the game to run
$required_vars = array(
	'lotto_current_jackpot', 'lotto_yesterday_jackpot', 'lotto_last_picked', 'lotto_last_winner', 'lotto_jackpot_increase',
	'round_time_begin', 'round_time_closing', 'round_time_end',
	'turns_next', 'turns_next_hourly', 'turns_next_daily'
);

// For the scope of one script execution, this is constant
define('CUR_TIME', time());

// Configurable time zones
$timezones = array(
	-43200 => 'UTC-12',
	-39600 => 'UTC-11',
	-36000 => 'UTC-10',
	-34200 => 'UTC-9:30',
	-32400 => 'UTC-9',
	-28800 => 'UTC-8',
	-25200 => 'UTC-7',
	-21600 => 'UTC-6',
	-18000 => 'UTC-5',
	-14400 => 'UTC-4',
	-12600 => 'UTC-3:30',
	-10800 => 'UTC-3',
	 -7200 => 'UTC-2',
	 -3600 => 'UTC-1',
	     0 => 'UTC',
	  3600 => 'UTC+1',
	  7200 => 'UTC+2',
	 10800 => 'UTC+3',
	 12600 => 'UTC+3:30',
	 14400 => 'UTC+4',
	 16200 => 'UTC+4:30',
	 18000 => 'UTC+5',
	 19800 => 'UTC+5:30',
	 20700 => 'UTC+5:45',
	 21600 => 'UTC+6',
	 23400 => 'UTC+6:30',
	 25200 => 'UTC+7',
	 28800 => 'UTC+8',
	 31500 => 'UTC+8:45',
	 32400 => 'UTC+9',
	 34200 => 'UTC+9:30',
	 36000 => 'UTC+10',
	 37800 => 'UTC+10:30',
	 39600 => 'UTC+11',
	 41400 => 'UTC+11:30',
	 43200 => 'UTC+12',
	 45900 => 'UTC+12:45',
	 46800 => 'UTC+13',
	 50400 => 'UTC+14',
);
?>
