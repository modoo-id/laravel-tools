<?php

namespace ModooId\LaravelTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Process\Process;

class InstallCodeFormatter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modoo:install-code-formatter {--composer=global : Absolute path to the Composer binary which should be used to install packages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install code formatter for Modoo platform';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        // Install composer package...
        if (! $this->requireComposerPackages(['laravel/pint:^1.0'], true)) {
            return 1;
        }

        // NPM Packages...
        $this->updateNodePackages(function ($packages) {
            return [
                'eslint' => '^8.56.0',
                'eslint-config-prettier' => '^9.1.0',
                'eslint-plugin-import' => '^2.29.1',
                'eslint-plugin-prettier' => '^5.1.3',
                'eslint-plugin-vue' => '^9.20.0',
                'husky' => '^8.0.3',
                'lint-staged' => '^15.2.0',
                'prettier' => '3.1.1',
                '@trivago/prettier-plugin-sort-imports' => '^4.3.0'
            ] + $packages;
        });

        copy(__DIR__.'/../../../stubs/code-formatter/.eslintrc.cjs', base_path('.eslintrc.cjs'));
        copy(__DIR__.'/../../../stubs/code-formatter/.prettierignore', base_path('.prettierignore'));
        copy(__DIR__.'/../../../stubs/code-formatter/pint.json', base_path('pint.json'));
        copy(__DIR__.'/../../../stubs/code-formatter/prettier.config.cjs', base_path('prettier.config.cjs'));

        $this->components->info('Installing and building Node dependencies.');

        $this->runCommands(['npm install', 'npm run build']);

        $this->runCommands([
            'npm pkg set scripts.prepare="husky install"',
            'npm pkg set "lint-staged[**/*.php]"="./vendor/bin/pint --preset laravel"',
            'npm pkg set "lint-staged[{**/*,*}.{js,vue,html,css}]"="./vendor/bin/pint --preset laravel"',
            'npm run prepare',
            'npx husky add .husky/pre-commit "npx lint-staged"',
        ]);

        $this->line('');
        $this->components->info('Modoo platform code formatter scaffolding installed successfully.');
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
     * Update the "package.json" file.
     *
     * @param  callable  $callback
     * @param  bool  $dev
     * @return void
     */
    protected static function updateNodePackages(callable $callback, $dev = true)
    {
        if (! file_exists(base_path('package.json'))) {
            return;
        }

        $configurationKey = $dev ? 'devDependencies' : 'dependencies';

        $packages = json_decode(file_get_contents(base_path('package.json')), true);

        $packages[$configurationKey] = $callback(
            array_key_exists($configurationKey, $packages) ? $packages[$configurationKey] : [],
            $configurationKey
        );

        ksort($packages[$configurationKey]);

        file_put_contents(
            base_path('package.json'),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );
    }

    /**
     * Run the given commands.
     *
     * @param  array  $commands
     * @return void
     */
    protected function runCommands($commands)
    {
        $process = Process::fromShellCommandline(implode(' && ', $commands), null, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $this->output->writeln('  <bg=yellow;fg=black> WARN </> '.$e->getMessage().PHP_EOL);
            }
        }

        $process->run(function ($type, $line) {
            $this->output->write('    '.$line);
        });
    }
}
