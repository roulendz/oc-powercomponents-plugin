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
        $model = $this->data;

        $methodName = 'get'.studly_case($this->fieldName).'FieldsDefinitions';
        if (
            !method_exists($model, $methodName) &&
            !method_exists($model, 'getFieldsDefinitions')
        ) {
            throw new ApplicationException(Lang::get('backend::lang.field.options_method_not_exists', [
                'model'  => get_class($model),
                'method' => $methodName,
                'field'  => $this->fieldName
            ]));
        }

        if (method_exists($model, $methodName)) {
            $fieldsDefinitions = $model->$methodName();
        } else {
            $fieldsDefinitions = $model->getFieldsDefinitions();
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

    /**
     * Get load value for view - mix data model (for relations like attachOne) with normal array values
     * @return Model Object with values set and relations
     */
    public function getLoadValue()
    {
        $value = parent::getLoadValue();

        $data = $this->data;

        if ($value !== null) {
            foreach ($value as $key => $value) {
                $data->$key = $value;
            }
        }

        return $data;
    }

    protected function getFileList()
    {
        $list = $this
            ->getRelationObject()
            ->withDeferred($this->sessionKey)
            ->orderBy('sort_order')
            ->get()
        ;

        /*
         * Decorate each file with thumb and custom download path
         */
        $list->each(function ($file) {
            $this->decorateFileAttributes($file);
        });

        return $list;
    }
}
