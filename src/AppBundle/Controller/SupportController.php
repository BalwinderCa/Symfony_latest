<?php

namespace App\AppBundle\Controller;

use App\AppBundle\Entity\Support;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface; // Correct import
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;


class SupportController extends AbstractController
{
    private $paginator;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, PaginatorInterface $paginator)
    {
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
    }

    #[Route('/api/add/{token}', name: 'api_add_support')]
    public function api_add(Request $request, $token)
    {
        if ($token !== "4F5A9C3D9A86FA54EACEDDD635185") {
            throw new NotFoundHttpException("Page not found");
        }

        $email = $request->get("email");
        $subject = $request->get("name");
        $message = $request->get("message");

        $em = $this->entityManager;
        $support = new Support();
        $support->setEmail($email);
        $support->setSubject($subject);
        $support->setMessage($message);
        $em->persist($support);
        $em->flush();

        $error = [
            "code" => "200",
            "message" => "Votre message a bien été envoyé",
            "values" => []
        ];

        return $this->json($error);
    }

    #[Route('/supports', name: 'app_support_index')]
    public function index(Request $request)
    {
        $em = $this->entityManager;
        $dql = "SELECT s FROM App\AppBundle\Entity\Support s";
        $query = $em->createQuery($dql);

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // current page number
            10 // number of items per page
        );

        // Fetch the actual list of supports
        $supports = $this->entityManager->getRepository(Support::class)->findAll();

        return $this->render('@AppBundle/support/index.html.twig', [
            'pagination' => $pagination,
            'supports' => $supports,  // Pass supports to the template
        ]);
    }

    #[Route('/support/{id}', name: 'app_support_view')]
    public function view(Request $request, $id)
    {
        $em = $this->entityManager;
        $support = $em->getRepository(Support::class)->find($id);
        if ($support === null) {
            throw new NotFoundHttpException("Page not found");
        }

        return $this->render('@AppBundle/support/view.html.twig', [
            "support" => $support
        ]);
    }

    #[Route('/support/{id}/delete', name: 'app_support_delete')]
    public function delete(Request $request, $id)
    {
        $em = $this->entityManager;
        $support = $em->getRepository(Support::class)->find($id);
        if ($support === null) {
            throw new NotFoundHttpException("Page not found");
        }

        $form = $this->createFormBuilder(['id' => $id])
            ->add('id', HiddenType::class)
            ->add('Yes', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->remove($support);
            $em->flush();
            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirectToRoute('app_support_index');
        }

        return $this->render('@AppBundle/support/delete.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
