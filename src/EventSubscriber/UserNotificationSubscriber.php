<?php

namespace App\EventSubscriber;

use App\Entity\Participant;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class UserNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private Environment $twig
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::preRemove,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Participant) {
            return;
        }

        $this->sendNotificationEmail(
            $entity,
            'Création de compte',
            'email/user_notification.html.twig',
            ['action' => 'created']
        );
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Participant) {
            return;
        }

        $changes = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($entity);
        
        if (isset($changes['actif'])) {
            $subject = $changes['actif'][1] ? 'Réactivation de compte' : 'Désactivation de compte';
            $action = $changes['actif'][1] ? 'reactivated' : 'deactivated';
            $this->sendNotificationEmail($entity, $subject, 'email/user_notification.html.twig', ['action' => $action]);
        } else {
            $this->sendNotificationEmail(
                $entity,
                'Modification de profil',
                'email/user_notification.html.twig',
                ['action' => 'updated', 'changes' => $changes]
            );
        }
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Participant) {
            return;
        }

        $this->sendNotificationEmail(
            $entity,
            'Suppression de compte',
            'email/user_notification.html.twig',
            ['action' => 'deleted']
        );
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

    private function renderEmailTemplate(Participant $user, string $subject, string $message): string
    {
        return sprintf('
            <!DOCTYPE html>
            <html>
                <head>
                    <meta charset="UTF-8">
                    <title>%s</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .footer { margin-top: 30px; font-size: 12px; color: #666; }
                        .warning { font-size: 12px; color: #666; margin-top: 20px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h2>%s</h2>
                        <p>Bonjour %s,</p>
                        <p>%s</p>
                        <div class="footer">
                            <p>Cordialement,<br>L\'équipe Sortir.com</p>
                            <p class="warning">Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                            <p class="warning">© 2025 Sortir.com - Tous droits réservés</p>
                        </div>
                    </div>
                </body>
            </html>
        ', $subject, $subject, $user->getPrenom(), $message);
    }
} 