<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: bank.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'BANK_TITLE';

page_header(); ?>

<br/><img src="/images/bank.jpg" style="max-width: 550px;"/>
<br/>

<?php

if (ROUND_FINISHED)
	unavailable(lang('BANK_UNAVAILABLE_END'));
if (!ROUND_STARTED)
	unavailable(lang('BANK_UNAVAILABLE_START'));

$size = $emp1->calcSizeBonus();
$loanrate = BANK_LOANRATE + $size;
$savrate = BANK_SAVERATE - $size;
$maxloan = $emp1->e_networth * 50;
$maxsave = $emp1->e_networth * 100;

$recalc = false;

if ($action == 'borrow') do
{
	if (!isFormPost())
		break;
	if (ROUND_CLOSING)
	{
		notice(lang('BANK_BORROW_TOO_LATE'));
		break;
	}
	$amount = fixInputNum(getFormVar('bank_amount'));
	if ($amount == 0)
		break;
	if ($amount + $emp1->e_loan > $maxloan)
	{
		notice(lang('BANK_BORROW_TOO_MUCH'));
		break;
	}
	$emp1->e_cash += $amount;
	$emp1->e_loan += $amount;
	notice(lang('BANK_BORROW_COMPLETE', money($amount)));
	logevent(varlist(array('amount'), get_defined_vars()));
	$recalc = true;
} while (0);
if ($action == 'repay') do
{
	if (!isFormPost())
		break;
	$amount = fixInputNum(getFormVar('bank_amount'));
	// if trying to repay more than what you owe, just repay what's owed - no need to complain.
	if ($amount > $emp1->e_loan)
		$amount = $emp1->e_loan;
	if ($amount == 0)
		break;
	if ($amount > $emp1->e_cash)
	{
		notice(lang('BANK_REPAY_NOT_ENOUGH'));
		break;
	}
	$emp1->e_cash -= $amount;
	$emp1->e_loan -= $amount;
	notice(lang('BANK_REPAY_COMPLETE', money($amount)));
	logevent(varlist(array('amount'), get_defined_vars()));
	$recalc = true;
} while (0);
if ($action == 'deposit') do
{
	if (!isFormPost())
		break;
	$amount = fixInputNum(getFormVar('bank_amount'));
	if ($amount == 0)
		break;
	if ($amount > $emp1->e_cash)
	{
		notice(lang('BANK_DEPOSIT_NOT_ENOUGH'));
		break;
	}
	if ($amount + $emp1->e_bank > $maxsave)
	{
		notice(lang('BANK_DEPOSIT_TOO_MUCH'));
		break;
	}
	$emp1->e_cash -= $amount;
	$emp1->e_bank += $amount;
	notice(lang('BANK_DEPOSIT_COMPLETE', money($amount)));
	logevent(varlist(array('amount'), get_defined_vars()));
	$recalc = true;
} while (0);
if ($action == 'withdraw') do
{
	if (!isFormPost())
		break;
	$amount = fixInputNum(getFormVar('bank_amount'));
	// if trying to withdraw more than what's in savings, just withdraw everything.
	if ($amount > $emp1->e_bank)
		$amount = $emp1->e_bank;
	if ($amount == 0)
		break;
	$emp1->e_cash += $amount;
	$emp1->e_bank -= $amount;
	notice(lang('BANK_WITHDRAW_COMPLETE', money($amount)));
	logevent(varlist(array('amount'), get_defined_vars()));
	$recalc = true;
} while (0);
notices();

if ($recalc)
{
	// if any action was performed, recalculate all of these values
	$size = $emp1->calcSizeBonus();
	$loanrate = BANK_LOANRATE + $size;
	$savrate = BANK_SAVERATE - $size;
	$maxloan = $emp1->e_networth * 50;
	$maxsave = $emp1->e_networth * 100;
}
?>
<table>
<tr class="inputtable">
    <th colspan="2"><?php echo lang('BANK_HEADER_SAVINGS'); ?></th>
    <td style="width:10%" rowspan="4"></td>
    <th colspan="2"><?php echo lang('BANK_HEADER_LOAN'); ?></th></tr>
<tr><th class="al"><?php echo lang('BANK_LABEL_SAVINGS_INTEREST'); ?></th>
    <td class="ar"><?php echo percent($savrate, 3); ?></td>
    <th class="al"><?php echo lang('BANK_LABEL_LOAN_INTEREST'); ?></th>
    <td class="ar"><?php echo percent($loanrate, 3); ?></td></tr>
<tr><th class="al"><?php echo lang('BANK_LABEL_MAX_SAVINGS'); ?></th>
    <td class="ar"><?php echo money($maxsave); ?></td>
    <th class="al"><?php echo lang('BANK_LABEL_MAX_LOAN'); ?></th>
    <td class="ar"><?php echo money($maxloan); ?></td></tr>
<tr><th class="al"><?php echo lang('BANK_LABEL_CUR_SAVINGS'); ?></th>
    <td class="ar"><?php echo money($emp1->e_bank); ?></td>
    <th class="al"><?php echo lang('BANK_LABEL_CUR_LOAN'); ?></th>
    <td class="ar"><?php echo money($emp1->e_loan); ?></td></tr>
<tr><td colspan="5" class="ac"><?php echo lang('BANK_INTEREST_DESCRIPTION'); ?></td></tr>
</table>
<?php
if ($emp1->is_protected())
	echo '<b>'. lang('BANK_SAVINGS_INTEREST_PROTECT') .'</b><br />';
?>
<br />
<table class="inputtable">
<?php
if (ROUND_CLOSING)
{
?>
<tr><td colspan="3"><?php echo lang('BANK_LOAN_UNAVAILABLE'); ?></td></tr>
<?php
}
else
{
?>
<tr><th><?php echo lang('BANK_LABEL_BORROW'); ?></th>
    <td><form method="post" action="?location=bank"><div><input type="text" name="bank_amount" value="<?php echo money(0); ?>" size="9" /> <input type="hidden" name="action" value="borrow" /><input type="submit" value="<?php echo lang('BANK_BORROW_SUBMIT'); ?>" /></div></form></td></tr>
<?php
}
?>
<tr><th><?php echo lang('BANK_LABEL_REPAY'); ?></th>
    <td><form method="post" action="?location=bank"><div><input type="text" name="bank_amount" value="<?php echo money(min($emp1->e_loan, $emp1->e_cash)); ?>" size="9" /> <input type="hidden" name="action" value="repay" /><input type="submit" value="<?php echo lang('BANK_REPAY_SUBMIT'); ?>" /></div></form></td></tr>
<tr><th><?php echo lang('BANK_LABEL_DEPOSIT'); ?></th>
    <td><form method="post" action="?location=bank"><div><input type="text" name="bank_amount" value="<?php echo money(0); ?>" size="9" /> <input type="hidden" name="action" value="deposit" /><input type="submit" value="<?php echo lang('BANK_DEPOSIT_SUBMIT'); ?>" /></div></form></td></tr>
<tr><th><?php echo lang('BANK_LABEL_WITHDRAW'); ?></th>
    <td><form method="post" action="?location=bank"><div><input type="text" name="bank_amount" value="<?php echo money($emp1->e_bank); ?>" size="9" /> <input type="hidden" name="action" value="withdraw" /><input type="submit" value="<?php echo lang('BANK_WITHDRAW_SUBMIT'); ?>" /></div></form></td></tr>
</table>
<?php
page_footer();
?>
