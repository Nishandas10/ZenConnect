<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ExternalApp extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'api_key',
        'api_secret',
        'webhook_url',
        'webhook_secret',
        'is_active',
        'allowed_origins',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allowed_origins' => 'array',
    ];

    protected $hidden = [
        'api_secret',
        'webhook_secret',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Generate a new API key pair for this app.
     */
    public static function generateCredentials(): array
    {
        return [
            'api_key' => 'zc_' . Str::random(32),
            'api_secret' => Str::random(64),
        ];
    }

    /**
     * Verify the API secret.
     */
    public function verifySecret(string $secret): bool
    {
        return hash_equals($this->api_secret, $secret);
    }

    /**
     * Generate webhook secret for secure callbacks.
     */
    public function generateWebhookSecret(): string
    {
        $secret = 'whsec_' . Str::random(32);
        $this->update(['webhook_secret' => $secret]);
        return $secret;
    }

    /**
     * Create signature for webhook payload.
     */
    public function signPayload(array $payload): string
    {
        $payloadJson = json_encode($payload);
        return hash_hmac('sha256', $payloadJson, $this->webhook_secret);
    }
}
