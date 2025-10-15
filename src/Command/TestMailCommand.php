<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'app:test:mail')]
class TestMailCommand extends Command
{
    public function __construct(private MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (new Email())
            ->from('no-reply@example.test')
            ->to('you@example.test') // mets une adresse que tu peux consulter dans Mailtrap (Inbox → Recipients acceptés)
            ->subject('Test Mailer')
            ->html('<p>Ça marche 🎉</p>');

        $this->mailer->send($email);
        $output->writeln('Email envoyé');
        return Command::SUCCESS;
    }
}
