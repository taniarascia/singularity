<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: bank.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'bank', 'g_bank', 'The World Bank');
function g_bank ()
{
	global $era;
?>
<h2>The World Bank</h2>
<p>The world bank provides a place for empires both to store their excess funds and to take out loans during emergencies.</p>
<p>The maximum size of an empire's savings account and the most it can loan at once is determined mainly by the empire's networth.</p>
<p>Savings account interest rates begin at <?php echo percent(BANK_SAVERATE); ?> and gradually decrease as your empire grows larger and begins to deposit more of its funds.</p>
<p>Loan interest rates begin at <?php echo percent(BANK_LOANRATE); ?> and gradually increase as your empire grows larger and becomes more easily able to pay off loans.</p>
<p>If, while spending turns, your empire manages to run out of money, a loan will automatically be taken out for the amount you need, and the Bank will respect your state of emergency by allowing you to temporarily exceed your usual loan size limit by 100%.</p>
<p>During the final week of a round, you will not be allowed to take out any additional loans.</p>
<?php
}
?>
