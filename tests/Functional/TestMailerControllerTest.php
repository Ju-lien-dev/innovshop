<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Mailer\Transport\InMemoryTransport;

class TestMailerControllerTest extends WebTestCase
{
    use MailerAssertionsTrait;

    public function test_it_sends_one_email(): void
    {
        $client = static::createClient();

        /** @var InMemoryTransport $transport */
        $transport = static::getContainer()->get('mailer.transport');
        $transport->reset();

        $client->request('GET', '/_test-mail');

        $this->assertResponseIsSuccessful();
        $this->assertEmailCount(1);

        $email = $this->getMailerMessage(); // index 0 par défaut
        $this->assertEmailHeaderSame($email, 'to', 'baylejulien14@gmail.com');
        $this->assertEmailHeaderSame($email, 'from', 'julien-dev@alwaysdata.net');
        $this->assertEmailSubjectContains($email, 'Test d’envoi Symfony');
        $this->assertEmailHtmlBodyContains($email, 'Ceci est un test');
    }
}
