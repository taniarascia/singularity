<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: prom_html.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

abstract class prom_html
{
	protected $starttime;

	protected function getstyle ()
	{
		global $styles;
		return $styles[DEFAULT_STYLE]['file'];
	}

	// Global styles, overriding all stylesheets
	protected function addStyles ()
	{
	}

	protected function addScripts ()
	{
?>
<script type="text/javascript">
//<![CDATA[
window.onload = function ()
{
	var tags = document.getElementsByTagName('a');
	for (var i = 0; i < tags.length; i++)
		if (tags[i].getAttribute('rel') == 'external')
			tags[i].target = '_blank';
	var logout = document.getElementById('logout');
	if (logout)
		logout.target = '_top';
}
function togglechecks (prefix)
{
	var tags = document.getElementsByTagName('input');
	for (var i = 0; i < tags.length; i++)
	{
		if ((tags[i].type == 'checkbox') && (tags[i].id.substring(0, prefix.length + 1) == prefix + '_'))
			tags[i].checked = !tags[i].checked;
	}
}
//]]>
</script>
<?php
	}

	abstract public function begin ($title);
	abstract public function end ();
}

// Generates HTML for pages accessed while logged in
class prom_html_full extends prom_html
{
	protected $user, $empire;

	public function __construct ($user, $empire)
	{
		$this->user = $user;
		$this->empire = $empire;
	}

	protected function getstyle ()
	{
		global $styles;
		return $styles[$this->user->u_style]['file'];
	}

