<?php namespace Initbiz\PowerComponents\FrontendFormWidgets;

use Backend\Classes\FormField;
use Initbiz\PowerComponents\Classes\FrontendFormWidgetBase;
use ApplicationException;

/**
 * Repeater Form Widget
 */
class Repeater extends FrontendFormWidgetBase
{
    const INDEX_PREFIX = '___index_';
    const GROUP_PREFIX = '___group_';

    //
    // Configurable properties
    //

    /**
     * @var array Form field configuration
     */
    public $form;

    /**
     * @var string Prompt text for adding new items.
     */
    public $prompt = 'Add new item';

    /**
     * @var bool Items can be sorted.
     */
    public $sortable = false;

    /**
     * @var int Maximum repeated items allowable.
     */
    public $maxItems = null;

    //
    // Object properties
    //

    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'repeater';

    /**
     * @var string Form field name for capturing an index.
     */
    protected $indexInputName;

    /**
     * @var int Count of repeated items.
     * TODO: Decremented the value because of https://github.com/octobercms/october/issues/2986 that in our scenario makes sense
     * If this will work as expected, than create PR
     */
    protected $indexCount = -1;

    /**
     * @var array Meta data associated to each field, organised by index
     */
    protected $indexMeta = [];

    /**
     * @var array Collection of form widgets.
     */
    protected $formWidgets = [];

    /**
     * @var bool Stops nested repeaters populating from previous sibling.
     */
    protected static $onAddItemCalled = false;

    protected $useGroups = false;

    /**
     * @var string Form field name for capturing an index.
     */
    protected $groupInputName;

    protected $groupDefinitions = [];

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fillFromConfig([
            'form',
            'prompt',
            'sortable',
            'maxItems',
        ]);

        if ($this->formField->disabled) {
            $this->previewMode = true;
        }

        $fieldName = $this->formField->getName(false);
        $this->indexInputName = self::INDEX_PREFIX.$fieldName;
        $this->groupInputName = self::GROUP_PREFIX.$fieldName;

        $this->processGroupMode();

