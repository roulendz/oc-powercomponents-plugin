<?php namespace Initbiz\PowerComponents\Classes;

use Db;
use Str;
use App;
use Lang;
use Flash;
use Redirect;
use Cms\Classes\Page;
use Cms\Classes\CodeBase;
use BadMethodCallException;
use Initbiz\PowerComponents\Classes\Helpers;
use Initbiz\PowerComponents\FrontendWidgets\FrontendForm;
use Initbiz\PowerComponents\Classes\EmpoweredComponentBase;

abstract class FormComponentBase extends EmpoweredComponentBase
{
    use \Backend\Traits\FormModelSaver;

    /**
     * @var string Active session key, used for editing forms and deferred bindings.
     */
    public $sessionKey;

    /**
     * @var \Initbiz\PowerComponents\FrontendWidgets\FrontendForm Reference to the widget object.
     */
    protected $formWidget;

    /**
     * @var Model The initialized model used by the form.
     */
    public $model;

    /**
     * @var Model The model that was saved most recently
     */
    public $savedModel;

    /**
     * @var array Configuration values that must exist when applying the primary config file.
     * - modelClass: Class name for the model
     * - form: Form field definitions
     */
    protected $requiredConfig = ['modelClass', 'form'];

    protected $widgetAjaxHandlers = [];
    /**
     * Component constructor. Takes in the page or layout code section object
     * and properties set by the page or layout.
     * @param null|CodeBase $cmsObject
     * @param array $properties
     */
    public function __construct(CodeBase $cmsObject = null, $properties = [])
    {
        parent::__construct($cmsObject, $properties);
        if (!App::runningInBackend()) {
            //TODO: It is run too much times or too long
            $this->defaultSuffix = 'pc-form';
            /*
             * If config not set in component then set the default value
             */
            $this->addDynamicProperty('formConfig', 'config_form.yaml');

            $this->config = $this->makeConfig($this->formConfig, $this->requiredConfig);
            $this->config->modelClass = Str::normalizeClassName($this->config->modelClass);

            $this->model = $this->createModel();
            $this->model = $this->formExtendModel($this->model) ?: $this->model;

            $this->prepareViewPaths();

            $this->addDynamicAjaxHandlers();
        }
    }

    /**
     * Dynamically handle calls and check if AJAX handlers are not defined in formwidgets
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        try {
            $result = parent::__call($method, $parameters);
            //TODO: Make sure it is not affecting any scenarios
            if ($result === null) {
                throw new BadMethodCallException();
            }
            return $result;
        } catch (BadMethodCallException $ex) {
        }

        foreach ($this->widgetAjaxHandlers as $field => $handlers) {
            if (in_array($method, $handlers)) {
                $this->formWidget = $this->createFormWidget();
                $this->formWidget->prepareVars();
                $widgetObj = $this->formWidget->getFormWidget($field);
                return call_user_func_array([$widgetObj, $method], $parameters);
            }
        }

        throw new BadMethodCallException(Lang::get('cms::lang.component.method_not_found', [
            'name' => get_class($this),
            'method' => $method
        ]));
    }

    protected function addDynamicAjaxHandlers()
    {
        $formWidget = $this->createFormWidget();

        $formWidget->prepareVars();
        foreach ($formWidget->getFormWidgets() as $field => $widget) {
            foreach (get_class_methods($widget) as $method) {
                if (preg_match('/^(?:\w+\:{2})?on[A-Z]{1}[\w+]*$/', $method)) {
                    $this->addDynamicMethod($method, function () {
                    });
                    $this->widgetAjaxHandlers[$field][] = $method;
                }
            }
        }
    }

    protected function prepareViewPaths()
    {
        $viewPathClasses = [
            FrontEndForm::class
        ];

        $this->viewPaths = $this->extractViewPaths($viewPathClasses);

        $this->injectPcViewPaths();

        $this->addViewPaths();
    }

    public function createModel()
    {
        $class = $this->config->modelClass;
        $model = new $class;
        return $model;
    }

    public function createFormWidget($context = null)
    {
        $formFields = $this->getConfig("form");
        $config = $this->makeConfig($formFields);
        $config->model = $this->model;
        $config->arrayName = class_basename($this->model);
        $config->context = $context;
        return $this->makeFrontendWidget(FrontendForm::class, $config);
    }

    protected function prepareComponent()
    {
        $this->formWidget = $this->createFormWidget();

        parent::prepareComponent();

        $this->formWidget->prepareVars();
        $this->includeWidgetAjaxHandlers($this->formWidget);
    }

    protected function includeWidgetAjaxHandlers($formWidget)
    {
        foreach ($formWidget->getFormWidgets() as $field => $widget) {
            foreach (get_class_methods($widget) as $method) {
                if (preg_match('/^(?:\w+\:{2})?on[A-Z]{1}[\w+]*$/', $method)) {
                    $this->addDynamicMethod($method, function () {
                    });
                    $this->widgetAjaxHandlers[$field][] = $method;
                }
            }
        }
    }

    // public function makeFormWidget()
    // {
    //     /*
    //      * Make form widget
    //      */
    //     $formWidget = $this->makeWidget(FrontendForm::class, $this, $this->config);
    //
    //     /*
    //      * Bind necessary properties to widget
    //      */
    //     $formWidget->model = $this->model;
    //     $formWidget->component = $this;
    //
    //     return $formWidget;
    // }

