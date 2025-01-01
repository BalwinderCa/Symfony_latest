<?php
namespace App\AppBundle\Controller;

use App\AppBundle\Entity\Version;
use App\AppBundle\Forms\VersionType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface; // Correct import
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class VersionController extends AbstractController
{   

    private $entityManager;

    // Inject the EntityManagerInterface into the controller
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/version/add', name: 'app_version_add')]
    public function add(Request $request)
    {
        $version = new Version();
        $form = $this->createForm(VersionType::class, $version);
        $em = $this->entityManager;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($version);
            $em->flush();
            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirectToRoute('app_version_index');
        }

        return $this->render('@AppBundle/Version/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/api/version/check/{code}/{token}', name: 'api_version_check')]
    public function api_check(Request $request, $code, $token)
    {
        if ($token != $this->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");
        }

        $em = $this->entityManager;
        $version = $em->getRepository(Version::class)->findOneBy([
            "code" => $code,
            "enabled" => true
        ]);

        $response = [];
        $message = "";
        if ($version === null) {
            $versions = $em->getRepository(Version::class)->findBy([
                "enabled" => true
            ], ["code" => "asc"]);

            $latestVersion = end($versions);
            if ($latestVersion === null) {
                $response["name"] = "update";
                $response["value"] = "App on update";
            } else {
                $response["name"] = "update";
                $response["value"] = "New version available " . $latestVersion->getTitle() . " please update your application";
                $message = $latestVersion->getFeatures();
            }
        } else {
            $response["name"] = "update";
            $response["value"] = "App on update";
        }

        $error = [
            "code" => "200",
            "message" => $message,
            "values" => [$response],
        ];

        return $this->json($error);
    }

    #[Route('/versions', name: 'app_version_index')]
    public function index()
    {
        $em = $this->entityManager;
        $versions = $em->getRepository(Version::class)->findBy([], ["code" => "asc"]);

        return $this->render('@AppBundle/Version/index.html.twig', [
            'versions' => $versions
        ]);
    }

    #[Route('/version/{id}/delete', name: 'app_version_delete')]
    public function delete($id, Request $request)
    {
        $em = $this->entityManager;
        $version = $em->getRepository(Version::class)->find($id);

        if ($version === null) {
            throw new NotFoundHttpException("Page not found");
        }

        $form = $this->createFormBuilder(['id' => $id])
            ->add('id', HiddenType::class)
            ->add('Yes', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->remove($version);
            $em->flush();

            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirectToRoute('app_version_index');
        }

        return $this->render('@AppBundle/Version/delete.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/version/{id}/edit', name: 'app_version_edit')]
    public function edit(Request $request, $id)
    {
        $em = $this->entityManager;
        $version = $em->getRepository(Version::class)->find($id);

        if ($version === null) {
            throw new NotFoundHttpException("Page not found");
        }

        $form = $this->createForm(VersionType::class, $version);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($version);
            $em->flush();
            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirectToRoute('app_version_index');
        }

        return $this->render('@AppBundle/Version/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
