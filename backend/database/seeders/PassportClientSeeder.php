<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

class PassportClientSeeder extends Seeder
{
    public function run(): void
    {
        $provider = config('auth.guards.api.provider');

        $alreadyExists = Passport::client()
            ->newQuery()
            ->where('revoked', false)
            ->where(function ($query) use ($provider): void {
                $query->whereNull('provider')
                    ->orWhere('provider', $provider);
            })
            ->get()
            ->contains(fn ($client) => $client->hasGrantType('personal_access'));

        if ($alreadyExists) {
            return;
        }

        app(ClientRepository::class)->createPersonalAccessGrantClient(
            'Operations Hub Personal Access Client',
            $provider,
        );
    }
}
