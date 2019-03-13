<?php namespace Initbiz\PowerComponents\Traits;

/**
 * Asset Maker Trait
 */
trait AssetMaker
{
    use \System\Traits\AssetMaker;

    /**
     * merge assets in param to the current object
     * @param  array $assets array with assets to merge
     * @return void
     */
    public function mergeAssets($assets)
    {
        //For js, css and rss run methods addjs, addcdd and addrss
        foreach (array_keys($this->assets) as $assetType) {
            if (!empty($assets[$assetType])) {
                foreach ($assets[$assetType] as $assetPath) {
                    $method = 'add'.$assetType;
                    $this->$method($assetPath);
                }
            }
        }
    }
}
