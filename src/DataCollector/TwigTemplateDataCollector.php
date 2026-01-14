<?php

namespace App\DataCollector;

use Twig\Environment;
use Twig\Error\LoaderError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Profiler\Profile;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;

class TwigTemplateDataCollector
{
    private $profile;
    private $twig;
    
    public function __construct(Profile $profile, Environment $twig = null)
    {
        $this->profile = $profile;
        $this->twig = $twig;
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {

        $this->data['profile'] = serialize($this->profile);
        $this->data['template_paths'] = [];

        if (null === $this->twig) {
            return;
        }

        // $templateFinder = function (Profile $profile) use (&$templateFinder) {
        //     $template = $this->twig->load($name = $profile->getName());
        //     dd($template);
        //     if ($profile->isTemplate()) {
        //         try {
        //             $template = $this->twig->load($name = $profile->getName());
        //         } catch (LoaderError $e) {
        //             $template = null;
        //         }

        //         if (null !== $template && '' !== $path = $template->getSourceContext()->getPath()) {
        //             $this->data['template_paths'][$name] = $path;
        //         }
        //     }

        //     foreach ($profile as $p) {
        //         $templateFinder($p);
        //     }
        // };
        // $templateFinder($this->profile);
    }

    public function getTemplatePaths()
    {
        return $this->data['template_paths'];
    }

    public static function getTemplate(): ?string
    {
        return 'data_collector/view_name.html.twig';
    }
}