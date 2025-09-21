<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:create {email} {--name=} {--password=}', function (string $email, ?string $name, ?string $password) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Invalid email format.');
        return self::FAILURE;
    }

    if (User::where('email', $email)->exists()) {
        $this->error("User with email {$email} already exists.");
        return self::FAILURE;
    }

    $name = $name ?: explode('@', $email)[0];
    $generated = false;
    if (!$password) {
        $password = Str::random(12);
        $generated = true;
    }

    $user = User::create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
    ]);

    $this->info('User created successfully:');
    $this->line("  ID: {$user->id}");
    $this->line("  Name: {$user->name}");
    $this->line("  Email: {$user->email}");
    $this->line('  Password: ' . ($generated ? "{$password} (generated)" : '****** (provided)'));

    return self::SUCCESS;
})->purpose('Create a user without using Tinker');
