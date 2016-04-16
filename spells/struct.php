<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: struct.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

class prom_spell_struct extends prom_spell implements prom_spelltype_enemy
{
	public function cost_enemy ()
	{
		return ceil(18.00 * $this->base_cost());
	}
	public function turns_enemy ()
	{
		return 2;
	}
	public function allow_enemy ()
	{
		return !$this->self->is_protected();
	}
	private function destroy_buildings ($type, $percent, $min)
	{
		$loss = ceil($this->other->getData($type) * $percent);
		if ($this->other->getData($type) >= $this->other->e_land / $min)
		{
			$this->other->subData($type, $loss);
			$this->other->e_freeland += $loss;
			return $loss;
		}
		else	return 0;
	}
	public function cast_enemy ()
	{
		if ($this->getpower_enemy() > 1.7)
		{
			if ($this->other->effects->m_shield)
			{
				$build = 0;
				$build += $this->destroy_buildings('e_bldcash', 0.01, 100);
				$build += $this->destroy_buildings('e_bldpop', 0.01, 100);
				$build += $this->destroy_buildings('e_bldtrp', 0.01, 100);
				$build += $this->destroy_buildings('e_bldcost', 0.01, 100);
				$build += $this->destroy_buildings('e_bldfood', 0.01, 100);
				$build += $this->destroy_buildings('e_bldwiz', 0.01, 100);
				$build += $this->destroy_buildings('e_blddef', 0.01, 150);
				if ($build > 0)
				{
					$this->result_shielded(lang('SPELL_STRUCT_SHIELDED', number($build)));
					addEmpireNews(EMPNEWS_MAGIC_STRUCT, $this->self, $this->other, SPELLRESULT_SHIELDED, $build);
					$this->self->e_offsucc++;
					$this->self->e_offtotal++;
					$this->other->e_deftotal++;
				}
				else
				{
					$this->result_shielded(lang('SPELL_STRUCT_NOEFFECT'));
					addEmpireNews(EMPNEWS_MAGIC_STRUCT, $this->self, $this->other, SPELLRESULT_NOEFFECT);
				}
			}
			else
			{
				$build = 0;
				$build += $this->destroy_buildings('e_bldcash', 0.03, 100);
				$build += $this->destroy_buildings('e_bldpop', 0.03, 100);
				$build += $this->destroy_buildings('e_bldtrp', 0.03, 100);
				$build += $this->destroy_buildings('e_bldcost', 0.03, 100);
				$build += $this->destroy_buildings('e_bldfood', 0.03, 100);
				$build += $this->destroy_buildings('e_bldwiz', 0.03, 100);
				$build += $this->destroy_buildings('e_blddef', 0.03, 150);
				if ($build > 0)
				{
					$this->result_success(lang('SPELL_STRUCT_SUCCESS', number($build)));
					addEmpireNews(EMPNEWS_MAGIC_STRUCT, $this->self, $this->other, SPELLRESULT_SUCCESS, $build);
					$this->self->e_offsucc++;
					$this->self->e_offtotal++;
					$this->other->e_deftotal++;
				}
				else
				{
					$this->result_success(lang('SPELL_STRUCT_NOEFFECT'));
					addEmpireNews(EMPNEWS_MAGIC_STRUCT, $this->self, $this->other, SPELLRESULT_NOEFFECT);
				}
			}
			return TRUE;
		}
		else
		{
			$wizloss = $this->getwizloss_enemy();
			$this->result_failed($wizloss);
			addEmpireNews(EMPNEWS_MAGIC_STRUCT, $this->self, $this->other, -$wizloss);
			$this->self->e_offtotal++;
			$this->other->e_defsucc++;
			$this->other->e_deftotal++;
			return FALSE;
		}
	}
	protected function getpoints ()
	{
		return 0.5;
	}
}
?>
