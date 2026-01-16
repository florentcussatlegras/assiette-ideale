<?php

namespace App\Controller;

use App\Service\AlertFeature;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_homepage', methods: ['GET'])]
    public function index(Request $request): Response
    {   
  
        if($this->isGranted('IS_AUTHENTICATED') && !$this->getUser()->isIsVerified()) {
            return $this->redirectToRoute('app_verify_resend_email', [
                'id' => $this->getUser()->getId(),
            ]);
        }

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
                                    ->withSecure(true);

        $response->headers->setCookie($cookieConnection);

        $response->setContent($this->render('homepage/index.html.twig'));

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

    #[Route('/clear-cookie', name:'app_clear_cookie', methods: ['GET', 'HEAD'])]
    public function clearCookie()
    {
        $response = new Response();
        $response->headers->clearCookie('already_register_last_7_days');

        return $response;
    }

    #[Route('/profilefill', name: 'app_first_profile_fill_true', methods: ['GET'])]
    public function firstProfileFillTrue(EntityManagerInterface $manager)
    {
        $user = $this->getUser();
        $user->setFirstProfileFill(true);
        $manager->persist($user);
        $manager->flush();

        return new RedirectResponse($this->generateUrl('app_homepage'));
    }
}
