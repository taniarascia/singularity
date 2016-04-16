<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: pguide.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$html = new prom_html_compact();
$html->begin('GUIDE_TITLE');

$races = prom_race::getNames();
$eras = prom_era::getNames();
$_era = ERA_PAST;
$adminflag = 0;

require_once(PROM_BASEDIR .'includes/guide.php');

$html->end();
?>
