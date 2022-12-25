<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Domain\Entity;

final class Configuration
{
    private const DEFAULT_TIMEOUT = 30;

    private const USER_AGENT = 'Camoo/Curl/Http/1.0 (+https://www.camoo.hosting)';

    private const DEBUG_FILE = 'php://output';

    private string $tempFolderPath;

    private static ?self $defaultConfiguration = null;

    /** Constructor */
    public function __construct(
        private int $timeout = self::DEFAULT_TIMEOUT,
        private string $username = '',
        private string $password = '',
        private ?string $referer = null,
        private string $userAgent = self::USER_AGENT,
        private bool $debug = false,
        private string $debugFile = self::DEBUG_FILE,
    ) {
        $this->tempFolderPath = sys_get_temp_dir();
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Sets the username for HTTP basic authentication
     *
     * @param string $username Username for HTTP basic authentication
     *
     * @return $this
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Gets the username for HTTP basic authentication
     *
     * @return string Username for HTTP basic authentication
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Sets the password for HTTP basic authentication
     *
     * @param string $password Password for HTTP basic authentication
     *
     * @return $this
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Gets the password for HTTP basic authentication
     *
     * @return string Password for HTTP basic authentication
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Sets the host
     *
     * @param string $host Host
     *
     * @return $this
     */
    public function setReferer(string $host): self
    {
        $this->referer = $host;

        return $this;
    }

    /**
     * Gets the host
     *
     * @return ?string Referer
     */
    public function getReferer(): ?string
    {
        return $this->referer;
    }

    /**
     * Sets the user agent of the api client
     *
     * @param string $userAgent the user agent of the api client
     *
     * @return $this
     */
    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Gets the user agent of the api client
     *
     * @return string user agent
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * Sets debug flag
     *
     * @param bool $debug Debug flag
     *
     * @return $this
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;

        return $this;
    }

    /** Gets the debug flag */
    public function getDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Sets the debug file
     *
     * @param string $debugFile Debug file
     *
     * @return $this
     */
    public function setDebugFile(string $debugFile): self
    {
        $this->debugFile = $debugFile;

        return $this;
    }

    /** Gets the debug file */
    public function getDebugFile(): string
    {
        return $this->debugFile;
    }

    /**
     * Sets the temp folder path
     *
     * @param string $tempFolderPath Temp folder path
     *
     * @return $this
     */
    public function setTempFolderPath(string $tempFolderPath): self
    {
        $this->tempFolderPath = $tempFolderPath;

        return $this;
    }

    /**
     * Gets the temp folder path
     *
     * @return string Temp folder path
     */
    public function getTempFolderPath(): string
    {
        return $this->tempFolderPath;
    }

    /** Gets the default configuration instance */
    public static function create(): self
    {
        if (self::$defaultConfiguration === null) {
            self::$defaultConfiguration = new self();
        }

        return self::$defaultConfiguration;
    }

    /**
     * Sets the default configuration instance
     *
     * @param self $config An instance of the Configuration Object
     */
    public static function setDefaultConfiguration(Configuration $config): void
    {
        self::$defaultConfiguration = $config;
    }

    /**
     * Gets the essential information for debugging
     *
     * @return string The report for debugging
     */
    public static function toDebugReport(): string
    {
        $report = 'PHP Client (Camoo\Curl\Http) Debug Report:' . PHP_EOL;
        $report .= '    OS: ' . php_uname() . PHP_EOL;
        $report .= '    PHP Version: ' . PHP_VERSION . PHP_EOL;

        return $report . ('    Temp Folder Path: ' . self::create()->getTempFolderPath() . PHP_EOL);
    }
}
