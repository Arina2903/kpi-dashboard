<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Runs `database/rls-tests/tenant_isolation.sql` — the fixture-based,
 * self-rolling-back behavioral RLS test suite — as one command with a clear
 * pass/fail result, instead of "here's a SQL file, remember to invoke psql
 * yourself, and remember never against production."
 *
 * The script raises `RAISE EXCEPTION 'INTENTIONAL ROLLBACK: ...'` at the very
 * end even on full success (that's how it forces its own fixture data to
 * roll back), so a bare `psql` exit code can't distinguish "everything
 * passed" from "scenario 3 actually failed" — both leave the outer
 * transaction rolled back. This command instead greps the combined
 * stdout/stderr for the script's own `FAIL (` markers, which only appear on
 * a genuine assertion failure, never on the deliberate final rollback.
 *
 * Never targets `SUPABASE_DB_URL` or any DSN mentioning this project's
 * production ref — the script's own header already says never run it
 * against the production ref directly, and that's enforced here rather
 * than left to whoever runs the command to remember.
 *
 * Production moved to project `mlggobjdsicuokblbsww` (2026-08-18) --
 * previously `eavmrurxxdxbufkkzlup`. This constant must always name
 * whichever project is live, since it's the one static guard that still
 * refuses a matching DSN even if SUPABASE_DB_URL isn't set in the
 * environment running this command.
 */
class PlatformRlsTest extends Command
{
    private const PRODUCTION_REF = 'mlggobjdsicuokblbsww';

    protected $signature = 'platform:rls-test {--dsn= : Postgres connection string for a DISPOSABLE target (a Supabase preview branch or throwaway Postgres instance) — never the production database.}';

    protected $description = 'Runs the tenant_isolation.sql behavioral RLS test suite against a disposable Postgres target and reports pass/fail.';

    public function handle(): int
    {
        $dsn = (string) $this->option('dsn');

        if (trim($dsn) === '') {
            $this->error('--dsn is required. Point it at a DISPOSABLE Postgres target (a Supabase preview branch, or a throwaway Postgres instance) — never at production.');

            return self::FAILURE;
        }

        if (str_contains($dsn, self::PRODUCTION_REF) || $dsn === env('SUPABASE_DB_URL')) {
            $this->error('Refusing to run: this DSN looks like the production database. tenant_isolation.sql creates and rolls back real fixture rows and must only ever run against a disposable target.');

            return self::FAILURE;
        }

        $psql = trim((string) shell_exec('command -v psql 2>/dev/null')) ?: trim((string) shell_exec('where psql 2>NUL'));

        if ($psql === '') {
            $this->error('psql is not installed / not on PATH. Install the PostgreSQL client to run this test.');

            return self::FAILURE;
        }

        $sqlPath = base_path('database/rls-tests/tenant_isolation.sql');

        $this->info('Running tenant_isolation.sql against the given disposable target...');

        $process = new Process(['psql', $dsn, '-f', $sqlPath]);
        $process->setTimeout(120);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        $output = $process->getOutput() . $process->getErrorOutput();

        if (str_contains($output, 'FAIL (')) {
            $this->error('RLS regression detected — see the FAIL ( ) line above.');

            return self::FAILURE;
        }

        if (!str_contains($output, 'ALL RLS ISOLATION SCENARIOS COMPLETED')) {
            $this->error('The suite did not run to completion — check the output above for a connection or setup error before trusting this as a pass.');

            return self::FAILURE;
        }

        $this->info('PASS — all RLS isolation scenarios completed with no FAIL markers.');

        return self::SUCCESS;
    }
}
