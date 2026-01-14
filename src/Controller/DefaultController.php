<?php

namespace App\Controller;

use App\Entity\Food;
use App\Entity\User;
use App\Entity\Order;
use App\Entity\Spice;
use App\Entity\AgeRange;
use App\Entity\TextAlign;
use App\Form\Type\BookType;
use App\Service\CommandBus;
use App\Form\Type\SpiceType;
use TestFormSubmitEventType;
use App\Form\Type\AuthorType;
use App\Form\Type\EnergyType;
use App\Service\AlertFeature;
use App\Service\EnergyHandler;
use App\Repository\DishRepository;
use App\Repository\FoodRepository;
use App\Repository\UserRepository;
use App\Service\SwitchUserService;
use Doctrine\ORM\EntityRepository;
use App\Entity\DummiesForTest\Book;
use Cocur\Slugify\SlugifyInterface;
use App\Entity\DummiesForTest\Author;
use App\Form\Type\BookCollectionType;
use App\Form\Type\StepRecipeFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use App\Entity\DummiesForTest\BookCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\UuidType;
use Symfony\Component\Form\Extension\Core\Type\WeekType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


class DefaultController extends AbstractController
{
    #[Route('/form')]
    public function testForm(Request $request, FoodRepository $foodRepository)
    {

        $form = $this->createFormBuilder()
                    ->add('choices', ChoiceType::class, [
                        'choices' => [
                            'foo' => 'foo',
                            'bar' => 'bar'
                        ],
                        'expanded' => true,
                        'multiple' => true
                    ])
                    ->getForm();
                        
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isvalid()) {
            dd($form->getData());
        }

