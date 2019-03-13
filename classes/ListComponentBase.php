<?php namespace Initbiz\PowerComponents\Classes;

use App;
use Str;
use Lang;
use Event;
use Flash;
use Cms\Classes\Page;
use Cms\Classes\CodeBase;
use ApplicationException;
use Initbiz\PowerComponents\Classes\Helpers;
use Initbiz\PowerComponents\FrontendWidgets\FrontendList;
use Initbiz\PowerComponents\FrontendWidgets\FrontendListToolbar;
use Initbiz\PowerComponents\Classes\EmpoweredComponentBase;

abstract class ListComponentBase extends EmpoweredComponentBase
{
    /*
     * Colums parsed from config_list.yaml
     */
    public $columns;

    /**
     * @var \Initbiz\PowerComponents\FrontendWidgets\FrontendList Reference to the widget object.
     */
    protected $listWidget;

    /**
     * @var array Configuration values that must exist when applying the primary config file.
     * - modelClass: Class name for the model
     * - list: List column definitions
     */
    protected $requiredConfig = ['modelClass', 'columns'];

    /**
     * @var \Initbiz\PowerComponents\FrontendWidgets\FrontendListToolbar Reference to the widget object.
     */
    protected $toolbarWidget;

    /**
     * @var Model The initialized model used by the list.
     */
    public $model;

    public function __construct(CodeBase $cmsObject = null, $properties = [])
    {
        parent::__construct($cmsObject, $properties);

        if (!App::runningInBackend()) {
            $this->defaultSuffix = 'pc-list';

            /*
             * If listConfig not set in component then set the default value
             */
            $this->addDynamicProperty('listConfig', 'config_list.yaml');

            $this->config = $this->makeConfig($this->listConfig, $this->requiredConfig);
            $this->config->modelClass = Str::normalizeClassName($this->config->modelClass);

            $this->model = $this->createModel();

            $this->prepareViewPaths();

            $this->listWidget = $this->createListWidget();

            /*
             * Prepare the toolbar widget (optional)
             */
            if (isset($this->config->toolbar)) {
                $this->toolbarWidget = $this->prepareToolbar();
            }
        }
    }

    protected function prepareViewPaths()
    {
        $viewPathClasses = [
            FrontendList::class
        ];
        if (isset($this->config->toolbar)) {
            $viewPathClasses[] = FrontendListToolbar::class;
        }

        $this->viewPaths = $this->extractViewPaths($viewPathClasses);

        $this->injectPcViewPaths();

        $this->addViewPaths();
    }

    public function prepareToolbar()
    {
        $toolbarConfig = $this->makeConfig($this->config->toolbar);
        $toolbarConfig->alias = $this->listWidget->alias . 'Toolbar';
        $toolbarWidget = $this->makeFrontendWidget(FrontendListToolbar::class, $toolbarConfig);

        /*
         * Link the Search Widget to the List Widget
         */
        if ($searchWidget = $toolbarWidget->getSearchWidget()) {
            $listWidget = $this->listWidget;
            $this->bindEvent('pc.list.search.submit', function () use ($listWidget, $searchWidget) {
                $listWidget->setSearchTerm($searchWidget->getActiveTerm());
            });

            $this->listWidget->setSearchOptions([
                'mode' => $searchWidget->mode,
                'scope' => $searchWidget->scope,
            ]);

            // Find predefined search term
            $this->listWidget->setSearchTerm($searchWidget->getActiveTerm());
        }

        $toolbarWidget->addViewPath($this->guessViewPathFrom(FrontendListToolbar::class));
        $toolbarWidget->addViewPath($this->guessViewPathFrom(get_class($this)));
        return $toolbarWidget;
    }


    public function createModel()
    {
        $class = $this->config->modelClass;
        $model = new $class;
        return $model;
    }

    public function createListWidget()
    {
        $config = $this->getConfig();
        $config = $this->makeConfig($config);
        $config->model = $this->model;
        $config->viewPaths = $this->viewPaths;

        return $this->makeFrontendWidget(FrontendList::class, $config);
    }

    /*
     * Component methods to override
     */


    public function componentDetails()
    {
        return [
            'name'        => 'List Component',
            'description' => 'No description provided yet...'
        ];
    }

    //List page variables

    public function listPageVariables()
    {
        $listOptions['createPageUrl'] = Page::Url($this->property('createPage'));
        $listOptions['componentAlias'] = $this->alias;

        $this->appendPcViewBag($this->alias, $listOptions);
    }

    //List component properties

    public function listProperties()
    {
        return [
            'recordPage' => [
                'title'        => 'initbiz.powercomponents::lang.list_component_properties.record_page',
                'description' => 'initbiz.powercomponents::lang.list_component_properties.record_page_desc',
                'type'        => 'dropdown'
            ],
            'createPage' => [
                'title'        => 'initbiz.powercomponents::lang.list_component_properties.create_page',
                'description' => 'initbiz.powercomponents::lang.list_component_properties.create_page_desc',
                'type'        => 'dropdown'
            ]
        ];
    }

    public function getRecordPageOptions()
    {
        return Helpers::getFileListToDropdown();
    }

    public function getCreatePageOptions()
    {
        return Helpers::getFileListToDropdown();
    }

    public function defineProperties()
    {
        return $this->listProperties();
    }

    public function onRun()
    {
        $this->listPageVariables();
    }

    /**
     * Refresh the list with toolbar and filter widgets
     */
    public function refreshList()
    {
        $this->listWidget->prepareVars();

        $data = [
            'componentOptions' => $this->options,
            'listWidget' => $this->listWidget
        ];

        if (isset($this->toolbarWidget)) {
            $this->toolbarWidget->prepareVars();
            $data['toolbarWidget'] = $this->toolbarWidget;
        }

        $assets = $this->listWidget->getAssetPaths();

        return [ 'X_OCTOBER_ASSETS' => $assets, '#'.$this->getDivId() => $this->makePartial('list', $data)];
    }

