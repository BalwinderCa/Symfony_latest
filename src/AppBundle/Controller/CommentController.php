<?php

namespace App\AppBundle\Controller;

use App\AppBundle\Entity\Comment;
use App\AppBundle\Entity\Status;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Knp\Component\Pager\PaginatorInterface;  // Paginator Interface
use Symfony\Component\Routing\Annotation\Route; // Import Route annotation
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CommentController extends AbstractController
{   

    private $entityManager;
    private $token;

    // Inject the EntityManagerInterface into the controller
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->token = "4F5A9C3D9A86FA54EACEDDD635185";
    }
    #[Route('/comments', name: 'app_comment_index')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->entityManager;
        $dql = "SELECT c FROM App\AppBundle\Entity\Comment c ORDER BY c.created DESC";
        $query = $em->createQuery($dql);

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), 10
        );

        $comments = $em->getRepository(Comment::class)->findAll();

        return $this->render('@AppBundle/Comment/index.html.twig', [
            'pagination' => $pagination,
            'comments' => $comments,
        ]);
    }

    // Helper function to calculate time difference
    private function getTimeDifference($createdDateTime)
    {
        $currentDateTime = new \DateTime();
        $interval = $currentDateTime->diff($createdDateTime);
        return $interval->format('%y years, %m months, %d days, %h hours, %i minutes ago');
    }

    #[Route('/api/comments/{id}/{token}', name: 'app_comment_api_list')]
    public function api_list($id, $token, SerializerInterface $serializer)
    {
        if ($token != $this->token) {
            throw new NotFoundHttpException("Page not found");
        }

        $em = $this->entityManager;
        $status = $em->getRepository(Status::class)->find($id);
        $comments = $status ? $em->getRepository(Comment::class)->findBy(['status' => $status]) : [];

        $list=array();
        foreach ($comments as $key => $comment) {
            $a["id"]=$comment->getId();
            $a["user"]=$comment->getUser()->getName();
            $a["image"]=$comment->getUser()->getImage();
            $a["content"]=$comment->getContent();
            $a["enabled"]=$comment->getEnabled();
            $trusted = "flase";
            if ($comment->getUser()->getTrusted()) {
                $trusted = "true";
            }
            $a["trusted"]=$trusted;
            $a["created"]=$this->getTimeDifference($comment->getCreated());
            $list[]=$a;
        }

        return new JsonResponse($list, JSON_UNESCAPED_UNICODE);
    }

    #[Route('/api/comments/add/{token}', name: 'app_comment_api_add')]
    public function apiAdd(Request $request, $token, SerializerInterface $serializer)
    {
        if ($token != $this->token) {
            throw new NotFoundHttpException("Page not found");
        }

        $userId = $request->get('user');
        $statusId = $request->get('id');
        $content = $request->get('comment');

        $em = $this->entityManager;
        $user = $em->getRepository('UserBundle:User')->find($userId);
        $status = $em->getRepository('AppBundle:Status')->find($statusId);

        $comment = new Comment();
        $comment->setContent($content);
        $comment->setEnabled(true);
        $comment->setUser($user);
        $comment->setStatus($status);

        $em->persist($comment);
        $em->flush();

        $message = "Your comment has been added";
        $errors = [
            ["name" => "id", "value" => $comment->getId()],
            ["name" => "content", "value" => $comment->getContent()],
            ["name" => "user", "value" => $comment->getUser()->getName()],
            ["name" => "image", "value" => $comment->getUser()->getImage()],
            ["name" => "enabled", "value" => $comment->getEnabled()],
            ["name" => "trusted", "value" => $comment->getUser()->getTrusted() ? "true" : "false"],
            ["name" => "created", "value" => "now"],
        ];

        $responseData = [
            'code' => '200',
            'message' => $message,
            'values' => $errors
        ];

        return new JsonResponse($responseData);
    }

    #[Route('/comments/{id}/hide', name: 'app_comment_hide')]
    public function hide($id, Request $request)
    {
        $em = $this->entityManager;
        $comment = $em->getRepository(Comment::class)->find($id);

        if (!$comment) {
            throw new NotFoundHttpException("Page not found");
        }

        $comment->setEnabled(!$comment->getEnabled());
        $em->flush();

        $this->addFlash('success', 'Operation has been done successfully');
        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/comments/{id}/delete', name: 'app_comment_delete')]
    public function delete($id, Request $request)
    {
        $em = $this->entityManager;
        $comment = $em->getRepository(Comment::class)->find($id);

        if (!$comment) {
            throw new NotFoundHttpException("Page not found");
        }

        $form = $this->createFormBuilder(['id' => $id])
            ->add('id', HiddenType::class)
            ->add('Yes', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->remove($comment);
            $em->flush();

            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirectToRoute('app_comment_index');
        }

        return $this->render('@AppBundle/Comment/delete.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
