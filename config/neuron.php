<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Neuron AI (Provider settings)
    |--------------------------------------------------------------------------
    |
    | This project uses Neuron 3.x.
    |
    | Current minimal runtime uses an Anthropic-compatible provider
    | (MiniMax). The repo's .env uses ANTHROPIC_* values for that.
    | We override the base URI in `App\\Neuron\\Providers\\MinimaxAnthropic`
    | so Neuron can still hit ".../v1/messages".
    */
    'anthropic' => [
        'base_uri' => env('ANTHROPIC_BASE_URL', ''),
        'key' => env('ANTHROPIC_API_KEY', ''),
        'model' => env('ANTHROPIC_MODEL', ''),
    ],
];
