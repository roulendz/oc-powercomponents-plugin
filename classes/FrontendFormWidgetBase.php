<?php namespace Initbiz\PowerComponents\Classes;

use October\Rain\Html\Helper as HtmlHelper;
use \Initbiz\PowerComponents\FrontendWidgets\FrontendForm;

abstract class FrontendFormWidgetBase extends FrontendWidgetBase
{

    /**
     * @var \October\Rain\Database\Model Form model object.
     */
    public $model;

    /**
     * @var array Dataset containing field values, if none supplied model should be used.
     */
    public $data;

    /**
     * @var string Active session key, used for editing forms and deferred bindings.
     */
    public $sessionKey;

    /**
     * @var boolean If this widget is in previewMode
     */
    public $previewMode;

    /**
     * @var boolean If this widget show default labels or not
     */
    public $showLabels = true;

    /**
     * @var FormField Object containing general form field information.
     */
    protected $formField;

    /**
     * @var string Form field name.
     */
    protected $fieldName;

    /**
     * @var string Model attribute to get/set value from.
     */
    protected $valueFrom;

    /**
     * @var \Initbiz\PowerComponents\Classes\FrontendWidgetManager;
     */
    protected $frontendWidgetManager;

    /**
     * Constructor
     * @param $controller Controller Active controller object.
     * @param $formField FormField Object containing general form field information.
     * @param $configuration array Configuration the relates to this widget.
     */
    public function __construct($controller, $formField, $configuration = [])
    {
        //Run it only on frontend pages
        $this->formField = $formField;
        $this->fieldName = $formField->fieldName;
        $this->valueFrom = $formField->valueFrom;
        // $this->model = $configuration->model;

        $this->config = $this->makeConfig($configuration);

        $this->fillFromConfig([
                'model',
                'data',
                'sessionKey',
                'previewMode',
                'showLabels'
            ]);

        parent::__construct($controller, $configuration);
    }

    /**
     * Returns the HTML element field name for this widget, used for capturing
     * user input, passed back to the getSaveValue method when saving.
     * @return string HTML element name
     */
    public function getFieldName()
    {
        return $this->formField->getName();
    }

    /**
     * Returns a unique ID for this widget. Useful in creating HTML markup.
     */
    public function getId($suffix = null)
    {
        $id = parent::getId($suffix);
        $id .= '-' . $this->fieldName;
        return HtmlHelper::nameToId($id);
    }

    /**
     * Process the postback value for this widget. If the value is omitted from
     * postback data, it will be NULL, otherwise it will be an empty string.
     * @param mixed $value The existing value for this widget.
     * @return string The new value for this widget.
     */
    public function getSaveValue($value)
    {
        return $value;
    }

    /**
     * Returns the value for this form field,
     * supports nesting via HTML array.
     * @return string
     */
    public function getLoadValue()
    {
        if ($this->formField->value !== null) {
            return $this->formField->value;
        }

        $defaultValue = !$this->model->exists
            ? $this->formField->getDefaultFromData($this->data ?: $this->model)
            : null;

        $value = $this->formField->getValueFromData($this->data ?: $this->model, $defaultValue);

        return $value;
    }
}
