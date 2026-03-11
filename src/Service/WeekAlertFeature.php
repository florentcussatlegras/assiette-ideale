<?php

namespace App\Service;

use App\Entity\Alert\LevelAlert;
use App\Service\AlertFeature;

class WeekAlertFeature
{
	public function get_lundi_vendredi_from_week($startingDate = null) 
	{
		$date = null === $startingDate ? new \Datetime() : $date = new \DateTime($startingDate);
		$week = $date->format('W');
		$year = $date->format('Y');

		$firstDayInYear = date("N", mktime(0, 0, 0, 1, 1, $year));
		
		if ($firstDayInYear<5)
			$shift = -($firstDayInYear-1) * 86400;
		else
			$shift = (8-$firstDayInYear) * 86400;
		
		if ($week>1) $weekInSeconds=($week-1) * 604800; else $weekInSeconds=0;
		
		for($i=1; $i<=7; $i++)
		{
			$jours[] = array(
				'l' => date('l', mktime(0,0,0,1,$i,$year)+$weekInSeconds+$shift),
				'Y-m-d' => date('Y-m-d', mktime(0,0,0,1,$i,$year)+$weekInSeconds+$shift)
			);
		}

		return $jours;

	}

	public function getDaysForWeekMenu($startingDate)
	{
		foreach ($this->get_lundi_vendredi_from_week($startingDate) as $dateOfDay) {
			$days[$dateOfDay['Y-m-d']] = $dateOfDay['l'];
		}

		return $days;
	}

	public function getStartingDayOfWeek(String $date)
	{
		return date("Y-m-d", strtotime('monday this week', strtotime($date)));
	}
}