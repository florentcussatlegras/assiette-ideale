<?php

namespace App\Twig;

use App\Entity\Alert\Level;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class LevelAlertExtension extends AbstractExtension
{
	private $em;

	public function __construct(EntityManagerInterface $em)
	{
		$this->em = $em;
	}

	public function getFilters()
	{
		return array(
			new TwigFilter('level', array($this, 'getLevel'))
		);
	}

	public function getLevel($priority)
	{
		return $this->em->getRepository(Level::class)->findOneByPriority($priority);
	}
}