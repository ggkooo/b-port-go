<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin-user {--email=} {--name=} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email') ?? $this->ask('Email do administrador');
        $name = $this->option('name') ?? $this->ask('Nome completo do administrador');
        $password = $this->option('password') ?? $this->secret('Senha do administrador');

        if (User::query()->where('email', $email)->exists()) {
            $this->error("Usuário com e-mail $email já existe!");

            return self::FAILURE;
        }

        $nameParts = explode(' ', $name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        $user = User::query()->create([
            'name' => $name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => bcrypt($password),
            'is_admin' => true,
            'phone' => 'N/A',
            'state' => 'N/A',
            'city' => 'N/A',
            'school' => 'N/A',
            'class' => 1,
            'shift' => 1,
        ]);

        $this->info("Usuário administrador criado com sucesso!");
        $this->info("Email: {$user->email}");
        $this->info("UUID: {$user->uuid}");

        return self::SUCCESS;
    }
}
