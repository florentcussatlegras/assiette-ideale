<?php

namespace App\DataFixtures;

use App\Entity\Hour;
use App\Entity\Gender;
use App\Entity\AgeRange;
use App\Entity\WorkingType;
use App\Entity\SportingTime;
use App\Entity\AgeRangeGender;
use App\Entity\PhysicalActivity;
use App\DataFixtures\BaseFixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends BaseFixture implements FixtureGroupInterface
{
    protected function loadData(ObjectManager $manager)
    {
        $male = new Gender();
        $male->setName('Homme'); 
        $male->setLongName('Homme'); 
        $male->setAlias(Gender::MALE);
        $manager->persist($male);

        $female = new Gender();
        $female->setName('Femme'); 
        $female->setLongName('Femme'); 
        $female->setAlias(Gender::FEMALE);
        $manager->persist($female);

        $other = new Gender();
        $other->setName('Autre'); 
        $other->setLongName('Autre'); 
        $other->setAlias(Gender::OTHER);
        $manager->persist($other);

        //WORKING TYPE
        $softWorking = new WorkingType() ;
        $softWorking->setIsHard(false);
        $softWorking->setDescription('Vous n\'exercez pas un métier physique');

        $hardWorking = new WorkingType() ;
        $hardWorking->setIsHard(true);
        $hardWorking->setDescription('Vous exercez un métier physique');

        //SPORTING TIME
        $noSport = new SportingTime();
        $noSport->setDuration('NO_SPORT');
        $noSport->setDescription('Vous ne faites pas de sport');

        $less5 = new SportingTime();
        $less5->setDuration('LESS_5_H');
        $less5->setDescription('Vous pratiquez moins de 5h de sport par semaine');
        
        $more5 = new SportingTime();
        $more5->setDuration('MORE_5_H');
        $more5->setDescription('Vous pratiquez plus de 5h de sport par semaine');

        //PHYSICAL ACTIVITY - Combinaisons des sportingTime avec les workingType
        $physicalActivity = new PhysicalActivity();
        $physicalActivity->setWorkingType($softWorking);
        $physicalActivity->setSportingTime($noSport);
        $physicalActivity->setValue(1);
        $manager->persist($physicalActivity);

        $physicalActivity = new PhysicalActivity();
        $physicalActivity->setWorkingType($softWorking);
        $physicalActivity->setSportingTime($less5);
        $physicalActivity->setValue(1.1);
        $manager->persist($physicalActivity);

        $physicalActivity = new PhysicalActivity();
        $physicalActivity->setWorkingType($softWorking);
        $physicalActivity->setSportingTime($more5);
        $physicalActivity->setValue(1.2);
        $manager->persist($physicalActivity);

        $physicalActivity = new PhysicalActivity();
        $physicalActivity->setWorkingType($hardWorking);
        $physicalActivity->setSportingTime($noSport);
        $physicalActivity->setValue(1.1);
        $manager->persist($physicalActivity);

        $physicalActivity = new PhysicalActivity();
        $physicalActivity->setWorkingType($hardWorking);
        $physicalActivity->setSportingTime($less5);
        $physicalActivity->setValue(1.2);
        $manager->persist($physicalActivity);

        $physicalActivity = new PhysicalActivity();
        $physicalActivity->setWorkingType($hardWorking);
        $physicalActivity->setSportingTime($more5);
        $physicalActivity->setValue(1.3);
        $manager->persist($physicalActivity);

        //HOURS
        $hour = new Hour();
        $hour->setTitle('%s normaux');
        $hour->setAlias('NORMAL_H');
        $hour->setDetails('(9h-18h...)');
        $manager->persist($hour);
        // $admin->setHours($hour);
        // $user->setHours($hour);

        $hour = new Hour();
        $hour->setTitle('%s décalés');
        $hour->setDetails('(2*8/3*8/5*8)');
        $hour->setAlias('STAGGERED_H');
        $manager->persist($hour);

        $hour = new Hour();
        $hour->setTitle('%s mi-temps');
        $hour->setDetails('(9h-12h, 14-18h...)');
        $hour->setAlias('HALF_TIME_H');
        $manager->persist($hour);

        $hour = new Hour();
        $hour->setTitle('Aucun %s');
        $hour->setAlias('NO_H');
        $manager->persist($hour);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['apps', 'dev', 'test'];
    }
}