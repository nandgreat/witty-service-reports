<?php

namespace Database\Seeders;

use App\Models\Platform;
use App\Models\ServiceType;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Initial logins. Set SEED_ADMIN_* / SEED_COORDINATOR_* in .env; a random password is
        // generated (and printed once) when none is given. Existing users are left untouched.
        $this->seedUser('Admin', 'SEED_ADMIN', 'admin@cozaglobal.org', User::ROLE_ADMIN);
        $this->seedUser('Service Coordinator', 'SEED_COORDINATOR', 'coordinator@cozaglobal.org', User::ROLE_COORDINATOR);

        $summary = "The {service} was successfully executed, with a smooth and uninterrupted livestream across all streaming platforms. Audio and video performance remained stable throughout the service, providing a seamless viewing experience for both the onsite congregation and the online audience.\n\nOverall, the service was well-coordinated and technically successful.";
        $conclusion = "The {service} concluded successfully with stable streaming, clear audio and video, and smooth technical coordination throughout.\n\nOverall, the service was well-coordinated and technically successful, delivering a seamless digital worship experience for the online audience.";

        foreach ([
            ['Sunday Service', 'COZA SUNDAY', 'Sunday Service'],
            ['Tuesday Service', 'COZA TUESDAY', 'Tuesday Service'],
            ['7 Days of Glory (7DG)', '7DG', '7DG'],
            ['12 Days of Glory (12DG)', '12DG', '12DG'],
        ] as [$name, $short, $label]) {
            ServiceType::firstOrCreate(['short_name' => $short], [
                'name' => $name,
                'default_summary' => str_replace('{service}', $label, $summary),
                'default_conclusion' => str_replace('{service}', $label, $conclusion),
            ]);
        }

        if (TaskTemplate::count() === 0) {
            $tasks = [
                ['1', 'YouTube moderation (COZATV & Biodun Fatoyinbo)'],
                ['2', 'Facebook stream moderation'],
                ['3', 'Facebook Page live updates'],
                ['4', 'YouTube (COZATV) – sound & video monitoring'],
                ['5', 'YouTube (Biodun Fatoyinbo) – sound & video monitoring'],
                ['6', 'Sermon quotes'],
                ['7', 'Sermon notes'],
                ['8', 'Our Father’s Blessing'],
                ['9a', 'Syntax – Sermon notes & quotes'],
                ['9b', 'Syntax – Our Father’s Blessing'],
                ['9c', 'Syntax – YouTube live updates'],
                ['9d', 'Syntax – Facebook live updates'],
                ['10', 'Livestream to all platforms'],
                ['11', 'Service coordination'],
            ];
            foreach ($tasks as $i => [$n, $t]) {
                TaskTemplate::create(['number' => $n, 'title' => $t, 'sort_order' => $i + 1]);
            }
        }

        if (Platform::count() === 0) {
            foreach (['YouTube · COZATV', 'YouTube · Biodun Fatoyinbo', 'Facebook'] as $i => $p) {
                Platform::create(['name' => $p, 'sort_order' => $i + 1]);
            }
        }

        $members = [
            ['Bro Godwin', 'HOD'], ['Sis Tonia', 'AHOD'],
            'Sis Ada', 'Bro Michael', 'Bro Humble', 'Bro Timi', 'Bro Emmanuel', 'Sis Gold', 'Sis Jade', 'Bro Nandom',
            'Bro Nejo', 'Sis Maureen', 'Bro Taiwo', 'Bro Nnamdi', 'Bro Chibuzor', 'Bro Bright', 'Sis Naomi', 'Sis Tolu',
            'Bro Jay John', 'Bro Muyiwa', 'Bro Leonard',
        ];
        foreach ($members as $m) {
            [$name, $role] = is_array($m) ? $m : [$m, null];
            Worker::firstOrCreate(['name' => $name], ['role' => $role]);
        }
    }

    private function seedUser(string $name, string $envPrefix, string $defaultEmail, string $role): void
    {
        $email = env("{$envPrefix}_EMAIL", $defaultEmail);

        if (User::where('email', $email)->exists()) {
            return;
        }

        $password = env("{$envPrefix}_PASSWORD") ?: Str::password(16, symbols: false);

        User::create(['name' => $name, 'email' => $email, 'password' => $password, 'role' => $role]);

        if (! env("{$envPrefix}_PASSWORD")) {
            $this->command?->warn("{$name} login created: {$email} / {$password}  (save this now)");
        }
    }
}
