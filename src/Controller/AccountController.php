<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Form\ChangePasswordType;
use App\Repository\CommandeRepository; // 👈 ajout
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Adresse;
use App\Form\AdresseType;
use Symfony\Component\HttpFoundation\RedirectResponse;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class AccountController extends AbstractController
{
    #[Route('/mon-compte', name: 'app_account', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        EntityManagerInterface $em,
        CommandeRepository $orders // 👈 ajout
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // --- Form profil inchangé ---
        $form = $this->createForm(ProfileType::class, $user, ['method' => 'POST']);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour.');
            return $this->redirectToRoute('app_account');
        }

        // commandes du client
        // “À venir” = payée / en préparation / expédiée
        $upcoming = $orders->createQueryBuilder('c')
            ->andWhere('c.user = :u')->setParameter('u', $user)
            ->andWhere('c.status IN (:st)')
            ->setParameter('st', ['paid', 'processing', 'shipped'])
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()->getResult();

        // “Passées” = livrée / remboursée / annulée
        $past = $orders->createQueryBuilder('c2')
            ->andWhere('c2.user = :u')->setParameter('u', $user)
            ->andWhere('c2.status IN (:st)')
            ->setParameter('st', ['delivered', 'refunded', 'cancelled'])
            ->orderBy('c2.createdAt', 'DESC')
            ->getQuery()->getResult();

        // “Terminées” = terminé (on accepte plusieurs libellés possibles)
        $done = $orders->createQueryBuilder('c3')
            ->andWhere('c3.user = :u')->setParameter('u', $user)
            ->andWhere('LOWER(c3.status) IN (:st)')
            ->setParameter('st', ['terminé', 'termine', 'livrée', 'delivered', 'done'])
            ->orderBy('c3.createdAt', 'DESC')
            ->getQuery()->getResult();

        return $this->render('account/profile.html.twig', [
            'form'            => $form->createView(),
            'upcomingOrders'  => $upcoming, // 👈 passé au template
            'pastOrders'      => $past,     // 👈 passé au template
            'doneOrders'      => $done,    // 👈 passé au template
        ]);
    }

    #[Route('/mon-compte/mot-de-passe', name: 'app_account_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            $user->setPassword($hasher->hashPassword($user, $plain));
            $em->flush();

            $this->addFlash('success', 'Mot de passe mis à jour.');
            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/mon-compte/adresse/ajouter', name: 'app_account_address_add')]
    public function addAddress(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $adresse = new Adresse();
        $form = $this->createForm(AdresseType::class, $adresse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $adresse->setUser($user);
            $em->persist($adresse);
            $em->flush();
            $this->addFlash('success', 'Adresse ajoutée avec succès.');
            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/address_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Ajouter une adresse',
        ]);
    }

    #[Route('/mon-compte/adresse/{id}/modifier', name: 'app_account_address_edit')]
    public function editAddress(Adresse $adresse, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $adresse->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(AdresseType::class, $adresse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Adresse mise à jour.');
            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/address_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier une adresse',
        ]);
    }

    #[Route('/mon-compte/adresse/{id}/supprimer', name: 'app_account_address_delete', methods: ['POST'])]
    public function deleteAddress(Adresse $adresse, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || $adresse->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_address_' . $adresse->getId(), $request->request->get('_token'))) {
            $em->remove($adresse);
            $em->flush();
            $this->addFlash('success', 'Adresse supprimée.');
        }

        return $this->redirectToRoute('app_account');
    }
}
