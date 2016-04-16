<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: guide.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'GUIDE_TITLE';

page_header();

$_era = $emp1->e_era;
$adminflag = $user1->u_flags;

require_once(PROM_BASEDIR .'includes/guide.php');

page_footer();
?>
