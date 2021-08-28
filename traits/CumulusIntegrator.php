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

    // Lists

    /**
     * Extend query to use only records with concrete cluster
     * @param   October\Rain\Database\Builder $query  Query to extend
     * @return  October\Rain\Database\Builder         Extended query
     */
    public function extendQueryBefore($query)
    {
        return $this->filterByCluster($query);
    }

    /**
     * filter query by cluster slug
     * @param  October\Rain\Database\Builder $query query to extend
     * @return October\Rain\Database\Builder        filtered query
     */
    public function filterByCluster($query)
    {
        $cluster = Helpers::getCluster();

        if (empty($cluster)) {
            return $query;
        }

        $query->whereHas('cluster', function ($query) use ($cluster) {
            $query->where('slug', $cluster->slug);
        });

        return $query;
    }

    /**
     * filter query by cluster ID
     * @param  October\Rain\Database\Builder $query query to extend
     * @return October\Rain\Database\Builder        filtered query
     */
    public function filterByClusterId($query)
    {
        $cluster = Helpers::getCluster();

        if (empty($cluster)) {
            return $query;
        }

        $query->whereHas('cluster', function ($query) use ($cluster) {
            $query->where('id', $cluster->id);
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
        return $this->addClusterSlugFormField($fields);
    }

    /**
     * Add cluster slug form field to form fields
     * @param array $fields Form fields
     */
    public function addClusterSlugFormField($fields)
    {
        $cluster = Helpers::getCluster();

        if (empty($cluster)) {
            return $fields;
        }

        $field = [
            'label' => 'Cluster slug',
            'type' => 'text',
            'cssClass' => 'hidden',
            'default' => $cluster->slug
        ];

        $fields['cluster_slug'] = $field;

        return $fields;
    }

    /**
     * Add cluster ID form field to form fields
     * @param array $fields Form fields
     */
    public function addClusterIdFormField($fields)
    {
        $cluster = Helpers::getCluster();

        if (empty($cluster)) {
            return $fields;
        }

        $field = [
            'label' => 'Cluster ID',
            'type' => 'text',
            'cssClass' => 'hidden',
            'default' => $cluster->id
        ];

        $fields['cluster_id'] = $field;

        return $fields;
    }

    /**
     * Returns true if cluster can see cluster's model
     * @param  October\Rain\Database\Model $model
     * @return  boolean
     */
    public function userCanSeeData($data)
    {
        return $this->clusterCanUseModel($data);
    }

    /**
     * Returns true if currenly logged in cluster can save model using cluster param
     * @param  array $data Data to be saved
     * @return $boolean       Can or cannot save the data
     */
    public function userCanSaveData($data)
    {
        return $this->clusterCanUseModel($data);
    }

    /**
     * Returns true if currenly logged in cluster can update model
     * You should check if cluster can save the data using clusterCanSaveData method before running this one
     * @param  October\Rain\Database\Model $model
     * @return $boolean       Can or cannot update the model
     */
    public function userCanUpdateData($data)
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
        $cluster = Helpers::getCluster();

        if (empty($cluster)) {
            return false;
        }

        if (!is_array($data)) {
            $data = $data->toArray();
        }

        $can = false;

        if (
            (isset($data['cluster_slug']) && $data['cluster_slug'] === $cluster->slug) ||
            (isset($data['cluster_id']) && (int) $data['cluster_id'] === $cluster->id)
        ) {
            $can = true;
        }

        return $can;
    }
}
