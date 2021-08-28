<?php namespace Initbiz\PowerComponents\FrontendFormWidgets;

use App;
use File;
use Event;
use Lang;
use Request;
use Backend\Models\EditorSetting;
use Initbiz\PowerComponents\Classes\FrontendFormWidgetBase;

class RichEditor extends FrontendFormWidgetBase
{
    public $size = 'small';
    public $placeholder = '';

    /**
     * @var boolean Determines whether content has HEAD and HTML tags.
     */
    public $toolbarButtons;

    /**
     * @var boolean If true, the editor is set to read-only mode
     */
    public $readOnly = false;

    //
    // Object properties
    //

    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'richeditor';

    /**
     * @inheritDoc
     */
    public function init()
    {
        if ($this->formField->disabled) {
            $this->readOnly = true;
        }

        $this->fillFromConfig([
            'size',
            'readOnly',
            'placeholder',
            'toolbarButtons',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('richeditor');
    }

    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        $this->vars['readOnly'] = $this->readOnly;
        $this->vars['value'] = $this->getLoadValue();
        $this->vars['size'] = $this->size;
        $this->vars['placeholder'] = $this->placeholder;
        // $this->vars['toolbarButtons'] = $this->evalToolbarButtons();
    }

    /**
     * Determine the toolbar buttons to use based on config.
     * @return string
     */
    protected function evalToolbarButtons()
    {
        $buttons = $this->toolbarButtons;

        if (is_string($buttons)) {
            $buttons = array_map(function ($button) {
                return strlen($button) ? $button : '|';
            }, explode('|', $buttons));
        }

        return $buttons;
    }

    /**
     * @inheritDoc
     */
    protected function loadAssets()
    {
        $this->addCss(
            [
                '~/plugins/initbiz/powercomponents/frontendformwidgets/richeditor/assets/css/quill.snow.css',
                '~/plugins/initbiz/powercomponents/frontendformwidgets/richeditor/assets/css/richeditor.css'
            ]
        );
        $this->addJs(
            [
                '~/plugins/initbiz/powercomponents/frontendformwidgets/richeditor/assets/js/quill.min.js',
            ]
        );
    }

    public function getSaveValue($value)
    {
        return $value;
        // return (array) $this->processSaveValue($value);
    }
}