        if (!self::$onAddItemCalled) {
            $this->processExistingItems();
        }
    }

    /**
     * Renders the widget.
     */
    public function render($options = [])
    {
        $this->prepareVars();

        return $this->makePartial('repeater', $options);
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        // Refresh the loaded data to support being modified by filterFields
        // @see https://github.com/octobercms/october/issues/2613
        if (!self::$onAddItemCalled) {
            $this->processExistingItems();
        }

        if ($this->previewMode) {
            foreach ($this->formWidgets as $widget) {
                $widget->previewMode = true;
            }
        }

        $this->vars['indexInputName'] = $this->indexInputName;
        $this->vars['groupInputName'] = $this->groupInputName;

        $this->vars['prompt'] = $this->prompt;
        $this->vars['formWidgets'] = $this->formWidgets;
        $this->vars['maxItems'] = $this->maxItems;

        $this->vars['useGroups'] = $this->useGroups;
        $this->vars['groupDefinitions'] = $this->groupDefinitions;
    }

    /**
     * @inheritDoc
     */
    protected function loadAssets()
    {
        $this->addCss(
            ['~/plugins/initbiz/powercomponents/frontendformwidgets/repeater/assets/css/repeater.css']
        );
        $this->addJs(
            ['~/plugins/initbiz/powercomponents/frontendformwidgets/repeater/assets/js/repeater.js']
        );
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        return (array) $this->processSaveValue($value);
    }

    /**
     * Splices in some meta data (group and index values) to the dataset.
     * @param array $value
     * @return array
     */
    protected function processSaveValue($value)
    {
        if (!is_array($value) || !$value) {
            return $value;
        }

        if ($this->useGroups) {
            foreach ($value as $index => &$data) {
                $data['_group'] = $this->getGroupCodeFromIndex($index);
            }
        }

        return array_values($value);
    }

    /**
     * Processes existing form data and applies it to the form widgets.
     * @return void
     */
    protected function processExistingItems()
    {
        $loadedIndexes = $loadedGroups = [];
        $loadValue = $this->getLoadValue();

        if (is_array($loadValue)) {
            foreach ($loadValue as $index => $loadedValue) {
                $loadedIndexes[] = $index;
                $loadedGroups[] = array_get($loadedValue, '_group');
            }
        }

        $itemIndexes = post($this->indexInputName, $loadedIndexes);
        $itemGroups = post($this->groupInputName, $loadedGroups);

        if (!count($itemIndexes)) {
            return;
        }

        $items = array_combine(
            (array) $itemIndexes,
            (array) ($this->useGroups ? $itemGroups : $itemIndexes)
        );

        foreach ($items as $itemIndex => $groupCode) {
            $this->makeItemFormWidget($itemIndex, $groupCode);
            $this->indexCount = max((int) $itemIndex, $this->indexCount);
        }
    }

    /**
     * Creates a form widget based on a field index and optional group code.
     * @param int $index
     * @param string $index
     * @return \Backend\Widgets\Form
     */
    protected function makeItemFormWidget($index = 0, $groupCode = null)
    {
        $configDefinition = $this->useGroups
            ? $this->getGroupFormFieldConfig($groupCode)
            : $this->form;

        $config = $this->makeConfig($configDefinition);
        $config->model = $this->model;
        $config->data = $this->getLoadValueFromIndex($index);
        $config->alias = $this->alias . 'Form'.$index;
        $config->arrayName = $this->getFieldName().'['.$index.']';
        $config->isNested = true;

        $widget = $this->makeFrontendWidget('Initbiz\PowerComponents\FrontendWidgets\FrontendForm', $config);
        $widget->bindToController();

        $this->indexMeta[$index] = [
            'groupCode' => $groupCode
        ];

        return $this->formWidgets[$index] = $widget;
    }

    /**
     * Returns the load data at a given index.
     * @param int $index
     */
    protected function getLoadValueFromIndex($index)
    {
        $loadValue = $this->getLoadValue();
        if (!is_array($loadValue)) {
            $loadValue = [];
        }

        return array_get($loadValue, $index, []);
    }

    //
    // AJAX handlers
    //

    public function onAddItem()
    {
        self::$onAddItemCalled = true;

        $options = post();
        if (!isset($options['extraVars'])) {
            $options['extraVars'] = [];
        }

        $this->indexCount++;

        $groupCode = post('_repeater_group');

        $this->prepareVars();
        $this->vars['widget'] = $this->makeItemFormWidget($this->indexCount, $groupCode);
        $this->vars['indexValue'] = $this->indexCount;

        $itemContainer = '@#'.$this->getId('items');
        return [$itemContainer => $this->makePartial('repeater_item', $options)];
    }

    public function onRemoveItem()
    {
        // Useful for deleting relations
    }

    public function onRefreshRepeaterItem()
    {
        $index = post('_repeater_index');
        $group = post('_repeater_group');

        $widget = $this->makeItemFormWidget($index, $group);

        return $widget->onRefresh();
    }

    //
    // Group mode
    //

    /**
     * Returns the form field configuration for a group, identified by code.
     * @param string $code
     * @return array|null
     */
    protected function getGroupFormFieldConfig($code)
    {
        if (!$code) {
            return null;
        }

        $fields = array_get($this->groupDefinitions, $code.'.fields');

        if (!$fields) {
            return null;
        }

        return ['fields' => $fields];
    }

    /**
     * Process features related to group mode.
     * @return void
     */
    protected function processGroupMode()
    {
        $palette = [];

        if (!$group = $this->getConfig('groups', [])) {
            $this->useGroups = false;
            return;
        }

        if (is_string($group)) {
            $group = $this->makeConfig($group);
        }

        foreach ($group as $code => $config) {
            $palette[$code] = [
                'code' => $code,
                'name' => array_get($config, 'name'),
                'icon' => array_get($config, 'icon', 'icon-square-o'),
                'description' => array_get($config, 'description'),
                'fields' => array_get($config, 'fields')
            ];
        }

        $this->groupDefinitions = $palette;
        $this->useGroups = true;
    }

    /**
     * Returns a field group code from its index.
     * @param $index int
     * @return string
     */
    public function getGroupCodeFromIndex($index)
    {
        return array_get($this->indexMeta, $index.'.groupCode');
    }

    /**
     * Returns the group title from its unique code.
     * @param $groupCode string
     * @return string
     */
    public function getGroupTitle($groupCode)
    {
        return array_get($this->groupDefinitions, $groupCode.'.name');
    }
}
