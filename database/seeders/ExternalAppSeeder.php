<?php

namespace Database\Seeders;

use App\Models\ExternalApp;
use Illuminate\Database\Seeder;

class ExternalAppSeeder extends Seeder
{
    public function run(): void
    {
        ExternalApp::firstOrCreate(
            ['name' => 'ShopHub'],
            [
                'api_key' => 'zc_shophub_key_123456789',
                'api_secret' => 'shophub_secret_abcdefghijklmnopqrstuvwxyz123456',
                'webhook_url' => 'http://localhost:8000/api/support/webhook',
                'webhook_secret' => 'whsec_shophub_webhook_secret_123456',
                'is_active' => true,
                'allowed_origins' => ['http://localhost:3001', 'http://localhost:8001'],
            ]
        );
    }
}
