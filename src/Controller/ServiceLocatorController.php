<?php 
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\ServiceLocator\TalkService;

class ServiceLocatorController
{
    #[Route('/coming')]
    public function coming(TalkService $talkService)
    {
        return new Response(
            $talkService->saySomething(TalkService::CASE_FRIEND_COMING)
        );
    }

    #[Route('/leaving')]
    public function leaving(TalkService $talkService)
    {
        return new Response(
            $talkService->saySomething(TalkService::CASE_FRIEND_LEAVING)
        );
    }
}