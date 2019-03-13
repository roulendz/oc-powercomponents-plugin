<?php namespace Initbiz\PowerComponents\Classes;

use Backend\Classes\WidgetManager;

class FrontendWidgetManager extends WidgetManager
{

    /**
     * Extending Backend\Classes\WidgetManager with methods to register frontend widgets
     * @return array Array keys are class names.
     */
    public function listFormWidgets()
    {
        if ($this->formWidgets === null) {
            $this->formWidgets = [];

            /*
             * Load module widgets
             */
            foreach ($this->formWidgetCallbacks as $callback) {
                $callback($this);
            }

            /*
             * Load plugin widgets
             */
            $plugins = $this->pluginManager->getPlugins();

            foreach ($plugins as $plugin) {
                //The only change in this method is this line - I want to use different method for frontend

                if (method_exists($plugin, 'registerFrontendFormWidgets')) {
                    if (!is_array($widgets = $plugin->registerFrontendFormWidgets())) {
                        continue;
                    }

                    foreach ($widgets as $className => $widgetInfo) {
                        $this->registerFormWidget($className, $widgetInfo);
                    }
                }
            }
        }

        return $this->formWidgets;
    }
}