	public function begin ($title)
	{
		$this->starttime = microtime(TRUE);

		Header("Cache-Control: no-cache");
		Header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
		if (isset($_SERVER["HTTP_ACCEPT"]) && stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml"))
		{
			Header("Content-Type: application/xhtml+xml; charset=utf-8");
			echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		}
		else	Header("Content-Type: text/html; charset=utf-8");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?php echo lang('LANG_CODE'); ?>" xml:lang="<?php echo lang('LANG_CODE'); ?>" dir="<?php echo lang('LANG_DIR'); ?>">
<head>
<title><?php echo lang('HTML_TITLE', $title); ?></title>
<link rel="stylesheet" type="text/css" href="css/<?php echo $this->getstyle(); ?>" />
<?php
		$this->addStyles();
		$this->addScripts();
?>
</head>
<body>
<div id="sidebar"><?php
		$this->printMenuBar();
?></div>
<div id="content" class="ac"><?php
		$this->printBanner();
	}

	public function end ()
	{
		global $db;
		$dur = microtime(TRUE) - $this->starttime;
?>
<br /><?php echo lang('HTML_FOOTER', GAME_VERSION); ?>
<br /><a href="?location=credits"><?php echo lang('HTML_LINK_CREDITS'); ?></a>
<?php
		if (DEBUG_FOOTER)
			echo '<br /><br />'.lang('HTML_DEBUG_FOOTER', round($dur * 1000, 1), number(memory_get_usage()), number(memory_get_peak_usage()), $db ? $db->getQueryCount() : 0);
		else	echo '<!-- '. $dur .', '. memory_get_usage() .'/'. memory_get_peak_usage() .', '. ($db ? $db->getQueryCount() : 0) .' -->';
?>
</div>
</body>
</html>
<?php
	}

	// Print the side menu
	private function printMenuBar ()
	{
		$menu_info = array();
		$menu_info[] = array('label' => lang('MENU_LINK_HOME'), 'location' => 'main');
		$menu_info[] = array('label' => lang('MENU_LINK_STATUS'), 'location' => 'status');
		$menu_info[] = array('label' => lang('MENU_LINK_SCORES'), 'location' => 'scores');
		$menu_info[] = array('label' => lang('MENU_LINK_GRAVEYARD'), 'location' => 'graveyard');
		$menu_info[] = array('label' => lang('MENU_LINK_SEARCH'), 'location' => 'search');
		$menu_info[] = array('label' => lang('MENU_LINK_NEWS'), 'location' => 'news');
		if (CLAN_ENABLE)
		{
			$menu_info[] = array('label' => lang('MENU_LINK_CONTACTS'), 'location' => 'contacts');
			$menu_info[] = array('label' => lang('MENU_LINK_CLANSTATS'), 'location' => 'clanstats');
		}
		if (defined('URL_FORUMS'))
			$menu_info[] = array('label' => lang('MENU_LINK_FORUMS'), 'url' => URL_FORUMS, 'extra' => 'rel="external"');

		$menu_turns = array();
		$menu_turns[] = array('label' => lang('MENU_LINK_FARM'), 'location' => 'farm');
		$menu_turns[] = array('label' => lang('MENU_LINK_CASH'), 'location' => 'cash');
		$menu_turns[] = array('label' => lang('MENU_LINK_LAND'), 'location' => 'land');
		$menu_turns[] = array('label' => lang('MENU_LINK_BUILD'), 'location' => 'build');

		$menu_money = array();
		$menu_money[] = array('label' => lang('MENU_LINK_PVTMARKETBUY'), 'location' => 'pvtmarketbuy');
		$menu_money[] = array('label' => lang('MENU_LINK_PUBMARKETBUY'), 'location' => 'pubmarketbuy');
		$menu_money[] = array('label' => lang('MENU_LINK_BANK'), 'location' => 'bank');
		$menu_money[] = array('label' => lang('MENU_LINK_LOTTERY'), 'location' => 'lottery');

		$menu_foreign = array();
		if (AID_ENABLE)
			$menu_foreign[] = array('label' => lang('MENU_LINK_AID'), 'location' => 'aid');
		if (CLAN_ENABLE)
		{
			$menu_foreign[] = array('label' => lang('MENU_LINK_CLAN'), 'location' => 'clan');
			if ($this->empire->c_id != 0)
				$menu_foreign[] = array('label' => lang('MENU_LINK_CLANFORUM'), 'location' => 'clanforum');
		}
		$menu_foreign[] = array('label' => lang('MENU_LINK_MILITARY'), 'location' => 'military');
		$menu_foreign[] = array('label' => lang('MENU_LINK_MAGIC'), 'location' => 'magic');

		$menu_manage = array();
		$menu_manage[] = array('label' => lang('MENU_LINK_MANAGE_EMPIRE'), 'location' => 'manage/empire');
		if ((CLAN_ENABLE) && ($this->empire->c_id != 0))
		$menu_manage[] = array('label' => lang('MENU_LINK_MANAGE_CLAN'), 'location' => 'manage/clan');
		$menu_manage[] = array('label' => lang('MENU_LINK_MANAGE_USER'), 'location' => 'manage/user');
		$menu_manage[] = array('label' => lang('MENU_LINK_DELETE'), 'location' => 'delete');

		$menu_admin = array();
		$menu_admin[] = array('label' => lang('MENU_LINK_ADMIN_USERS'), 'location' => 'admin/users');
		$menu_admin[] = array('label' => lang('MENU_LINK_ADMIN_EMPIRES'), 'location' => 'admin/empires');
		if (CLAN_ENABLE)
			$menu_admin[] = array('label' => lang('MENU_LINK_ADMIN_CLANS'), 'location' => 'admin/clans');
		$menu_admin[] = array('label' => lang('MENU_LINK_ADMIN_MARKET'), 'location' => 'admin/market');
		$menu_admin[] = array('label' => lang('MENU_LINK_ADMIN_MESSAGES'), 'location' => 'admin/messages');
		$menu_admin[] = array('label' => lang('MENU_LINK_ADMIN_HISTORY'), 'location' => 'admin/history');
		$menu_admin[] = array('label' => lang('MENU_LINK_ADMIN_ROUND'), 'location' => 'admin/round');
		$menu_admin[] = array('label' => lang('MENU_LINK_ADMIN_LOG'), 'location' => 'admin/log');
		$menu_admin[] = array('label' => lang('MENU_LINK_ADMIN_PERMISSIONS'), 'location' => 'admin/permissions');
		$menu_admin[] = array('label' => lang('MENU_LINK_ADMIN_EMPEDIT'), 'location' => 'admin/empedit');

		$menu_moderator = array();
		$menu_moderator[] = array('label' => lang('MENU_LINK_ADMIN_EMPIRES'), 'location' => 'admin/empires');
		$menu_moderator[] = array('label' => lang('MENU_LINK_ADMIN_MESSAGES'), 'location' => 'admin/messages');
		$menu_moderator[] = array('label' => lang('MENU_LINK_ADMIN_HISTORY'), 'location' => 'admin/history');
		$menu_moderator[] = array('label' => lang('MENU_LINK_ADMIN_LOG'), 'location' => 'admin/log');

		$menus = array();
		$menus[] = array('label' => lang('MENU_HEADER_INFO'), 'submenu' => $menu_info);
		$menus[] = array('label' => lang('MENU_HEADER_TURNS'), 'submenu' => $menu_turns);
		$menus[] = array('label' => lang('MENU_HEADER_MONEY'), 'submenu' => $menu_money);
		$menus[] = array('label' => lang('MENU_HEADER_FOREIGN'), 'submenu' => $menu_foreign);
		$menus[] = array('label' => lang('MENU_HEADER_MANAGE'), 'submenu' => $menu_manage);
		if ($this->user->u_flags & UFLAG_ADMIN)
			$menus[] = array('label' => lang('MENU_HEADER_ADMIN'), 'submenu' => $menu_admin);
		elseif ($this->user->u_flags & UFLAG_MOD)
			$menus[] = array('label' => lang('MENU_HEADER_MODERATOR'), 'submenu' => $menu_moderator);
		$menus[] = array('label' => lang('MENU_LINK_LOGOUT'), 'location' => 'logout', 'extra' => 'id="logout"');
?>
<h2 class="ac"><a href="?location=main"><?php echo lang('MENU_HEADER_TOP'); ?></a></h2>
<div class="menu"><?php echo submenu($menus); ?></div>
<?php
	}

	// Print a banner image or display a "Collect Bonus Turns" button if appropriate
	private function printBanner ()
	{
		global $banners, $page;
		if (count($banners) > 0)
		{
			$id = mt_rand(0, count($banners) - 1);
?>
<form method="post" action="?location=banner" id="bannerform">
<div>
<input type="hidden" name="action" value="click" />
<input type="hidden" name="banner_id" value="<?php echo $id; ?>" />
<input type="image" name="banner_img" src="<?php echo $banners[$id]['image']; ?>" alt="<?php echo $banners[$id]['label']; ?>" style="width:<?php echo $banners[$id]['width']; ?>px;height:<?php echo $banners[$id]['height']; ?>px;border:0" />
</div>
<script type="text/javascript">
//<![CDATA[
document.getElementById('bannerform').target = '_blank';
//]]>
</script>
</form>
<?php
		}
		else
		{
			global $emp1;
			if (!BONUS_TURNS)
				return;	// bonus turns not enabled
			if (!ROUND_STARTED)
				return;	// game not started, no turns to collect
			if (!isset($emp1))	// No empire loaded?
				return;	// Should be impossible, but just in case...
			if ($emp1->e_vacation > 0)
				return;	// on vacation? not eligible
			if ($emp1->e_flags & EFLAG_DISABLE)
				return;	// disabled? not eligible
			if ($emp1->effects->m_freeturns)
				return;	// already got free turns
?>
<form method="post" action="?location=banner">
<div>
<input type="hidden" name="action" value="bonus" />
<input type="hidden" name="bonus_return" value="<?php echo $page; ?>" />
<input type="submit" value="<?php echo lang('HTML_BONUS_SUBMIT'); ?>" />
</div>
</form>
<?php
		}
	}
}

// Generates HTML for pages accessed while not logged in - no menubar/banner in the header, and include a "Return to Login" link in the footer
class prom_html_compact extends prom_html
{
	public function begin ($title)
	{
		$this->starttime = microtime(TRUE);

		Header("Cache-Control: no-cache");
		Header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
		if (isset($_SERVER["HTTP_ACCEPT"]) && stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml"))
		{
			Header("Content-Type: application/xhtml+xml; charset=utf-8");
			echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		}
		else	Header("Content-Type: text/html; charset=utf-8");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?php echo lang('LANG_CODE'); ?>" xml:lang="<?php echo lang('LANG_CODE'); ?>" dir="<?php echo lang('LANG_DIR'); ?>">
<head>
<title><?php echo lang('HTML_TITLE', $title); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="stylesheet" type="text/css" href="css/<?php echo $this->getstyle(); ?>" />
<?php
		$this->addStyles();
		$this->addScripts();
?>
</head>
<body>
<div class="ac">
<?php
	}

	public function end ()
	{
		global $db, $page;
		$dur = microtime(TRUE) - $this->starttime;
?>
<br /><?php
		echo lang('HTML_FOOTER', GAME_VERSION);
		if ($page != 'credits')
		{
?>
<br /><a href="?location=credits"><?php echo lang('HTML_LINK_CREDITS'); ?></a>
<?php
		}
		if ($page != 'login')
		{
?>
<br /><a href="?location=login"><?php echo lang('HTML_LINK_LOGIN'); ?></a>
<?php
		}
		if (DEBUG_FOOTER)
			echo '<br /><br />'.lang('HTML_DEBUG_FOOTER', round($dur * 1000, 1), number(memory_get_usage()), number(memory_get_peak_usage()), $db ? $db->getQueryCount() : 0);
		else	echo '<!-- '. $dur .', '. memory_get_usage() .'/'. memory_get_peak_usage() .', '. ($db ? $db->getQueryCount() : 0) .' -->';
?>
</div>
</body>
</html>
<?php
	}
}
?>
