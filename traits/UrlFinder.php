<?php namespace Initbiz\PowerComponents\Traits;

use Cms\Classes\Page;

trait UrlFinder
{
    public function pageUrl2($filename ='', $params = [])
    {
        return Page::Url($filename, $params);
    }

}
