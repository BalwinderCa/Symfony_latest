<?php

namespace App\UserBundle\Service;

use App\UserBundle\Entity\User;
use App\UserBundle\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;
use Psr\Log\LoggerInterface;  // Add LoggerInterface

class PasswordResetService
{
    private MailerInterface $mailer;
    private RouterInterface $router;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private LoggerInterface $logger;  // Add LoggerInterface property

    public function __construct(
        MailerInterface $mailer,
        RouterInterface $router,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger  // Inject LoggerInterface
    ) {
        $this->mailer = $mailer;
        $this->router = $router;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->logger = $logger;  // Assign the logger
    }

    public function sendResetEmail(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $this->logger->warning('Password reset request: No user found for email ' . $email);  // Log when user is not found
            return; // Handle error or success silently
        }

        // Generate password reset token
        $token = bin2hex(random_bytes(16));
        $user->setPasswordResetToken($token);

        $this->entityManager->flush();

        // Generate the reset URL
        $resetUrl = $this->router->generate('app_password_reset', ['token' => $token], RouterInterface::ABSOLUTE_URL);

        // Send email with the reset link
        $emailMessage = (new Email())
            ->from('no-reply@gmail.com')
            ->to($user->getEmail())
            ->subject('Password Reset Request')
            ->html('<p>To reset your password, click the following link: <a href="' . $resetUrl . '">Reset Password</a></p>');

        try {
            $this->mailer->send($emailMessage);
            $this->logger->info('Password reset email sent to ' . $user->getEmail());  // Log success
            $this->logger->info('Password reset email sent to ' . $resetUrl);  // Log success
        } catch (\Exception $e) {
            $this->logger->error('Failed to send password reset email to ' . $user->getEmail() . ': ' . $e->getMessage());  // Log failure
        }
    }

    public function resetPassword(string $token, string $newPassword): void
    {
        $user = $this->userRepository->findOneBy(['passwordResetToken' => $token]);

        if (!$user) {
            $this->logger->warning('Password reset attempt with invalid token: ' . $token);  // Log invalid token usage
            throw new \Exception('Invalid token');
        }

        // Hash the new password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword); // Corrected method
        $user->setPassword($hashedPassword);
        $user->setPasswordResetToken(null); // Clear reset token

        $this->entityManager->flush();

        $this->logger->info('Password successfully reset for user ' . $user->getEmail());  // Log successful password reset
    }

}