    protected function prepareWidgets()
    {
        // foreach ($this->viewPaths as $viewPath) {
        //     $this->formWidget->addViewPath($viewPath);
        // }
    }


    /*
     * Component methods
     */

    // Form page variables

    public function formPageVariables()
    {
        $formOptions['updatePageUrl'] = Page::Url($this->property('updatePage'));
        $formOptions['createPageUrl'] = Page::Url($this->property('createPage'));
        $formOptions['previewPageUrl'] = Page::Url($this->property('previewPage'));
        $formOptions['listPageUrl'] = Page::Url($this->property('listPage'));
        $formOptions['context'] = $this->getContext();
        $formOptions['recordKey'] = $this->getRecordKey();
        $formOptions['recordKeyName'] = $this->getRecordKeyName();
        $formOptions['componentAlias'] = $this->alias;

        $this->appendPcViewBag($this->alias, $formOptions);
    }

    public function onRun()
    {
        $this->formPageVariables();
    }

    //Form component properties

    public function formProperties()
    {
        return [
            'context' => [
                'title'       => 'initbiz.powercomponents::lang.form_component_properties.context',
                'description' => 'initbiz.powercomponents::lang.form_component_properties.context_name',
                'type'        => 'dropdown',
                'options'     => [
                    'create'=>'initbiz.powercomponents::lang.form_component_properties.context_create', 'update'=>'initbiz.powercomponents::lang.form_component_properties.context_update', 'preview'=>'initbiz.powercomponents::lang.form_component_properties.context_preview'],
                'default'     => 'create'

            ],
            'recordKey' => [
                'title' => 'initbiz.powercomponents::lang.form_component_properties.record_key',
                'description' => 'initbiz.powercomponents::lang.form_component_properties.record_key_desc',
                'type' => 'string',
                'default' => '{{ :id }}'
            ],
            'recordKeyName' => [
                'title' => 'initbiz.powercomponents::lang.form_component_properties.record_key_name',
                'description' => 'initbiz.powercomponents::lang.form_component_properties.record_key_name_desc',
                'type' => 'string',
                'default' => 'id'
            ],
            'listPage' => [
                'title'       => 'initbiz.powercomponents::lang.component_properties.list_page',
                'description' => 'initbiz.powercomponents::lang.component_properties.list_page_desc',
                'type'        => 'dropdown',
                'group'       => 'initbiz.powercomponents::lang.component_properties.pages_group'
            ],
            'createPage' => [
                'title'       => 'initbiz.powercomponents::lang.component_properties.create_page',
                'description' => 'initbiz.powercomponents::lang.component_properties.create_page_desc',
                'type'        => 'dropdown',
                'group'       => 'initbiz.powercomponents::lang.component_properties.pages_group'
            ],
            'updatePage' => [
                'title'       => 'initbiz.powercomponents::lang.component_properties.update_page',
                'description' => 'initbiz.powercomponents::lang.component_properties.update_page_desc',
                'type'        => 'dropdown',
                'group'       => 'initbiz.powercomponents::lang.component_properties.pages_group'
            ],
            'previewPage' => [
                'title'       => 'initbiz.powercomponents::lang.component_properties.preview_page',
                'description' => 'initbiz.powercomponents::lang.component_properties.preview_page_desc',
                'type'        => 'dropdown',
                'group'       => 'initbiz.powercomponents::lang.component_properties.pages_group'
            ]
        ];
    }

