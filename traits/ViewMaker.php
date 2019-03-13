<?php namespace Initbiz\PowerComponents\Traits;

use Cms\Classes\Theme;

/**
 * View Maker Trait
 */
trait ViewMaker
{
    use \System\Traits\ViewMaker;

    /**
     * ViewPaths to search for views
     * @var array
     */
    protected $viewPaths;

    public function extractViewPaths($viewPathClasses = [])
    {
        if (is_string($viewPathClasses)) {
            return $this->guessViewPathFrom($viewPathClasses).'/partials';
        }

        if (is_array($viewPathClasses)) {
            $viewPaths = [];
            foreach ($viewPathClasses as $viewPathClass) {
                $viewPaths[] = $this->guessViewPathFrom($viewPathClass).'/partials';
            }
            return $viewPaths;
        }

        return [];
    }

    protected function injectPcViewPaths()
    {
        $this->viewPaths[] = Theme::getActiveTheme()->getPath().'/pcviews';

        $this->viewPaths[] = $this->guessViewPathFrom(get_class($this->component));
    }

    protected function addViewPaths()
    {
        //TODO: Some events and env values to add custom viewpaths
        foreach ($this->viewPaths as $viewPath) {
            $this->addViewPath($viewPath);
        }
    }
}
