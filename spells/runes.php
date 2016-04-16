<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: runes.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

class prom_spell_runes extends prom_spell implements prom_spelltype_enemy
{
	public function cost_enemy ()
	{
		return ceil(9.50 * $this->base_cost());
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
		if ($this->getpower_enemy() > 1.3)
		{
			if ($this->other->effects->m_shield)
			{
				$rune = ceil($this->other->e_runes * 0.01);
				$this->other->e_runes -= $rune;
				$this->result_shielded(lang('SPELL_RUNES_SHIELDED', number($rune), $this->other->era->runes));
				addEmpireNews(EMPNEWS_MAGIC_RUNES, $this->self, $this->other, SPELLRESULT_SHIELDED, $rune);
			}
			else
			{
				$rune = ceil($this->other->e_runes * 0.03);
				$this->other->e_runes -= $rune;
				$this->result_success(lang('SPELL_RUNES_SUCCESS', number($rune), $this->other->era->runes));
				addEmpireNews(EMPNEWS_MAGIC_RUNES, $this->self, $this->other, SPELLRESULT_SUCCESS, $rune);
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
			addEmpireNews(EMPNEWS_MAGIC_RUNES, $this->self, $this->other, -$wizloss);
			$this->self->e_offtotal++;
			$this->other->e_defsucc++;
			$this->other->e_deftotal++;
			return FALSE;
		}
	}
	protected function getpoints ()
	{
		return 0.4;
	}
}
?>
