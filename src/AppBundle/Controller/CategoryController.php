<?php

namespace App\AppBundle\Controller;

use App\AppBundle\Entity\Category;
use App\AppBundle\Entity\Section;
use App\MediaBundle\Entity\Media;
use App\AppBundle\Forms\CategoryType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

class CategoryController extends AbstractController
{   
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;
    private CacheManager $imagineCacheManager;
    private $token;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params,
        CacheManager $imagineCacheManager
    ) {
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->imagineCacheManager = $imagineCacheManager;
        $this->token = "4F5A9C3D9A86FA54EACEDDD635185";
    }

    #[Route('/categories', name: 'app_category_index')]
    public function index(): Response
    {
        $categories = $this->entityManager
            ->getRepository(Category::class)
            ->findBy([], ['position' => 'asc']);

        return $this->render('@AppBundle/Category/index.html.twig', [
            'categories' => $categories
        ]);
    }

    #[Route('/api/categories/popular/{token}', name: 'app_category_api_popular')]
    public function api_popular(Request $request, string $token): Response
    {
        if ($token != $this->token) {
            throw new NotFoundHttpException("Page not found");
        }

        $repository = $this->entityManager->getRepository(Category::class);
        $query = $repository->createQueryBuilder('C')
            ->select([
                'C.id',
                'C.title',
                'm.url as image',
                'm.extension as extension',
                'SUM(w.downloads) as test'
            ])
            ->leftJoin('C.status', 'w')
            ->leftJoin('C.media', 'm')
            ->groupBy('C.id')
            ->orderBy('test', 'DESC')
            ->where('w.enabled = true')
            ->getQuery();

        $categories = $query->getResult();
        $list = [];

        foreach ($categories as $category) {
            $s = [
                'id' => $category['id'],
                'title' => $category['title'],
                'image' => $this->imagineCacheManager->getBrowserPath(
                    "uploads/" . $category['extension'] . "/" . $category['image'],
                    'category_thumb_api'
                )
            ];
            $list[] = $s;
        }

        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($list, 'json');
        
        $response = new Response($jsonContent);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    #[Route('/api/categories/all/{token}', name: 'app_category_api_all')]
    public function api_all(Request $request, string $token): Response
    {
        if ($token != $this->token) {
            throw new NotFoundHttpException("Page not found");
        }

        $categories = $this->entityManager
            ->getRepository(Category::class)
            ->findBy([], ['position' => 'asc']);

        $list = [];
        foreach ($categories as $category) {
            $s = [
                'id' => $category->getId(),
                'title' => $category->getTitle(),
                'image' => $this->imagineCacheManager->getBrowserPath(
                    $category->getMedia()->getLink(),
                    'category_thumb_api'
                )
            ];
            $list[] = $s;
        }

        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($list, 'json');
        
        $response = new Response($jsonContent);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    #[Route('/api/categories/by-section/{id}/{token}', name: 'app_category_api_by_section')]
    public function apiBySection(int $id, string $token): Response
    {
        if ($token != $this->token) {
            throw new NotFoundHttpException("Page not found");
        }

        $section = $this->entityManager->getRepository(Section::class)->find($id);
        if (!$section) {
            throw new NotFoundHttpException("Section not found");
        }

        $categories = $this->entityManager
            ->getRepository(Category::class)
            ->findBy(['section' => $section], ['position' => 'asc']);

        $list = [];
        foreach ($categories as $category) {
            $s = [
                'id' => $category->getId(),
                'title' => $category->getTitle(),
                'image' => $this->imagineCacheManager->getBrowserPath(
                    $category->getMedia()->getLink(),
                    'section_thumb_api'
                )
            ];
            $list[] = $s;
        }

        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($list, 'json');
        
        $response = new Response($jsonContent);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    #[Route('/category/add', name: 'app_category_add')]
    public function add(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($category->getFile() !== null) {
                $media = new Media();
                $media->setFile($category->getFile());
                $media->upload($this->params->get('kernel.project_dir') . '/public/uploads');
                $this->entityManager->persist($media);
                $this->entityManager->flush();
                
                $category->setMedia($media);
                
                // Set position
                $max = 0;
                $categories = $this->entityManager->getRepository(Category::class)->findAll();
                foreach ($categories as $value) {
                    if ($value->getPosition() > $max) {
                        $max = $value->getPosition();
                    }
                }
                $category->setPosition($max + 1);
                
                $this->entityManager->persist($category);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Operation has been done successfully');
                return $this->redirectToRoute('app_category_index');
            } else {
                $error = new FormError("Required image file");
                $form->get('file')->addError($error);
            }
        }

        return $this->render('@AppBundle/Category/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/category/edit/{id}', name: 'app_category_edit')]
    public function edit(Request $request, int $id): Response
    {
        $category = $this->entityManager->getRepository(Category::class)->find($id);
        if (!$category) {
            throw new NotFoundHttpException("Page not found");
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($category->getFile() !== null) {
                $media = new Media();
                $mediaOld = $category->getMedia();
                $media->setFile($category->getFile());
                $media->upload($this->params->get('kernel.project_dir') . '/public/uploads');
                
                $this->entityManager->persist($media);
                $this->entityManager->flush();
                $category->setMedia($media);

                if ($mediaOld) {
                    $mediaOld->delete($this->params->get('kernel.project_dir') . '/public/uploads');
                    $this->entityManager->remove($mediaOld);
                    $this->entityManager->flush();
                }
            }

            $this->entityManager->persist($category);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirectToRoute('app_category_index');
        }

        return $this->render('@AppBundle/Category/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView()
        ]);
    }

    #[Route('/category/delete/{id}', name: 'app_category_delete')]
    public function delete(Request $request, int $id): Response
    {
        $category = $this->entityManager->getRepository(Category::class)->find($id);
        if (!$category) {
            throw new NotFoundHttpException("Page not found");
        }

        $form = $this->createFormBuilder(['id' => $id])
            ->add('id', \Symfony\Component\Form\Extension\Core\Type\HiddenType::class)
            ->add('Yes', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mediaOld = $category->getMedia();
            
            $this->entityManager->remove($category);
            $this->entityManager->flush();

            if ($mediaOld) {
                $mediaOld->delete($this->params->get('kernel.project_dir') . '/public/uploads');
                $this->entityManager->remove($mediaOld);
                $this->entityManager->flush();
            }

            // Reorder remaining categories
            $categories = $this->entityManager
                ->getRepository(Category::class)
                ->findBy([], ['position' => 'asc']);

            $p = 1;
            foreach ($categories as $value) {
                $value->setPosition($p);
                $p++;
            }
            $this->entityManager->flush();

            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirectToRoute('app_category_index');
        }

        return $this->render('@AppBundle/Category/delete.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/category/up/{id}', name: 'app_category_up')]
    public function up(int $id): Response
    {
        $category = $this->entityManager->getRepository(Category::class)->find($id);
        if (!$category) {
            throw new NotFoundHttpException("Page not found");
        }

        if ($category->getPosition() > 1) {
            $p = $category->getPosition();
            $categories = $this->entityManager->getRepository(Category::class)->findAll();
            
            foreach ($categories as $value) {
                if ($value->getPosition() == $p - 1) {
                    $value->setPosition($p);
                }
            }
            
            $category->setPosition($category->getPosition() - 1);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_category_index');
    }

    #[Route('/category/down/{id}', name: 'app_category_down')]
    public function down(int $id): Response
    {
        $category = $this->entityManager->getRepository(Category::class)->find($id);
        if (!$category) {
            throw new NotFoundHttpException("Page not found");
        }

        $max = 0;
        $categories = $this->entityManager
            ->getRepository(Category::class)
            ->findBy([], ['position' => 'asc']);

        foreach ($categories as $value) {
            $max = $value->getPosition();
        }

        if ($category->getPosition() < $max) {
            $p = $category->getPosition();
            foreach ($categories as $value) {
                if ($value->getPosition() == $p + 1) {
                    $value->setPosition($p);
                }
            }
            
            $category->setPosition($category->getPosition() + 1);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_category_index');
    }
}