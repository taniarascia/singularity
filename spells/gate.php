<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: gate.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

class prom_spell_gate extends prom_spell implements prom_spelltype_self
{
	public function cost_self ()
	{
		return ceil(20.00 * $this->base_cost());
	}
	public function turns_self ()
	{
		return 2;
	}
	public function allow_self ()
	{
		return TRUE;
	}
	public function cast_self ()
	{
		if ($this->getpower_self() >= 75)
		{
			if ($this->self->effects->m_gate)
			{
				if ($this->self->effects->m_gate < 3600*9)
				{
					$this->result_success(lang('SPELL_GATE_RENEW'));
					$this->self->effects->m_gate = 3600*12;
				}
				else
				{
					$this->result_success(lang('SPELL_GATE_EXTEND'));
					$this->self->effects->m_gate += 3600*3;
				}
			}
			else
			{
				$this->result_success(lang('SPELL_GATE_CREATE'));
				$this->self->effects->m_gate = 3600*12;
			}
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
