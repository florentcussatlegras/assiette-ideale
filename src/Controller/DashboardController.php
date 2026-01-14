<?php

namespace App\Controller;

use App\Service\DashboardUtils;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class DashboardController extends AbstractController
{
    /**
     * @Route("/dashboard", name="app_dashboard_index")
     */
    public function index(Request $request, DashboardUtils $dashboardUtils)
    {
        // $process = new Process(['ls', '-lsa']);
        // dump($process);
        // $process->run();
        // if(!$process->isSuccessful()) {
        //     throw new ProcessFailedException($process);
        // }

        // dd($process->getOutput());

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        if($request->getSession()->has('recipe_dish')) {
            $request->getSession()->remove('recipe_dish');
            $request->getSession()->remove('recipe_foods');
        }

        if(!$this->getUser()->hasFirstFillProfile())
        {
            return $this->redirectToRoute('app_profile_edit');
        }

        // //dump($this->getUser());
        // //dump($session->all());

        return $this->render('dashboard/index.html.twig', [
            'principals' => $dashboardUtils->getPrincipal(),
            'secondary' => $dashboardUtils->getSecondary(),
        ]);
    }
}