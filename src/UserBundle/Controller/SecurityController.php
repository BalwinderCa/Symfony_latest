<?php

namespace App\UserBundle\Controller;

use App\UserBundle\Form\PasswordResetRequestType;
use App\UserBundle\Form\PasswordResetType;
use App\UserBundle\Service\PasswordResetService;
use App\UserBundle\Form\ChangePasswordType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;

class SecurityController extends AbstractController
{
    // Login action
    #[Route('/login', name: 'app_login')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@UserBundle/Security/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    // Password reset request
    #[Route('/password-reset', name: 'app_password_reset_request')]
    public function passwordResetRequest(Request $request, PasswordResetService $resetService): Response
    {
        $form = $this->createForm(PasswordResetRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $resetService->sendResetEmail($form->get('email')->getData());
            $this->addFlash('success', 'Password reset email sent!');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('@UserBundle/Resetting/password_reset_request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Password reset with token
    #[Route('/password-reset/{token}', name: 'app_password_reset')]
    public function passwordReset(Request $request, string $token, PasswordResetService $resetService): Response
    {
        $form = $this->createForm(PasswordResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $resetService->resetPassword($token, $form->get('password')->getData());
            $this->addFlash('success', 'Password successfully reset!');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('@UserBundle/Resetting/reset_content.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Change password (for logged-in user)
    #[Route('/change-password', name: 'app_change_password')]
    public function changePassword(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'The new passwords do not match.');
                return $this->redirectToRoute('app_change_password');
            }

            // Hash the new password
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            // Save changes
            $entityManager->flush();
            
            $this->addFlash('success', 'Password has been updated successfully!');
            return $this->redirectToRoute('app_home_index');
        }

        return $this->render('@UserBundle/ChangePassword/changePassword_content.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Logout action
    #[Route('/logout', name: 'app_logout')]
    public function logout(Security $security): RedirectResponse
    {
        $response = $security->logout();
        return $this->redirectToRoute('app_home_index');
    }
}