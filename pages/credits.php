<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: credits.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die('Access denied');

$html = new prom_html_compact();
$html->begin('CREDITS_TITLE');
// If you wish to customize QM Promisance v4, insert a new line at the top of these credits
// and reword the previous top line to be consistent with the others below it
// All previous development history must remain intact and unmodified
?>
<h2><?php echo lang('CREDITS_HEADER'); ?></h2>
QM Promisance version 4 code written by <a href="mailto:quietust@qmtpro.com">Quietust</a><br />
Based on QM Promisance version 3, written by <a href="mailto:quietust@qmtpro.com">Quietust</a> and <a href="mailto:morvandium@qmtpro.com">Morvandium</a><br />
Based on code from EZClan Promisance v2.6, by Tom 'Inferno' Finnell and Jordan 'Mambo' Heine<br />
Based on the original <a href="http://promisance.sourceforge.net/">Promisance</a>, by the late Paul C. Purgett<br />
<hr />
Alpha/Beta Testers for QM Promisance v3.0:<br />
Devlyn, General Galaf, Lady Gwendolyn, Archbishop Hetchman, The High Priest, Leaping Frogs, Leaijrn, Starion, Swest, Dark Templar, Thorin, Thrustaevis, Woody, zEro, and many others.
<hr />
Alpha/Beta Testers for QM Promisance v4.0:<br />
Amnay, Awayboys, Ferret, Laios, Lord Corwin, Raven, Zion Zero, and many others.
<hr />
<?php
$html->end();
?>
