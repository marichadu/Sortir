<?php

namespace App\Service;

use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class SortieService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ?MailerInterface $mailer = null,
        private ?LoggerInterface $logger = null
    ) {}

    public function register(Sortie $sortie, Participant $participant): void
    {
        if (!$sortie->canRegister($participant)) {
            throw new \RuntimeException('Inscription impossible à cette sortie.');
        }

        $sortie->addParticipant($participant);
        $this->entityManager->flush();

        // Send confirmation email if mailer is available
        try {
            if ($this->mailer) {
                $this->sendRegistrationEmail($sortie, $participant);
            }
        } catch (\Exception $e) {
            // Log the error but don't stop the registration process
            if ($this->logger) {
                $this->logger->error('Failed to send registration email: ' . $e->getMessage());
            }
        }
    }

    public function unregister(Sortie $sortie, Participant $participant): void
    {
        if (!$sortie->canUnregister($participant)) {
            throw new \RuntimeException('Désinscription impossible de cette sortie.');
        }

        $sortie->removeParticipant($participant);
        $this->entityManager->flush();

        // Send confirmation email if mailer is available
        try {
            if ($this->mailer) {
                $this->sendUnregistrationEmail($sortie, $participant);
            }
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Failed to send unregistration email: ' . $e->getMessage());
            }
        }
    }

    public function cancel(Sortie $sortie, string $motif, Participant $canceller): void
    {
        // Check if user is organizer or admin
        if ($sortie->getOrganisateur() !== $canceller && !in_array('ROLE_ADMIN', $canceller->getRoles())) {
            throw new \RuntimeException('Vous n\'avez pas les droits pour annuler cette sortie.');
        }

        if (!$sortie->canCancel()) {
            throw new \RuntimeException('Cette sortie ne peut pas être annulée.');
        }

        $sortie->cancel($motif);
        $this->entityManager->flush();

        // Notify all participants if mailer is available
        if ($this->mailer) {
            foreach ($sortie->getParticipants() as $participant) {
                try {
                    $this->sendCancellationEmail($sortie, $participant);
                } catch (\Exception $e) {
                    if ($this->logger) {
                        $this->logger->error('Failed to send cancellation email: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    private function sendRegistrationEmail(Sortie $sortie, Participant $participant): void
    {
        if (!$this->mailer) {
            return;
        }

        // For testing purposes, send to your email if the user's email is a test domain
        $toEmail = $participant->getEmail();
        if (str_ends_with($toEmail, '@campus-eni.fr')) {
            $toEmail = 'marichadu5@gmail.com';
        }

        $email = (new Email())
            ->from('noreply@sortir.com')
            ->to($toEmail)
            ->subject('Confirmation d\'inscription - ' . $sortie->getNom())
            ->html($this->renderRegistrationEmail($sortie, $participant));

        try {
            $this->mailer->send($email);
            if ($this->logger) {
                $this->logger->info('Registration email sent successfully', [
                    'sortie_id' => $sortie->getId(),
                    'participant_id' => $participant->getId(),
                    'email' => $toEmail
                ]);
            }
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Failed to send registration email', [
                    'sortie_id' => $sortie->getId(),
                    'participant_id' => $participant->getId(),
                    'email' => $toEmail,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function sendUnregistrationEmail(Sortie $sortie, Participant $participant): void
    {
        if (!$this->mailer) {
            return;
        }

        // For testing purposes, send to your email if the user's email is a test domain
        $toEmail = $participant->getEmail();
        if (str_ends_with($toEmail, '@campus-eni.fr')) {
            $toEmail = 'marichadu5@gmail.com';
        }

        $email = (new Email())
            ->from('noreply@sortir.com')
            ->to($toEmail)
            ->subject('Confirmation de désinscription - ' . $sortie->getNom())
            ->html($this->renderUnregistrationEmail($sortie, $participant));

        try {
            $this->mailer->send($email);
            if ($this->logger) {
                $this->logger->info('Unregistration email sent successfully', [
                    'sortie_id' => $sortie->getId(),
                    'participant_id' => $participant->getId(),
                    'email' => $toEmail
                ]);
            }
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Failed to send unregistration email', [
                    'sortie_id' => $sortie->getId(),
                    'participant_id' => $participant->getId(),
                    'email' => $toEmail,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function sendCancellationEmail(Sortie $sortie, Participant $participant): void
    {
        if (!$this->mailer) {
            return;
        }

        // For testing purposes, send to your email if the user's email is a test domain
        $toEmail = $participant->getEmail();
        if (str_ends_with($toEmail, '@campus-eni.fr')) {
            $toEmail = 'marichadu5@gmail.com';
        }

        $email = (new Email())
            ->from('noreply@sortir.com')
            ->to($toEmail)
            ->subject('Annulation de sortie - ' . $sortie->getNom())
            ->html($this->renderCancellationEmail($sortie, $participant));

        try {
            $this->mailer->send($email);
            if ($this->logger) {
                $this->logger->info('Cancellation email sent successfully', [
                    'sortie_id' => $sortie->getId(),
                    'participant_id' => $participant->getId(),
                    'email' => $toEmail
                ]);
            }
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Failed to send cancellation email', [
                    'sortie_id' => $sortie->getId(),
                    'participant_id' => $participant->getId(),
                    'email' => $toEmail,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function renderRegistrationEmail(Sortie $sortie, Participant $participant): string
    {
        return "
            <h1>Confirmation d'inscription</h1>
            <p>Bonjour {$participant->getPrenom()},</p>
            <p>Votre inscription à la sortie \"{$sortie->getNom()}\" a bien été enregistrée.</p>
            <p>Date: {$sortie->getDateHeureDebut()->format('d/m/Y H:i')}</p>
            <p>Lieu: {$sortie->getLieu()->getNom()}</p>
        ";
    }

    private function renderUnregistrationEmail(Sortie $sortie, Participant $participant): string
    {
        return "
            <h1>Confirmation de désinscription</h1>
            <p>Bonjour {$participant->getPrenom()},</p>
            <p>Votre désinscription de la sortie \"{$sortie->getNom()}\" a bien été enregistrée.</p>
        ";
    }

    private function renderCancellationEmail(Sortie $sortie, Participant $participant): string
    {
        return "
            <h1>Annulation de sortie</h1>
            <p>Bonjour {$participant->getPrenom()},</p>
            <p>La sortie \"{$sortie->getNom()}\" a été annulée.</p>
            <p>Motif: {$sortie->getMotifAnnulation()}</p>
        ";
    }
} 