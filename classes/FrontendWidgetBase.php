<?php namespace Initbiz\PowerComponents\Classes;

use App;
use Initbiz\PowerComponents\Classes\FrontendWidgetManager;
use October\Rain\Html\Helper as HtmlHelper;
use October\Rain\Extension\Extendable;

/**
 */
abstract class FrontendWidgetBase extends Extendable
{
    use \System\Traits\EventEmitter;
    use \Initbiz\PowerComponents\Traits\FrontendWidgetMaker;
    use \Initbiz\PowerComponents\Traits\AssetMaker;
    use \Initbiz\PowerComponents\Traits\ViewMaker;
    use \Initbiz\PowerComponents\Traits\ConfigMaker;
    use \Initbiz\PowerComponents\Traits\SessionMaker;

    /**
     * @var \Backend\Classes\Controller Backend controller object.
     */
    protected $controller;

    /*
     * Frontend widget config
     */
    protected $config;

    /**
     * @var Cms\Classes\ComponentBase Component using the behavior
     */
    public $component;

    /**
     * @var string Defined alias used for this widget.
     */
    public $alias;

    /**
     * @var array Substitute of controller's $vars attribute
     */
    public $vars = [];

    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'widget';

    protected $widgetManager;

    /**
     * Constructor
     * @param \Backend\Classes\Controller $controller
     * @param array $configuration Proactive configuration definition.
     */
    public function __construct($controller, $component, $configuration = [])
    {
        if (!App::runningInBackend()) {
            $this->controller = $controller;
            $this->controller->widget = null;

            $this->component = $component;
            /*
             * Apply configuration values to a new config object, if a parent
             * constructor hasn't done it already.
             */
            if ($this->config === null) {
                $this->config = $this->makeConfig($configuration);
            }
            $this->fillFromConfig();

            /*
             * If no alias is set by the configuration.
             */
            if (!isset($this->alias)) {
                $this->alias = (isset($this->config->alias)) ? $this->config->alias : $this->defaultAlias;
            }

            $this->widgetManager = FrontendWidgetManager::instance();

            $this->viewPaths[] = $this->configPath = $this->guessViewPath('/partials');
            $this->assetPath = $this->guessViewPath('/assets', true);
            
            /*
             * Prepare assets used by this widget.
             */
            $this->loadAssets();

            parent::__construct();

            /*
             * Initialize the widget.
             */
            if (!$this->getConfig('noInit', false)) {
                $this->init();
            }

            $this->injectPcViewPaths();

            $this->addViewPaths();
        }
    }

    /**
     * Returns a unique ID for this widget. Useful in creating HTML markup.
     * @param string $suffix An extra string to append to the ID.
     * @return string A unique identifier.
     */
    public function getId($suffix = null)
    {
        $id = class_basename(get_called_class());

        if ($this->alias != $this->defaultAlias) {
            $id .= '-' . $this->alias;
        }

        if ($suffix !== null) {
            $id .= '-' . $suffix;
        }

        return HtmlHelper::nameToId($id);
    }

    /**
     * Loading assets in widgets
     * @return void
     */
    protected function loadAssets()
    {
    }

    /**
     * Load PowerComponents assets in widget
     * @return void
     */
    protected function loadPcAssets()
    {
        $locale = App::getLocale();

        $this->addCss(['~/modules/backend/assets/vendor/sweet-alert/sweet-alert.css']);

        $this->addJs(['~/plugins/initbiz/powercomponents/assets/ui/storm.js',
                      '~/modules/backend/assets/vendor/sweet-alert/sweet-alert.js',
                      '~/modules/backend/assets/js/october.lang.js',
                      '~/modules/system/assets/js/lang/lang.'.$locale.'.js',
                      '~/modules/backend/assets/js/october.alert.js']);
    }

    /**
     * Returns a event handler name for this widget embedded in power component.
     * @param string $name The ajax event handler name.
     * @return string
     */
    public function getEventHandler($name)
    {
        return $this->component->alias . '::' . $name;
    }

    /**
     * Binds a widget to the controller for safe use.
     * @return void
     */
    public function bindToController()
    {
        if ($this->controller->widget === null) {
            $this->controller->widget = new \stdClass;
        }

        $this->controller->widget->{$this->alias} = $this;
    }
}
