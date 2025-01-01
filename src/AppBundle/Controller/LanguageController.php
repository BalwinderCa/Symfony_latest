<?php 

namespace App\AppBundle\Controller;

use App\AppBundle\Entity\Language;
use App\MediaBundle\Entity\Media;
use App\AppBundle\Forms\LanguageType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class LanguageController extends AbstractController
{   

    private $entityManager;
    private  $params;

     // Inject the EntityManagerInterface into the controller
     public function __construct(EntityManagerInterface $entityManager,ParameterBagInterface $params)
     {
         $this->entityManager = $entityManager;
         $this->params = $params;
     }

    #[Route('/api/languages/{token}', name: 'api_languages_all')]
    public function api_all(Request $request, $token)
    {
        if ($token != $this->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");
        }

        $em = $this->entityManager;
        $imagineCacheManager = $this->get('liip_imagine.cache.manager');
        $languages = $em->getRepository(Language::class)->findBy(['enabled' => true], ['position' => 'asc']);
        $list = [];
        $s = [
            "id" => 0,
            "language" => "All languages",
            "image" => $imagineCacheManager->getBrowserPath("/img/global.png", 'language_thumb_api')
        ];
        $list[] = $s;

        foreach ($languages as $language) {
            $s = [
                "id" => $language->getId(),
                "language" => $language->getLanguage(),
                "image" => $imagineCacheManager->getBrowserPath($language->getMedia()->getLink(), 'language_thumb_api')
            ];
            $list[] = $s;
        }

        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder(), new XmlEncoder()]);
        $jsonContent = $serializer->serialize($list, 'json');
        return new Response($jsonContent);
    }

    #[Route('/language/add', name: 'app_language_add')]
    public function add(Request $request)
    {
        $language = new Language();
        $form = $this->createForm(LanguageType::class, $language);
        $em = $this->entityManager;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($language->getFile() !== null) {
                $media = new Media();
                $media->setFile($language->getFile());
                $media->upload($this->params->get('kernel.project_dir') . '/public/uploads');
                $em->persist($media);
                $em->flush();
                $language->setMedia($media);
                // Update position
                $languages = $em->getRepository(Language::class)->findAll();
                if (count($languages) > 0) {
                    $maxPosition = max(array_map(fn($lang) => $lang->getPosition(), $languages));
                    $language->setPosition($maxPosition + 1);
                } else {
                    // If no languages exist, start from position 1
                    $language->setPosition(1);
                }


                $em->persist($language);
                $em->flush();
                $this->addFlash('success', 'Language has been added successfully');
                return $this->redirectToRoute('app_language_index');
            } else {
                $error = new FormError("Required image file");
                $form->get('file')->addError($error);
            }
        }

        return $this->render('@AppBundle/Language/add.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/language', name: 'app_language_index')]
    public function index()
    {
        $em = $this->entityManager;
        $languages = $em->getRepository(Language::class)->findBy([], ['position' => 'asc']);
        return $this->render('@AppBundle/Language/index.html.twig', ['languages' => $languages]);
    }

    #[Route('/language/up/{id}', name: 'app_language_up')]
    public function up(Request $request, $id)
    {
        $em = $this->entityManager;
        $language = $em->getRepository(Language::class)->find($id);
        if ($language === null) {
            throw new NotFoundHttpException("Language not found");
        }

        if ($language->getPosition() > 1) {
            $position = $language->getPosition();
            $languages = $em->getRepository(Language::class)->findAll();
            foreach ($languages as $lang) {
                if ($lang->getPosition() === $position - 1) {
                    $lang->setPosition($position);
                }
            }
            $language->setPosition($position - 1);
            $em->flush();
        }

        return $this->redirectToRoute('app_language_index');
    }

    #[Route('/language/down/{id}', name: 'app_language_down')]
    public function down(Request $request, $id)
    {
        $em = $this->entityManager;
        $language = $em->getRepository(Language::class)->find($id);
        if ($language === null) {
            throw new NotFoundHttpException("Language not found");
        }

        $languages = $em->getRepository(Language::class)->findBy([], ['position' => 'asc']);
        $maxPosition = max(array_map(fn($lang) => $lang->getPosition(), $languages));

        if ($language->getPosition() < $maxPosition) {
            $position = $language->getPosition();
            foreach ($languages as $lang) {
                if ($lang->getPosition() === $position + 1) {
                    $lang->setPosition($position);
                }
            }
            $language->setPosition($position + 1);
            $em->flush();
        }

        return $this->redirectToRoute('app_language_index');
    }

    #[Route('/language/delete/{id}', name: 'app_language_delete')]
    public function delete(Request $request, $id)
    {
        $em = $this->entityManager;
        $language = $em->getRepository(Language::class)->find($id);
        if ($language === null) {
            throw new NotFoundHttpException("Language not found");
        }

        $form = $this->createFormBuilder(['id' => $id])
            ->add('id', HiddenType::class)
            ->add('Yes', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $media = $language->getMedia();
            $em->remove($language);
            $em->flush();

            if ($media !== null) {
                $media->delete($this->params->get('kernel.project_dir') . '/public/uploads');
                $em->remove($media);
                $em->flush();
            }

            $languages = $em->getRepository(Language::class)->findBy([], ['position' => 'asc']);
            $position = 1;
            foreach ($languages as $lang) {
                $lang->setPosition($position++);
            }
            $em->flush();

            $this->addFlash('success', 'Language has been deleted successfully');
            return $this->redirectToRoute('app_language_index');
        }

        return $this->render('@AppBundle/Language/delete.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/language/edit/{id}', name: 'app_language_edit')]
    public function edit(Request $request, $id)
    {
        $em = $this->entityManager;
        $language = $em->getRepository(Language::class)->find($id);
        if ($language === null) {
            throw new NotFoundHttpException("Language not found");
        }

        $form = $this->createForm(LanguageType::class, $language);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($language->getFile() !== null) {
                $media = new Media();
                $mediaOld = $language->getMedia();
                $media->setFile($language->getFile());
                $media->upload($this->params->get('kernel.project_dir') . '/public/uploads');
                $em->persist($media);
                $em->flush();

                $language->setMedia($media);
                $em->flush();

                $mediaOld->delete($this->params->get('kernel.project_dir') . '/public/uploads');
                $em->remove($mediaOld);
                $em->flush();
            }

            $em->persist($language);
            $em->flush();

            $this->addFlash('success', 'Language has been updated successfully');
            return $this->redirectToRoute('app_language_index');
        }

        return $this->render('@AppBundle/Language/edit.html.twig', ['form' => $form->createView()]);
    }
}
