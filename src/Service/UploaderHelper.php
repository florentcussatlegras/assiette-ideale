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

/**
 * UploaderHelper.php
 * 
 * Service d'upload et gestion des fichiers/images pour l'application.
 *
 * Fonctionnalités principales :
 *  - Centraliser la logique d'upload, renommage et suppression de fichiers.
 *  - Fournir un chemin public accessible via l'application.
 *  - Gérer les images pour différents types d'entités (plats, aliments, utilisateurs).
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class UploaderHelper
{
    // Dossiers des images
    const DISH = 'dish'; // images plats
    const FOOD = 'food'; // images aliments
    const USER = 'user'; // images utilisateurs

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
        string $uploadedAssetsBaseUrl
    ) {
        $this->filesystem = $publicUploadsFilesystem;
        $this->slugger = $slugger;
        $this->requestStackContext = $requestStackContext;
        $this->logger = $logger;
        $this->publicAssetBaseUrl = $uploadedAssetsBaseUrl;
    }

    /**
     * Upload générique d'un fichier sur un dossier spécifique.
     *
     * @param File $file
     * @param string $uploadPath
     * @param string|null $existingFilename Nom de fichier à remplacer
     * @return string Nom du fichier uploadé
     */
    public function upload(File $file, string $uploadPath, ?string $existingFilename = null): string
    {
        $safename = $this->safename($file);
        $this->move($file, $uploadPath, $safename, $existingFilename);

        return $safename;
    }

    /**
     * Génère un nom de fichier unique et "safe" pour URL.
     */
    public function safename(File $file): string
    {
        $originalFilename = $file instanceof UploadedFile
            ? $file->getClientOriginalName()
            : $file->getFilename();

        return $this->slugger->slug(pathinfo($originalFilename, PATHINFO_FILENAME))
            . '-' . uniqid() . '.' . $file->guessExtension();
    }

    /**
     * Déplace un fichier sur le filesystem et supprime l'ancien fichier si nécessaire.
     */
    public function move(File $file, string $uploadPath, string $safename, ?string $existingFilename): void
    {
        $stream = fopen($file->getPathname(), 'r');
        $this->filesystem->writeStream($uploadPath.'/'.$safename, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        if ($existingFilename) {
            try {
                $result = $this->filesystem->delete($uploadPath.'/'.$existingFilename);

                if ($result === false) {
                    throw new \Exception(sprintf('Could not delete old uploaded file "%s"', $existingFilename));
                }
            } catch (FileNotFoundException $e) {
                $this->logger->alert(sprintf('Old uploaded file "%s" was missing when trying to delete', $existingFilename));
            }
        }
    }

    /**
     * Retourne le chemin public complet d’un fichier uploadé.
     *
     * @param string|null $path
     * @return string|null
     */
    public function getPublicPath(?string $path): ?string
    {
        return $this->requestStackContext
            ->getBasePath() . $this->publicAssetBaseUrl . '/' . $path;
    }
}