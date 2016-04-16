<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: prom_spell.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

abstract class prom_spell
{
	protected $self, $other;

	public function __construct($emp_1 = NULL, $emp_2 = NULL)
	{
		$this->self = $emp_1;
		$this->other = $emp_2;
	}

	// Base cost for all spells, scales as empire size increases
	protected function base_cost ()
	{	
		return ($this->self->e_land * 0.10) + 100 + ($this->self->e_bldwiz * 0.20) * $this->self->getModifier('magic') * $this->self->calcSizeBonus();
	}

	// Determine wizard power when casting spells on self
	protected function getpower_self ()
	{
		return $this->self->e_trpwiz * $this->self->getModifier('magic') / max($this->self->e_bldwiz, 1);
	}

	// Determine wizard power when casting spells on a friend
	protected function getpower_friend ()
	{
		$uratio = $this->self->e_trpwiz / (($this->self->e_land + $this->other->e_land) / 2) * $this->self->getModifier('magic');
		$eratio = max($this->other->e_trpwiz, 1) / $this->other->e_land * $this->other->getModifier('magic');
		return $uratio / $eratio;
	}

	// Determine wizard power when casting spells on an enemy
	protected function getpower_enemy ()
	{
		$uratio = $this->self->e_trpwiz / (($this->self->e_land + $this->other->e_land) / 2) * $this->self->getModifier('magic');
		$eratio = max($this->other->e_trpwiz, 1) / $this->other->e_land * 1.05 * $this->other->getModifier('magic');
		return $uratio / $eratio;
	}

	// Determine wizard loss when failing to cast a spell on self
	protected function getwizloss_self ()
	{
		$wizloss = mt_rand(ceil($this->self->e_trpwiz * 0.01), ceil($this->self->e_trpwiz * 0.05 + 1));
		if ($wizloss > $this->self->e_trpwiz)
			$wizloss = $this->self->e_trpwiz;
		return $wizloss;
	}

	// Determine wizard loss when failing to cast a spell on a friend
	protected function getwizloss_friend ()
	{
		$wizloss = mt_rand(ceil($this->self->e_trpwiz * 0.01), ceil($this->self->e_trpwiz * 0.05 + 1));
		if ($wizloss > $this->self->e_trpwiz)
			$wizloss = $this->self->e_trpwiz;
		return $wizloss;
	}

	// Determine wizard loss when failing to cast a spell on an ememy
	protected function getwizloss_enemy ()
	{
		$wizloss = mt_rand(ceil($this->self->e_trpwiz * 0.01), ceil($this->self->e_trpwiz * 0.05 + 1));
		if ($wizloss > $this->self->e_trpwiz)
			$wizloss = $this->self->e_trpwiz;
		return $wizloss;
	}

	// Scale number of points gained when casting a spell on another empire, friend or enemy (since one spell will likely never work on both)
	protected function getpoints ()
	{
		return 0;
	}

	// Spell was cast successfully - print message
	protected function result_success ($msg)
	{
		echo lang('SPELL_GENERIC_SUCCESS');
		if (SCORE_ENABLE)
		{
			$points = ceil($this->self->findScorePoints($this->other) * $this->getpoints());
			if ($points > 0)
			{
				$this->self->e_score += $points;
				$this->other->e_score -= 1;
			}
			echo ' '. plural($points, 'COMMON_POINTS_GAIN_SINGLE', 'COMMON_POINTS_GAIN_PLURAL');
		}
		echo '<br />';
		echo $msg .'<br />';
	}

	// Failed to cast spell - print message and record losses
	protected function result_failed ($loss)
	{
		echo lang('SPELL_GENERIC_FAILED');
		if (SCORE_ENABLE)
		{
			$points = ceil($this->self->findScorePoints($this->other) * $this->getpoints() / 2);
			if ($points > 0)
			{
				$this->self->e_score -= $points;
				$this->other->e_score += 1;
			}
			echo ' '. plural($points, 'COMMON_POINTS_LOSE_SINGLE', 'COMMON_POINTS_LOSE_PLURAL');
		}
		echo '<br />';
		echo lang('SPELL_GENERIC_FAILED_LOSSES', $loss, $this->self->era->trpwiz) .'<br />';
		$this->self->e_trpwiz -= $loss;
	}

	// Spell was shielded - print message
	protected function result_shielded ($msg)
	{
		echo lang('SPELL_GENERIC_SHIELDED');
		if (SCORE_ENABLE)
		{
			$points = ($this->getpoints() > 0) ? 1 : 0;
			if ($points > 0)
			{
				$this->self->e_score += 1;
				$this->other->e_score -= 1;
			}
			echo ' '. plural($points, 'COMMON_POINTS_GAIN_SINGLE', 'COMMON_POINTS_GAIN_PLURAL');
		}
		echo '<br />';
		echo $msg .'<br />';
	}
}

// Spells which can be cast on yourself
interface prom_spelltype_self
{
	public function cost_self ();
	public function turns_self ();
	public function allow_self ();
	public function cast_self ();
}

// Spells which can be cast on a friendly empire
interface prom_spelltype_friend
{
	public function cost_friend ();
	public function turns_friend ();
	public function allow_friend ();
	public function cast_friend ();
}

// Spells which can be cast on enemies
interface prom_spelltype_enemy
{
	public function cost_enemy ();
	public function turns_enemy ();
	public function allow_enemy ();
	public function cast_enemy ();
}

// Spells whose mana and turn costs are determined based on the target
// Has no effect when applied to self-cast spells
interface prom_spelltype_varycost
{
}

// Offensive spells which do not count as attacks (no health loss, no networth restrictions)
// Has no effect when applied to self-cast or friend-cast spells
interface prom_spelltype_spy
{
}
?>
