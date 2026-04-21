<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('categories')->where('slug', 'feature-request')->update([
            'name' => 'Refund Request',
            'slug' => 'refund-request',
            'description' => 'Refund and return requests',
        ]);

        DB::table('categories')->where('slug', 'general-inquiry')->update([
            'name' => 'Product Enquiry',
            'slug' => 'product-enquiry',
            'description' => 'Product related questions',
        ]);
    }

    public function down(): void
    {
        DB::table('categories')->where('slug', 'refund-request')->update([
            'name' => 'Feature Request',
            'slug' => 'feature-request',
            'description' => 'New feature suggestions',
        ]);

        DB::table('categories')->where('slug', 'product-enquiry')->update([
            'name' => 'General Inquiry',
            'slug' => 'general-inquiry',
            'description' => 'General questions',
        ]);
    }
};
