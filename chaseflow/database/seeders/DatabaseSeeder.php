<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@chaseflow.test'],
            ['name' => 'Demo Admin', 'password' => Hash::make('password')]
        );

        // Demo data so the dashboard looks alive immediately.
        $acme = Client::updateOrCreate(
            ['email' => 'billing@acme.test'],
            ['user_id' => $user->id, 'name' => 'Wile E. Coyote', 'company' => 'Acme Corp']
        );
        $globex = Client::updateOrCreate(
            ['email' => 'ap@globex.test'],
            ['user_id' => $user->id, 'name' => 'Hank Scorpio', 'company' => 'Globex']
        );

        Invoice::updateOrCreate(
            ['number' => 'INV-10001'],
            ['client_id' => $acme->id, 'user_id' => $user->id, 'amount' => 2400,
             'due_date' => now()->subDays(9), 'status' => 'sent', 'sent_at' => now()->subDays(16)]
        );
        Invoice::updateOrCreate(
            ['number' => 'INV-10002'],
            ['client_id' => $globex->id, 'user_id' => $user->id, 'amount' => 850,
             'due_date' => now()->addDays(6), 'status' => 'sent', 'sent_at' => now()->subDays(2)]
        );
        Invoice::updateOrCreate(
            ['number' => 'INV-10003'],
            ['client_id' => $acme->id, 'user_id' => $user->id, 'amount' => 1200,
             'due_date' => now()->subDays(20), 'status' => 'paid',
             'sent_at' => now()->subDays(30), 'paid_at' => now()->subDays(18)]
        );
    }
}
