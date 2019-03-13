<?php namespace Initbiz\PowerComponents\FormWidgets;

use Lang;
use ApplicationException;
use Backend\Classes\FormWidgetBase;

class DynamicForm extends FormWidgetBase
{

    /**
     * @var bool Display mode: datetime, date, time.
     */
    public $fieldsFrom = 'fields';

    /**
     * {@inheritDoc}
     */
    protected $defaultAlias = 'dynamicform';

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        $this->fillFromConfig([
            'fields',
        ]);
    }

    public function render()
    {
        $fieldsDefinitions = $this->getFieldsDefinitions();
        $this->formWidget = $this->vars['widget'] = $this->makeDynamicFormWidget($fieldsDefinitions);

        return $this->makePartial('form');
    }


    public function getFieldsDefinitions()
    {
        $methodName = 'get'.studly_case($this->fieldName).'FieldsDefinitions';
        if (
            !method_exists($this->model, $methodName) &&
            !method_exists($this->model, 'getFieldsDefinitions')
        ) {
            throw new ApplicationException(Lang::get('backend::lang.field.options_method_not_exists', [
                'model'  => get_class($this->model),
                'method' => $methodName,
                'field'  => $this->fieldName
            ]));
        }

        if (method_exists($this->model, $methodName)) {
            $fieldsDefinitions = $this->model->$methodName();
        } else {
            $fieldsDefinitions = $this->model->getFieldsDefinitions();
        }

        $configDefinition = [];

        $configDefinition['fields'] = $fieldsDefinitions;

        return $configDefinition;
    }

    public function makeDynamicFormWidget($configDefinition = [])
    {
        $config = $this->makeConfig($configDefinition);
        $config->model = $this->model;
        $config->data = $this->getLoadValue();
        $config->alias = $this->alias . 'Form';
        $config->arrayName = $this->getFieldName();

        $widget = $this->makeWidget('Backend\Widgets\Form', $config);
        $widget->bindToController();

        return $widget;
    }

    public function onRefresh()
    {
        $fieldsDefinitions = $this->getFieldsDefinitions();
        $widget = $this->makeDynamicFormWidget($fieldsDefinitions);

        return $widget->onRefresh();
    }
}
