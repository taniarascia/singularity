<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: fight.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

class prom_spell_fight extends prom_spell implements prom_spelltype_enemy
{
	public function cost_enemy ()
	{
		return ceil(22.50 * $this->base_cost());
	}
	public function turns_enemy ()
	{
		return 2;
	}
	public function allow_enemy ()
	{
		return !$this->self->is_protected();
	}
	protected function destroy_buildings ($type, $pcloss)
	{
		$pcloss /= 3;	// 33% compared to normal attacks
		$loss = 0;
		if ($this->other->getData($type) > 0)
			$loss = mt_rand(1, ceil($this->other->getData($type) * $pcloss + 2));
		if ($loss > $this->other->getData($type))
			$loss = $this->other->getData($type);
	
		$this->other->subData($type, $loss);
		return $loss;
	}
	public function cast_enemy ()
	{
		if ($this->getpower_self() < 50)
		{
			$wizloss = $this->getwizloss_enemy();
			$this->result_failed($wizloss);
			addEmpireNews(EMPNEWS_MAGIC_FIGHT, $this->self, $this->other, -$wizloss);
			return FALSE;
		}
		$this->self->e_offtotal++;
		$this->other->e_deftotal++;
		// This spell does not use result_success, since it only gives points when you break their defense
		echo lang('SPELL_FIGHT_ATTACKING', $this->self->era->trpwiz, $this->other->era->trpwiz, $this->other) .'<br />';
		if ($this->getpower_enemy() > 2.2)
		{
			$uloss = mt_rand(0, round($this->self->e_trpwiz * 0.05 + 1));
			$eloss = mt_rand(0, round($this->other->e_trpwiz * 0.07 + 1));

			if ($uloss > $this->self->e_trpwiz)
				$uloss = $this->self->e_trpwiz;
			if ($eloss > 50 * $uloss)
				$eloss = mt_rand(0, 50 * $uloss + 1);	// to weaken MUF suiciders
			if ($eloss > $this->other->e_trpwiz)
				$eloss = $this->other->e_trpwiz;

			$this->self->e_trpwiz -= $uloss;
			$this->other->e_trpwiz -= $eloss;

			$bldloss = 0;
			$bldloss += $this->destroy_buildings('e_bldcash',  0.05);
			$bldloss += $this->destroy_buildings('e_bldpop',   0.07);
			$bldloss += $this->destroy_buildings('e_bldtrp',   0.07);
			$bldloss += $this->destroy_buildings('e_bldcost',  0.07);
			$bldloss += $this->destroy_buildings('e_bldfood',  0.08);
			$bldloss += $this->destroy_buildings('e_bldwiz',   0.07);
			$bldloss += $this->destroy_buildings('e_blddef',   0.11);
			$bldloss += $this->destroy_buildings('e_freeland', 0.10);
			$this->other->e_land -= $bldloss;
			$this->self->e_land += $bldloss;
			$this->self->e_freeland += $bldloss;

			if (DROP_DELAY)
				$this->self->effects->m_droptime = DROP_DELAY;
			echo lang('SPELL_FIGHT_SUCCESS_HEADER', $this->other, $this->self->era->trpwiz, number($bldloss));
			if (SCORE_ENABLE)
			{
				$points = ceil($this->self->findScorePoints($this->other) * $this->getpoints());
				$this->self->e_score += $points;
				$this->other->e_score -= 1;
				echo ' '. plural($points, 'COMMON_POINTS_GAIN_SINGLE', 'COMMON_POINTS_GAIN_PLURAL');
			}
			echo '<br />';
			echo lang('SPELL_FIGHT_SUCCESS_LOSSES', $uloss, $this->self->era->trpwiz, $eloss, $this->other->era->trpwiz) .'<br />';
			$killed = 0;
			if ($this->other->e_land == 0)
			{
				if (!($this->other->e_flags & EFLAG_DELETE) || (CUR_TIME < $this->other->e_idle + 60*30))
				{			// if killed within 30 minutes of deletion, still give credit
					echo '<span class="cgood">'. lang('MILITARY_SUCCESS_KILLED', $this->other) .'</span>';
					$this->self->e_kills++;
					$this->other->e_killedby = $this->self->e_id;
					if (CLAN_ENABLE)
						$this->other->e_killclan = $this->self->c_id;
					if (SCORE_ENABLE)
					{
						$points = max(round($this->other->e_score / 5), 100);
						$this->self->e_score += $points;
						echo ' '. plural($points, 'COMMON_POINTS_GAIN_SINGLE', 'COMMON_POINTS_GAIN_PLURAL');
					}
					echo '<br />';
					$killed = 1;
				}
				else	echo '<span class="cgood">'. lang('MILITARY_SUCCESS_KILLED_LATE', $this->other) .'</span>';
			}
			addEmpireNews(EMPNEWS_MAGIC_FIGHT, $this->self, $this->other, $bldloss, $eloss, $uloss);
			if ($killed)
				addEmpireNews(EMPNEWS_MILITARY_KILL, $this->self, $this->other, 0);
			$this->self->e_offsucc++;
			return TRUE;
		}
		else
		{
			$uloss = mt_rand(0, round($this->self->e_trpwiz * 0.08 + 1));
			$eloss = mt_rand(0, round($this->other->e_trpwiz * 0.04 + 1));

			if ($uloss > $this->self->e_trpwiz)
				$uloss = $this->self->e_trpwiz;
			if ($eloss > 50 * $uloss)
				$eloss = mt_rand(0, 50 * $uloss + 1);	// to weaken MUF suiciders
			if ($eloss > $this->other->e_trpwiz)
				$eloss = $this->other->e_trpwiz;

			$this->self->e_trpwiz -= $uloss;
			$this->other->e_trpwiz -= $eloss;

			echo lang('SPELL_FIGHT_BLOCKED_HEADER', $this->other, $this->other->era->trpwiz);
			if (SCORE_ENABLE)
			{
				$points = ceil($this->self->findScorePoints($this->other) * $this->getpoints() / 3);
				$this->self->e_score -= $points;
				$this->other->e_score += 1;
				echo ' '. plural($points, 'COMMON_POINTS_LOSE_SINGLE', 'COMMON_POINTS_LOSE_PLURAL');
			}
			echo '<br />';
			echo lang('SPELL_FIGHT_BLOCKED_LOSSES', $uloss, $this->self->era->trpwiz, $eloss, $this->other->era->trpwiz) .'<br />';
			addEmpireNews(EMPNEWS_MAGIC_FIGHT, $this->self, $this->other, SPELLRESULT_NOEFFECT, $eloss, $uloss);
			$this->other->e_defsucc++;
			return FALSE;	// even though the cast was successful, the battle failed
		}
	}
	protected function getpoints ()
	{
		return 1.0;
	}
}
?>
