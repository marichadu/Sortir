<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use App\Repository\CampusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ParticipantController extends AbstractController
{
    #[Route('/profile', name: 'app_participant_profile')]
    public function profile(): Response
    {
        $participant = $this->getUser();
        if (!$participant instanceof Participant) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('participant/profile.html.twig', [
            'participant' => $participant,
        ]);
    }

    #[Route('/profile/edit', name: 'app_participant_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        CampusRepository $campusRepository,
        SluggerInterface $slugger
    ): Response {
        $participant = $this->getUser();
        if (!$participant instanceof Participant) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ParticipantType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('plainPassword')->getData()) {
                $participant->setPassword(
                    $passwordHasher->hashPassword(
                        $participant,
                        $form->get('plainPassword')->getData()
                    )
                );
            }

            // Handle photo upload
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de la photo.');
                    return $this->redirectToRoute('app_participant_edit');
                }

                $participant->setPhoto($newFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

            return $this->redirectToRoute('app_participant_profile');
        }

        return $this->render('participant/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/participant/{id}', name: 'app_participant_show')]
    public function show(Participant $participant): Response
    {
        return $this->render('participant/show.html.twig', [
            'participant' => $participant,
        ]);
    }
} 