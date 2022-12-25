# curl-http-client
Simple Curl Http Client build with the clean code architecture approach.
PSR-7 standard

## Usage
### without dependency injection
```php
use Camoo\Http\Curl\Infrastructure\Client;

$client = new Client();

$uri = 'https://api.example.com/v1/users';

$response = $client->get($uri);

$status = $response->getStatusCode();
$body = (string)$response->getBody();

// get all headers
$headers = $response->getHeaders();

// get single header
$header = $response->getHeader('foo');

// get status code
$code = $response->getStatusCode();

```

### With dependency injection
```php
## in Module
use Camoo\Http\Curl\Domain\Client\ClientInterface;
use Camoo\Http\Curl\Infrastructure\Client;

$this->bind(ClientInterface::class)->to(Client::class);

## in Adapter port

final class TemplateRepository implements TemplateRepositoryInterface
{
    private const URI = 'https://api.example.com/v2/template';
    private const SUCCESS_STATUS = 201;
    
    public function __construct(private readonly ClientInterface $client)
    {
    }
    
    public function save(Template $template): bool
    {
        $response = $this->client->post(self::URI, $template->toArray());
        return $response->getStatusCode() === self::SUCCESS_STATUS;
    }
    
    public function getById(string $id): Template
    {
        $uri = self::URI. '?id=' . $id;
        $response = $this->client->get($uri);
        if ($response->getStatusCode() !== self::SUCCESS_STATUS){
            throw new NotFoundTemplate(sprintf('Template with id %s not found!', $id));
        }
        $body = (string)$response->getBody();
        return Template::fromArray(json_decode($body, true));
    }
    # ...
}

```