    public function getListPageOptions()
    {
        return Helpers::getFileListToDropdown();
    }

    public function getUpdatePageOptions()
    {
        return Helpers::getFileListToDropdown();
    }

    public function getCreatePageOptions()
    {
        return Helpers::getFileListToDropdown();
    }

    public function getPreviewPageOptions()
    {
        return Helpers::getFileListToDropdown();
    }

    public function defineProperties()
    {
        return $this->formProperties();
    }


    /**
     * AJAX handler for saving the form to models
     */
    public function onSave()
    {
        $this->prepareComponent();

        $dataToSave = $this->formWidget->getSaveData();

        if ($this->userCanSaveData($dataToSave) !== true) {
            Flash::error(Lang::get('initbiz.powercomponents::lang.permissions.access_forbidden'));
            return Redirect::refresh();
        }

        $model = $this->formExtendModel($this->model) ?: $this->model;

        $modelsToSave = $this->prepareModelsToSave($model, $dataToSave);
        Db::transaction(function () use ($modelsToSave) {
            $this->fireSystemEvent('pc.frontend.form.beforeSave', [&$modelsToSave], false);

            $modelsToSave = $this->beforeSave($modelsToSave);

            foreach ($modelsToSave as $modelToSave) {
                $modelToSave->save(null, $this->getSessionKey());
            }
        });

        $this->afterSave($model);

        Flash::success(Lang::get('initbiz.powercomponents::lang.form_save.success'));

        $this->savedModel = $model;

        $data = post();

        if (isset($data['redirectPage'])) {
            return Redirect::to($data['redirectPage']);
        }

        //TODO: Check is it a good idea to return whole model after save
        return $model;
    }

    /**
     * AJAX handler for updating model
     */
    public function onUpdate()
    {
        $this->prepareComponent();

        $recordKey = $this->options['recordKey'];
        $recordKeyName = $this->options['recordKeyName'];

        $model = $this->model->where($recordKeyName, $recordKey)->first();

        if ($this->userCanUpdateData($model) !== true) {
            Flash::error(Lang::get('initbiz.powercomponents::lang.permissions.access_forbidden'));
            return Redirect::refresh();
        }

        $this->formWidget->data = $model;
        $dataToSave = $this->formWidget->getSaveData();

        $modelsToSave = $this->prepareModelsToSave($model, $dataToSave);

        Db::transaction(function () use ($modelsToSave) {
            $this->fireSystemEvent('pc.frontend.form.beforeUpdate', [&$modelsToSave], false);

            //According to Laravel's convention, save is also run when updating
            $modelsToSave = $this->beforeSave($modelsToSave);
            $modelsToSave = $this->beforeUpdate($modelsToSave);

            foreach ($modelsToSave as $modelToSave) {
                $modelToSave->save(null, $this->getSessionKey());
            }
        });


        $this->afterSave($model);
        $this->afterUpdate($model);
        Flash::success(Lang::get('initbiz.powercomponents::lang.form_save.success'));

        $this->savedModel = $model;

        $data = post();

        if (isset($data['redirectPage'])) {
            return Redirect::to($data['redirectPage']);
        } else {
            return Redirect::refresh();
        }
    }

