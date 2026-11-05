<?php

use Illuminate\Foundation\Inspiring;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('oneqr:make-admin {email}', function (string $email) {
    $user = User::query()->where('email', $email)->first();

    if (! $user) {
        $this->error('მომხმარებელი მითითებული ელფოსტით ვერ მოიძებნა.');

        return 1;
    }

    if ($user->is_platform_admin) {
        $this->info('მომხმარებელს უკვე აქვს პლატფორმის ადმინისტრატორის უფლება.');

        return 0;
    }

    $user->forceFill(['is_platform_admin' => true])->save();

    $this->info('პლატფორმის ადმინისტრატორის უფლება წარმატებით მიენიჭა.');

    return 0;
})->purpose('არსებული მომხმარებლისთვის oneQR პლატფორმის ადმინისტრატორის უფლების მინიჭება');
