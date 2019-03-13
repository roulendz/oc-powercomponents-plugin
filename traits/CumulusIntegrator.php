<?php namespace Initbiz\PowerComponents\Traits;

use Cookie;
use Session;
use Initbiz\CumulusCore\Classes\Helpers;

/**
 * Cumulus integrator Trait
 * Use the trait in components only
 * Adds methods to empowered components used to restrict:
 * * list records filtered by cluster
 * * extend form fields with cluster slug and restrict access to data by clusters
 */
trait CumulusIntegrator
{
    protected $clusterSlug;

    /**
     * Initialize parameters used in component
     */
    protected function initClusterSlugProperty()
    {
        //If $this->clusterSlug is {{...}} or null, than set as a current slug
        if ($this->clusterSlug === null) {
            $this->clusterSlug = Session::get('cumulus_clusterslug', Cookie::get('cumulus_clusterslug'));
        }
    }

    // Lists

    /**
     * Extend query to use only records with concrete cluster
     * @param   October\Rain\Database\Builder $query  Query to extend
     * @return  October\Rain\Database\Builder         Extended query
     */
    public function extendQueryBefore($query)
    {
        $query = $this->filterByCluster($query);

        return $query;
    }

    /**
     * filter query by cluster
     * @param  October\Rain\Database\Builder $query query to extend
     * @return October\Rain\Database\Builder        filtered query
     */
    public function filterByCluster($query)
    {
        $this->initClusterSlugProperty();

        $query->whereHas('cluster', function ($query) {
            $query->where('slug', $this->clusterSlug);
        });

        return $query;
    }

    // Forms

    /**
     * Extend the fields with cluster field and hide it
     * @param  array  $fields
     * @return  array
     */
    public function extendFieldsBefore($fields)
    {
        $fields = $this->addClusterSlugFormField($fields);

        return $fields;
    }

    /**
     * Add cluster slug form field to form fields
     * @param array $fields Form fields
     */
    public function addClusterSlugFormField($fields)
    {
        $this->initClusterSlugProperty();

        $field = [
            'label' => 'Cluster slug',
            'type' => 'text',
            'cssClass' => 'hidden',
            'default' => $this->clusterSlug
        ];

        $fields['cluster_slug'] = $field;

        return $fields;
    }

    /**
     * Returns true if cluster can see cluster's model
     * @param  October\Rain\Database\Model $model
     * @return  boolean
     */
    public function clusterCanSeeData($data)
    {
        return $this->clusterCanUseModel($data);
    }

    /**
     * Returns true if currenly logged in cluster can save model using cluster param
     * @param  array $data Data to be saved
     * @return $boolean       Can or cannot save the data
     */
    public function clusterCanSaveData($data)
    {
        return $this->clusterCanUseModel($data);
    }

    /**
     * Returns true if currenly logged in cluster can update model
     * You should check if cluster can save the data using clusterCanSaveData method before running this one
     * @param  October\Rain\Database\Model $model
     * @return $boolean       Can or cannot update the model
     */
    public function clusterCanUpdateData($data)
    {
        return $this->clusterCanUseModel($data);
    }

    /**
     * Returns true if currenly logged in cluster can use model
     * @param  array        $data   data with cluster property
     * @return $boolean       Can or cannot use the model
     */
    public function clusterCanUseModel($data)
    {
        $this->initClusterSlugProperty();

        if (!is_array($data)) {
            $data = $data->toArray();
        }

        //Get cluster id from data sent in form, cast to int and check
        if ($data['cluster_slug'] === $this->clusterSlug) {
            return true;
        }

        return false;
    }
}
