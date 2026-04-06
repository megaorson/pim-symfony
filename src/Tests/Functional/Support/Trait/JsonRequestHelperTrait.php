<?php
declare(strict_types=1);

namespace App\Tests\Functional\Support\Trait;

use Symfony\Contracts\HttpClient\ResponseInterface;

trait JsonRequestHelperTrait
{
    protected function jsonGet(string $url, array $headers = []): ResponseInterface
    {
        return $this->client->request('GET', $url, [
            'headers' => $this->buildJsonHeaders($headers),
        ]);
    }

    protected function jsonPost(string $url, array $payload = [], array $headers = []): ResponseInterface
    {
        return $this->client->request('POST', $url, [
            'headers' => $this->buildJsonHeaders($headers),
            'json' => $payload,
        ]);
    }

    protected function jsonPatch(string $url, array $payload = [], array $headers = []): ResponseInterface
    {
        return $this->client->request('PATCH', $url, [
            'headers' => $this->buildJsonHeaders($headers, [
                'Content-Type' => 'application/merge-patch+json',
            ]),
            'json' => $payload,
        ]);
    }

    protected function jsonPut(string $url, array $payload = [], array $headers = []): ResponseInterface
    {
        return $this->client->request('PUT', $url, [
            'headers' => $this->buildJsonHeaders($headers),
            'json' => $payload,
        ]);
    }

    protected function jsonDelete(string $url, array $headers = []): ResponseInterface
    {
        return $this->client->request('DELETE', $url, [
            'headers' => $this->buildJsonHeaders($headers),
        ]);
    }

    protected function responseData(bool $throw = true): array
    {
        return $this->client->getResponse()->toArray($throw);
    }

    protected function responseContent(): string
    {
        return $this->client->getResponse()->getContent(false);
    }

    protected function responseStatusCode(): int
    {
        return $this->client->getResponse()->getStatusCode();
    }

    /**
     * @return array<string, string>
     */
    private function buildJsonHeaders(array $headers = [], array $overrideDefaults = []): array
    {
        return array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $overrideDefaults, $headers);
    }
}
