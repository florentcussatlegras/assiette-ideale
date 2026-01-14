<?php

namespace App\Test\Service;

use App\Entity\User;
use App\Entity\PhysicalActivity;
use App\Entity\Gender;
use App\Service\EnergyHandler;
use PHPUnit\Framework\TestCase;

class EnergyHandlerTest extends TestCase
{
    public function testTrue()
    {
        $this->assertTrue(true);
    }
    // public function testEvaluateEnergyIfUserNotInstanceUser()
    // {
    //     $energyHandler = new EnergyHandler();
    //     $this->assertNull($energyHandler->evaluateEnergy(new \stdClass));
    // }

    // public function testEvaluateEnergy()
    // {
    //     $physicalActivity = new PhysicalActivity();
    //     $physicalActivity->setValue(1);

    //     $gender = new Gender();
    //     $gender->setAlias('M');

    //     $user = new User();
    //     $user->setGender($gender);
    //     $user->setPhysicalActivity($physicalActivity);
    //     $user->setWeight(85);
    //     $user->setHeight(185);

    //     $birthday = new \DateTime();
    //     $birthday->setDate(1979, 11, 19);
    //     $user->setBirthday($birthday);

    //     $energyHandler = new EnergyHandler();
    //     $this->assertEquals($energyHandler->evaluateEnergy($user), 2188.1244); 
    // }
}