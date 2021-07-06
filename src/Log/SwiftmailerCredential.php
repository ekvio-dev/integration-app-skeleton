<?php
declare(strict_types=1);

namespace Ekvio\Integration\Skeleton\Log;

use RuntimeException;

/**
 * Class SwiftmailerCredential
 * @package Ekvio\Integration\Skeleton\Log
 */
class SwiftmailerCredential
{
    private const ALLOW_ENCRYPTION = ['tls', 'ssl'];
    private string $host;
    private string $user;
    private string $password;
    private int $port;
    private ?string $encryption;

    /**
     * SwiftmailerCredential constructor.
     * @param string $host
     * @param string $user
     * @param string $password
     * @param int $port
     */
    public function __construct(string $host, string $user, string $password, int $port = 25, ?string $encryption = null)
    {
        $this->assertString($host, 'Swiftmailer host not set');
        $this->host = $host;

        $this->assertString($user, 'Swiftmailer user not set');
        $this->user = $user;

        $this->assertString($password, 'Swiftmailer password not set');
        $this->password = $password;

        $this->port = $port;

        $this->assertEncryption($encryption, 'Swiftmailer allow encryption type: tls, ssl');
        $this->encryption = $encryption;
    }

    /**
     * @return string
     */
    public function host(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function user(): string
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function password(): string
    {
        return $this->password;
    }

    /**
     * @return int
     */
    public function port(): int
    {
        return $this->port;
    }

    /**
     * @return string|null
     */
    public function encryption(): ?string
    {
        return $this->encryption;
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
     * @param string|null $encryption
     * @param string $message
     */
    private function assertEncryption(?string $encryption, string $message): void
    {
        if(is_null($encryption)) {
            return;
        }

        if(!in_array($encryption, self::ALLOW_ENCRYPTION, true)) {
            throw new RuntimeException($message);
        }
    }
}