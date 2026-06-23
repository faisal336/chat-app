<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature = 'webpush:vapid {--force : Overwrite existing keys in .env}';

    protected $description = 'Generate VAPID keys for WebPush. Writes to .env when missing or --force is used.';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->newLine();
        $this->info('VAPID keys generated.');
        $this->line('');
        $this->line('  VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('  VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->line('');

        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $this->warn('.env not found — copy the keys above into your environment.');

            return self::SUCCESS;
        }

        $contents = file_get_contents($envPath);
        $hasPublic = preg_match('/^VAPID_PUBLIC_KEY=(.*)$/m', $contents, $m1);
        $hasPrivate = preg_match('/^VAPID_PRIVATE_KEY=(.*)$/m', $contents, $m2);

        $existing = ($hasPublic && trim($m1[1]) !== '') || ($hasPrivate && trim($m2[1]) !== '');

        if ($existing && ! $this->option('force')) {
            $this->warn('VAPID keys already set in .env. Use --force to overwrite.');

            return self::SUCCESS;
        }

        $contents = $this->upsertEnv($contents, 'VAPID_PUBLIC_KEY', $keys['publicKey']);
        $contents = $this->upsertEnv($contents, 'VAPID_PRIVATE_KEY', $keys['privateKey']);

        file_put_contents($envPath, $contents);

        $this->info('.env updated.');

        return self::SUCCESS;
    }

    private function upsertEnv(string $contents, string $key, string $value): string
    {
        if (preg_match("/^{$key}=.*$/m", $contents)) {
            return preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $contents);
        }

        return rtrim($contents)."\n{$key}={$value}\n";
    }
}
