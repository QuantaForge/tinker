<?php

namespace QuantaForge\Tinker\Console;

use QuantaForge\Console\Command;
use QuantaForge\Support\Env;
use QuantaForge\Tinker\ClassAliasAutoloader;
use Psy\Configuration;
use Psy\Shell;
use Psy\VersionUpdater\Checker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class TinkerCommand extends Command
{
    /**
     * Artisan commands to include in the tinker shell.
     *
     * @var array
     */
    protected $commandWhitelist = [
        'clear-compiled', 'down', 'env', 'inspire', 'migrate', 'migrate:install', 'optimize', 'up',
    ];

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'tinker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interact with your application';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->getApplication()->setCatchExceptions(false);

        $config = Configuration::fromInput($this->input);
        $config->setUpdateCheck(Checker::NEVER);

        $config->getPresenter()->addCasters(
            $this->getCasters()
        );

        if ($this->option('execute')) {
            $config->setRawOutput(true);
        }

        $shell = new Shell($config);
        $shell->addCommands($this->getCommands());
        $shell->setIncludes($this->argument('include'));

        $path = Env::get('COMPOSER_VENDOR_DIR', $this->getQuantaForge()->basePath().DIRECTORY_SEPARATOR.'vendor');

        $path .= '/composer/autoload_classmap.php';

        $config = $this->getQuantaForge()->make('config');

        $loader = ClassAliasAutoloader::register(
            $shell, $path, $config->get('tinker.alias', []), $config->get('tinker.dont_alias', [])
        );

        if ($code = $this->option('execute')) {
            try {
                $shell->setOutput($this->output);
                $shell->execute($code);
            } finally {
                $loader->unregister();
            }

            return 0;
        }

        try {
            return $shell->run();
        } finally {
            $loader->unregister();
        }
    }

    /**
     * Get artisan commands to pass through to PsySH.
     *
     * @return array
     */
    protected function getCommands()
    {
        $commands = [];

        foreach ($this->getApplication()->all() as $name => $command) {
            if (in_array($name, $this->commandWhitelist)) {
                $commands[] = $command;
            }
        }

        $config = $this->getQuantaForge()->make('config');

        foreach ($config->get('tinker.commands', []) as $command) {
            $commands[] = $this->getApplication()->add(
                $this->getQuantaForge()->make($command)
            );
        }

        return $commands;
    }

    /**
     * Get an array of QuantaForge tailored casters.
     *
     * @return array
     */
    protected function getCasters()
    {
        $casters = [
            'QuantaForge\Support\Collection' => 'QuantaForge\Tinker\TinkerCaster::castCollection',
            'QuantaForge\Support\HtmlString' => 'QuantaForge\Tinker\TinkerCaster::castHtmlString',
            'QuantaForge\Support\Stringable' => 'QuantaForge\Tinker\TinkerCaster::castStringable',
        ];

        if (class_exists('QuantaForge\Database\Eloquent\Model')) {
            $casters['QuantaForge\Database\Eloquent\Model'] = 'QuantaForge\Tinker\TinkerCaster::castModel';
        }

        if (class_exists('QuantaForge\Process\ProcessResult')) {
            $casters['QuantaForge\Process\ProcessResult'] = 'QuantaForge\Tinker\TinkerCaster::castProcessResult';
        }

        if (class_exists('QuantaForge\Foundation\Application')) {
            $casters['QuantaForge\Foundation\Application'] = 'QuantaForge\Tinker\TinkerCaster::castApplication';
        }

        $config = $this->getQuantaForge()->make('config');

        return array_merge($casters, (array) $config->get('tinker.casters', []));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['include', InputArgument::IS_ARRAY, 'Include file(s) before starting tinker'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['execute', null, InputOption::VALUE_OPTIONAL, 'Execute the given code using Tinker'],
        ];
    }
}
