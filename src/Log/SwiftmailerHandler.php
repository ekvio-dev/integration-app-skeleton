<?php
declare(strict_types=1);

namespace Ekvio\Integration\Skeleton\Log;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use RuntimeException;
use Swift_SmtpTransport;
use Swift_Message;
use Swift_Mailer;
use Throwable;

/**
 * Class SwiftmailerHandler
 * @package Ekvio\Integration\Skeleton\Log
 */
class SwiftmailerHandler extends AbstractProcessingHandler
{
    private SwiftmailerCredential $credential;
    private SwiftmailerMessage $message;
    /**
     * SwiftmailerHandler constructor.
     * @param SwiftmailerCredential $credential
     * @param SwiftmailerMessage $message
     * @param int $level
     * @param bool $bubble
     */
    public function __construct(SwiftmailerCredential $credential, SwiftmailerMessage $message, $level = Logger::DEBUG, bool $bubble = true)
    {
        $this->assertSwiftMailer();
        parent::__construct($level, $bubble);
        $this->credential = $credential;
        $this->message = $message;
    }

    private function assertSwiftMailer(): void
    {
        if(!in_array('Swift', get_declared_classes(), true)) {
            throw new RuntimeException('SwiftMailer not installed');
        }
    }

    /**
     * @param array $record
     * @noinspection PhpUndefinedClassInspection
     * @noinspection PhpUndefinedMethodInspection
     */
    protected function write(array $record): void
    {
        $transport = (new Swift_SmtpTransport($this->credential->host(), $this->credential->port(), $this->credential->encryption()))
            ->setUsername($this->credential->user())
            ->setPassword($this->credential->password());

        $mailer = new Swift_Mailer($transport);

        $message = (new Swift_Message($this->message->title()))
            ->setFrom($this->message->from())
            ->setTo($this->message->to())
            ->setBody($record['message']);

        try {
            if($mailer->send($message) === false) {
                fwrite(STDOUT, sprintf('Can not send email with logs to: %s', implode(',', $this->message->to())));
            }
        } catch (Throwable $exception) {
            fwrite(STDOUT, $exception->getMessage());
        }
    }

    /**
     * @param array $records
     */
    public function handleBatch(array $records): void
    {
        $aggregate['message'] = '';
        foreach ($records as $record) {
            $message = sprintf("[%s][%s]%s\n", $record['level_name'], $record['datetime'], $record['message']);
            $aggregate['message'] = sprintf("%s%s", $aggregate['message'], $message);
            $aggregate['context'] = $record['context'];
            $aggregate['level'] = $record['level'];
            $aggregate['level_name'] = $record['level_name'];
            $aggregate['datetime'] = $record['datetime'];
            $aggregate['extra'] = $record['extra'];
        }
        $this->handle($aggregate);
    }
}