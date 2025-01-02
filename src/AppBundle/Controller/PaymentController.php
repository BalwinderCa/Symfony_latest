<?php 
namespace App\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use App\AppBundle\Entity\Withdraw;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface; // Correct import


class PaymentController extends AbstractController
{   

    private $entityManager;
    private $token;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->token = "4F5A9C3D9A86FA54EACEDDD635185";
    }
    #[Route('/api/transaction/{user}/{key}/{token}', name: 'api_transaction_by_user')]
    public function api_transaction_by_user(Request $request, $user, $key, $token)
    {
        if ($token != $this->token) {
            throw new NotFoundHttpException("Page not found");
        }

        $userId = $user;
        $userKey = $key;
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->find($userId);

        if (!$user) {
            throw new NotFoundHttpException("Page not found");
        }

        if (!password_verify($userKey, $user->getPassword())) {
            throw new NotFoundHttpException("Page not found");
        }

        $setting = $em->getRepository(Settings::class)->findOneBy(array());
        $repository = $em->getRepository(Transaction::class);
        $query = $repository->createQueryBuilder('w')
            ->where('w.user = :user')
            ->andWhere('w.enabled = true')
            ->setParameter('user', $user)
            ->addOrderBy('w.created', 'DESC')
            ->addOrderBy('w.id', 'asc')
            ->getQuery();
        $transactions = $query->getResult();

        return $this->render('@AppBundle/Payment/api_all.html.php', [
            "currency" => $setting->getCurrency(),
            "toCurrency" => $setting->getOneusdtopoints(),
            "transactions" => $transactions
        ]);
    }

    #[Route('/withdrawals', name: 'app_payment_withdrawal')]
    public function withdrawal(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->entityManager;
        
        // Use the QueryBuilder for more flexibility
        $queryBuilder = $em->getRepository(Withdraw::class)->createQueryBuilder('s')
            ->orderBy('s.created', 'DESC');
        
        // Paginate the query results
        $pagination = $paginator->paginate(
            $queryBuilder, // Pass the QueryBuilder instead of the DQL query
            $request->query->getInt('page', 1),
            10
        );

        // Counting withdrawals (if needed)
        $count = $em->getRepository(Withdraw::class)->count([]);

        return $this->render('@AppBundle/Payment/withdrawals.html.twig', [
            'pagination' => $pagination,
            'count' => $count,  // You can directly use the count method
        ]);
    }


    #[Route('/api/withdrawal/request', name: 'api_request_by_user')]
    public function api_request_by_user(Request $request, $token)
    {
        if ($token != $this->token) {
            throw new NotFoundHttpException("Page not found");
        }

        $code = "200";
        $message = "";
        $errors = [];

        $userId = $request->get("user");
        $userKey = $request->get("key");
        $method = $request->get("method");
        $account = $request->get("account");

        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->find($userId);

        if (!$user) {
            throw new NotFoundHttpException("Page not found");
        }

        if (!password_verify($userKey, $user->getPassword())) {
            throw new NotFoundHttpException("Page not found");
        }

        $setting = $em->getRepository(Settings::class)->findOneBy(array());
        $transactions = $em->getRepository(Transaction::class)->findBy(array("user" => $user, "enabled" => true));
        $total = 0;
        foreach ($transactions as $transaction) {
            $total += $transaction->getPoints();
        }
        $earning = $total / $setting->getOneusdtopoints() . " " . $setting->getCurrency();
        $onetopoits = "1 " . $setting->getCurrency() . " = " . $setting->getOneusdtopoints();

        if ($total > $setting->getMinpoints()) {
            $withdraw = new Withdraw();
            $withdraw->setUser($user);
            $withdraw->setMethode($method);
            $withdraw->setAccount($account);
            $withdraw->setPoints($total);
            $withdraw->setAmount($earning);
            $withdraw->setType("Pending");
            $em->persist($withdraw);
            $em->flush();

            $code = 200;
            $message = "Your withdrawal request has been submitted (" . $earning . ")";

            foreach ($transactions as $transaction) {
                $transaction->setEnabled(false);
            }
            $em->flush();
        } else {
            $code = "300";
            $message = "Withdrawal minimum " . $setting->getMinpoints() . " Points";
        }

        $error = [
            "code" => $code,
            "message" => $message,
            "values" => $errors
        ];

        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($error, 'json');

        return new Response($jsonContent);
    }

    #[Route('/api/withdrawals/{user}/{key}/{token}', name: 'api_withdrawals_by_user')]
    public function api_withdrawals_by_user(Request $request, $user, $key, $token)
    {
        if ($token != $this->token) {
            throw new NotFoundHttpException("Page not found");
        }

        $code = "200";
        $message = "";
        $errors = [];

        $userId = $user;
        $userKey = $key;

        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->find($userId);

        if (!$user) {
            throw new NotFoundHttpException("Page not found");
        }

        if (!password_verify($userKey, $user->getPassword())) {
            throw new NotFoundHttpException("Page not found");
        }

        $list = [];
        $withdrawals = $em->getRepository(Withdraw::class)->findBy(["user" => $user], ["created" => "desc"]);
        foreach ($withdrawals as $withdrawal) {
            $s = [
                "id" => $withdrawal->getId(),
                "method" => $withdrawal->getMethode(),
                "account" => $withdrawal->getAccount(),
                "amount" => $withdrawal->getAmount(),
                "points" => $withdrawal->getPoints(),
                "name" => $withdrawal->getUser()->getName(),
                "state" => $withdrawal->getType(),
                "date" => $withdrawal->getCreated()->format('Y/m/d H:i:s')
            ];
            $list[] = $s;
        }

        header('Content-Type: application/json');
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($list, 'json');

        return new Response($jsonContent);
    }

    #[Route('/api/earning/{user}/{key}/{token}', name: 'api_earning_by_user')]
    public function api_earning_by_user(Request $request, $user, $key, $token)
    {
        if ($token != $this->token) {
            throw new NotFoundHttpException("Page not found");
        }

        $code = "200";
        $message = "";
        $errors = [];

        $userId = $user;
        $userKey = $key;
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->find($userId);

        if (!$user) {
            throw new NotFoundHttpException("Page not found");
        }

        if (!password_verify($userKey, $user->getPassword())) {
            throw new NotFoundHttpException("Page not found");
        }

        $setting = $em->getRepository(Settings::class)->findOneBy(array());
        $transactions = $em->getRepository(Transaction::class)->findBy(array("user" => $user, "enabled" => true));
        $total = 0;
        foreach ($transactions as $transaction) {
            $total += $transaction->getPoints();
        }

        $earning = $this->number_format_short($total / $setting->getOneusdtopoints()) . " " . $setting->getCurrency();
        $onetopoits = "1 " . $setting->getCurrency() . " = " . $setting->getOneusdtopoints();

        $errors[] = ["name" => "earning", "value" => $earning];
        $errors[] = ["name" => "points", "value" => $this->number_format_short($total)];
        $errors[] = ["name" => "equals", "value" => $onetopoits];
        $errors[] = ["name" => "code", "value" => $user->getCode()];

        $error = [
            "code" => $code,
            "message" => $message,
            "values" => $errors
        ];

        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($error, 'json');

        return new Response($jsonContent);
    }

    public function reject($id,Request $request)
    {
        $em=$this->entityManager;
        $withdraw = $em->getRepository(Withdraw::class)->find($id);
        if($withdraw==null){
            throw new NotFoundHttpException("Page not found");
        }
        $withdraw->setType("Rejected");
        $em->flush();
        $this->addFlash('success', 'Operation has been done successfully');
        return  $this->redirect($request->server->get('HTTP_REFERER'));
    }

    public function approve($id,Request $request)
    {
        $em=$this->entityManager;
        $withdraw = $em->getRepository(Withdraw::class)->find($id);
        if($withdraw==null){
            throw new NotFoundHttpException("Page not found");
        }
        $withdraw->setType("Paid");
        $em->flush();
        $this->addFlash('success', 'Operation has been done successfully');
        return $this->redirect($this->generateUrl('app_home_notif_user_payment',array("withdraw"=>$withdraw->getId())));
    }

    private function number_format_short($n)
    {
        if ($n < 1000) {
            return $n;
        }
        $precision = 1;
        if ($n < 900) {
            $n_format = number_format($n, $precision);
            $suffix = '';
        } else if ($n < 900000) {
            $n_format = number_format($n / 1000, $precision);
            $suffix = 'K';
        } else if ($n < 900000000) {
            $n_format = number_format($n / 1000000, $precision);
            $suffix = 'M';
        } else if ($n < 900000000000) {
            $n_format = number_format($n / 1000000000, $precision);
            $suffix = 'B';
        } else {
            $n_format = number_format($n / 1000000000000, $precision);
            $suffix = 'T';
        }

        if ($precision > 0) {
            $dotzero = '.' . str_repeat('0', $precision);
            $n_format = str_replace($dotzero, '', $n_format);
        }

        return $n_format . $suffix;
    }
}
