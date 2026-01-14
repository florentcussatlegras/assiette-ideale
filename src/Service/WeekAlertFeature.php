<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class WeekAlertFeature
{
	public function __construct(
			private RequestStack $requestStack, 
			private EntityManagerInterface $manager, 
			private Security $security)
	{}

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