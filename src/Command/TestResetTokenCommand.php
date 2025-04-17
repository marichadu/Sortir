<?php

namespace App\Command;

use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[AsCommand(
    name: 'app:test-reset-token',
    description: 'Test the reset token generation with increased memory limit',
)]
class TestResetTokenCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ResetPasswordHelperInterface $resetPasswordHelper
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Increase memory limit for this command
        ini_set('memory_limit', '256M');

        try {
            $output->writeln('Checking database connection...');
            
            // Test database connection
            $this->entityManager->getConnection()->connect();
            $output->writeln('✅ Database connection successful');

            // Get all users
            $users = $this->entityManager->getRepository(Participant::class)->findAll();
            $output->writeln(sprintf('Found %d users in database', count($users)));

            if (empty($users)) {
                $output->writeln('❌ No users found in the database');
                return Command::FAILURE;
            }

            // Try with each user
            foreach ($users as $user) {
                $output->writeln("\nTesting with user: " . $user->getEmail());
                
                try {
                    $output->writeln('Generating reset token...');
                    $resetToken = $this->resetPasswordHelper->generateResetToken($user);
                    $output->writeln('✅ Token generated successfully');
                    $output->writeln('Token: ' . $resetToken->getToken());
                    return Command::SUCCESS;
                } catch (\Exception $e) {
                    $output->writeln('❌ Error generating token: ' . $e->getMessage());
                    $output->writeln('Error class: ' . get_class($e));
                    $output->writeln('Stack trace:');
                    $output->writeln($e->getTraceAsString());
                }
            }

            return Command::FAILURE;
        } catch (\Exception $e) {
            $output->writeln('❌ General error: ' . $e->getMessage());
            $output->writeln('Stack trace:');
            $output->writeln($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
} 