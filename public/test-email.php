<?php

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../.env');

// Get mailer DSN from environment
$mailerDsn = $_ENV['MAILER_DSN'] ?? '';

if (empty($mailerDsn)) {
    die("Error: MAILER_DSN not found in .env file\n");
}

// Create the Transport
$transport = Transport::fromDsn($mailerDsn);

// Create the Mailer
$mailer = new Mailer($transport);

// Create the email with a custom display name
$email = (new Email())
    ->from(new Address('marichadu5@gmail.com', 'Sortir.com Admin'))  // Custom display name
    ->to('marichadu5@gmail.com')
    ->subject('Test Email from Sortir.com')
    ->text('This is a test email from Sortir.com!')
    ->html('<p>This is a <strong>test email</strong> from Sortir.com!</p>');

try {
    $mailer->send($email);
    echo "Email sent successfully!\n";
    echo "Check your Gmail inbox for the test email.\n";
} catch (\Exception $e) {
    echo "Error sending email: " . $e->getMessage() . "\n";
    echo "\nMake sure you:\n";
    echo "1. Have enabled 2-Step Verification in your Google Account\n";
    echo "2. Generated an App Password for your Gmail account\n";
    echo "3. Replaced YOUR_GMAIL_ADDRESS and YOUR_APP_PASSWORD in the script\n";
} 