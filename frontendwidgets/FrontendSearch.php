<?php namespace Initbiz\PowerComponents\FrontendWidgets;

use Lang;
use \Initbiz\PowerComponents\Classes\FrontendWidgetBase;

/**
 * Search Widget
 * Used for building a toolbar, Renders a search container.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class FrontendSearch extends FrontendWidgetBase
{
    //
    // Configurable properties
    //

    /**
     * @var string Search placeholder text.
     */
    public $prompt;

    /**
     * @var bool Field show grow when selected.
     */
    public $growable = true;

    /**
     * @var string Custom partial file definition, in context of the controller.
     */
    public $partial;

    /**
     * @var string Defines the search mode. Commonly passed to the searchWhere() query.
     */
    public $mode;

    /**
     * @var string Custom scope method name. Commonly passed to the query.
     */
    public $scope;

    //
    // Object properties
    //

    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'search';

    /**
     * @var string Active search term pulled from session data.
     */
    protected $activeTerm;

    /**
     * @var array List of CSS classes to apply to the list container element.
     */
    public $cssClasses = [];

    /**
     * Initialize the widget, called by the constructor and free from its parameters.
     */
    public function init()
    {
        $this->fillFromConfig([
            'prompt',
            'partial',
            'growable',
            'scope',
            'mode',
            'viewPaths',
        ]);

        /*
         * Add CSS class styles
         */
        $this->cssClasses[] = 'icon search';

        $viewPath = [$this->extractViewPaths(get_class($this))];

        if (is_array($this->viewPaths)) {
            $this->viewPaths = array_merge($viewPath, $this->viewPaths);
        } else {
            $this->viewPaths = $viewPath;
        }

        if ($this->growable) {
            $this->cssClasses[] = 'growable';
        }
    }

    /**
     * Renders the widget.
     */
    public function render($options = [])
    {
        $this->prepareVars();

        if ($this->partial) {
            return $this->controller->makePartial($this->partial);
        } else {
            return $this->makePartial('search', ['componentOptions' => $options]);
        }
    }

    /**
     * Prepares the view data
     */
    public function prepareVars()
    {
        $this->vars['cssClasses'] = implode(' ', $this->cssClasses);
        $this->vars['placeholder'] = Lang::get($this->prompt);
        $this->vars['value'] = $this->getActiveTerm();
    }

    /**
     * Returns an active search term for this widget instance.
     */
    public function getActiveTerm()
    {
        return $this->activeTerm = $this->getSession('term', '');
    }

    /**
     * Sets an active search term for this widget instance.
     */
    public function setActiveTerm($term)
    {
        if (strlen($term)) {
            $this->putSession('term', $term);
        } else {
            $this->resetSession();
        }

        $this->activeTerm = $term;
    }

    /**
     * Returns a value suitable for the field name property.
     * @return string
     */
    public function getName()
    {
        return $this->alias . '[term]';
    }
}
