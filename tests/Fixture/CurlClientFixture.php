<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Test\Fixture;

final class CurlClientFixture
{
    private const DEFAULT_HTTP_CODE = 200;

    public function __construct(private string $url, private int $httpCode = self::DEFAULT_HTTP_CODE)
    {
    }

    public function getResponse(): string
    {
        return 'HTTP/2 ' . self::DEFAULT_HTTP_CODE . ' OK
date: ' . gmdate(DATE_RFC822) . ' GMT
content-type: application/json; charset=UTF-8
vary: Accept-Encoding
content-length: 1
set-cookie: localhost=0bcpoc8vq6gu4opv4o573940f; expires=Mon, ' . gmdate('d-M-Y') . ' GMT; Max-Age=900; path=/; domain=localhost
set-cookie: PHPSESSID=6sf8fa8rlm8c44avk33hhcegt0; path=/; HttpOnly
expires: Thu, 19 Nov 1981 08:52:00 GMT
cache-control: no-store, no-cache, must-revalidate
pragma: no-cache
x-ratelimit-limit: 40
x-ratelimit-remaining: 39
x-ratelimit-reset: 1685968385
strict-transport-security: max-age=31536000; includeSubDomains
x-xss-protection: 1; mode=block
x-content-security-policy: frame-ancestors \'self\'
x-frame-options: SAMEORIGIN
x-content-type-options: nosniff
access-control-allow-origin: *
cf-cache-status: DYNAMIC
nel: {"success_fraction":0,"report_to":"cf-nel","max_age":604800}
server: camooCloud
cf-ray: 7d286faf09472147-MAD
alt-svc: h3=":443"; ma=86400
';
    }

    public function getInfo(): array
    {
        return
            [
                'url' => $this->url,
                'content_type' => 'application/json; charset=UTF-8',
                'http_code' => $this->httpCode,
                'header_size' => 1115,
                'request_size' => 122,
                'filetime' => -1,
                'ssl_verify_result' => 0,
                'redirect_count' => 0,
                'total_time' => 0.512727,
                'namelookup_time' => 0.00325,
                'connect_time' => 0.131273,
                'pretransfer_time' => 0.3313,
                'size_upload' => 0.0,
                'size_download' => 108.0,
                'speed_download' => 210.0,
                'speed_upload' => 0.0,
                'download_content_length' => -1.0,
                'upload_content_length' => 0.0,
                'starttransfer_time' => 0.512119,
                'redirect_time' => 0.0,
                'redirect_url' => '',
                'primary_ip' => '188.114.97.5',
                'certinfo' => [],
                'primary_port' => 443,
                'local_ip' => '172.16.21.78',
                'local_port' => 56112,
                'http_version' => 3,
                'protocol' => 2,
                'ssl_verifyresult' => 0,
                'scheme' => 'HTTPS',
                'appconnect_time_us' => 331255,
                'connect_time_us' => 131273,
                'namelookup_time_us' => 3250,
                'pretransfer_time_us' => 331300,
                'redirect_time_us' => 0,
                'starttransfer_time_us' => 512119,
                'total_time_us' => 512727,
            ];
    }
}
