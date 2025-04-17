<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:check-reset-password-table',
    description: 'Check if the reset password request table exists and is properly configured',
)]
class CheckResetPasswordTableCommand extends Command
{
    public function __construct(
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $tableExists = $this->connection->executeQuery("SHOW TABLES LIKE 'reset_password_request'")->rowCount() > 0;
            
            if ($tableExists) {
                $output->writeln('✅ Reset password request table exists');
                
                // Check table structure
                $columns = $this->connection->executeQuery("SHOW COLUMNS FROM reset_password_request")->fetchAllAssociative();
                $output->writeln("\nTable structure:");
                foreach ($columns as $column) {
                    $output->writeln(sprintf(
                        '- %s: %s %s',
                        $column['Field'],
                        $column['Type'],
                        $column['Null'] === 'YES' ? '(nullable)' : '(not null)'
                    ));
                }
            } else {
                $output->writeln('❌ Reset password request table does not exist');
                $output->writeln("\nPlease run the following command to create the table:");
                $output->writeln('php bin/console doctrine:schema:update --force');
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('❌ Error checking table: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 