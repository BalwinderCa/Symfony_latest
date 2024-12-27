<?php

namespace App\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use App\AppBundle\Entity\Support;
use App\AppBundle\Entity\Status;
use App\AppBundle\Entity\Category;
use App\AppBundle\Entity\Device;
use App\AppBundle\Entity\Settings;
use App\AppBundle\Forms\SettingsType;
use App\AppBundle\Entity\Withdraw;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

class HomeController extends AbstractController
{   

    private $entityManager;
    private CacheManager $imagineCacheManager;

    // Inject the EntityManagerInterface into the controller
    public function __construct(EntityManagerInterface $entityManager,CacheManager $imagineCacheManager)
    {
        $this->entityManager = $entityManager;
        $this->imagineCacheManager = $imagineCacheManager;
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

   
    public function notifCategory(Request $request)
    {
        // Get peak memory usage (optional)
        memory_get_peak_usage();

        // EntityManager
        $em = $this->entityManager;

        // Fetch categories and devices
        $categories = $em->getRepository(Category::class)->findAll();
        $devices = $em->getRepository(Device::class)->findAll();

        // Extract device tokens
        $tokens = array_map(fn($device) => $device->getToken(), $devices);

        // Prepare the form
        $defaultData = [];
        $form = $this->createFormBuilder($defaultData)
            ->setMethod('GET')
            ->add('title', TextType::class)
            ->add('message', TextareaType::class)
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'title', // Adjust this to the property you want to display
                'label' => 'Select Category',
                'placeholder' => 'Choose a category',
            ])
            ->add('icon', UrlType::class, [
                'label' => 'Large Icon',
                'required' => false,
            ])
            ->add('image', UrlType::class, [
                'label' => 'Big Picture',
                'required' => false,
            ])
            ->add('send', SubmitType::class, [
                'label' => 'Send Notification',
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Fetch submitted data
            $data = $form->getData();

            // Find the selected category
            $categorySelected = $em->getRepository(Category::class)->find($data['category']);

            // Build notification message
            $message = [
                'type' => 'category',
                'id' => $categorySelected->getId(),
                'title_category' => $categorySelected->getTitle(),
                'video_category' => $imagineCacheManager->getBrowserPath(
                    $categorySelected->getMedia()->getLink(),
                    'category_thumb_api'
                ),
                'title' => $data['title'],
                'message' => $data['message'],
                'image' => $data['image'],
                'icon' => $data['icon'],
            ];

            // Fetch Firebase key from settings
            $setting = $em->getRepository(Settings::class)->findOneBy([]);
            $key = $setting->getFirebasekey();

            // Send notification
            $this->send_notification(null, $message, $key);

            // Add a success flash message
            $this->addFlash('success', 'Operation has been done successfully');
        }

        return $this->render('@AppBundle/Home/notif_category.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function notifUrl(Request $request)
    {
        // EntityManager
        $em = $this->entityManager;
    
        // Default data for the form
        $defaultData = [];
    
        // Create the form
        $form = $this->createFormBuilder($defaultData)
            ->setMethod('GET')
            ->add('title', TextType::class, [
                'label' => 'Notification Title',
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Notification Message',
            ])
            ->add('url', UrlType::class, [
                'label' => 'Target URL',
            ])
            ->add('icon', UrlType::class, [
                'label' => 'Large Icon',
                'required' => false,
            ])
            ->add('image', UrlType::class, [
                'label' => 'Big Picture',
                'required' => false,
            ])
            ->add('send', SubmitType::class, [
                'label' => 'Send Notification',
            ])
            ->getForm();
    
        $form->handleRequest($request);
    
        // Handle form submission
        if ($form->isSubmitted() && $form->isValid()) {
            // Get submitted data
            $data = $form->getData();
    
            // Build notification message
            $message = [
                'type' => 'link',
                'id' => strlen($data['url']),
                'link' => $data['url'],
                'title' => $data['title'],
                'message' => $data['message'],
                'image' => $data['image'],
                'icon' => $data['icon'],
            ];
    
            // Retrieve Firebase key
            $setting = $em->getRepository(Settings::class)->findOneBy([]);
            $key = $setting->getFirebasekey();
    
            // Send the notification
            $this->send_notification(null, $message, $key);
    
            // Add success message
            $this->addFlash('success', 'Operation has been done successfully');
        }
    
        // Render the form in the view
        return $this->render('@AppBundle/Home/notif_url.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    


    public function notifStatus(Request $request)
    {
        $defaultData = array();
        $form = $this->createFormBuilder($defaultData)
            ->setMethod('GET')
            ->add('title', TextType::class)
            ->add('message', TextareaType::class)
            ->add('object', EntityType::class, [
                'class' => Status::class, // The class of the entity to be selected
                'choice_label' => 'title', // You can adjust the field to display as options
            ])
            ->add('icon', UrlType::class, array("label" => "Large Icon", "required" => false))
            ->add('image', UrlType::class, array("label" => "Big Picture", "required" => false))
            ->add('send', SubmitType::class, array("label" => "Send notification"))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $selected_status = $this->entityManager->getRepository(Status::class)->find($data["object"]);

            $original = "";
            $thumbnail = "";
            $type = "";
            $extension = "";
            $color = "";

            if ($selected_status->getType() != "quote") {
                if ($selected_status->getVideo()) {
                    $type = $selected_status->getVideo()->getType();
                    $extension = $selected_status->getVideo()->getExtension();
                } else {
                    $type = $selected_status->getMedia()->getType();
                    $extension = $selected_status->getMedia()->getExtension();
                }

                $thumbnail = $this->imagineCacheManager->getBrowserPath($selected_status->getMedia()->getLink(), 'status_thumb_api');

                if ($selected_status->getVideo()) {
                    if ($selected_status->getVideo()->getEnabled()) {
                        $original = $this->getRequest()->getUriForPath("/" . $selected_status->getVideo()->getLink());
                    } else {
                        $original = $selected_status->getVideo()->getLink();
                    }
                } else {
                    $original = $this->getRequest()->getUriForPath("/" . $selected_status->getMedia()->getLink());
                }
            } else {
                $color = $selected_status->getColor();
            }

            $message = array(
                "type" => "status",
                "kind" => $selected_status->getType(),
                "id" => $selected_status->getId(),
                "status_title" => $selected_status->getTitle(),
                "status_description" => $selected_status->getDescription(),
                "status_review" => $selected_status->getReview(),
                "status_comment" => $selected_status->getComment(),
                "status_comments" => sizeof($selected_status->getComments()),
                "status_downloads" => $selected_status->getDownloads(),
                "status_views" => $selected_status->getViews(),
                "status_font" => $selected_status->getFont(),
                "status_user" => $selected_status->getUser()->getName(),
                "status_userid" => $selected_status->getUser()->getId(),
                "status_userimage" => $selected_status->getUser()->getImage(),
                "status_type" => $type,
                "status_extension" => $extension,
                "status_thumbnail" => $thumbnail,
                "status_original" => $original,
                "status_color" => $color,
                "status_created" => "Now",
                "status_tags" => $selected_status->getTags(),
                "status_like" => $selected_status->getLike(),
                "status_love" => $selected_status->getLove(),
                "status_woow" => $selected_status->getWoow(),
                "status_angry" => $selected_status->getAngry(),
                "status_sad" => $selected_status->getSad(),
                "status_haha" => $selected_status->getHaha(),
                "title" => $data["title"],
                "message" => $data["message"],
                "image" => $data["image"],
                "icon" => $data["icon"]
            );

            $setting = $this->entityManager->getRepository(Settings::class)->findOneBy(array());
            $key = $setting->getFirebasekey();
            $message_image = $this->send_notification(null, $message, $key);

            $this->addFlash('success', 'Operation has been done successfully');
        }

        return $this->render('@AppBundle/Home/notif_status.html.twig', array("form" => $form->createView()));
    }

    public function notifUserStatus(Request $request, CacheManager $imagineCacheManager)
    {
        $statusId = $request->query->get('status_id');
        $em = $this->entityManager;
    
        // Fetch the status
        $selectedStatus = $em->getRepository(Status::class)->find($statusId);
        if (!$selectedStatus) {
            throw $this->createNotFoundException('Status not found.');
        }
    
        $defaultData = [
            'object' => $statusId,
        ];
    
        // Build the form
        $form = $this->createFormBuilder($defaultData)
            ->setMethod('GET')
            ->add('title', TextType::class, ['label' => 'Notification Title'])
            ->add('object', HiddenType::class)
            ->add('message', TextareaType::class, ['label' => 'Notification Message'])
            ->add('icon', UrlType::class, ['label' => 'Large Icon', 'required' => false])
            ->add('image', UrlType::class, ['label' => 'Big Picture', 'required' => false])
            ->add('send', SubmitType::class, ['label' => 'Send Notification'])
            ->getForm();
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $user = $selectedStatus->getUser();
    
            if (!$user) {
                throw $this->createNotFoundException('User not found.');
            }
    
            // Prepare notification tokens
            $tokens = [$user->getToken()];
    
            // Generate thumbnail and original media paths
            $thumbnail = $selectedStatus->getMedia() 
                ? $imagineCacheManager->getBrowserPath($selectedStatus->getMedia()->getLink(), 'status_thumb_api') 
                : null;
            $original = $selectedStatus->getMedia() 
                ? $this->getParameter('kernel.project_dir') . '/' . $selectedStatus->getMedia()->getLink() 
                : null;
    
            // Build the notification message
            $message = [
                'type' => 'status',
                'title' => $data['title'],
                'message' => $data['message'],
                'thumbnail' => $thumbnail,
                'original' => $original,
                'icon' => $data['icon'],
                'image' => $data['image'],
            ];
    
            // Retrieve Firebase key
            $setting = $em->getRepository(Settings::class)->findOneBy([]);
            $key = $setting->getFirebasekey();
    
            // Send notification
            $this->send_notificationToken($tokens, $message, $key);
    
            $this->addFlash('success', 'Notification sent successfully!');
            return $this->redirectToRoute('app_status_index');
        }
    
        return $this->render('@AppBundle/Home/notif_user_status.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    

    public function notifUserPayment(Request $request, EntityManagerInterface $em): Response
    {
        $payment = $request->query->get('withdraw');

        $defaultData = [];
        $form = $this->createFormBuilder($defaultData)
            ->setMethod('GET')
            ->add('title', TextType::class)
            ->add('object', HiddenType::class, [
                'attr' => ['value' => $payment],
            ])
            ->add('message', TextareaType::class)
            ->add('icon', UrlType::class, ['label' => 'Large Icon', 'required' => false])
            ->add('image', UrlType::class, ['label' => 'Big Picture', 'required' => false])
            ->add('send', SubmitType::class, ['label' => 'Send notification'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $withdrawal = $em->getRepository(Withdraw::class)->find($data['object']);

            if (!$withdrawal) {
                throw $this->createNotFoundException('Withdrawal not found.');
            }

            $user = $withdrawal->getUser();
            if (!$user) {
                throw new NotFoundHttpException('User not found.');
            }

            // Prepare notification tokens (assuming user has token)
            $tokens = [$user->getToken()];

            $message = [
                'id' => (string) $withdrawal->getId(),
                'type' => 'payment',
                'title' => $data['title'],
                'message' => $data['message'],
                'icon' => $data['icon'],
                'image' => $data['image'],
            ];

            // Assuming you have a service to send notifications
            $accessToken = $this->getAccessToken();
            $response = $this->sendNotification($tokens, $message, $accessToken);

            $this->addFlash('success', 'Notification has been sent successfully!');
            return $this->redirectToRoute('app_payment_withdrawal');
        }

        return $this->render('@AppBundle/Home/notif_user_payment.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    public function settings(Request $request)
    {   
        $em=$this->entityManager;
        $settings=$em->getRepository(Settings::class)->findOneBy(array());
        if ($settings==null) {
            throw new NotFoundHttpException("Page not found");
        }
        $form = $this->createForm(SettingsType::class,$settings);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Operation has been done successfully');
        }
        return $this->render('@AppBundle/Home/settings.html.twig',array("form"=>$form->createView()));
    }  

    public function index(Request $request): Response
    {
        // Use the injected entity manager
        $em = $this->entityManager;

        // Get counts using the repository
        $supports_count = $em->getRepository(\App\AppBundle\Entity\Support::class)->count([]);
        $devices_count = $em->getRepository(\App\AppBundle\Entity\Device::class)->count([])??0;
        $video_count = $em->getRepository(\App\AppBundle\Entity\Status::class)->count(['type' => 'video']);
        $image_count = $em->getRepository(\App\AppBundle\Entity\Status::class)->count(['type' => 'image']);
        $gif_count = $em->getRepository(\App\AppBundle\Entity\Status::class)->count(['type' => 'gif']);
        $quote_count = $em->getRepository(\App\AppBundle\Entity\Status::class)->count(['type' => 'quote']);
        $withdrawals_count = $em->getRepository(\App\AppBundle\Entity\Withdraw::class)->count([]);
        $review_count = $em->getRepository(\App\AppBundle\Entity\Status::class)->countReview(); // Custom method, ensure it exists
        $count_downloads = $em->getRepository(\App\AppBundle\Entity\Status::class)->countDownloads(); // Custom method, ensure it exists
        $count_views = $em->getRepository(\App\AppBundle\Entity\Status::class)->countViews(); // Custom method, ensure it exists

        $category_count = $em->getRepository(\App\AppBundle\Entity\Category::class)->count([]);
        $comment_count = $em->getRepository(\App\AppBundle\Entity\Comment::class)->count([]);
        $language_count = $em->getRepository(\App\AppBundle\Entity\Language::class)->count([]);
        $version_count = $em->getRepository(\App\AppBundle\Entity\Version::class)->count([]);
        $users = $em->getRepository(\App\UserBundle\Entity\User::class)->findAll();
        $users_count = count($users) - 1; // Adjust count to exclude 1 user (perhaps the admin?)

        // Render the response
        return $this->render('@AppBundle/Home/index.html.twig', [
            "count_views" => $count_views,
            "count_downloads" => $count_downloads,
            "withdrawals_count" => $withdrawals_count,
            "devices_count" => $devices_count,
            "video_count" => $video_count,
            "image_count" => $image_count,
            "gif_count" => $gif_count,
            "quote_count" => $quote_count,
            "category_count" => $category_count,
            "review_count" => $review_count,
            "users_count" => $users_count,
            "comment_count" => $comment_count,
            "version_count" => $version_count,
            "language_count" => $language_count,
            "supports_count" => $supports_count,
        ]);
    }
    public function api_device($tkn,$token){
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }
        $code="200";
        $message="";
        $errors=array();
        $em = $this->entityManager;
        $d=$em->getRepository(Device::class)->findOneBy(array("token"=>$tkn));
        if ($d==null) {
            $device = new Device();
            $device->setToken($tkn);
            $em->persist($device);
            $em->flush();
            $message="Deivce added";
        }else{
            $message="Deivce Exist";
        }

        $error=array(
            "code"=>$code,
            "message"=>$message,
            "values"=>$errors
        );
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent=$serializer->serialize($error, 'json');
        return new Response($jsonContent);
    }
}