<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: spy.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

class prom_spell_spy extends prom_spell implements prom_spelltype_enemy, prom_spelltype_spy
{
	public function cost_enemy ()
	{
		return ceil(1.00 * $this->base_cost());
	}
	public function turns_enemy ()
	{
		return 2;
	}
	public function allow_enemy ()
	{
		return !$this->self->is_protected();
	}
	public function cast_enemy ()
	{
		if ($this->getpower_enemy() > 1)
		{
			$this->result_success(lang('SPELL_SPY_SUCCESS'));
			$this->other->printMainStats();
			addEmpireNews(EMPNEWS_MAGIC_SPY, $this->self, $this->other, SPELLRESULT_SUCCESS);
			return TRUE;
		}
		else
		{
			$wizloss = $this->getwizloss_enemy();
			$this->result_failed($wizloss);
			addEmpireNews(EMPNEWS_MAGIC_SPY, $this->self, $this->other, -$wizloss);
			return FALSE;
		}
	}
	protected function getpoints ()
	{
		return 0;
	}
}
?>
