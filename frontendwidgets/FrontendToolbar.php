<?php namespace Initbiz\PowerComponents\FrontendWidgets;

use \Initbiz\PowerComponents\Classes\FrontendWidgetBase;
use \Initbiz\PowerComponents\FrontendWidgets\FrontendSearch;

class FrontendToolbar extends FrontendWidgetBase
{
    //
    // Configurable properties
    //

    /**
     * @var string Partial name containing the toolbar buttons
     */
    public $buttons;

    /**
     * @var array|string Search widget configuration or partial name, optional.
     */
    public $search;

    //
    // Object properties
    //

    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'toolbar';

    /**
     * @var WidgetBase Reference to the search widget object.
     */
    public $searchWidget;

    /**
     * @var array List of CSS classes to apply to the toolbar container element
     */
    public $cssClasses = [];

    /**
     * Initialize the widget, called by the constructor and free from its parameters.
     */
    public function init()
    {
        $this->fillFromConfig([
            'buttons',
            'search',
        ]);

        /*
         * Prepare the search widget (optional)
         */
        if (isset($this->search)) {
            if (is_string($this->search)) {
                $searchConfig = $this->makeConfig(['partial' => $this->search]);
            } else {
                $searchConfig = $this->makeConfig($this->search);
            }

            $searchConfig->alias = $this->alias . 'Search';
            $this->searchWidget = $this->makeFrontendWidget(FrontendSearch::class, $this->component, $searchConfig);
            $this->searchWidget->init();
            $this->searchWidget->prepareVars();
        }
    }

    /**
     * Renders the widget.
     */
    public function render($options = [])
    {
        $this->prepareVars();
        return $this->makePartial('toolbar');
    }

    /**
     * Prepares the view data
     */
    public function prepareVars()
    {
        $this->vars['search'] = $this->searchWidget ? $this->searchWidget->render($this->component->options) : '';
        $this->vars['cssClasses'] = implode(' ', $this->cssClasses);
    }

    public function getSearchWidget()
    {
        return $this->searchWidget;
    }
}
