<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DyteService
{
    protected string $base;
    protected string $org;
    protected string $key;
    protected ?string $preset;
    protected string $preferredRegion;

    public function __construct()
    {
        $this->base = rtrim((string) config('services.dyte.api_base_url'), '/');
        $this->org = (string) config('services.dyte.org_id');
        $this->key = (string) config('services.dyte.api_key');
        $this->preset = config('services.dyte.preset_name');
        $this->preferredRegion = (string) config('services.dyte.preferred_region', 'ap-south-1');
    }

    protected function client()
    {
        if ($this->base === '' || $this->org === '' || $this->key === '') {
            throw new RuntimeException('Dyte configuration is incomplete. Set DYTE_API_BASE_URL, DYTE_ORG_ID, and DYTE_API_KEY.');
        }

        return Http::withHeaders([
            'Authorization' => 'Basic '.base64_encode("{$this->org}:{$this->key}"),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
    }

    protected function unwrap(Response $response, string $action): array
    {
        if ($response->failed()) {
            $message = $response->json('message')
                ?? $response->json('error.message')
                ?? $response->body()
                ?? "Dyte {$action} request failed.";

            throw new RuntimeException("Dyte {$action} failed: {$message}");
        }

        $data = $response->json('data');

        if (!is_array($data)) {
            throw new RuntimeException("Dyte {$action} returned an unexpected response.");
        }

        return $data;
    }

    public function createMeeting(string $title): array
    {
        $response = $this->client()->post("{$this->base}/meetings", [
            'title' => $title,
            'preferred_region' => $this->preferredRegion,
            'record_on_start' => false,
            'waiting_room' => false,
        ]);

        return $this->unwrap($response, 'meeting creation');
    }

    public function createParticipantToken(string $meetingId, string $name, string|int $userId, ?string $presetName = null): string
    {
        $payload = [
            'name' => $name,
            'client_specific_id' => (string) $userId,
        ];

        $resolvedPreset = $presetName ?: $this->preset ?: 'group_call_host';

        if (!empty($resolvedPreset)) {
            $payload['preset_name'] = $resolvedPreset;
        }

        $response = $this->client()->post("{$this->base}/meetings/{$meetingId}/participants", $payload);
        $data = $this->unwrap($response, 'participant token creation');
        $token = $data['auth_token']
            ?? $data['authToken']
            ?? $data['token']
            ?? null;

        if (!is_string($token) || $token === '') {
            throw new RuntimeException('Dyte participant token was missing from the response.');
        }

        return $token;
    }
}
