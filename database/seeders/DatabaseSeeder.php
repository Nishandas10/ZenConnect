<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@zenconnect.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create Agents
        $agent1 = User::create([
            'name' => 'Sarah Agent',
            'email' => 'sarah@zenconnect.com',
            'password' => Hash::make('password'),
            'role' => 'agent',
            'email_verified_at' => now(),
        ]);

        $agent2 = User::create([
            'name' => 'Mike Agent',
            'email' => 'mike@zenconnect.com',
            'password' => Hash::make('password'),
            'role' => 'agent',
            'email_verified_at' => now(),
        ]);

        // Create Regular Users
        $user1 = User::create([
            'name' => 'John User',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $user2 = User::create([
            'name' => 'Jane User',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // Create Categories
        $categories = collect([
            ['name' => 'Technical Support', 'slug' => 'technical-support', 'description' => 'Technical issues and bugs'],
            ['name' => 'Billing', 'slug' => 'billing', 'description' => 'Billing and payment issues'],
            ['name' => 'Account', 'slug' => 'account', 'description' => 'Account related queries'],
            ['name' => 'Feature Request', 'slug' => 'feature-request', 'description' => 'New feature suggestions'],
            ['name' => 'General Inquiry', 'slug' => 'general-inquiry', 'description' => 'General questions'],
        ])->map(fn($cat) => Category::create($cat));

        // Create Tags
        $tags = collect([
            ['name' => 'Bug', 'slug' => 'bug', 'color' => '#EF4444'],
            ['name' => 'Enhancement', 'slug' => 'enhancement', 'color' => '#3B82F6'],
            ['name' => 'Urgent', 'slug' => 'urgent', 'color' => '#F59E0B'],
            ['name' => 'Documentation', 'slug' => 'documentation', 'color' => '#10B981'],
            ['name' => 'Question', 'slug' => 'question', 'color' => '#8B5CF6'],
        ])->map(fn($tag) => Tag::create($tag));

        // Create Sample Tickets
        $ticketData = [
            [
                'title' => 'Cannot login to my account',
                'description' => 'I have been trying to login for the past hour but keep getting an "Invalid credentials" error. I am sure my password is correct.',
                'priority' => 'high',
                'status' => 'open',
                'user_id' => $user1->id,
                'category_id' => $categories[2]->id,
            ],
            [
                'title' => 'Payment failed but amount deducted',
                'description' => 'I tried to upgrade my plan and the payment failed but the amount was deducted from my bank account. Transaction ID: TXN12345678',
                'priority' => 'urgent',
                'status' => 'in_progress',
                'user_id' => $user1->id,
                'assigned_to' => $agent1->id,
                'category_id' => $categories[1]->id,
            ],
            [
                'title' => 'Feature request: Dark mode',
                'description' => 'It would be great if the application supported a dark mode theme. Many users prefer dark interfaces especially when working at night.',
                'priority' => 'low',
                'status' => 'open',
                'user_id' => $user2->id,
                'category_id' => $categories[3]->id,
            ],
            [
                'title' => 'API returning 500 error',
                'description' => 'The /api/reports endpoint has been returning 500 errors intermittently since the last update. Attached are the error logs.',
                'priority' => 'high',
                'status' => 'in_progress',
                'user_id' => $user2->id,
                'assigned_to' => $agent2->id,
                'category_id' => $categories[0]->id,
            ],
            [
                'title' => 'How to export my data?',
                'description' => 'I need to export all my data in CSV format. Could you please guide me on how to do that?',
                'priority' => 'medium',
                'status' => 'resolved',
                'user_id' => $user1->id,
                'assigned_to' => $agent1->id,
                'category_id' => $categories[4]->id,
                'resolved_at' => now()->subHours(5),
            ],
        ];

        foreach ($ticketData as $index => $data) {
            $data['ticket_number'] = 'ZEN-' . str_pad($index + 1, 6, '0', STR_PAD_LEFT);
            $data['sla_deadline'] = now()->addHours(match($data['priority']) {
                'urgent' => 4,
                'high' => 8,
                'medium' => 24,
                'low' => 48,
            });

            $ticket = Ticket::create($data);
            $ticket->tags()->attach($tags->random(rand(1, 3))->pluck('id'));
        }

        // Add some comments
        $tickets = Ticket::all();

        TicketComment::create([
            'ticket_id' => $tickets[1]->id,
            'user_id' => $agent1->id,
            'body' => 'I have escalated this to the billing department. Your refund should be processed within 3-5 business days.',
        ]);

        TicketComment::create([
            'ticket_id' => $tickets[1]->id,
            'user_id' => $user1->id,
            'body' => 'Thank you for the quick response. I will wait for the refund.',
        ]);

        TicketComment::create([
            'ticket_id' => $tickets[3]->id,
            'user_id' => $agent2->id,
            'body' => 'I have identified the issue. It seems to be related to the database connection pooling. Working on a fix.',
            'is_internal' => true,
        ]);

        TicketComment::create([
            'ticket_id' => $tickets[3]->id,
            'user_id' => $agent2->id,
            'body' => 'We have identified the issue and are working on a fix. Expected resolution within 2 hours.',
        ]);

        TicketComment::create([
            'ticket_id' => $tickets[4]->id,
            'user_id' => $agent1->id,
            'body' => 'You can export your data by going to Settings > Data Management > Export. Choose CSV format and click Export.',
        ]);

        // Seed external apps
        $this->call(ExternalAppSeeder::class);
    }
}
