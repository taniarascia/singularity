<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: ungate.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

class prom_spell_ungate extends prom_spell implements prom_spelltype_self
{
	public function cost_self ()
	{
		return ceil(14.50 * $this->base_cost());
	}
	public function turns_self ()
	{
		return 2;
	}
	public function allow_self ()
	{
		// can't close a time gate you don't have open
		if ($this->self->effects->m_gate)
			return TRUE;
		else	return FALSE;
	}
	public function cast_self ()
	{
		if ($this->getpower_self() >= 80)
		{
			$this->result_success(lang('SPELL_UNGATE_SUCCESS'));
			$this->self->effects->m_gate = 0;
			return TRUE;
		}
		else
		{
			$wizloss = $this->getwizloss_self();
			$this->result_failed($wizloss);
			return FALSE;
		}
	}
	protected function getpoints ()
	{
		return 0;
	}
}
?>
