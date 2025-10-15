<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class TestMailerController extends AbstractController
{
    #[Route('/_test-mail', name: 'app_test_mail')]
    public function testMail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('no-reply@votresite.fr')
            ->to('ton@email@test') // ⚠️ mets ton adresse Mailtrap ici
            ->subject('Test d’envoi Symfony')
            ->text('Ceci est un test');

        $mailer->send($email);

        return new Response('✅ Mail de test envoyé');
    }
}
