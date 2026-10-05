<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Test\TestCase\Infrastructure;

use Camoo\Http\Curl\Domain\Entity\Configuration;
use Camoo\Http\Curl\Infrastructure\Client;
use Camoo\Http\Curl\Infrastructure\MultiCurl;
use Camoo\Http\Curl\Infrastructure\Request;
use PHPUnit\Framework\TestCase;

class RealCurlIntegrationTest extends TestCase
{
    private static mixed $serverProcess = null;

    private static string $baseUrl = '';

    public static function setUpBeforeClass(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($socket === false) {
            throw new \RuntimeException(sprintf('Failed to bind server socket: %s (%d)', $errstr, $errno));
        }
        $name = stream_socket_get_name($socket, false);
        $port = (int)parse_url('tcp://' . $name, PHP_URL_PORT);
        fclose($socket);

        self::$baseUrl = sprintf('http://127.0.0.1:%d', $port);

        $serverScript = dirname(__DIR__, 2) . '/Fixture/server.php';
        $cmd = sprintf('php -S 127.0.0.1:%d %s', $port, escapeshellarg($serverScript));
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        self::$serverProcess = proc_open($cmd, $descriptors, $pipes);

        $startTime = microtime(true);
        $ready = false;
        while (microtime(true) - $startTime < 3.0) {
            $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if ($fp !== false) {
                fclose($fp);
                $ready = true;
                break;
            }
            usleep(10000);
        }

        if (!$ready) {
            self::tearDownAfterClass();
            throw new \RuntimeException('Test server failed to start within 3 seconds.');
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$serverProcess)) {
            proc_terminate(self::$serverProcess, 15);
            $startTime = microtime(true);
            while (microtime(true) - $startTime < 1.0) {
                $status = proc_get_status(self::$serverProcess);
                if (!$status['running']) {
                    break;
                }
                usleep(20000);
            }
            $status = proc_get_status(self::$serverProcess);
            if ($status['running']) {
                proc_terminate(self::$serverProcess, 9);
            }
            proc_close(self::$serverProcess);
            self::$serverProcess = null;
        }
    }

    public function testRealGetRequest(): void
    {
        $client = new Client();
        $response = $client->get(self::$baseUrl);

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getJson();
        $this->assertSame(['status' => 'ok'], $data);
    }

    public function testRealPostRequest(): void
    {
        $client = new Client();
        $response = $client->post(self::$baseUrl . '/post', ['hello' => 'world']);

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getJson();
        $this->assertSame(['received' => 'hello=world'], $data);
    }

    public function testRealPostJsonRequest(): void
    {
        $client = new Client();
        $response = $client->post(
            self::$baseUrl . '/post',
            ['hello' => 'world'],
            ['Content-Type' => 'application/json']
        );

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getJson();
        $this->assertSame(['received' => json_encode(['hello' => 'world'])], $data);
    }

    public function testRealRedirectFollowing(): void
    {
        $config = (new Configuration())->setFollowRedirects(true)->setMaxRedirects(5);
        $client = new Client($config);

        $response = $client->get(self::$baseUrl . '/redirect');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['redirected' => true], $response->getJson());
    }

    public function testRealAuthentication(): void
    {
        $config = (new Configuration())
            ->setUsername('testuser')
            ->setPassword('testpass');
        $client = new Client($config);

        $response = $client->get(self::$baseUrl . '/auth');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['authenticated' => true], $response->getJson());
    }

    public function testRealRepeatedRequestExecution(): void
    {
        $config = (new Configuration())
            ->setUsername('testuser')
            ->setPassword('testpass');

        $request = new Request($config, self::$baseUrl . '/auth', [], [], 'GET');

        $client = new Client();

        // First execution with real cURL
        $response1 = $client->sendRequest($request);
        $this->assertSame(200, $response1->getStatusCode());
        $this->assertSame(['authenticated' => true], $response1->getJson());

        // Reusing same Request object for second execution
        $response2 = $client->sendRequest($request);
        $this->assertSame(200, $response2->getStatusCode());
        $this->assertSame(['authenticated' => true], $response2->getJson());
    }

    public function testRealMultiCurlRequests(): void
    {
        $multi = new MultiCurl();
        $p1 = $multi->get(self::$baseUrl);
        $p2 = $multi->get(self::$baseUrl . '/redirect');

        $responses = $multi->send();

        $this->assertCount(2, $responses);
        $this->assertSame(200, $p1->wait()->getStatusCode());
        $this->assertSame(200, $p2->wait()->getStatusCode());
    }
}