    /**
     * AJAX handler for deleting the record from form view
     */
    public function onDeleteRecord()
    {
        $this->prepareComponent();

        $recordKey = $this->options['recordKey'];
        $recordKeyName = $this->options['recordKeyName'];

        $model = $this->model->where($recordKeyName, $recordKey)->first();

        if ($this->userCanUpdateData($model) !== true) {
            Flash::error(Lang::get('initbiz.powercomponents::lang.permissions.access_forbidden'));
            return Redirect::refresh();
        }

        $model->delete();

        Flash::success(Lang::get('backend::lang.list.delete_selected_success'));

        return Redirect::to($this->options['redirectPage']);
    }

    /**
     * AJAX handler for refreshing the form.
     */
    public function onRefreshForm()
    {
        $this->prepareComponent();

        $saveData = $this->formWidget->getSaveData();

        /*
         * Extensibility
         */
        $dataHolder = (object) ['data' => $saveData];
        $this->fireSystemEvent('pc.frontend.form.beforeRefresh', [$dataHolder]);
        $saveData = $dataHolder->data;

        $data = $this->model;
        if ($this->options['context'] == 'preview' || $this->options['context'] == 'update') {
            $data = $this->model->where(
                $this->options['recordKeyName'],
                $this->options['recordKey']
            )->first();

            if ($this->userCanSeeData($data) !== true) {
                return [ '#'.$this->getDivId() => $this->makePartial('403')];
            }

            $this->formWidget->data = $data;
        }

        /*
         * Data set differs from current fields values
         */
        //TODO: still not perfect, but works
        if ($data !== $saveData) {
            foreach ($saveData as $key => $value) {
                if (!empty($value)) {
                    $data->$key = $value;
                }
            }
        }

        $this->formWidget->setFormValues($data);

        // Need to be run here, because originally it was run to early
        // In PowerComponents we have only ajax respones - without renders to be run early
        $this->formWidget->applyFiltersFromModel();

        if (($updateFields = post('fields')) && is_array($updateFields)) {
            foreach ($updateFields as $field) {
                $fieldObject = $this->formWidget->allFields[$field];

                if (!isset($field)) {
                    continue;
                }

                /** @var FormWidgetBase $fieldObject */
                $result['#' . $fieldObject->getId('group')] = $this->formWidget->renderFieldObject($fieldObject);
            }
        }

        // $this->formWidget->alias = $this->alias;

        $assets = ['X_OCTOBER_ASSETS' => $this->formWidget->getAssetPaths()];
        if (empty($result)) {
            $result = ['#'.$this->getDivId() => $this->formWidget->render($this->options)];
        }
        // $result = $this->formWidget->onRefresh();

        return $assets + $result;
    }

    // Overrides

    public function getRecordKey()
    {
        return $this->property('recordKey');
    }

    public function getRecordKeyName()
    {
        return $this->property('recordKeyName');
    }

    public function getContext()
    {
        return $this->property('context');
    }

    public function formExtendModel($model)
    {
    }

    public function userCanSeeData($data)
    {
        return true;
    }

    public function userCanSaveData($data)
    {
        return true;
    }

    public function userCanUpdateData($model)
    {
        return true;
    }

    public function beforeUpdate($modelsToSave)
    {
        return $modelsToSave;
    }

    public function beforeSave($modelsToSave)
    {
        return $modelsToSave;
    }

    public function afterSave($model)
    {
    }

    public function afterUpdate($model)
    {
    }


    // Helpers

    /**
     * Returns the active session key.
     *
     * @return \Illuminate\Routing\Route|mixed|string
     */
    protected function getSessionKey()
    {
        if ($this->sessionKey) {
            return $this->sessionKey;
        }

        if (post('_session_key')) {
            return $this->sessionKey = post('_session_key');
        }

        return $this->sessionKey = FormHelper::getSessionKey();
    }
}
