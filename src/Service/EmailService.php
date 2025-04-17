<?php

namespace App\Service;

use App\Entity\Participant;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig
    ) {}

    public function sendWelcomeEmail(Participant $user, string $password): void
    {
        $email = (new Email())
            ->from('noreply@sortir.com')
            ->to('marichadu5@gmail.com')
            ->subject('Bienvenue sur Sortir.com')
            ->html(
                $this->twig->render('email/welcome.html.twig', [
                    'user' => $user,
                    'password' => $password
                ])
            );

        $this->mailer->send($email);
    }
} 