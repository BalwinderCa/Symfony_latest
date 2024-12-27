<?php 

namespace App\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ResettingController  extends AbstractController
{
    private $entityManager;
    private $tokenGenerator;
    private CacheManager $imagineCacheManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        CacheManager $imagineCacheManager
    )
    {
        $this->entityManager = $entityManager;
        $this->imagineCacheManager = $imagineCacheManager;
    }

    const SESSION_EMAIL = 'fos_user_send_resetting_email/email';

    public function api_emailAction($email, $token)
    {
        if ($token !== $this->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");
        }

        $code = "200";
        $message = "A new activation key email has successfully been sent";
        $errors = [];
        $username = $email;
        $user = $this->userManager->findUserByUsernameOrEmail($username);

        if (null === $user) {
            $code = "500";
            $message = "There is no account for this email address";
        } else {
            if ($user->isPasswordRequestNonExpired($this->getParameter('fos_user.resetting.token_ttl'))) {
                $code = "500";
                $message = "You cannot reset the password just now as the maximum number of invalid login attempts has been reached. Try again in 24 hours";
            } elseif ($user->hasRole("ROLE_ADMIN")) {
                $code = "500";
                $message = "There is no account for this email address";
            } elseif ($user->getType() !== "email") {
                $code = "500";
                $message = "There is no account for this email address";
            } else {
                $tkn = $this->tokenGenerator->generateToken();
                if (null === $user->getConfirmationToken()) {
                    $user->setConfirmationToken($tkn);
                }
                $this->get('session')->set(static::SESSION_EMAIL, $this->getObfuscatedEmail($user));

                // Email sending logic (as is in your original code)
                // Assuming email sending logic is correct as in your original code
                // Make sure mail() function is properly configured in your server

                $user->setPasswordRequestedAt(new \DateTime());
                $this->userManager->updateUser($user);
            }
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

    public function api_requestAction($key, $token)
    {
        if ($token !== $this->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");
        }

        $code = "200";
        $message = "Reset your password";
        $errors = [];
        $user = $this->userManager->findUserByConfirmationToken($key);

        if (null === $user) {
            $code = "500";
            $message = "There is no account for this key";
        } elseif ($user->hasRole("ROLE_ADMIN") || $user->getType() !== "email") {
            $code = "500";
            $message = "There is no account for this key";
        } else {
            $code = "200";
            $message = "Reset your password";
            $errors[] = ["name" => "id", "value" => $user->getId()];
            $errors[] = ["name" => "token", "value" => sha1($user->getPassword())];
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

    public function api_resetAction($id, $key, $new_password, $token)
    {
        if ($token !== $this->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");
        }

        $code = "200";
        $message = "";
        $errors = [];
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->findOneBy(["id" => $id]);

        if ($user) {
            if (sha1($user->getPassword()) !== $key || $user->hasRole("ROLE_ADMIN") || $user->getType() !== "email") {
                $code = "500";
                $message = "There is no account for this key";
            } elseif (strlen($new_password) < 6) {
                $code = 500;
                $message = "Password is too short";
            } else {
                $encoder_service = $this->get('security.password_encoder');
                $newPasswordEncoded = $encoder_service->encodePassword($user, $new_password);
                $user->setPassword($newPasswordEncoded);
                $user->setConfirmationToken(null);
                $user->setPasswordRequestedAt(null);
                $em->persist($user);
                $em->flush();

                $code = 200;
                $message = "Password has been reset successfully";
                $errors[] = ["name" => "id", "value" => $user->getId()];
                $errors[] = ["name" => "name", "value" => $user->getName()];
                $errors[] = ["name" => "type", "value" => $user->getType()];
                $errors[] = ["name" => "username", "value" => $user->getUsername()];
                $errors[] = ["name" => "salt", "value" => $user->getSalt()];
                $errors[] = ["name" => "token", "value" => sha1($user->getPassword())];
            }
        } else {
            $code = "500";
            $message = "There is no account for this key";
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

    protected function getObfuscatedEmail(UserInterface $user)
    {
        $email = $user->getEmail();
        if (false !== $pos = strpos($email, '@')) {
            $email = '...' . substr($email, $pos);
        }

        return $email;
    }
}