    /**
     * Refresh the list table
     */
    public function refreshListTable()
    {
        $this->listWidget->prepareVars();

        $data = [
            'componentOptions' => $this->options,
            'listWidget' => $this->listWidget
        ];

        $assets = $this->listWidget->getAssetPaths();

        $this->defaultSuffix = 'pc-table';

        return [ 'X_OCTOBER_ASSETS' => $assets, '#'.$this->getDivId() => $this->makePartial('table', $data)];
    }


    //Ajax handlers

    /**
     * AJAX handler for refreshing whole list with toolbar and filter.
     */
    public function onRefreshList()
    {
        $this->prepareComponent();

        return $this->refreshList();
    }

    /**
     * Search field has been submitted.
     */
    public function onSubmit()
    {
        $this->prepareComponent();

        $this->defaultSuffix = 'pc-table-body';

        $searchWidgetName = $this->toolbarWidget->searchWidget->getName();
        $searchTerm = post($searchWidgetName);
        $this->toolbarWidget->searchWidget->setActiveTerm($searchTerm);
        $this->listWidget->setSearchTerm($searchTerm);

        $this->listWidget->setSearchOptions([
            'mode'  => $this->toolbarWidget->searchWidget->mode,
            'scope' => $this->toolbarWidget->searchWidget->scope,
        ]);

        $this->listWidget->prepareVars();

        $data = [
            'componentOptions' => $this->options,
            'listWidget' => $this->listWidget
        ];

        /*
         * Save or reset search term in session
         */
        $this->setActiveTerm(post($this->toolbarWidget->searchWidget->getName()));

        /*
         * Trigger class event, merge results as viewable array
         */
        $params = func_get_args();
        //In result there is a return of refreshTableBody method
        $result = $this->fireEvent('pc.list.search.submit', [$params]);
        if ($result && is_array($result)) {
            $result = call_user_func_array('array_merge', $result);
        }

        return $this->refreshListTable();
    }

    /**
     * Event handler for sorting the list.
     */
    public function onSort()
    {
        if (null === post('sortColumn')) {
            return [];
        }

        $this->prepareComponent();

        $sortOptions['column'] = $this->options['sortColumn'] ?
                                 $this->options['sortColumn']:$this->listWidget->getSortColumn();

        $sortOptions['direction'] = $this->options['sortDirection'] ?
                                    $this->options['sortDirection']:'desc';

        $this->putSession('sort', $sortOptions);

        /*
         * Persist the page number
         */
        $this->listWidget->currentPageNumber = post('page');

        return $this->refreshListTable();
    }


    /**
     * Delete records
     * @return void
     */
    public function onDelete()
    {
        $checkedIds = post('checked');

        if (!$checkedIds || !is_array($checkedIds) || !count($checkedIds)) {
            Flash::error(Lang::get('backend::lang.list.delete_selected_empty'));
            return $this->refreshList();
        }

        $this->prepareComponent();

        $query = $this->model->newQuery();

        $query->whereIn($this->model->getKeyName(), $checkedIds);

        $records = $query->get();

        $records = $this->beforeDelete($records);

        if ($records->count()) {
            foreach ($records as $record) {
                $record->delete();
            }

            Flash::success(Lang::get('backend::lang.list.delete_selected_success'));
        } else {
            Flash::error(Lang::get('backend::lang.list.delete_selected_empty'));
        }

        return $this->refreshListTable();
    }

    /**
     * Event handler to display the list set up.
     */
    public function onLoadSetup()
    {
        $this->prepareComponent();

        $options['columns'] = $this->listWidget->getSetupListColumns();
        $options['perPageOptions'] = $this->listWidget->getSetupPerPageOptions();
        $options['recordsPerPage'] = $this->listWidget->recordsPerPage;

        $this->defaultSuffix = 'pc-list-setup';

        // return [ '#'.$divId => $this->listWidget->makePartial('setup_form', ['options' => $options])];
        return $this->listWidget->makePartial('setup_form', ['options' => $options, 'componentOptions' => $this->options]);
    }

    /**
     * Event handler to apply the list set up.
     */
    public function onApplySetup()
    {
        $this->prepareComponent();
        if (($visibleColumns = post('visible_columns')) && is_array($visibleColumns)) {
            $this->listWidget->columnOverride = $visibleColumns;
            $this->putSession('visible', $this->listWidget->columnOverride);
        }

        $this->listWidget->recordsPerPage = post('records_per_page', $this->listWidget->recordsPerPage);
        $this->putSession('order', post('column_order'));
        $this->putSession('per_page', $this->listWidget->recordsPerPage);
        return $this->refreshListTable();
    }

    /**
     * Event handler for switching the page number.
     */
    public function onPaginate()
    {
        $this->prepareComponent();
        $this->listWidget->currentPageNumber = post('page');
        return $this->refreshListTable();
    }

    /**
     * AJAX handler for getting raw list elements - without the partial
     */
    public function onGetRawData()
    {
        //TODO: may be a good idea
        return true;
    }

    protected function prepareWidgets()
    {
        $this->listWidget->componentAlias = $this->alias;
        $this->listWidget->options = $this->options;

        if (isset($this->toolbarWidget)) {
            $this->toolbarWidget->options = $this->options;
            $this->toolbarWidget->componentAlias = $this->alias;
        }
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


    // Overrides

    public function beforeDelete($records)
    {
        return $records;
    }
}
