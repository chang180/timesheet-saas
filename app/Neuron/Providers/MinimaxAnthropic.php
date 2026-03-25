<?php

declare(strict_types=1);

namespace App\Neuron\Providers;

use NeuronAI\HttpClient\HttpClientInterface;
use NeuronAI\Providers\Anthropic\Anthropic;

/**
 * MiniMax provides an Anthropic-compatible API, but the base URL differs from api.anthropic.com.
 * Neuron's built-in Anthropic provider hardcodes the base URI, so we override it here.
 */
class MinimaxAnthropic extends Anthropic
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        protected string $key,
        protected string $model,
        string $baseUriOverride,
        string $version = '2023-06-01',
        int $maxTokens = 8192,
        array $parameters = [],
        ?HttpClientInterface $httpClient = null,
    ) {
        $baseUri = rtrim($baseUriOverride, '/');

        // Neuron's Anthropic endpoints assume ".../v1/messages".
        if (! str_ends_with($baseUri, '/v1')) {
            $baseUri .= '/v1';
        }

        // Parent constructor reads `$this->baseUri` to configure the HTTP client.
        $this->baseUri = $baseUri.'/';

        parent::__construct(
            key: $this->key,
            model: $this->model,
            version: $version,
            max_tokens: $maxTokens,
            parameters: $parameters,
            httpClient: $httpClient,
        );
    }
}
