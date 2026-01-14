<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Doctrine\ORM\EntityManagerInterface;

class WeekExtension extends AbstractExtension
{
	private $em;

	public function __construct(EntityManagerInterface $em)
	{
		$this->em = $em;
	}

	public function getFilters()
	{
		return array(
			new TwigFilter('dayTrans', array($this, 'getDayTranslate')),
			new TwigFilter('monthTrans', array($this, 'getMonthTranslate')),
			new TwigFilter('detailedDate', array($this, 'getDetailedDate')),
		);
	}	

	public function getDayTranslate($dayEn)
	{
		$days = array(
			   'Monday' => 'Lundi', 
			  'Tuesday' => 'Mardi', 
			'Wednesday' => 'Mercredi', 
			 'Thursday' => 'Jeudi', 
			   'Friday' => 'Vendredi', 
			 'Saturday' => 'Samedi', 
			   'Sunday' => 'Dimanche'
			);

		return $days[$dayEn];
	}

	public function getMonthTranslate($monthEn)
	{
		$months = array(
			'January'  => 'Janvier',                
			'February' => 'Février',      
			'March'    => 'Mars',  
			'April'    => 'Avril',
			'May'      => 'Mai',
			'June'     => 'Juin',
			'July'     => 'Juillet',
			'August'   => 'Août',
		   'September' => 'Septembre',
			 'October' => 'Octobre',
			'November' => 'Novembre',
		    'December' => 'Décembre'
		);

		return $months[$monthEn];
	}

	public function getDetailedDate($dateStr)
	{
		$date = new \DateTime($dateStr);
		$day = $this->getDayTranslate($date->format('l'));
		$number = $date->format('d');
		$month = $this->getMonthTranslate($date->format('F'));
		$year = $date->format('Y');

		return $day. ' '.$number.' '.$month.' '.$year;
	}
}