        return $this->renderForm('default/index.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/test-file')]
    public function testFile()
    {
        $file = new File('foo.pdf');
        // shortcut for new BinaryFileresponse?
        return $this->file($file);

        // get file /public/uploads/foo.txt ou foo.json


        // load the file from the filesystem

        // rename the downloaded file
        return $this->file($file, 'foo_rename.pdf');

        // display the file contents in the browser instead of downloading it
        return $this->file($file, 'foo_rename.pdf', ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }



    

    #[Route('/author/{id?}', name: 'app_author')]
    public function author(?Author $author, Request $request, EntityManagerInterface $manager)
    {
        dd($author);
        if (null === $author)
            $author = new Author();

            
            $formAuthor = $this->createForm(AuthorType::class, $author);
            $formAuthor->handleRequest($request);
            
        if($formAuthor->isSubmitted() && $formAuthor->isValid()) {
                
            dd($author);
            $manager->persist($author);
            $manager->flush();

            return $this->redirectToRoute('app_author', [
                'id' => $author->getId()
            ]);

        }
        // elseif($formAuthor->isSubmitted() && !$formAuthor->isValid()) {
        //     foreach($formAuthor->getErrors(true) as $formError) {
        //         dump($formError->getCause()->getConstraint());
        //     }
        //     exit;
        // }

        return $this->renderForm('test/author.html.twig', [
           'formAuthor' => $formAuthor
        ]);
    }

    // force the generation of /blog/1 instead of /blog
    #[Route('/blog/{!page}')]
    public function blog(int $page = 1)
    {
        dd($page);
    }

    #[Route('/bloge/{page?}')]
    public function bloge(?int $page)
    {
        dd($page);
    }

    #[Route('/', name: 'app_homepage')]
    public function index(AlertFeature $alertFeature, SerializerInterface $serializer, ValidatorInterface $validator, Request $request, EntityManagerInterface $manager, \App\DataFixtures\FoodFixtures $foodFixtures): Response
    {   
  
        if($this->isGranted('IS_AUTHENTICATED') && !$this->getUser()->isIsVerified()) {
            return $this->redirectToRoute('app_verify_resend_email', [
                'id' => $this->getUser()->getId(),
            ]);
        }

        // dd($alertFeature);
        // trigger_deprecation('vendor-name/package-name', '1.3', 'Your deprecation message');

        // $form = $this->createForm(EnergyType::class, [], [
        //     'validation_groups' => ['energy']
        // ]);
        
        // $form->handleRequest($request);

        // if($form->isSubmitted() && $form->isValid())
        // {
        //     $user = $this->getUser();
        //     if('kj' == $form->get('unitMeasureEnergy')->getData()) {
        //         $user->setEnergy($form->get('energy')->getData() 
        //                         * EnergyHandler::MULTIPLICATOR_CONVERT_KJ_IN_KCAL);
        //     }else{
        //         $user->setEnergy($form->get('energy')->getData());
        //     }

        //     $user->setFirstProfileFill(true);

        //     $manager->persist($user);
        //     $manager->flush();

        //     $this->addFlash('success', 'Votre énergie a bien été enregistrée.');

        //     return $this->redirectToRoute('app_dashboard_index');
        // }
        
        // $response->setPublic();
        // $response->setMaxAge(600);

        $response = new Response();

        //Cookie qui stocke les dates et heures de connexion du dernier mois
        $connectionTimes = [];
        if($request->cookies->has('connection_times')) {
            $connectionTimes = unserialize($request->cookies->get('connection_times'));
            $response->headers->clearCookie('connection_times');
        }

        $connectionTimes[] = new \DateTime();
        $cookieConnection = Cookie::create('connection_times')
                                    ->withValue(serialize($connectionTimes))
                                    ->withExpires(new \DateTime("+1 month"))
                                    // ->withDomain($this->getParameter('app.domain'))
                                    ->withSecure(true);

        $response->headers->setCookie($cookieConnection);

        // dump($request->getSession());

        $form = $this->createFormBuilder()
                        ->add('color', ColorType::class)
                        ->getForm();
        

        // $response->setContent($this->renderView('homepage/index.html.twig', [
        //     'energyForm' => $form->createView(),
        // ]));

        $response->setContent($this->renderView('homepage/index.html.twig', [
            'form' => $form->createView()
        ]));

        return $response;
    }

    #[Route(
        '/connections',
        name: 'app_connections',
        methods: ['GET'],
        condition: "context.getMethod() in ['GET']"
    )]
    public function connections(Request $request)
    {
        $cookieConnections = [];
        if($request->cookies->has('connection_times')) {
            $cookieConnections = unserialize($request->cookies->get('connection_times'));
            foreach($cookieConnections as $connection) {
                dump($connection->format('d/m/Y à H\hi'));
            }
        }
        exit;
    }

    #[Route(
        '/parameter', 
        name:'app_parameter', 
        methods: ['GET'],
        condition: "context.getMethod() in ['GET']",
    )]
    public function testParameter($aparameter)
    {
        dd($aparameter);
    }

    #[Route('/clear-cookie', name:'app_clear_cookie', methods: ['GET', 'HEAD'])]
    public function clearCookie()
    {
        $response = new Response();
        $response->headers->clearCookie('already_register_last_7_days');

        return $response;
    }

    public function esi($foo)
    {
        return $this->render('homepage/esi.html.twig', [
            'foo' => $foo
        ]);
    }


    /**
     * @Route("/profilefill", name="app_first_profile_fill_true")
     */
    public function firstProfileFillTrue(EntityManagerInterface $manager)
    {
        $user = $this->getUser();
        $user->setFirstProfileFill(true);
        $manager->persist($user);
        $manager->flush();

        return new RedirectResponse($this->generateUrl('app_homepage'));
    }

    // /**
    //  * @Route("/set_dish_user", name="app_dish_user")
    //  */
    // public function setDish(DishRepository $dishRepo, UserRepository $userRepo, EntityManagerInterface $manager)
    // {
    //     $dishes = $dishRepo->findAll();
    //     $user = $userRepo->findOneById(138);

    //     foreach($dishes as $dish) {
    //         $dish->setUser($user);
    //         dump($dish);
    //     }

    //     $manager->flush();

    //     return new Response('update dish');
    // }

    #[Route('/test-form-submit-event')]
    public function testFormSubmitEvent(Request $request)
    {
        $form = $this->createForm(TestFormSubmitEventType::class);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            dd($form);

        }

        return $this->render('default/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

}
