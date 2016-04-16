<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: steal.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

class prom_spell_steal extends prom_spell implements prom_spelltype_enemy
{
	public function cost_enemy ()
	{
		return ceil(25.75 * $this->base_cost());
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
		if ($this->getpower_enemy() > 1.75)
		{
			if ($this->other->effects->m_shield)
			{
				$cash = round($this->other->e_cash / 100000 * mt_rand(3000, 5000));
				$this->other->e_cash -= $cash;
				$this->self->e_cash += $cash;
				$this->result_shielded(lang('SPELL_STEAL_SHIELDED', money($cash)));
				addEmpireNews(EMPNEWS_MAGIC_STEAL, $this->self, $this->other, SPELLRESULT_SHIELDED, $cash);
			}
			else
			{
				$cash = round($this->other->e_cash / 100000 * mt_rand(10000, 15000));
				$this->other->e_cash -= $cash;
				$this->self->e_cash += $cash;
				$this->result_success(lang('SPELL_STEAL_SUCCESS', money($cash)));
				addEmpireNews(EMPNEWS_MAGIC_STEAL, $this->self, $this->other, SPELLRESULT_SUCCESS, $cash);
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
			addEmpireNews(EMPNEWS_MAGIC_STEAL, $this->self, $this->other, -$wizloss);
			$this->self->e_offtotal++;
			$this->other->e_defsucc++;
			$this->other->e_deftotal++;
			return FALSE;
		}
	}
	protected function getpoints ()
	{
		return 0.15;
	}
}
?>
