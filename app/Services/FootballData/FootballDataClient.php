<?php

namespace App\Services\FootballData;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class FootballDataClient
{
    public function __construct(
        protected ?string $token = null,
        protected string $baseUrl = 'https://api.football-data.org/v4',
        protected string $competition = 'WC',
    ) {
        $this->token ??= (string) config('services.football_data.token');
        $this->baseUrl = rtrim((string) config('services.football_data.base_url', $this->baseUrl), '/');
        $this->competition = (string) config('services.football_data.competition', $this->competition);
    }

    public function competitionCode(): string
    {
        return $this->competition;
    }

    /**
     * @return array<string, mixed>
     */
    public function competition(): array
    {
        return $this->get("competitions/{$this->competition}");
    }

    /**
     * @return array<string, mixed>
     */
    public function teams(): array
    {
        return $this->get("competitions/{$this->competition}/teams");
    }

    /**
     * @param  array<string, string>  $filters  e.g. ['status' => 'FINISHED']
     * @return array<string, mixed>
     */
    public function matches(array $filters = []): array
    {
        return $this->get("competitions/{$this->competition}/matches", $filters);
    }

    /**
     * @return array<string, mixed>
     */
    public function standings(): array
    {
        return $this->get("competitions/{$this->competition}/standings");
    }

    /**
     * @return array<string, mixed>
     */
    public function scorers(int $limit = 15): array
    {
        return $this->get("competitions/{$this->competition}/scorers", ['limit' => $limit]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    protected function get(string $path, array $query = []): array
    {
        return $this->request()
            ->get("{$this->baseUrl}/{$path}", $query)
            ->throw()
            ->json();
    }

    protected function request(): PendingRequest
    {
        return Http::withHeaders(['X-Auth-Token' => $this->token])
            ->acceptJson()
            ->retry(2, 1500, throw: false)
            ->timeout(20);
    }
}
