<?php

namespace App\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use App\UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use FOS\UserBundle\Form\Model\ChangePassword;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\UserBundle\Form\UserType;
use App\MediaBundle\Entity\Media as Media;
use App\AppBundle\Entity\Transaction;
use Symfony\Component\Routing\Annotation\Route; // Import Route annotation
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Knp\Component\Pager\PaginatorInterface; // Correct import

class UserController extends AbstractController
{
 
    private const REFERRAL_LIMIT = 10; // Max referrals per user per day
    private const FILE_PATH = __DIR__ . "/../Resources/referral_counts.json";

    private $entityManager;
    private CacheManager $imagineCacheManager;
    private $paginator;
    private $params;

    public function __construct(
        EntityManagerInterface $entityManager,
        CacheManager $imagineCacheManager,
        PaginatorInterface $paginator
    ) {
        $this->entityManager = $entityManager;
        $this->imagineCacheManager = $imagineCacheManager;
        $this->paginator = $paginator;
    }

    private function getAccessToken(): string
    {
        $key = json_decode(file_get_contents(dirname(__DIR__, 3) . '/src/secret/firebase-service-account.json'), true);

        $now = time();
        $header = base64_encode(
            json_encode(["alg" => "RS256", "typ" => "JWT"])
        );
        $payload = base64_encode(
            json_encode([
                "iss" => $key["client_email"],
                "scope" => "https://www.googleapis.com/auth/firebase.messaging",
                "aud" => $key["token_uri"],
                "exp" => $now + 3600,
                "iat" => $now,
            ])
        );

        $unsignedJwt = $header . "." . $payload;
        $signature = "";
        openssl_sign(
            $unsignedJwt,
            $signature,
            $key["private_key"],
            OPENSSL_ALGO_SHA256
        );
        $jwt = $unsignedJwt . "." . base64_encode($signature);

        // Exchange JWT for access token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $key["token_uri"]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
        ]);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            json_encode([
                "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
                "assertion" => $jwt,
            ])
        );

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            throw new \Exception("Failed to get access token.");
        }

        $data = json_decode($response, true);
        return $data["access_token"];
    }

    public function sendNotification(
        array $tokens,
        array $message,
        string $accessToken
    ): string {
        $url =
            "https://fcm.googleapis.com/v1/projects/daily-status-9b9f8/messages:send";

        // Prepare the message in JSON format
        $fields = [
            "message" => [
                "token" => $tokens[0], // Token to send the notification to
                "data" => $message, // Include the message data
            ],
        ];

        $headers = [
            "Authorization: Bearer " . $accessToken,
            "Content-Type: application/json",
        ];

        // Send the POST request to FCM
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            throw new \Exception("Failed to send notification.");
        }

        return $response;
    }

    public function comment(Request $request, $id)
    {
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->find($id);
        if ($user == null) {
            throw new NotFoundHttpException("Page not found");
        }
        if ($user->hasRole("ROLE_ADMIN")) {
            throw new NotFoundHttpException("Page not found");
        }

        $dql =
            "SELECT c FROM App\AppBundle\Entity\Comment c  WHERE c.user = " .
            $user->getId();
        $query = $em->createQuery($dql);
        $paginator = $this->get("knp_paginator");
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt("page", 1),
            7
        );

        return $this->render("@UserBundle/User/comment.html.twig", [
            "pagination" => $pagination,
            "user" => $user,
        ]);
    }

    public function transaction(Request $request, $id)
    {
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->find($id);
        if ($user == null) {
            throw new NotFoundHttpException("Page not found");
        }
        if ($user->hasRole("ROLE_ADMIN")) {
            throw new NotFoundHttpException("Page not found");
        }

        $dql =
            "SELECT c FROM App\AppBundle\Entity\Transaction c  WHERE c.user = " .
            $user->getId() .
            " order by c.created asc";
        $query = $em->createQuery($dql);
        $paginator = $this->get("knp_paginator");
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt("page", 1),
            7
        );

        $setting = $em->getRepository(Settings::class)->findOneBy([]);
        $transactions = $em
            ->getRepository(Transaction::class)
            ->findBy(["user" => $user, "enabled" => true]);
        $total = 0;
        foreach ($transactions as $key => $transaction) {
            $total += $transaction->getPoints();
        }
        $earning =
            $this->number_format_short($total / $setting->getOneusdtopoints()) .
            " " .
            $setting->getCurrency();
        $onetopoits =
            "1 " .
            $setting->getCurrency() .
            " = " .
            $setting->getOneusdtopoints();

        return $this->render("@UserBundle/User/transaction.html.twig", [
            "pagination" => $pagination,
            "user" => $user,
            "earning" => $earning,
            "points" => $total,
        ]);
    }

    public function withdraw(Request $request, $id)
    {
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->find($id);
        if ($user == null) {
            throw new NotFoundHttpException("Page not found");
        }
        if ($user->hasRole("ROLE_ADMIN")) {
            throw new NotFoundHttpException("Page not found");
        }

        $dql =
            "SELECT c FROM App\AppBundle\Entity\Withdraw c  WHERE c.user = " .
            $user->getId();
        $query = $em->createQuery($dql);
        $paginator = $this->get("knp_paginator");
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt("page", 1),
            7
        );
        return $this->render("@UserBundle/User/withdraw.html.twig", [
            "pagination" => $pagination,
            "user" => $user,
        ]);
    }

    public function edit(Request $request, $id)
    {
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->find($id);
        if ($user == null) {
            throw new NotFoundHttpException("Page not found");
        }
        if ($user->hasRole("ROLE_ADMIN")) {
            throw new NotFoundHttpException("Page not found");
        }

        $form = $this->createForm(new UserType(), $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $request
                ->getSession()
                ->getFlashBag()
                ->add("success", "Operation has been done successfully");
            return $this->redirect($this->generateUrl("user_user_index"));
        }
        return $this->render("@UserBundle/User/edit.html.twig", [
            "form" => $form->createView(),
            "user" => $user,
        ]);
    }

    public function delete($id, Request $request)
    {
        $em = $this->entityManager;
        $transaction = $em->getRepository(Transaction::class)->find($id);
        if ($transaction == null) {
            throw new NotFoundHttpException("Page not found");
        }
        $id_u = $transaction->getUser()->getId();
        $em->remove($transaction);
        $em->flush();
        $this->addFlash("success", "Operation has been done successfully");
        return $this->redirect(
            $this->generateUrl("user_user_transaction", ["id" => $id_u])
        );
    }

    public function delete_withdraw($id, Request $request)
    {
        $em = $this->entityManager;
        $withdraw = $em->getRepository(Withdraw::class)->find($id);
        if ($withdraw == null) {
            throw new NotFoundHttpException("Page not found");
        }
        $id_u = $withdraw->getUser()->getId();
        $em->remove($withdraw);
        $em->flush();
        $this->addFlash("success", "Operation has been done successfully");
        return $this->redirect(
            $this->generateUrl("user_user_withdraw", ["id" => $id_u])
        );
    }

    function number_format_short($n)
    {
        $precision = 1;
        if ($n < 900) {
            // 0 - 900
            $n_format = number_format($n, $precision);
            $suffix = "";
        } elseif ($n < 900000) {
            // 0.9k-850k
            $n_format = number_format($n / 1000, $precision);
            $suffix = "K";
        } elseif ($n < 900000000) {
            // 0.9m-850m
            $n_format = number_format($n / 1000000, $precision);
            $suffix = "M";
        } elseif ($n < 900000000000) {
            // 0.9b-850b
            $n_format = number_format($n / 1000000000, $precision);
            $suffix = "B";
        } else {
            // 0.9t+
            $n_format = number_format($n / 1000000000000, $precision);
            $suffix = "T";
        }
        // Remove unecessary zeroes after decimal. "1.0" -> "1"; "1.00" -> "1"
        // Intentionally does not affect partials, eg "1.50" -> "1.50"
        if ($precision > 0) {
            $dotzero = "." . str_repeat("0", $precision);
            $n_format = str_replace($dotzero, "", $n_format);
        }
        return $n_format . $suffix;
    }

    public function index(Request $request)
    {
        $em = $this->entityManager;
        $users = $em->getRepository(User::class)->count();

        $q = " AND ( 1=1 ) ";
        if ($request->query->has("q") and $request->query->get("q") != "") {
            $q .=
                " AND ( u.name like '%" .
                $request->query->get("q") .
                "%' or u.username like '%" .
                $request->query->get("q") .
                "%') ";
        }
        $dql =
            "SELECT u FROM App\UserBundle\Entity\User u  WHERE (NOT u.roles LIKE '%ROLE_ADMIN%')   " .
            $q .
            " ";
        $query = $em->createQuery($dql);
        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt("page", 1), // current page number
            7 // number of items per page
        );
        return $this->render("@UserBundle/User/index.html.twig", [
            "pagination" => $pagination,
            "users" => $users,
        ]);
    }

    public function api_get(Request $request, $user, $me, $token)
    {
        if ($token != $this->container->getParameter("token_app")) {
            throw new NotFoundHttpException("Page not found");
        }
        $code = 200;
        $message = "";
        $values = [];

        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->find($user);
        if ($user == null) {
            throw new NotFoundHttpException("Page not found");
        }

        if ($me != -1) {
            $me = $em->getRepository(User::class)->find($me);
            if ($me) {
                $followers = $user->getFollowers();
                $exists = false;
                foreach ($followers as $key => $f) {
                    if ($f->getId() == $me->getId()) {
                        $exists = true;
                    }
                }
                if ($exists) {
                    $values[] = ["name" => "follow", "value" => "true"];
                } else {
                    $values[] = ["name" => "follow", "value" => "false"];
                }
            } else {
                $values[] = ["name" => "follow", "value" => "false"];
            }
        } else {
            $values[] = ["name" => "follow", "value" => "false"];
        }
        $followers = $user->getFollowers();
        $followings = $user->getUsers();
        $status = $user->getStatus();

        $values[] = ["name" => "followers", "value" => sizeof($followers)];
        $values[] = ["name" => "followings", "value" => sizeof($followings)];
        $values[] = ["name" => "status", "value" => sizeof($status)];
        $values[] = ["name" => "facebook", "value" => $user->getFacebook()];
        $values[] = ["name" => "twitter", "value" => $user->getTwitter()];
        $values[] = ["name" => "instagram", "value" => $user->getInstagram()];
        $values[] = ["name" => "email", "value" => $user->getEmailo()];
        $trusted = "false";
        if ($user->getTrusted()) {
            $trusted = "true";
        }
        $values[] = ["name" => "trusted", "value" => $trusted];

        $setting = $em->getRepository(Settings::class)->findOneBy([]);
        $transactions = $em
            ->getRepository(Transaction::class)
            ->findBy(["user" => $user]);
        $total = 0;
        foreach ($transactions as $key => $transaction) {
            $total += $transaction->getPoints();
        }
        $earning =
            $total / $setting->getOneusdtopoints() .
            " " .
            $setting->getCurrency();

        $values[] = ["name" => "earning", "value" => $earning];

        $value = [
            "code" => $code,
            "message" => $message,
            "values" => $values,
        ];
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($value, "json");
        return new Response($jsonContent);
    }

    public function api_follow(
        Request $request,
        $user,
        $follower,
        $key_,
        $token
    ) {
        if ($token != $this->container->getParameter("token_app")) {
            throw new NotFoundHttpException("Page not found");
        }
        $code = 200;
        $message = "";
        $errors = [];

        $em = $this->entityManager;

        $user = $em->getRepository(User::class)->find($user);
        $follower = $em->getRepository(User::class)->find($follower);

        if ($user != null and $follower != null) {
            $followers = $user->getFollowers();
            $exists = false;
            foreach ($followers as $key => $f) {
                if ($f->getId() == $follower->getId()) {
                    $exists = true;
                }
            }
            if (sha1($follower->getPassword()) == $key_) {
                if ($exists) {
                    $user->removeFollower($follower);
                    $em->flush();
                    $code = 202;
                    $message = "You Unfollowing " . $user->getName();
                } else {
                    $user->addFollower($follower);
                    $em->flush();
                    $code = 200;
                    $message = "You following " . $user->getName();

                    $messageNotif = [
                        "type" => "user",
                        "id" => (string) $follower->getId(),
                        "name_user" => $follower->getName(),
                        "image_user" => $follower->getImage(),
                        "trusted_user" => (string) $follower->getTrusted(),
                        "title" => "New Follower",
                        "message" =>
                            $follower->getName() . " started follwing you.",
                        "icon" => $follower->getImage(),
                    ];

                    //$setting = $em->getRepository(Settings::class)->findOneBy(array());
                    // $key=$setting->getFirebasekey();

                    $accessToken = $this->getAccessToken();
                    $tokens = [$user->getToken()];

                    $message_status = $this->sendNotification(
                        $tokens,
                        $messageNotif,
                        $accessToken
                    );

                    //$tokens[]=$user->getToken();
                    //$message_status = $this->send_notificationToken($tokens,$messageNotif,$key);
                }
            } else {
                $code = 500;
                $message = "Request denied please check data usage (IK)";
            }
        } else {
            $code = 500;
            $message = "Request denied please check data usage (NU)";
        }
        $error = [
            "code" => $code,
            "message" => $message,
            "values" => [],
        ];
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($error, "json");
        return new Response($jsonContent);
    }

    public function api_follow_check(
        Request $request,
        $user,
        $follower,
        $key_,
        $token
    ) {
        if ($token != $this->container->getParameter("token_app")) {
            throw new NotFoundHttpException("Page not found");
        }
        $code = 200;
        $message = "";
        $errors = [];

        $em = $this->entityManager;

        $user = $em->getRepository(User::class)->find($user);
        $follower = $em->getRepository(User::class)->find($follower);

        if ($user != null and $follower != null) {
            $followers = $user->getFollowers();
            $exists = false;
            foreach ($followers as $key => $f) {
                if ($f->getId() == $follower->getId()) {
                    $exists = true;
                }
            }
            if (sha1($follower->getPassword()) == $key_) {
                if ($exists) {
                    $code = 200;
                    $message = "You Following " . $user->getName();
                } else {
                    $code = 202;
                    $message = "You Unfollowing " . $user->getName();
                }
            } else {
                $code = 500;
                $message = "Request denied please check data usage (IK)";
            }
        } else {
            $code = 500;
            $message = "Request denied please check data usage (NU)";
        }
        $error = [
            "code" => $code,
            "message" => $message,
            "values" => [],
        ];
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($error, "json");
        return new Response($jsonContent);
    }

    public function api_login($username, $password, $token)
    {
        if ($token != $this->container->getParameter("token_app")) {
            throw new NotFoundHttpException("Page not found");
        }
        $code = "200";
        $message = "";
        $errors = [];
        $em = $this->entityManager;
        $user = $em
            ->getRepository(User::class)
            ->findOneBy(["username" => $username]);

        if ($user) {
            $encoder_service = $this->get("security.encoder_factory");
            $encoder = $encoder_service->getEncoder($user);
            if (
                $encoder->isPasswordValid(
                    $user->getPassword(),
                    $password,
                    $user->getSalt()
                ) and !$user->hasRole("ROLE_ADMIN")
            ) {
                if ($user->isEnabled() == true) {
                    $code = 200;
                    $message = "You have successfully logged in";
                    $errors[] = ["name" => "id", "value" => $user->getId()];
                    $errors[] = ["name" => "name", "value" => $user->getName()];
                    $errors[] = ["name" => "type", "value" => $user->getType()];
                    $errors[] = [
                        "name" => "username",
                        "value" => $user->getUsername(),
                    ];
                    $errors[] = ["name" => "salt", "value" => $user->getSalt()];
                    $errors[] = [
                        "name" => "token",
                        "value" => sha1($user->getPassword()),
                    ];
                    if ($user->getMedia() == null) {
                        $errors[] = [
                            "name" => "url",
                            "value" => $this->imagineCacheManager->getBrowserPath(
                                "img/default_male.png",
                                "profile_picture"
                            ),
                        ];
                    } else {
                        $errors[] = [
                            "name" => "url",
                            "value" => $this->imagineCacheManager->getBrowserPath(
                                $user->getMedia()->getLink(),
                                "profile_picture"
                            ),
                        ];
                    }
                } else {
                    $message =
                        "Your account has been disabled by an administrator";
                    $code = 500;
                }
            } else {
                $code = 500;
                $message = "Invalid email address or password ";
            }
        } else {
            $code = 500;
            $message = "Invalid email address or password ";
        }
        $error = [
            "code" => $code,
            "message" => $message,
            "values" => $errors,
        ];
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($error, "json");
        return new Response($jsonContent);
    }

    public function api_edit(Request $request, $token)
    {
        if ($token != $this->container->getParameter("token_app")) {
            throw new NotFoundHttpException("Page not found");
        }
        $email = $request->get("email");
        $facebook = $request->get("facebook");
        $instagram = $request->get("instagram");
        $twitter = $request->get("twitter");
        $name = $request->get("name");
        $user = $request->get("user");
        $key = $request->get("key");

        $code = "200";
        $message = "";
        $errors = [];

        $em = $this->entityManager;

        $user = $em->getRepository(User::class)->find($user);

        if (!$user) {
            throw new NotFoundHttpException("Page not found");
        }
        if (sha1($user->getPassword()) == $key) {
            $user->setFacebook($facebook);
            $user->setTwitter($twitter);
            $user->setInstagram($instagram);
            $user->setEmailo($email);
            $user->setName($name);
            $em->flush();
            $code = 200;
            $message = "Your info has been edit successfully.";
        }
        $error = [
            "code" => $code,
            "message" => $message,
            "values" => $errors,
        ];
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($error, "json");
        return new Response($jsonContent);
    }

    public function api_token(Request $request, $token)
    {
        if ($token != $this->container->getParameter("token_app")) {
            throw new NotFoundHttpException("Page not found");
        }
        $token_f = $request->get("token_f");
        $user = $request->get("user");
        $key = $request->get("key");

        $code = "200";
        $message = "";
        $errors = [];

        $em = $this->entityManager;

        $user = $em->getRepository(User::class)->find($user);

        if (!$user) {
            throw new NotFoundHttpException("Page not found");
        }
        if (sha1($user->getPassword()) == $key) {
            $user->setToken($token_f);
            $em->flush();
            $code = 200;
            $message = "Your info has been updated successfully.";
        }
        $error = [
            "code" => $code,
            "message" => $message,
            "values" => $errors,
        ];
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($error, "json");
        return new Response($jsonContent);
    }

    public function api_code(Request $request, $token)
    {
        if ($token != $this->container->getParameter("token_app")) {
            throw new NotFoundHttpException("Page not found");
        }
        $ReferenceCode = $request->get("code");
        $user = $request->get("user");
        $key = $request->get("key");

        $code = "200";
        $message =
            "Thank You to Join us. The Reference code has been registered.";
        $errors = [];

        $em = $this->entityManager;

        $invited = $em->getRepository(User::class)->find($user);

        if (!$invited) {
            throw new NotFoundHttpException("Page not found");
        }
        if (sha1($invited->getPassword()) == $key) {
            $winner = $em
                ->getRepository(User::class)
                ->findOneBy(["code" => $ReferenceCode, "enabled" => true]);
            if ($winner) {
                $transaction = $em
                    ->getRepository(Transaction::class)
                    ->findOneBy(["invited" => $invited]);

                if ($transaction == null) {
                    $setting = $em
                        ->getRepository(Settings::class)
                        ->findOneBy([]);

                    $today = (new \DateTime("today"))->format("Y-m-d");
                    $data = $this->getReferralData();

                    // Clean up old records, retain only today's data
                    foreach ($data as $userId => $dates) {
                        foreach ($dates as $date => $count) {
                            if ($date !== $today) {
                                unset($data[$userId][$date]);

                                if (empty($data[$userId])) {
                                    unset($data[$userId]);
                                }
                            }
                        }
                    }

                    if (!isset($data[$winner->getId()][$today])) {
                        $data[$winner->getId()][$today] = 0;
                    }

                    if (
                        $data[$winner->getId()][$today] >= self::REFERRAL_LIMIT
                    ) {
                        $code = 200; // Too Many Requests
                        $message =
                            "They reached daily referral limit but your registration has been done.";
                    } else {
                        $tokens = $invited->getToken();

                        if (strlen($tokens) > 100) {
                            // Update daily referral count
                            $data[$winner->getId()][$today]++;
                            $this->saveReferralData($data);

                            //throw new NotFoundHttpException(json_encode($this->getReferralData(), JSON_PRETTY_PRINT)); die;

                            $transaction = new Trans();
                            $transaction->setLabel(
                                $invited->getName() .
                                    " has been registered by your reference code"
                            );
                            $transaction->setPoints(
                                $setting->getPoints("adduser")
                            );
                            $transaction->setInvited($invited);
                            $transaction->setUser($winner);
                            $transaction->setType("add_user");
                            $em->persist($transaction);
                            $em->flush();
                        } else {
                            $transaction = new Trans();
                            $transaction->setLabel(
                                "This user has been done a big scam!"
                            );
                            $transaction->setPoints(-500);
                            $transaction->setInvited($invited);
                            $transaction->setUser($winner);
                            $transaction->setType("add_user");
                            $em->persist($transaction);
                            $em->flush();
                        }
                    }

                    $transaction1 = new Trans();
                    $transaction1->setLabel(
                        "You have been registered by " .
                            $winner->getName() .
                            " reference code"
                    );
                    $transaction1->setPoints(
                        2 * $setting->getPoints("adduser")
                    );
                    $transaction1->setInvited($winner);
                    $transaction1->setUser($invited);
                    $transaction1->setType("add_user");
                    $em->persist($transaction1);
                    $em->flush();

                    $messageNotif = [
                        "type" => "user",
                        "id" => (string) $invited->getId(),
                        "name_user" => $invited->getName(),
                        "image_user" => $invited->getImage(),
                        "trusted_user" => (string) $invited->getTrusted(),
                        "title" => "New Registration",
                        "message" =>
                            $invited->getName() .
                            " has been registered by your reference code.",
                        "icon" => $invited->getImage(),
                    ];

                    // $setting = $em->getRepository(Settings::class)->findOneBy(array());
                    //  $key=$setting->getFirebasekey();

                    $accessToken = $this->getAccessToken();
                    $tokens = [$winner->getToken()];
                    $message_status = $this->sendNotification(
                        $tokens,
                        $messageNotif,
                        $accessToken
                    );

                    //  $tokens=$winner->getToken();
                    //  $message_status = $this->send_notificationToken($tokens,$messageNotif,$key);
                }
            } else {
                $code = 404;
                $message = "Reference code not found. Please try correct one!";
            }
        }
        $error = [
            "code" => $code,
            "message" => $message,
            "values" => $errors,
        ];
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($error, "json");
        return new Response($jsonContent);
    }

    private function getReferralData(): array
    {
        if (!file_exists(self::FILE_PATH)) {
            return [];
        }
        return json_decode(file_get_contents(self::FILE_PATH), true) ?: [];
    }

    private function saveReferralData(array $data): void
    {
        file_put_contents(self::FILE_PATH, json_encode($data));
    }

    public function api_dsem($user_id, $point_value, $offer_title)
    {
        $user = $user_id;
        $ReferenceCode = "MdMohsin";
        $code = "200";
        $message = "Thanks for participation.";
        $errors = [];

        $em = $this->entityManager;
        $invited = $em->getRepository(User::class)->find($user);

        if (!$invited) {
            throw new NotFoundHttpException("Page not found");
        }

        $winner = $em
            ->getRepository(User::class)
            ->findOneBy(["code" => $ReferenceCode, "enabled" => true]);
        if ($winner) {
            $transaction = $em
                ->getRepository(Transaction::class)
                ->findOneBy(["invited" => $invited]);

            if ($transaction == null) {
                $transaction = new Trans();
                $setting = $em->getRepository(Settings::class)->findOneBy([]);

                $transaction->setLabel(
                    $winner->getName() . " added points for " . $offer_title
                );

                $transaction->setPoints($point_value);
                $transaction->setInvited($invited);
                $transaction->setUser($winner);
                $transaction->setType("add_user");
                $em->persist($transaction);
                $em->flush();

                /*$messageNotif = array(
                            "type"=>"user",
                            "id"=>$invited->getId(),
                            "name_user"=>$invited->getName(),
                            "image_user"=>$invited->getImage(),
                            "trusted_user"=>$invited->getTrusted(),
                            "title"=>"New Registration",
                            "message"=>$invited->getName() ." has been registered by your reference code.",
                            "icon"=>$invited->getImage(),
                            );


                            $setting = $em->getRepository(Settings::class)->findOneBy(array());            
                            $key=$setting->getFirebasekey();
        
                            $tokens[]=$winner->getToken();
                            $message_status = $this->send_notificationToken($tokens,$messageNotif,$key); */
            }
        } else {
            $code = 404;
            $message = "You did not complete the task.";
        }

        $error = [
            "code" => $code,
            "message" => $message,
            "values" => $errors,
        ];
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($error, "json");
        return new Response($jsonContent);
    }

    public function api_register(Request $request, $token)
    {
        if ($token != $this->container->getParameter("token_app")) {
            throw new NotFoundHttpException("Page not found");
        }
        $username = $request->get("username");
        $password = $request->get("password");
        $name = $request->get("name");
        $type = $request->get("type");
        $image = $request->get("image");

        $code = "200";
        $message = "";
        $errors = [];
        $em = $this->entityManager;
        $u = $em->getRepository(User::class)->findOneByUsername($username);
        if ($u != null) {
            if ($u->getType() == "email") {
                $code = 500;
                $message = "this email address already exists";
                $errors[] = [
                    "name" => "username",
                    "value" => "this email address already exists",
                ];
            } else {
                $code = 200;
                $message = "You have successfully logged in";
                $u->setImage($image);
                $em->flush();
                $errors[] = ["name" => "id", "value" => $u->getId()];
                $errors[] = ["name" => "name", "value" => $u->getName()];
                $errors[] = [
                    "name" => "username",
                    "value" => $u->getUsername(),
                ];
                $errors[] = ["name" => "salt", "value" => $u->getSalt()];
                $errors[] = ["name" => "type", "value" => $u->getType()];
                $errors[] = [
                    "name" => "token",
                    "value" => sha1($u->getPassword()),
                ];
                $errors[] = ["name" => "url", "value" => $u->getImage()];
                $errors[] = ["name" => "enabled", "value" => $u->isEnabled()];
            }
        } else {
            $user = new User();
            if (count($errors) == 0) {
                $ReferenaceCode = str_pad(
                    dechex(mt_rand(0, 0xffffffff)),
                    8,
                    "0",
                    STR_PAD_LEFT
                );

                $user->setUsername($username);
                $user->setPlainPassword($password);
                $user->setEmail($username);
                $user->setEnabled(true);
                $user->setName($name);
                $user->setType($type);
                $user->setImage($image);
                $user->setCode($ReferenaceCode);
                $em->persist($user);
                $em->flush();
                $code = 200;
                $message = "You have successfully registered";
                $errors[] = ["name" => "id", "value" => $user->getId()];
                $errors[] = ["name" => "name", "value" => $user->getName()];
                $errors[] = [
                    "name" => "username",
                    "value" => $user->getUsername(),
                ];
                $errors[] = ["name" => "salt", "value" => $user->getSalt()];
                $errors[] = ["name" => "type", "value" => $user->getType()];
                $errors[] = [
                    "name" => "token",
                    "value" => sha1($user->getPassword()),
                ];
                $errors[] = ["name" => "url", "value" => $user->getImage()];
                $errors[] = [
                    "name" => "enabled",
                    "value" => $user->isEnabled(),
                ];
                $errors[] = ["name" => "registered", "value" => "true"];
            } else {
                $code = 500;
                $message = "validation error";
            }
        }
        $error = [
            "code" => $code,
            "message" => $message,
            "values" => $errors,
        ];
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($error, "json");
        return new Response($jsonContent);
    }

    public function api_change_password($id, $password, $new_password, $token)
    {
        if ($token != $this->container->getParameter("token_app")) {
            throw new NotFoundHttpException("Page not found");
        }

        $code = "200";
        $message = "";
        $errors = [];
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->findOneBy(["id" => $id]);
        if ($user->hasRole("ROLE_ADMIN")) {
            throw new NotFoundHttpException("Page not found");
        }
        if ($user->getType() != "email") {
            throw new NotFoundHttpException("Page not found");
        }
        if ($user) {
            $encoder_service = $this->get("security.encoder_factory");
            $encoder = $encoder_service->getEncoder($user);
            if (
                $encoder->isPasswordValid(
                    $user->getPassword(),
                    $password,
                    $user->getSalt()
                )
            ) {
                if (strlen($new_password) < 6) {
                    $code = 500;
                    $errors["password"] = "cette valeur est trop courte";
                } else {
                    $newPasswordEncoded = $encoder->encodePassword(
                        $new_password,
                        $user->getSalt()
                    );
                    $user->setPassword($newPasswordEncoded);
                    $em->persist($user);
                    $em->flush();
                    $code = 200;
                    $message = "Password has been changed successfully";
                    $errors[] = ["name" => "id", "value" => $user->getId()];
                    $errors[] = ["name" => "name", "value" => $user->getName()];
                    $errors[] = ["name" => "type", "value" => $user->getType()];
                    $errors[] = [
                        "name" => "username",
                        "value" => $user->getUsername(),
                    ];
                    $errors[] = ["name" => "salt", "value" => $user->getSalt()];
                    $errors[] = [
                        "name" => "token",
                        "value" => sha1($user->getPassword()),
                    ];
                }
            } else {
                $code = 500;
                $message = "Current password is incorrect";
            }
        }
        $error = [
            "code" => $code,
            "message" => $message,
            "values" => $errors,
        ];
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($error, "json");
        return new Response($jsonContent);
    }

    public function api_edit_name($id, $name, $key, $token)
    {
        if ($token != $this->container->getParameter("token_app")) {
            throw new NotFoundHttpException("Page not found");
        }
        sleep(2);
        $code = "200";
        $message = "";
        $errors = [];
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->findOneBy(["id" => $id]);
        if ($user->hasRole("ROLE_ADMIN")) {
            throw new NotFoundHttpException("Page not found");
        }
        if (sha1($user->getPassword()) != $key) {
            throw new NotFoundHttpException("Page not found");
        }
        if ($user) {
            $user->setName($name);
            $em->flush();
            $message = "Your information has been edit ";
            $code = "200";
        }
        $error = [
            "code" => $code,
            "message" => $message,
            "values" => $errors,
        ];
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($error, "json");
        return new Response($jsonContent);
    }

    public function api_check($id, $key, $token)
    {
        $code = "500";
        $message = "";
        $errors = [];
        if ($token != $this->container->getParameter("token_app")) {
            $code = 500;
        }

        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->findOneBy(["id" => $id]);

        if ($user) {
            if ($user->isEnabled()) {
                if ($key == sha1($user->getPassword())) {
                    $code = 200;
                } else {
                    $code = 500;
                }
            } else {
                $code = 500;
            }
            if ($user->hasRole("ROLE_ADMIN")) {
                $code = 500;
            }
        }

        $error = [
            "code" => $code,
            "message" => $message,
            "values" => $errors,
        ];
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($error, "json");
        return new Response($jsonContent);
    }

    public function api_upload(Request $request, $id, $key, $token)
    {
        $code = "200";
        $message = "Ok";
        $values = [];
        if ($token != $this->container->getParameter("token_app")) {
            throw new NotFoundHttpException("Page not found");
        }
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->findOneBy(["id" => $id]);
        if ($user->hasRole("ROLE_ADMIN")) {
            throw new NotFoundHttpException("Page not found");
        }
        if (sha1($user->getPassword()) != $key) {
            throw new NotFoundHttpException("Page not found");
        }
        if ($user) {
            if ($this->getRequest()->files->has("uploaded_file")) {
                $old_media = $user->getMedia();
                $media = new Media();
                $media->setFile(
                    $this->getRequest()->files->get("uploaded_file")
                );
                $media->upload(
                    $this->params->get('kernel.project_dir') . '/public/uploads'
                );
                $media->setEnabled(true);
                $em->persist($media);
                $em->flush();
                $user->setMedia($media);
                if ($old_media != null) {
                    $old_media->delete(
                        $this->params->get('kernel.project_dir') . '/public/uploads'
                    );
                    $em->remove($old_media);
                    $em->flush();
                }
                $em->flush();
                $values[] = [
                    "name" => "url",
                    "value" => $this->imagineCacheManager->getBrowserPath(
                        $media->getLink(),
                        "profile_picture"
                    ),
                ];
            }
        }
        $error = [
            "code" => $code,
            "message" => $message,
            "values" => $values,
        ];
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($error, "json");
        return new Response($jsonContent);
    }

    public function followers(Request $request, $id)
    {
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->find($id);
        if ($user == null) {
            throw new NotFoundHttpException("Page not found");
        }
        if ($user->hasRole("ROLE_ADMIN")) {
            throw new NotFoundHttpException("Page not found");
        }
        return $this->render("@UserBundle/User/followers.html.twig", [
            "user" => $user,
        ]);
    }

    public function followings(Request $request, $id)
    {
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->find($id);
        if ($user == null) {
            throw new NotFoundHttpException("Page not found");
        }
        if ($user->hasRole("ROLE_ADMIN")) {
            throw new NotFoundHttpException("Page not found");
        }
        return $this->render("@UserBundle/User/followings.html.twig", [
            "user" => $user,
        ]);
    }

    public function comments(Request $request, $id)
    {
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->find($id);
        if ($user == null) {
            throw new NotFoundHttpException("Page not found");
        }
        if ($user->hasRole("ROLE_ADMIN")) {
            throw new NotFoundHttpException("Page not found");
        }

        $dql =
            "SELECT c FROM App\AppBundle\Entity\Comment c  WHERE c.user = " .
            $user->getId();
        $query = $em->createQuery($dql);
        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt("page", 1), // current page number
            7 // number of items per page
        );
        return $this->render("@UserBundle/User/comments.html.twig", [
            "user" => $user,
            "pagination" => $pagination,
        ]);
    }

    public function api_followings(Request $request, $user, $token)
    {
        if ($token != $this->container->getParameter("token_app")) {
            throw new NotFoundHttpException("Page not found");
        }
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->find($user);
        if ($user == null) {
            throw new NotFoundHttpException("Page not found");
        }
        $users = [];
        foreach ($user->getUsers() as $key => $e) {
            $b["id"] = $e->getId();
            $b["name"] = $e->getName();
            $b["image"] = $e->getImage();
            $users[] = $b;
        }
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($users, "json");
        return new Response($jsonContent);
    }

    public function api_followers(Request $request, $user, $token)
    {
        if ($token != $this->container->getParameter("token_app")) {
            throw new NotFoundHttpException("Page not found");
        }
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->find($user);
        if ($user == null) {
            throw new NotFoundHttpException("Page not found");
        }
        $followers = [];
        foreach ($user->getFollowers() as $key => $f) {
            $a["id"] = $f->getId();
            $a["name"] = $f->getName();
            $a["image"] = $f->getImage();
            $followers[] = $a;
        }
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($followers, "json");
        return new Response($jsonContent);
    }

    public function api_search(Request $request, $query, $token)
    {
        if ($token != $this->container->getParameter("token_app")) {
            throw new NotFoundHttpException("Page not found");
        }
        $em = $this->entityManager;
        $repo = $em->getRepository(User::class);

        $qb = $repo
            ->createQueryBuilder("u")
            ->where("u.name like '%" . $query . "%'");

        $users_list = $qb->getQuery()->getResult();

        $users = [];
        foreach ($users_list as $key => $f) {
            $a["id"] = $f->getId();
            $a["name"] = $f->getName();
            $a["image"] = $f->getImage();
            $users[] = $a;
        }
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($users, "json");
        return new Response($jsonContent);
    }

    public function status(Request $request, $id)
    {
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->find($id);
        if ($user == null) {
            throw new NotFoundHttpException("Page not found");
        }
        if ($user->hasRole("ROLE_ADMIN")) {
            throw new NotFoundHttpException("Page not found");
        }

        $dql =
            "SELECT w FROM AppBundle:Status w  WHERE w.user = " .
            $user->getId() .
            " ORDER BY w.created desc";
        $query = $em->createQuery($dql);
        $paginator = $this->get("knp_paginator");
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt("page", 1),
            9
        );
        return $this->render("@UserBundle/User/status.html.twig", [
            "pagination" => $pagination,
            "user" => $user,
        ]);
    }

    public function api_followingstop(Request $request, $user, $token)
    {
        if ($token != $this->container->getParameter("token_app")) {
            throw new NotFoundHttpException("Page not found");
        }
        if ($token != $this->container->getParameter("token_app")) {
            throw new NotFoundHttpException("Page not found");
        }
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->find($user);
        if ($user == null) {
            throw new NotFoundHttpException("Page not found");
        }
        $users = [];
        foreach ($user->getUsers() as $key => $e) {
            if (sizeof($e->getStatus()) > 0) {
                $b["id"] = $e->getId();
                $b["name"] = $e->getName();
                $b["trusted"] = $e->getTrusted();
                $b["image"] = $e->getImage();
                $last_wallpaper = $em
                    ->getRepository("AppBundle:Status")
                    ->findOneBy(
                        ["enabled" => true, "user" => $e],
                        ["created" => "desc"]
                    );
                if ($last_wallpaper != null) {
                    $b["status"] = $last_wallpaper;
                    $users[] = $b;
                }
            }
        }
        return $this->render("@UserBundle/User/api_export.html.php", [
            "users" => $users,
        ]);
    }
}
