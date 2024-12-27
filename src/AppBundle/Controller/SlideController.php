<?php

namespace App\AppBundle\Controller;

use App\AppBundle\Entity\Slide;
use App\AppBundle\Forms\SlideType;
use App\MediaBundle\Entity\Media;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class SlideController extends AbstractController 
{

    private $entityManager;
    private  $params;


    // Inject the EntityManagerInterface into the controller
    public function __construct(EntityManagerInterface $entityManager,ParameterBagInterface $params)
     {
         $this->entityManager = $entityManager;
         $this->params = $params;
     }

    
    #[Route('/slides', name: 'app_slide_index')]
    public function index() {
        $em = $this->entityManager;
        $slides = $em->getRepository(Slide::class)->findBy([], ['position' => 'asc']);
        return $this->render('@AppBundle/Slide/index.html.twig', ['slides' => $slides]);
    }

    #[Route('/api/slides', name: 'app_slide_api_all')]
    public function api_all() {
        $em = $this->entityManager;
        $slides = $em->getRepository(Slide::class)->findBy([], ['position' => 'asc']);
        return $this->render('@AppBundle/Slide/api_all.html.php', ['slides' => $slides]);
    }

    #[Route('/slides/up/{id}', name: 'app_slide_up')]
    public function up(Request $request, $id) {
        $em = $this->entityManager;
        $slide = $em->getRepository(Slide::class)->find($id);
        if ($slide === null) {
            throw new NotFoundHttpException("Page not found");
        }
        if ($slide->getPosition() > 1) {
            $p = $slide->getPosition();
            $slides = $em->getRepository(Slide::class)->findAll();
            foreach ($slides as $value) {
                if ($value->getPosition() === $p - 1) {
                    $value->setPosition($p);
                }
            }
            $slide->setPosition($slide->getPosition() - 1);
            $em->flush();
        }
        return $this->redirectToRoute('app_slide_index');
    }

    #[Route('/slides/down/{id}', name: 'app_slide_down')]
    public function down(Request $request, $id) {
        $em = $this->entityManager;
        $slide = $em->getRepository(Slide::class)->find($id);
        if ($slide === null) {
            throw new NotFoundHttpException("Page not found");
        }
        $max = 0;
        $slides = $em->getRepository(Slide::class)->findBy([], ['position' => 'asc']);
        foreach ($slides as $value) {
            $max = $value->getPosition();
        }
        if ($slide->getPosition() < $max) {
            $p = $slide->getPosition();
            foreach ($slides as $value) {
                if ($value->getPosition() === $p + 1) {
                    $value->setPosition($p);
                }
            }
            $slide->setPosition($slide->getPosition() + 1);
            $em->flush();
        }
        return $this->redirectToRoute('app_slide_index');
    }

    #[Route('/slides/delete/{id}', name: 'app_slide_delete')]
    public function delete($id, Request $request) {
        $em = $this->entityManager;
        $slide = $em->getRepository(Slide::class)->find($id);
        if ($slide === null) {
            throw new NotFoundHttpException("Page not found");
        }
        $form = $this->createFormBuilder(['id' => $id])
            ->add('id', HiddenType::class)
            ->add('Yes', SubmitType::class)
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $media_old = $slide->getMedia();
            $em->remove($slide);
            $em->flush();
            if ($media_old !== null) {
                $media_old->delete($this->params->get('kernel.project_dir') . '/public/uploads');
                $em->remove($media_old);
                $em->flush();
            }
            $slides = $em->getRepository(Slide::class)->findBy([], ['position' => 'asc']);
            $p = 1;
            foreach ($slides as $value) {
                $value->setPosition($p);
                $p++;
            }
            $em->flush();
            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirectToRoute('app_slide_index');
        }
        return $this->render('@AppBundle/Slide/delete.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/slides/add', name: 'app_slide_add')]
    public function add(Request $request) 
    {
        $em = $this->entityManager;
        $slide = new Slide();
        $form = $this->createForm(SlideType::class, $slide);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) 
        {
            if ($slide->getFile() !== null) 
            {
                $media = new Media();
                $media->setFile($slide->getFile());
                $media->upload($this->params->get('kernel.project_dir') . '/public/uploads');
                $em->persist($media);
                $em->flush();
                $slide->setMedia($media);
                $slide->setTitle(base64_encode($slide->getTitle()));
                $max = 0;
                $slides = $em->getRepository(Slide::class)->findBy([], ['position' => 'asc']);
                foreach ($slides as $value) {
                    if ($value->getPosition() > $max) {
                        $max = $value->getPosition();
                    }
                }
                $slide->setPosition($max + 1);
                if ($slide->getType() === 1) {
                    $slide->setUrl(null);
                    $slide->setStatus(null);
                } elseif ($slide->getType() === 2) {
                    $slide->setCategory(null);
                    $slide->setStatus(null);
                } elseif ($slide->getType() === 3) {
                    $slide->setCategory(null);
                    $slide->setUrl(null);
                }
                $em->persist($slide);
                $em->flush();
                $this->addFlash('success', 'Operation has been done successfully');
                return $this->redirectToRoute('app_slide_index');
            } else {
                $error = new FormError("Required image file");
                $form->get('file')->addError($error);
            }
        }
        return $this->render('@AppBundle/Slide/add.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/slides/edit/{id}', name: 'app_slide_edit')]
    public function edit(Request $request, $id) 
    {
        $em = $this->entityManager;
        $slide = $em->getRepository(Slide::class)->find($id);
        
        if ($slide === null) {
            throw new NotFoundHttpException("Page not found");
        }

        if ($slide->getStatus() !== null) {
            $this->entityManager->initializeObject($slide->getStatus());
        }

        $slide->setTitle(base64_decode($slide->getTitle()));

        $form = $this->createForm(SlideType::class, $slide);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($slide->getFile() !== null) {
                $media = new Media();
                $media_old = $slide->getMedia();
                $media->setFile($slide->getFile());
                $media->upload($this->params->get('kernel.project_dir') . '/public/uploads');
                $em->persist($media);
                $em->flush();
                $slide->setMedia($media);
                $em->flush();
                $media_old->delete($this->params->get('kernel.project_dir') . '/public/uploads');
                $em->remove($media_old);
                $em->flush();
            }
            $slide->setTitle(base64_encode($slide->getTitle()));
            if ($slide->getType() === 1) {
                $slide->setUrl(null);
                $slide->setStatus(null);
            } elseif ($slide->getType() === 2) {
                $slide->setCategory(null);
                $slide->setStatus(null);
            } elseif ($slide->getType() === 3) {
                $slide->setCategory(null);
                $slide->setUrl(null);
            }
            $em->persist($slide);
            $em->flush();

            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirectToRoute('app_slide_index');
        }
        return $this->render('@AppBundle/Slide/edit.html.twig', ['form' => $form->createView()]);
    }
}
