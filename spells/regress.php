<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: regress.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

class prom_spell_regress extends prom_spell implements prom_spelltype_self
{
	public function cost_self ()
	{
		return ceil(47.50 * $this->base_cost());
	}
	public function turns_self ()
	{
		return 2;
	}
	public function allow_self ()
	{
		// make sure there's an era to regress to
		if (!$this->self->era->era_prev)
			return FALSE;
		// and can't regress until you've spent enough time in your current era
		if ($this->self->effects->r_newera)
			return FALSE;
		return TRUE;
	}
	public function cast_self ()
	{
		if ($this->getpower_self() >= 90)
		{
			$this->result_success(lang('SPELL_REGRESS_SUCCESS'));
			$this->self->e_era = $this->self->era->era_prev;
			$this->self->era = new prom_era($this->self->e_era);
			$this->self->effects->r_newera = TURNS_ERA;
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
