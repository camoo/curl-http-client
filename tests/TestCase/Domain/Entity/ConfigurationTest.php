<?php

namespace Camoo\Http\Curl\Test\TestCase\Domain\Entity;

use Camoo\Http\Curl\Domain\Entity\Configuration;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testGetTimeout()
    {
        $configuration = new Configuration();
        $this->assertSame(30, $configuration->getTimeout());
    }

    public function testSetTimeout()
    {
        $configuration = new Configuration();
        $newConfiguration = $configuration->setTimeout(60);

        $this->assertSame(60, $newConfiguration->getTimeout());
    }

    public function testGetUsername()
    {
        $configuration = new Configuration();
        $this->assertSame('', $configuration->getUsername());
    }

    public function testSetUsername()
    {
        $configuration = new Configuration();
        $newConfiguration = $configuration->setUsername('testuser');

        $this->assertSame('testuser', $newConfiguration->getUsername());
    }

    public function testGetPassword()
    {
        $configuration = new Configuration();
        $this->assertSame('', $configuration->getPassword());
    }

    public function testSetPassword()
    {
        $configuration = new Configuration();
        $newConfiguration = $configuration->setPassword('testpassword');

        $this->assertSame('testpassword', $newConfiguration->getPassword());
    }

    public function testGetReferer()
    {
        $configuration = new Configuration();
        $this->assertNull($configuration->getReferer());
    }

    public function testSetReferer(): void
    {
        $configuration = new Configuration();
        $newConfiguration = $configuration->setReferer('https://example.com');

        $this->assertSame('https://example.com', $newConfiguration->getReferer());
    }

    public function testGetUserAgent(): void
    {
        $configuration = new Configuration();
        $this->assertSame('Camoo/Curl/Http/1.0 (+https://www.camoo.hosting)', $configuration->getUserAgent());
    }

    public function testSetUserAgent(): void
    {
        $configuration = new Configuration();
        $newConfiguration = $configuration->setUserAgent('Custom User Agent');

        $this->assertSame('Custom User Agent', $newConfiguration->getUserAgent());
    }

    public function testGetDebug(): void
    {
        $configuration = new Configuration();
        $this->assertFalse($configuration->getDebug());
    }

    public function testSetDebug(): void
    {
        $configuration = new Configuration();
        $newConfiguration = $configuration->setDebug(true);

        $this->assertTrue($newConfiguration->getDebug());
    }

    public function testGetDebugFile(): void
    {
        $configuration = new Configuration();
        $this->assertSame('php://output', $configuration->getDebugFile());
    }

    public function testSetDebugFile(): void
    {
        $configuration = new Configuration();
        $newConfiguration = $configuration->setDebugFile('/path/to/debug.log');

        $this->assertSame('/path/to/debug.log', $newConfiguration->getDebugFile());
    }

    public function testGetTempFolderPath(): void
    {
        $configuration = new Configuration();
        $this->assertSame(sys_get_temp_dir(), $configuration->getTempFolderPath());
    }

    public function testSetTempFolderPath(): void
    {
        $configuration = new Configuration();
        $newConfiguration = $configuration->setTempFolderPath('/path/to/temp/folder');

        $this->assertSame('/path/to/temp/folder', $newConfiguration->getTempFolderPath());
    }

    public function testCreate(): void
    {
        $configuration = Configuration::create();
        $this->assertInstanceOf(Configuration::class, $configuration);
    }

    public function testSetDefaultConfiguration(): void
    {
        $configuration = new Configuration(45);
        Configuration::setDefaultConfiguration($configuration);

        $created = Configuration::create();
        $this->assertEquals($configuration, $created);
        $this->assertSame(45, $created->getTimeout());
        $this->assertNotSame($configuration, $created);
    }

    public function testToDebugReport(): void
    {
        $expectedReport = 'PHP Client (Camoo\Curl\Http) Debug Report:' . PHP_EOL;
        $expectedReport .= '    OS: ' . php_uname() . PHP_EOL;
        $expectedReport .= '    PHP Version: ' . PHP_VERSION . PHP_EOL;
        $expectedReport .= '    Temp Folder Path: ' . sys_get_temp_dir() . PHP_EOL;

        $this->assertSame($expectedReport, Configuration::toDebugReport());
    }

    public function testGetAndSetFollowRedirects(): void
    {
        $configuration = new Configuration();
        $this->assertTrue($configuration->getFollowRedirects());
        $newConfiguration = $configuration->setFollowRedirects(false);
        $this->assertFalse($newConfiguration->getFollowRedirects());
    }

    public function testGetAndSetMaxRedirects(): void
    {
        $configuration = new Configuration();
        $this->assertSame(10, $configuration->getMaxRedirects());
        $newConfiguration = $configuration->setMaxRedirects(5);
        $this->assertSame(5, $newConfiguration->getMaxRedirects());
    }

    public function testGetAndSetUnrestrictedAuth(): void
    {
        $configuration = new Configuration();
        $this->assertFalse($configuration->getUnrestrictedAuth());
        $newConfiguration = $configuration->setUnrestrictedAuth(true);
        $this->assertTrue($newConfiguration->getUnrestrictedAuth());
    }
}
