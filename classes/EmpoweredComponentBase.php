<?php namespace Initbiz\PowerComponents\Classes;

use Cms\Classes\CodeBase;
use Cms\Classes\ComponentBase;

abstract class EmpoweredComponentBase extends ComponentBase
{
    use \System\Traits\EventEmitter;
    use \Initbiz\PowerComponents\Traits\FrontendWidgetMaker;
    use \Initbiz\PowerComponents\Traits\ViewMaker;
    use \Initbiz\PowerComponents\Traits\ConfigMaker;
    use \Initbiz\PowerComponents\Traits\SessionMaker;

    /**
     * @var Cms\Classes\ComponentBase this object to use in widgets, probably in future releases will be removed
     */
    public $component;

    /*
     * Component's config parsed from yaml in setConfig method
     */
    protected $config;

    /*
     * Options sent to component render functions
     * By default it is for variables defined in *PageVariables traits
     */
    public $options;

    /**
     * @var string Default suffix to add in div ID
     */
    public $defaultSuffix;

    /*
     * Power Components View Bag injected to page. Right now array, maybe someday component
     */
    public $pcViewBag;

    /**
     * Component constructor. Takes in the page or layout code section object
     * and properties set by the page or layout.
     * @param null|CodeBase $cmsObject
     * @param array $properties
     */
    public function __construct(CodeBase $cmsObject = null, $properties = [])
    {
        parent::__construct($cmsObject, $properties);

        //TODO: to be changed in later versions, hotfix
        $this->component = $this;

        $this->configPath =  $this->guessConfigPathFrom(get_class($this));
    }

    protected function prepareComponent()
    {
        $this->options = post();

        //To override by components
        $this->prepareWidgets();
    }

    /**
     * Returns the ID of div with this component
     * @param string $suffix An extra string to append to the ID.
     * @return string A unique identifier.
     */
    public function getDivId($suffix = null)
    {
        $id = $this->alias;

        $id = $this->defaultSuffix.'-'.$id;

        if ($suffix !== null) {
            $id .= '-' . $suffix;
        }

        return $id;
    }

    /**
     * Returns a fully qualified event handler name for this component.
     * @param string $name The ajax event handler name.
     * @return string
     */
    public function getEventHandler($name)
    {
        return $this->alias . '::' . $name;
    }

    /**
     * Method setting the config property
     * @param string $configFileName yaml file containing components config
     */
    protected function setConfig($configFileName)
    {
        $this->config = $this->makeConfig($configFileName);
    }

    //Overrides

    /**
     * Override for children to be run in prepareComponent method
     */
    protected function prepareWidgets()
    {
    }

    /**
     * Executes the event cycle when running an AJAX handler.
     * @return boolean Returns true if the handler was found. Returns false otherwise.
     */
    public function runAjaxHandler($handler)
    {
        /*
         * Extensibility
         */
        if ($event = $this->fireSystemEvent('cms.component.beforeRunAjaxHandler', [$handler])) {
            return $event;
        }

        $result = $this->$handler();

        /*
         * Extensibility
         */
        if ($event = $this->fireSystemEvent('cms.component.runAjaxHandler', [$handler, $result])) {
            return $event;
        }

        return $result;
    }

    public function appendPcViewBag($key, $value)
    {
        $newData[$key] = $value;

        if (!$this->page['pcViewBag']) {
            $this->page['pcViewBag'] = [];
        }
        $pcViewBag = $this->page['pcViewBag'];

        $this->page['pcViewBag'] = $pcViewBag + $newData;
    }
}
