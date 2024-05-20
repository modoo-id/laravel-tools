<?php

namespace ModooId\LaravelTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class InstallSingleSignOn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modoo:install-sso {--composer=global : Absolute path to the Composer binary which should be used to install packages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Single Sign On features for Modoo Platform';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        // Install league/oauth2-client package...
        if (! $this->requireComposerPackages(['league/oauth2-client:^2.7'])) {
            return 1;
        }

        $files = new Filesystem;

        // Environment...
        if (! $files->exists(base_path('.env'))) {
            copy(base_path('.env.example'), base_path('.env'));
        }

        $content = file_get_contents(base_path('.env'));
        $content .= PHP_EOL.'SSO_SERVER_CLIENT_ID=input-client-id'.PHP_EOL.'SSO_SERVER_CLIENT_SECRET=input-client-secret'.PHP_EOL.'SSO_SERVER_REDIRECT_URI=http://modoo-permit.test/sso/callback'.PHP_EOL.'SSO_SERVER_AUTHORIZATION_URL=http://modoo-apps.test/oauth/authorize'.PHP_EOL.'SSO_SERVER_TOKEN_URL=http://modoo-apps.test/oauth/token'.PHP_EOL.'SSO_SERVER_RESOURCE_URL=http://modoo-apps.test/api/user'.PHP_EOL.'SSO_SERVER_LOGOUT_URL=http://modoo-apps.test/logout';
        file_put_contents(base_path('.env'), $content);

        // Controllers...
        (new Filesystem)->ensureDirectoryExists(app_path('Http/Controllers/SSO'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../../stubs/single-sign-on/app/Http/Controllers/SSO', app_path('Http/Controllers/SSO'));

        // Services...
        (new Filesystem)->ensureDirectoryExists(app_path('Services'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../../stubs/single-sign-on/app/Services', app_path('Services'));

        // Routes...
        copy(__DIR__.'/../../../stubs/single-sign-on/routes/sso.php', base_path('routes/sso.php'));

        $content = file_get_contents(base_path('routes/web.php'));
        $content .= PHP_EOL."require __DIR__.'/sso.php';";
        file_put_contents(base_path('routes/web.php'), $content);

        // Migrations...
        copy(__DIR__.'/../../../stubs/single-sign-on/database/migrations/2024_05_18_184355_add_sso_id_to_users_table.php', base_path('database/migrations/2024_05_18_184355_add_sso_id_to_users_table.php'));

        $this->replaceInFile(
            <<<'EOT'
            ];
            EOT,
            <<<'EOT'
                'sso_server' => [
                    'client_id' => env('SSO_SERVER_CLIENT_ID'),
                    'client_secret' => env('SSO_SERVER_CLIENT_SECRET'),
                    'redirect' => env('SSO_SERVER_REDIRECT_URI'),
                    'authorization_url' => env('SSO_SERVER_AUTHORIZATION_URL'),
                    'token_url' => env('SSO_SERVER_TOKEN_URL'),
                    'resource_url' => env('SSO_SERVER_RESOURCE_URL'),
                    'logout_url' => env('SSO_SERVER_LOGOUT_URL'),
                ],

            ];
            EOT,
            base_path('config/services.php')
        );

        $this->line('');
        $this->components->info('Modoo Single Sign On scaffolding installed successfully.');
    }

    /**
     * Installs the given Composer Packages into the application.
     *
     * @param  array  $packages
     * @param  bool  $asDev
     * @return bool
     */
    protected function requireComposerPackages(array $packages, $asDev = false)
    {
        $composer = $this->option('composer');

        if ($composer !== 'global') {
            $command = ['php', $composer, 'require'];
        }

        $command = array_merge(
            $command ?? ['composer', 'require'],
            $packages,
            $asDev ? ['--dev'] : [],
        );

        return (new Process($command, base_path(), ['COMPOSER_MEMORY_LIMIT' => '-1']))
            ->setTimeout(null)
            ->run(function ($type, $output) {
                $this->output->write($output);
            }) === 0;
    }

    /**
     * Replace a given string within a given file.
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $path
     * @return void
     */
    protected function replaceInFile($search, $replace, $path)
    {
        file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));
    }
}
