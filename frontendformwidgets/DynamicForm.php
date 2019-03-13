<?php namespace Initbiz\PowerComponents\FrontendFormWidgets;

use Lang;
use ApplicationException;
use Initbiz\PowerComponents\Classes\FrontendFormWidgetBase;

class DynamicForm extends FrontendFormWidgetBase
{
    use \Backend\Traits\FormModelWidget;

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

    public function loadAssets()
    {
        // TODO: there is a problem with loading assets order
        // One idea to do this is to add "availableFormWidgets" in yaml config for dynamicform to include the assets before
        // Right now there is statically included fileupload assets for fileupload to work in dynamicform
        $this->addCss(['~/plugins/initbiz/powercomponents/frontendformwidgets/fileupload/assets/css/fileupload.css']);
        $this->addJs(['~/modules/backend/assets/vendor/dropzone/dropzone.js',
                      '~/plugins/initbiz/powercomponents/frontendformwidgets/fileupload/assets/js/fileupload.js']);
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
        $config->component = $this->component;
        $config->data = $this->getLoadValue();
        $config->alias = $this->alias . 'Form';
        $config->arrayName = $this->getFieldName();

        $widget = $this->makeFrontendWidget('Initbiz\PowerComponents\FrontendWidgets\FrontendForm', $config);
        $widget->bindToController();

        return $widget;
    }

    public function onRefreshDynamicForm()
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

    public function getSaveValue($value)
    {
        $fieldsDefinitions = $this->getFieldsDefinitions();
        // TODO: test if this cannot be in better place
        $this->model = $this->data;

        // $list = $this->getFileList();
        $attachRelations = array_merge($this->data->attachOne, $this->data->attachMany);

        foreach ($attachRelations as $relation => $class) {
            list($model, $attribute) = $this->resolveModelAttribute($relation);
            if (false) {
                // code...
            }
        }

        return $value;
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
