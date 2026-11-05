<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakePlatformAdmin extends Command
{
    protected $signature = 'oneqr:make-admin {email : The existing user's email address}';

    protected $description = 'Grant platform administrator access to an existing oneQR user';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->components->error('მომხმარებელი მითითებული ელფოსტით ვერ მოიძებნა.');

            return self::FAILURE;
        }

        if ($user->is_platform_admin) {
            $this->components->info('მომხმარებელს უკვე აქვს პლატფორმის ადმინისტრატორის უფლება.');

            return self::SUCCESS;
        }

        $user->forceFill(['is_platform_admin' => true])->save();

        $this->components->info('პლატფორმის ადმინისტრატორის უფლება წარმატებით მიენიჭა.');

        return self::SUCCESS;
    }
}
