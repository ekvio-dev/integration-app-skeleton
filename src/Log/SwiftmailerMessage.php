<?php
declare(strict_types=1);

namespace Ekvio\Integration\Skeleton\Log;

use RuntimeException;

/**
 * Class SwiftmailerMessage
 * @package Ekvio\Integration\Skeleton\Log
 */
class SwiftmailerMessage
{
    private string $title;
    private string $from;
    private array $to;

    /**
     * SwiftmailerMessage constructor.
     * @param string $title
     * @param string $from
     * @param array $to
     */
    public function __construct(string $title, string $from, array $to)
    {
        $this->assertString($title, 'Email title required');
        $this->title = $title;

        $this->assertEmail([$from], 'Invalid FROM email format');
        $this->from = $from;

        $this->assertEmail($to, 'Invalid TO email format');
        $this->to = $to;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function from(): string
    {
        return $this->from;
    }

    /**
     * @return array
     */
    public function to(): array
    {
        return $this->to;
    }

    /**
     * @param string $word
     * @param string $message
     */
    private function assertString(string $word, string $message): void
    {
        if(mb_strlen($word) === 0) {
            throw new RuntimeException($message);
        }
    }

    /**
     * @param array $emails
     * @param string $message
     */
    private function assertEmail(array $emails, string $message): void
    {
        foreach ($emails as $email) {
            if(filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new RuntimeException(sprintf($message . ': %s', $email));
            }
        }
    }
}