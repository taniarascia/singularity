<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: food.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

class prom_spell_food extends prom_spell implements prom_spelltype_self
{
	public function cost_self ()
	{
		return ceil(17.00 * $this->base_cost());
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
		if ($this->getpower_self() >= 30)
		{
			$food = round($this->self->e_trpwiz * ($this->self->e_health / 100) * 65 * (1 + sqrt($this->self->e_bldwiz / $this->self->e_land) / 2) * $this->self->getModifier('magic') / ($this->self->calcSizeBonus() * $this->self->calcSizeBonus()) / PVTM_FOOD);
			$this->self->e_food += $food;
			$this->result_success(lang('SPELL_FOOD_SUCCESS', number($food), $this->self->era->food));
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
