<?php

namespace App\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class InstanceOfExtension extends AbstractExtension
{
	private $em;

	public function __construct(EntityManagerInterface $em)
	{
		$this->em = $em;
	}
	
	public function getFunctions()
	{
		return [
			new TwigFunction('instanceOf', [$this, 'getInstanceOf']),
			new TwigFunction('object', [$this, 'getObject']),
			new TwigFunction('class', [$this, 'getClass'])
		];
	}

	public function getClass($object)
	{
		return get_class($object);
	}

	public function getInstanceOf($object, $entity)
	{
		return $object instanceof $entity;
	}

	public function getObject($id, $entity)
	{
		if (null === $result = $this->em->getRepository($entity)->findOneById($id))
			return null;

		return $result;
	}
}