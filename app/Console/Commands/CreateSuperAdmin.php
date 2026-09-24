<?php

namespace App\Console\Commands;

use App\Actions\ProvisionSuperAdminAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdmin extends Command
{
    protected $signature = 'hardflow:create-super-admin
                            {email? : The Super Admin email address}
                            {--name=Platform Administrator : The Super Admin name}
                            {--password= : Password; omit to enter it securely}';

    protected $description = 'Create or promote an active HardFlow Super Admin';

    public function handle(ProvisionSuperAdminAction $provisionSuperAdmin): int
    {
        $email = $this->argument('email') ?: $this->ask('Email address');
        $password = $this->option('password') ?: $this->secret('Password (minimum 12 characters)');
        $data = [
            'name' => $this->option('name'),
            'email' => $email,
            'password' => $password,
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:12'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = $provisionSuperAdmin->execute($data['name'], $data['email'], $data['password']);
        $this->info("Super Admin ready: {$user->email}");

        return self::SUCCESS;
    }
}
