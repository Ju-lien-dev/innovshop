<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Security\LoginFormAuthenticator;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;



class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['POST', 'GET'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $authenticator // <-- injection de ton authenticator
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($request->isMethod('GET')) {
            // Redirection vers la page d’inscription
            return $this->redirectToRoute('app_login', ['tab' => 'signup']);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash du mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            // ✅ Notification
            $this->addFlash('success', 'Bienvenue ' . ($user->getPrenom() ?: '') . '! Votre compte a bien été créé.');

            // ✅ Connexion automatique et redirection (souvent vers la page d’accueil)
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        // En cas d’erreur
        return $this->render('security/login.html.twig', [
            'registrationForm' => $form->createView(),
            'activeTab'        => 'signup',
        ]);
    }
}
