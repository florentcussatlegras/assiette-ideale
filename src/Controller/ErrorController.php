<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class ErrorController extends AbstractController
{
    #[Route('/error1', name: 'error1')]
    public function show1(Request $request): Response
    {

        dump($request->attributes->get('exception'));
        dump($request->attributes->get('logger'));
        dd($request->attributes->all());
    }






    #[Route('/error2', name: 'error2')]
    public function show(Request $request): Response
    {
        /*
            The error listener class used by the FrameworkBundle as a listener
            of the kernel.exception event creates the request that will be dispatched to
            your controller. In addition, your controller will be passed two parameters
        */

        
        exit;

        return new Response('Error toto');
    }
}
