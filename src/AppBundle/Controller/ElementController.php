<?php

namespace App\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;

class ElementController extends AbstractController
{   

    private $entityManager;

    // Inject the EntityManagerInterface into the controller
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/menu', name: 'app_element_menu')]
    public function menu()
    {
        return $this->render('@AppBundle/elements/menu.html.twig');
    }
}
