<?php namespace Initbiz\PowerComponents\Traits;

use Lang;
use SystemException;
use Backend\Classes\FormField;

trait FrontendWidgetMaker
{
    public function makeFrontendWidget($class, $widgetConfig = [])
    {
        $component = property_exists($this, 'component') && $this->component
            ? $this->component
            : $this;

        if (!is_a($this->controller, "\Cms\Classes\Controller")) {
            throw new SystemException(Lang::get('initbiz.powercomponents::lang.exception.not_defined', [
                'name' => 'Controller'
            ]));
        }

        if (!class_exists($class)) {
            throw new SystemException(Lang::get('backend::lang.widget.not_registered', [
                'name' => $class
            ]));
        }

        return new $class($this->controller, $component, $widgetConfig);
    }

    public function makeFrontendFormWidget($class, $fieldConfig = [], $widgetConfig = [])
    {
        $controller = property_exists($this, 'controller') && $this->controller
            ? $this->controller
            : $this;

        if (!class_exists($class)) {
            throw new SystemException(Lang::get('backend::lang.widget.not_registered', [
                'name' => $class
            ]));
        }

        if (is_string($fieldConfig)) {
            $fieldConfig = ['name' => $fieldConfig];
        }

        if (is_array($fieldConfig)) {
            $formField = new FormField(
                array_get($fieldConfig, 'name'),
                array_get($fieldConfig, 'label')
            );
            $formField->displayAs('widget', $fieldConfig);
        } else {
            $formField = $fieldConfig;
        }

        $widgetConfig->component = $this->component;
        $widget = new $class($controller, $formField, $widgetConfig);
        return $widget;
    }
}
