<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

final class TestMailerController extends AbstractController
{

    #[Route('/_test-mail', name: 'app_test_mail')]
    public function testMail(MailerInterface $mailer): Response
    {
        try {
            $email = (new Email())
                ->from(new Address('julien-dev@alwaysdata.net', 'InnovShop')) // expéditeur réel
                ->to('baylejulien14@gmail.com')                                 // destinataire réel
                ->replyTo('julien-dev@alwaysdata.net')                        // optionnel
                ->subject('Test d’envoi Symfony via AlwaysData')
                ->html('<p>Ceci est un test ✅</p>');

            $mailer->send($email);
            return new Response('✅ Mail de test envoyé');
        } catch (\Throwable $e) {
            return new Response('❌ Échec: ' . $e->getMessage(), 500);
        }
    }
}
