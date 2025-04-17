<?php

namespace App\Mailer;

use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class FileTransport extends AbstractTransport
{
    private string $logFile;

    public function __construct(?string $logFile = null)
    {
        parent::__construct();
        $this->logFile = $logFile ?? sys_get_temp_dir() . '/mail.log';
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        
        $logEntry = sprintf(
            "[%s] From: %s, To: %s, Subject: %s\nContent: %s\n\n",
            date('Y-m-d H:i:s'),
            $email->getFrom()[0]->getAddress(),
            $email->getTo()[0]->getAddress(),
            $email->getSubject(),
            $email->getHtmlBody() ?? $email->getTextBody()
        );

        // Ensure the directory exists
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Append the email to the log file
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }

    public function __toString(): string
    {
        return 'file://default';
    }
} 