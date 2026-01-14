<?php

namespace App\Service;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Security\Core\Security;

class ContactUtils
{
    private $finder;
    private $dirJson;
    private $jsonSubjectContact;

    public function __construct(Finder $finder, string $dirJson, string $jsonSubjectContact)
    {
        $this->finder = $finder;
        $this->dirJson = $dirJson;
        $this->jsonSubjectContact = $jsonSubjectContact;
    }

    public function getListSubject(): array
    {
        $objects = iterator_to_array($this->finder->in($this->dirJson)->files()->name($this->jsonSubjectContact));

        
        

        return json_decode(current($objects)->getContents(), true);
    }
}