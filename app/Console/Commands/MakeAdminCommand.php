<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('umrah:make-admin {email}')]
#[Description('Promote and activate a user as a main administrator')]
class MakeAdminCommand extends Command
{
    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('لم يتم العثور على مستخدم بهذا البريد الإلكتروني.');

            return self::FAILURE;
        }

        $user->promoteToMainAdminForEmergency();

        $this->info('تم تفعيل المستخدم ومنحه صلاحية المدير الرئيسي.');

        return self::SUCCESS;
    }
}
