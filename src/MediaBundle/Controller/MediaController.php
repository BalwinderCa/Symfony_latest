<?php

namespace App\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MediaController extends AbstractController
{   

    private $entityManager;

    // Inject the EntityManagerInterface into the controller
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/media', name: 'media_index')]
    public function index(): Response
    {
        return $this->render('media_bundle/media/index.html.twig');
    }
}
