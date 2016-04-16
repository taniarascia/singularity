<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: shield.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

class prom_spell_shield extends prom_spell implements prom_spelltype_self
{
	public function cost_self ()
	{
		return ceil(4.90 * $this->base_cost());
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
		if ($this->getpower_self() >= 15)
		{
			if ($this->self->effects->m_shield)
			{
				if ($this->self->effects->m_shield < 3600*9)
				{
					$this->result_success(lang('SPELL_SHIELD_RENEW'));
					$this->self->effects->m_shield = 3600*12;
				}
				else
				{
					$this->result_success(lang('SPELL_SHIELD_EXTEND'));
					$this->self->effects->m_shield += 3600*3;
				}
			}
			else
			{
				$this->result_success(lang('SPELL_SHIELD_CREATE'));
				$this->self->effects->m_shield = 3600*12;
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
