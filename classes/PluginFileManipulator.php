<?php namespace Initbiz\PowerComponents\Classes;

use Str;
use File;
use Exception;
use Carbon\Carbon;

/**
 * Plugin file manipulator
 */
class PluginFileManipulator
{
    protected $pluginCode;

    protected $filepath;

    protected $fileContents;

    protected $newFileContents;

    protected $pluginName;

    protected $pluginAuthor;

    protected $backupSuffix = '.bak';

    public function __construct($pluginCode)
    {
        $this->pluginCode = $pluginCode;

        $parts = explode('.', $this->pluginCode);

        $this->pluginName = array_pop($parts);

        $this->pluginAuthor = array_pop($parts);

        $this->filepath = 'plugins/'.Str::lower($this->pluginAuthor).'/'.Str::lower($this->pluginName).'/Plugin.php';

        $this->loadFileContents();
    }

    protected function backupFile()
    {
        //Backup in case of mess in file
        $carbonNow = Carbon::now()->format('Y_m_d_H_i_s');

        $backupFilePath = $this->filepath."_".$carbonNow.$this->backupSuffix;

        File::copy($this->filepath, $backupFilePath);
    }

    protected function loadFileContents()
    {
        $this->fileContents = File::get(base_path($this->filepath));

        $this->newFileContents = $this->fileContents;
    }

    public function validateRegisterMethodSyntax($type)
    {
        $type = studly_case($type);
        // if return []; // Remove this line to activate line is present, then it is probably first running
        $regex  ='/public function register'.$type.'\s*\(\)\n';
        $regex .= '\s*{\n';
        $regex .= '\s*return\s+\[\]; \/\/ Remove this line to activate\n';
        $regex .= '/s';
        $matched = preg_match($regex, $this->newFileContents);

        if ($matched === 1) {
            return true;
        }

        //check register method syntax
        $regex  ='/public function register'.$type.'\s*\(\)\n';
        $regex .= '\s*{\n';
        $regex .= '\s*return\s+\[';
        $regex .= '(\s*\'.*\'\s*\=\>\s*\'.*\',\s*)*';
        $regex .= '\];\n';
        $regex .= '\s*}';
        $regex .= '/s';
        $matched = preg_match($regex, $this->newFileContents);

        return $matched;
    }

    public function register($type, $data)
    {
        //Types in lower case
        $supportedTypes = ['component'];

        if (!in_array(Str::lower($type), $supportedTypes)) {
            throw new Exception('Unsupported type.');
        }

        $type = studly_case($type);

        $method = 'register'.$type;

        if (Str::lower($type) === 'component') {
            //last parameter is componentName to pass to registerComponent method
            $data = array_shift($data);
        }

        return $this->$method($data);
    }

    public function replaceMethodContents($methodName, $methodContents)
    {
        $regex  ='/public function '.$methodName.'\s*\(\)\n';
        $regex .= '\s*{\n';
        $regex .= '(\s*return\s+\[';
        $regex .= '(\s*\'.*\'\s*\=\>\s*\'.*\',\s*)*';
        $regex .= '\];( \/\/ Remove this line to activate\n){0,1}\n)+';
        $regex .= '\s*}';
        $regex .= '/s';

        $content =  'public function '.$methodName."()\n".
                    "    {\n".
                    $methodContents.
                    "\n    }";

        $this->newFileContents = preg_replace($regex, $content, $this->newFileContents);
        return true;
    }

    public function getMethodContents($methodName)
    {
        $regex  ='/public function '.$methodName.'\s*\(\)\n';
        $regex .= '\s*{\n';
        $regex .= '(\s*return\s+\[';
        $regex .= '(\s*\'.*\'\s*\=\>\s*\'.*\',\s*)*';
        $regex .= '\];( \/\/ Remove this line to activate\n){0,1}\n)+';
        $regex .= '\s*}';
        $regex .= '/s';

        //Get contents of register method
        preg_match($regex, $this->newFileContents, $match);

        return $match[0];
    }

    public function addMethodWithContents($methodName, $methodContents)
    {
        $content =  "\n\n    public function ".
                    $methodName."()\n".
                    "    {\n".
                    $methodContents.
                    "\n    }".
                    "\n}";

        $this->newFileContents = preg_replace('/\n}/s', $content, $this->newFileContents);
    }

    public function removeMethod($methodName)
    {
        $this->newFileContents = preg_match('/'.$methodName.'(.*)\];\s*}/s', '', $match);
    }

    public function methodDefined($methodName)
    {
        $defined = preg_match('/'.$methodName.'.*\];\s*}/s', $this->newFileContents, $match);
        if ($defined === 1) {
            return true;
        }

        return false;
    }

    public function saveNewFileContents()
    {
        //Before saving create backup of file
        $this->backupFile();

        //at the end replace whole method with new content
        File::put($this->filepath, $this->newFileContents);
    }

    public function registerComponent($componentName)
    {
        $newLine  = "            '";
        $newLine .= Str::studly($this->pluginAuthor)."\\";
        $newLine .= Str::studly($this->pluginName)."\\";
        $newLine .= "Components\\";
        $newLine .= Str::studly($componentName);
        $newLine .= "' => '";
        $newLine .= Str::camel($componentName)."',";

        //Method registerComponents is not defined in file
        if (!$this->methodDefined("registerComponents")) {
            $content  = "        return [\n";
            $content .= $newLine;
            $content .= "\n        ];";

            $this->addMethodWithContents("registerComponents", $content);

            $this->saveNewFileContents();

            return true;
        }

        if (!$this->validateRegisterMethodSyntax('Components')) {
            throw new Exception('Unsupported registerComponent method syntax. Read the docs.');
        }

        $registerMethod = $this->getMethodContents("registerComponents");

        //If method has empty return entry
        if (preg_match('/return \[\];/', $registerMethod, $match)) {
            $content  = "        return [\n";
            $content .= $newLine;
            $content .= "\n        ];";

            $this->replaceMethodContents("registerComponents", $content);

            if (!$this->validateRegisterMethodSyntax('Components')) {
                throw new Exception('Something went wrong, restore the file from backup');
            }

            $this->saveNewFileContents();

            return true;
        }

        //If method is defined, and it has contents, time to get current content
        preg_match('/return \[.*?\]/s', $registerMethod, $match);

        //split lines
        $lines = preg_split('/\n|\r\n?/', $match[0]);

        //Drop return statement and closing ]
        array_shift($lines);
        array_pop($lines);

        $lines[] = $newLine;

        $content  = "        return [\n";
        foreach ($lines as $line) {
            $content .= $line."\n";
        }
        $content .= "        ];\n";

        $this->replaceMethodContents("registerComponents", $content);

        $this->saveNewFileContents();

        return true;
    }
}
