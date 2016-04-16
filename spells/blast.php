<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: blast.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

class prom_spell_blast extends prom_spell implements prom_spelltype_enemy
{
	public function cost_enemy ()
	{
		return ceil(2.50 * $this->base_cost());
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
		if ($this->getpower_enemy() > 1.15)
		{
			if ($this->other->effects->m_shield)
			{
				$this->other->e_trparm -= ceil($this->other->e_trparm * 0.01);
				$this->other->e_trplnd -= ceil($this->other->e_trplnd * 0.01);
				$this->other->e_trpfly -= ceil($this->other->e_trpfly * 0.01);
				$this->other->e_trpsea -= ceil($this->other->e_trpsea * 0.01);
				$this->other->e_trpwiz -= ceil($this->other->e_trpwiz * 0.01);
				$this->result_shielded(lang('SPELL_BLAST_SHIELDED'));
				addEmpireNews(EMPNEWS_MAGIC_BLAST, $this->self, $this->other, SPELLRESULT_SHIELDED);
			}
			else
			{
				$this->other->e_trparm -= ceil($this->other->e_trparm * 0.03);
				$this->other->e_trplnd -= ceil($this->other->e_trplnd * 0.03);
				$this->other->e_trpfly -= ceil($this->other->e_trpfly * 0.03);
				$this->other->e_trpsea -= ceil($this->other->e_trpsea * 0.03);
				$this->other->e_trpwiz -= ceil($this->other->e_trpwiz * 0.03);
				$this->result_success(lang('SPELL_BLAST_SUCCESS'));
				addEmpireNews(EMPNEWS_MAGIC_BLAST, $this->self, $this->other, SPELLRESULT_SUCCESS);
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
			addEmpireNews(EMPNEWS_MAGIC_BLAST, $this->self, $this->other, -$wizloss);
			$this->self->e_offtotal++;
			$this->other->e_defsucc++;
			$this->other->e_deftotal++;
			return FALSE;
		}
	}
	protected function getpoints ()
	{
		return 0.2;
	}
}
?>
