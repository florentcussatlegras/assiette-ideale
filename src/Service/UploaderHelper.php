<?php

namespace App\Service;

use App\Entity\Picture;
use Psr\Log\LoggerInterface;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class UploaderHelper
{
    // Dossiers des images
    const DISH = 'dish'; // images plats
    const FOOD = 'food'; // images aliments
    const USER = 'user'; // images user

    private $filesystem;
    private $slugger;
    private $requestStackContext;
    private $logger;
    private $publicAssetBaseUrl;

    public function __construct(
        SluggerInterface $slugger, 
        Filesystem $publicUploadsFilesystem, 
        RequestStackContext $requestStackContext, 
        LoggerInterface $logger, 
        string $uploadedAssetsBaseUrl)
    {
        $this->filesystem = $publicUploadsFilesystem;
        $this->slugger = $slugger;
        $this->requestStackContext = $requestStackContext;
        $this->logger = $logger;
        $this->publicAssetBaseUrl = $uploadedAssetsBaseUrl;
    }

    public function upload(File $file, string $uploadPath, ?string $existingFilename = null): string
    {
        $safename = $this->safename($file);
        $this->move($file, $uploadPath, $safename, $existingFilename);

        return $safename;
    }
    
    public function safename($file)
    {
        if($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        }else{
            $originalFilename = $file->getFilename();
        }

        return $this->slugger->slug(pathinfo($originalFilename, PATHINFO_FILENAME))
                                . '-' . uniqid() . '.' . $file->guessExtension();
    }

    public function move($file, string $uploadPath, string $safename, $existingFilename)
    {
        $stream = fopen($file->getPathname(), 'r');

        $result = $this->filesystem->writeStream(
            $uploadPath.'/'.$safename,
            $stream
        );

        if(is_resource($stream)) {
            fclose($stream);
        }

        if($existingFilename) {
            try {
                $result = $this->filesystem->delete($uploadPath.'/'.$existingFilename);

                if($result === false) {
                    throw new \Exception(sprintf('Could not delete old uploaded file "%s"', $existingFilename));
                }
            }catch(FileNotFoundException $e) {
                $this->logger->alert(sprintf('Old uploaded file "%s" was missing when trying to delete', $existingFilename));
            }
        }
    }

    public function getPublicPath(?string $path): ?string
    {
        return $this->requestStackContext
            ->getBasePath().$this->publicAssetBaseUrl.'/'.$path;
    }
}