<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: storm.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

class prom_spell_storm extends prom_spell implements prom_spelltype_enemy
{
	public function cost_enemy ()
	{
		return ceil(7.25 * $this->base_cost());
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
		if ($this->getpower_enemy() > 1.21)
		{
			if ($this->other->effects->m_shield)
			{
				$food = ceil($this->other->e_food * 0.0304);
				$cash = ceil($this->other->e_cash * 0.0422);
				$this->other->e_food -= $food;
				$this->other->e_cash -= $cash;
				$this->result_shielded(lang('SPELL_STORM_SHIELDED', number($food), $this->other->era->food, money($cash)));
				addEmpireNews(EMPNEWS_MAGIC_STORM, $this->self, $this->other, SPELLRESULT_SHIELDED, $food, $cash);
			}
			else
			{
				$food = ceil($this->other->e_food * 0.0912);
				$cash = ceil($this->other->e_cash * 0.1266);
				$this->other->e_food -= $food;
				$this->other->e_cash -= $cash;
				$this->result_success(lang('SPELL_STORM_SUCCESS', number($food), $this->other->era->food, money($cash)));
				addEmpireNews(EMPNEWS_MAGIC_STORM, $this->self, $this->other, SPELLRESULT_SUCCESS, $food, $cash);
			}
			$this->self->e_offsucc++;
			$this->self->e_offtotal++;
			$this->other->e_deftotal++;
			return TRUE;
		}
		else
		{
			$wizloss = $this->getwizloss_enemy();
			$this->result_failed($wizloss);
			addEmpireNews(EMPNEWS_MAGIC_STORM, $this->self, $this->other, -$wizloss);
			$this->self->e_offtotal++;
			$this->other->e_defsucc++;
			$this->other->e_deftotal++;
			return FALSE;
		}
	}
	protected function getpoints ()
	{
		return 0.3;
	}
}
?>
