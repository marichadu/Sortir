<?php

namespace App\EventSubscriber;

use App\Entity\Participant;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Templating\EngineInterface;

class SortieNotificationSubscriber
{
    private $twig;
    private $mailer;

    public function __construct(EngineInterface $twig, MailerInterface $mailer)
    {
        $this->twig = $twig;
        $this->mailer = $mailer;
    }

    private function sendNotificationEmail(Participant $user, string $subject, string $template, array $context = []): void
    {
        $email = (new Email())
            ->from('noreply@sortir.com')
            ->to('marichadu5@gmail.com') // Always send to your email for testing
            ->subject($subject)
            ->html(
                $this->twig->render($template, array_merge($context, ['user' => $user]))
            );

        $this->mailer->send($email);
    }
} 