<?php

namespace App\Services\Odds;

use Illuminate\Support\Facades\Http;

class OddsApiClient
{
    public function __construct(
        protected ?string $key = null,
        protected ?string $baseUrl = null,
        protected ?string $sport = null,
        protected ?string $regions = null,
    ) {
        $this->key ??= (string) config('services.odds_api.key');
        $this->baseUrl = rtrim((string) ($this->baseUrl ?? config('services.odds_api.base_url')), '/');
        $this->sport ??= (string) config('services.odds_api.sport');
        $this->regions ??= (string) config('services.odds_api.regions', 'eu');
    }

    /**
     * Head-to-head (1X2) odds for every upcoming match in the configured sport.
     *
     * @return array<int, array<string, mixed>>
     */
    public function events(): array
    {
        return Http::acceptJson()
            ->retry(2, 1000, throw: false)
            ->timeout(20)
            ->get("{$this->baseUrl}/sports/{$this->sport}/odds", [
                'apiKey' => $this->key,
                'regions' => $this->regions,
                'markets' => 'h2h',
                'oddsFormat' => 'decimal',
            ])
            ->throw()
            ->json();
    }
}
