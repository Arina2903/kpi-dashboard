<?php

namespace App\Console\Commands;

use App\Services\SupabaseAuthService;
use App\Services\SupabaseService;
use Illuminate\Console\Command;

/**
 * One-time bootstrap for the very first Richworks Super Admin. This is the
 * single legitimate use of service_role to grant that role directly — every
 * other role/permission change in the platform goes through RLS-scoped
 * requests instead, but the first Super Admin can't grant themselves the
 * role via RLS (nothing exists yet to authorize it), so this command does it
 * once, explicitly, outside the normal request path.
 */
class BootstrapSuperAdmin extends Command
{
    protected $signature = 'platform:bootstrap-super-admin {email} {password}';

    protected $description = 'Creates the first Richworks Super Admin account for the multi-company platform';

    public function handle(SupabaseAuthService $auth, SupabaseService $supabase): int
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $existing = $supabase->first('users', [
            'email' => 'eq.' . $email,
            'select' => 'id,role',
        ]);

        if ($existing) {
            if ($existing['role'] === 'richworks_super_admin') {
                $this->info("{$email} is already a Richworks Super Admin.");
                return self::SUCCESS;
            }

            $supabase->update('users', ['email' => 'eq.' . $email], ['role' => 'richworks_super_admin']);
            $this->info("Promoted existing user {$email} to Richworks Super Admin.");
            return self::SUCCESS;
        }

        try {
            $auth->createUser($email, $password, ['name' => 'Richworks Super Admin']);
        } catch (\Throwable $e) {
            $this->error('Failed to create the auth user: ' . $e->getMessage());
            return self::FAILURE;
        }

        // The auth.users trigger auto-creates the matching `users` row a
        // moment later — poll briefly rather than assuming it's instant.
        $newUser = null;
        for ($i = 0; $i < 10; $i++) {
            $newUser = $supabase->first('users', ['email' => 'eq.' . $email, 'select' => 'id']);
            if ($newUser) {
                break;
            }
            usleep(300_000);
        }

        if (!$newUser) {
            $this->error('Auth user was created but the matching `users` row never appeared — check the on_auth_user_created trigger.');
            return self::FAILURE;
        }

        $supabase->update('users', ['email' => 'eq.' . $email], ['role' => 'richworks_super_admin']);

        $this->info("Created Richworks Super Admin: {$email}");
        return self::SUCCESS;
    }
}