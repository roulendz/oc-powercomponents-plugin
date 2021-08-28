<?php namespace Initbiz\PowerComponents\Console;

use Str;
use File;
use Artisan;
use Exception;
use Cms\Classes\Theme;
use October\Rain\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Initbiz\PowerComponents\Classes\PluginFileManipulator;

class PcUtil extends GeneratorCommand
{
    /**
     * @var string The console command name.
     */
    protected $name = 'pc:util';

    /**
     * @var string The console command description.
     */
    protected $description = 'PowerComponents CLI Utility';

    /**
     * A mapping of stub to generated file.
     * @var array
     */
    protected $stubs = [];

    /**
     * Plugin Code, it needs to be set for proper stubs rendering.
     * @var string
     */
    protected $pluginCode;

    /**
     * Absolute paths for stubs destination
     * @var boolean
     */
    protected $absolutePaths;

    /**
     * Prepare variables for stubs.
     *
     */
    protected function prepareVars()
    {
        $this->vars = $this->processVars($this->vars);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $commandName = $this->argument('name');
        $arguments = $this->argument('arguments');

        $method = 'util'.studly_case($commandName);

        $methods = preg_grep('/^util/', get_class_methods(get_called_class()));
        $list = array_map(function ($item) {
            return "pc:".snake_case($item, " ");
        }, $methods);


        if (!$commandName) {
            $message = 'There are no commands defined.';
            $message .= "\n\nDid you mean one of these?\n    ";
            $message .= implode("\n    ", $list);
            throw new \InvalidArgumentException($message);
        }

        if (!method_exists($this, $method)) {
            $this->error(sprintf('Utility command "%s" does not exist!', $commandName));
            return;
        }

        $this->$method($arguments);
    }


    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::OPTIONAL , 'Power Components CLI command'],
            ['arguments', InputArgument::IS_ARRAY, 'Parameters to command'],
        ];
    }

    protected function utilCreate($arguments = [])
    {
        $supportedTypes = ['crud', 'components', 'pages'];
        $type = array_shift($arguments);

        if (!in_array($type, $supportedTypes)) {
            $message = 'There are no commands defined.';
            $message .= "\n\nDid you mean one of these?\n    pc:util create ";
            $message .= implode("\n    pc:util create ", $supportedTypes);
            throw new \InvalidArgumentException($message);
        }

        $method = 'create'.studly_case($type);

        $this->$method($arguments);
    }

    public function createCrud($arguments)
    {
        $this->pluginCode = array_shift($arguments);
        $parts = explode('.', $this->pluginCode);
        $pluginName = array_pop($parts);
        $pluginAuthor = array_pop($parts);
        $modelName = array_shift($arguments);
        $urlPrefix = array_shift($arguments);

        //plugin not found, want to create some? :)
        $pluginPath = 'plugins/'.Str::lower($pluginAuthor).'/'.Str::lower($pluginName).'/';
        if (!File::exists($pluginPath.'Plugin.php')) {
            if (!$this->confirm($this->pluginCode.' plugin not found. Do you want to create it?')) {
                $this->comment('Command Cancelled!');
                return;
            }
            $this->call('create:plugin', ['plugin' => $this->pluginCode]);
        }

        //model not found, want to create some? :)
        if (!File::exists($pluginPath.'models/'.studly_case($modelName).'.php')) {
            if (!$this->confirm($modelName.' model not found in '.$this->pluginCode.' plugin. Do you want to create it?')) {
                $this->comment('Command Cancelled!');
                return;
            }
            $this->call('create:model', ['plugin' => $this->pluginCode, 'model' => $modelName]);
        }

        $this->createComponents([$this->pluginCode, $modelName]);
        $this->createPages([$modelName, $urlPrefix]);

        if (!$this->confirm('Proceed with registering CRUD components in '.$this->pluginCode.' Plugin.php?')) {
            $this->comment('Command Cancelled!');
            return;
        }
        $crudOperations = ['Create', 'Update', 'Preview', 'List'];
        foreach ($crudOperations as $crudOperation) {
            $this->registerComponent([$modelName.$crudOperation]);
        }
    }

    public function createComponents($arguments)
    {
        $this->pluginCode = array_shift($arguments);
        $modelName = array_shift($arguments);

        if (!$this->confirm('Proceed with creating CRUD components for '.$this->pluginCode.' plugin and '.$modelName.' model?')) {
            $this->comment('Command Cancelled!');
            return;
        }

        $parts = explode('.', $this->pluginCode);
        $pluginName = array_pop($parts);
        $pluginAuthor = array_pop($parts);

        $this->vars = [
            'name' => $pluginName,
            'author' => $pluginAuthor,
            'model' => $modelName
        ];
        $this->prepareVars();

        $this->stubs = [
            'stubs/pcutil/components/create/component.stub'    => 'components/{{studly_model}}Create.php',
            'stubs/pcutil/components/create/default.stub'      => 'components/{{lower_model}}create/default.htm',
            'stubs/pcutil/components/create/config_form.stub'  => 'components/{{lower_model}}create/config_form.yaml',

            'stubs/pcutil/components/update/component.stub'    => 'components/{{studly_model}}Update.php',
            'stubs/pcutil/components/update/default.stub'      => 'components/{{lower_model}}update/default.htm',
            'stubs/pcutil/components/update/config_form.stub'  => 'components/{{lower_model}}update/config_form.yaml',

            'stubs/pcutil/components/preview/component.stub'   => 'components/{{studly_model}}Preview.php',
            'stubs/pcutil/components/preview/default.stub'     => 'components/{{lower_model}}preview/default.htm',
            'stubs/pcutil/components/preview/config_form.stub' => 'components/{{lower_model}}preview/config_form.yaml',

            'stubs/pcutil/components/list/component.stub'      => 'components/{{studly_model}}List.php',
            'stubs/pcutil/components/list/default.stub'        => 'components/{{lower_model}}list/default.htm',
            'stubs/pcutil/components/list/config_list.stub'    => 'components/{{lower_model}}list/config_list.yaml',
        ];

        $this->makeStubs();

        $this->info('CRUD components created successfully.');
    }

    public function createPages($arguments)
    {
        $modelName = array_shift($arguments);
        $urlPrefix = array_shift($arguments);

        $this->absolutePaths = true;

        $pagesPath = '/themes/'.Theme::getActiveTheme()->getDirName().'/pages';

        if (!$this->confirm('Proceed with creating CRUD sites for '.Theme::getActiveThemeCode().' theme and '.$modelName.' model?')) {
            $this->comment('Command Cancelled!');
            return;
        }

        $pluralModelName = Str::lower(str_plural($modelName));
        $lowerModelName = Str::lower($modelName);

        $this->vars = [
            'model'          => $modelName,
            'listpage'       => $lowerModelName.'/list-'.$lowerModelName,
            'listpageurl'    => $urlPrefix.'/'.$pluralModelName,
            'createpage'     => $lowerModelName.'/create-'.$lowerModelName,
            'createpageurl'  => $urlPrefix.'/'.$pluralModelName.'/create',
            'previewpage'    => $lowerModelName.'/preview-'.$lowerModelName,
            'previewpageurl' => $urlPrefix.'/'.$pluralModelName.'/:id/preview',
            'updatepage'     => $lowerModelName.'/update-'.$lowerModelName,
            'updatepageurl'  => $urlPrefix.'/'.$pluralModelName.'/:id/update',
        ];
        $this->prepareVars();

        $this->stubs = [
            'stubs/pcutil/pages/list.stub'      => $pagesPath.'/'.$lowerModelName.'/list-{{lower_model}}.htm',
            'stubs/pcutil/pages/create.stub'    => $pagesPath.'/'.$lowerModelName.'/create-{{lower_model}}.htm',
            'stubs/pcutil/pages/update.stub'    => $pagesPath.'/'.$lowerModelName.'/update-{{lower_model}}.htm',
            'stubs/pcutil/pages/preview.stub'   => $pagesPath.'/'.$lowerModelName.'/preview-{{lower_model}}.htm'
        ];

        $this->makeStubs();

        $this->info('CRUD pages created successfully.');
    }

    protected function utilRegister($arguments = [])
    {
        $supportedTypes = ['component'];
        $type = array_shift($arguments);

        $this->pluginCode = array_shift($arguments);

        if (!$this->confirm('Proceed with registering '.$type.' in '.$this->pluginCode.'?')) {
            $this->comment('Command Cancelled!');
            return;
        }

        if (!in_array($type, $supportedTypes)) {
            $message = 'There are no commands defined.';
            $message .= "\n\nDid you mean one of these?\n    pc:util register ";
            $message .= implode("\n    pc:util register ", $supportedTypes);
            throw new \InvalidArgumentException($message);
        }

        $method = 'register'.studly_case($type);

        $this->$method($arguments);
    }

    /**
     * register component in plugin file
     * @param  array $arguments ['componentCode']
     * @return void
     */
    public function registerComponent($arguments)
    {
        $pluginFileManipulator = new PluginFileManipulator($this->pluginCode);

        if ($pluginFileManipulator->register("component", $arguments)) {
            $this->info('Component registered');
        } else {
            $this->info('Component registering failed');
        }
    }

    // /**
    //  * Get the desired plugin name from the input.
    //  * The method is overrided. We have to set $this->pluginCode before making stubs
    //  *
    //  * @return string
    //  */
    // protected function getPluginInput()
    // {
    //     return $this->pluginCode;
    // }

    /**
     * getPluginInput gets the desired plugin name from the input
     */
    protected function getPluginInput(): string
    {
        return $this->argument('plugin');
    }

    /**
     * Get the plugin path from the input.
     * The method is overrided so that we can use other paths than plugins
     *
     * @return string
     */
    protected function getDestinationPath(): string
    {
        $plugin = $this->getPluginInput();

        $parts = explode('.', $plugin);
        $name = array_pop($parts);
        $author = array_pop($parts);

        return plugins_path(strtolower($author) . '/' . strtolower($name));
    }
}
