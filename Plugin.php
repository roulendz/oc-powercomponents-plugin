<?php namespace Initbiz\PowerComponents;

use Backend\Classes\WidgetManager;
use Lang;
use Event;
use System\Classes\CombineAssets;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    use \System\Traits\EventEmitter;

    public function register()
    {
        $this->registerConsoleCommand('pc.crud', 'Initbiz\PowerComponents\Console\PcUtil');
        // TODO: This breaks fileupload 
        // "Partial '_field_fileupload.htm' not found." on line 93 of \modules\system\traits\ViewMaker.php
         $this->registerAssetBundles();
         $this->registerFrontendFormWidgets();
         $this->registerFormWidgets();
    }

    public function registerFormWidgets()
    {
        return [
            'Initbiz\PowerComponents\FormWidgets\DynamicForm' => 'dynamicform',
        ];
    }

    /**
     * Register asset bundles
     */
    protected function registerAssetBundles()
    {
        CombineAssets::registerCallback(function ($combiner) {
            $combiner->registerBundle('~/modules/backend/widgets/table/assets/js/build.js');
            $combiner->registerBundle('~/modules/backend/formwidgets/codeeditor/assets/less/codeeditor.less');
            $combiner->registerBundle('~/modules/backend/formwidgets/repeater/assets/less/repeater.less');
            $combiner->registerBundle('~/modules/backend/formwidgets/codeeditor/assets/js/build.js');
            $combiner->registerBundle('~/modules/backend/formwidgets/nestedform/assets/less/nestedform.less');
            $combiner->registerBundle('~/modules/backend/formwidgets/richeditor/assets/js/build-plugins.js');
            $combiner->registerBundle('~/modules/backend/formwidgets/permissioneditor/assets/less/permissioneditor.less');
            $combiner->registerBundle('~/modules/backend/formwidgets/sensitive/assets/less/sensitive.less');

            /*
             * Rich Editor is protected by DRM
             */
            if (file_exists(base_path('modules/backend/formwidgets/richeditor/assets/vendor/froala_drm'))) {
                $combiner->registerBundle('~/modules/backend/formwidgets/richeditor/assets/less/richeditor.less');
                $combiner->registerBundle('~/modules/backend/formwidgets/richeditor/assets/js/build.js');
            }
        });
    }

    public function registerFrontendFormWidgets()
    {
        return [
            'Initbiz\PowerComponents\FrontendFormWidgets\Relation'    => 'relation',
            'Initbiz\PowerComponents\FrontendFormWidgets\Repeater'    => 'repeater',
            'Initbiz\PowerComponents\FrontendFormWidgets\DatePicker'  => 'datepicker',
            // 'Initbiz\PowerComponents\FrontendFormWidgets\CodeEditor'  => 'codeeditor',
            'Initbiz\PowerComponents\FrontendFormWidgets\FileUpload'  => 'fileupload',
            'Initbiz\PowerComponents\FrontendFormWidgets\TagList'     => 'taglist',
            'Initbiz\PowerComponents\FrontendFormWidgets\ColorPicker' => 'colorpicker',
            'Initbiz\PowerComponents\FrontendFormWidgets\DynamicForm' => 'dynamicform',
            'Initbiz\PowerComponents\FrontendFormWidgets\RichEditor'  => 'richeditor',
        ];
    }

    public function registerMarkupTags()
    {
        return [
            'functions' => [
                'pcrender'          => [$this, 'pcrender']
            ]
        ];
    }

    /**
     * Render part
     * @param string $widgetName to be rendered
     * @param array  $options array containing options of a component
     * @return string
     */
    public function pcrender($widgetName, $options)
    {
        $ajaxHandlerName = '';

        $widgetsWithHandlers = [
            'list' => 'onRefreshList',
            'form' => 'onRefreshForm',
        ];

        $this->fireEvent('pc.handlers.beforePcrender', [$widgetsWithHandlers], false);

        foreach ($widgetsWithHandlers as $name => $handler) {
            if ($widgetName === $name) {
                $ajaxHandlerName = $handler;
                break;
            }
        }

        if ($ajaxHandlerName === '') {
            return 'Unknown widget type';
        }

        $divId = 'pc-'.$widgetName.'-'.$options['componentAlias'];

        return '
        <div id="'.$divId.'" class="empowered-component">
            '.$this->preloaderContent().'
        </div>
        <script>
            $(document).ready(function() {
                $.request(\''.$options['componentAlias'].'::'.$ajaxHandlerName.'\', {
                    data: '.json_encode($options).'
                });
            });
        </script>
        ';
    }

    /**
     * Method returning preloader div content. It will be replaced after successfull AJAX
     * @return string $content to be placed in components div
     */
    public function preloaderContent()
    {
        //TODO: move this one to view file which can be overriden theme wide
        $content = '
        <div class="loading-indicator-container">
            <div class="loading-indicator indicator-center">
                <span></span>
                <div>'.
                Lang::get('initbiz.powercomponents::lang.misc.loader_text')
                .'</div>
            </div>
        </div>
        ';

        Event::fire('pc.frontend.preloader.beforeRender', [&$content]);

        return $content;
    }
}
