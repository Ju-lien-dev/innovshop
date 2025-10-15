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

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['POST', 'GET'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        AuthenticationUtils $authenticationUtils // pour repasser last_username au template
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($request->isMethod('GET')) {
            // on ne sert pas une autre page : on renvoie vers /login
            return $this->redirectToRoute('app_login', ['tab' => 'signup']);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Compte créé. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        // Si erreurs → on réutilise le MÊME template que /login
        return $this->render('security/login.html.twig', [
            'last_username'    => $authenticationUtils->getLastUsername(),
            'error'            => null,
            'registrationForm' => $form->createView(),
            'activeTab'        => 'signup', // pour cocher l’onglet "S’enregistrer"
        ]);
    }
}
