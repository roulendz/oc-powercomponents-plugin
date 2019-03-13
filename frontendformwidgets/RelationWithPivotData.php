<?php namespace Initbiz\PowerComponents\FrontendFormWidgets;

use Db;
use Lang;
use SystemException;
use ApplicationException;
use Backend\Classes\FormField;
use October\Rain\Html\Helper as HtmlHelper;
use Initbiz\PowerComponents\Classes\FrontendFormWidgetBase;
use Illuminate\Database\Eloquent\Relations\Relation as RelationBase;

/**
 * Form Relationship
 * Renders a field prepopulated with a belongsTo and belongsToHasMany relation.
 */
class RelationWithPivotData extends FrontendFormWidgetBase
{
    use \Backend\Traits\FormModelWidget;

    //
    // Configurable properties
    //

    /**
     * @var string Model column to use for the name reference
     */
    public $nameFrom = 'name';

    /**
     * @var string Custom SQL column selection to use for the name reference
     */
    public $sqlSelect;

    /**
     * @var string Empty value to use if the relation is singluar (belongsTo)
     */
    public $emptyOption;

    /**
     * @var string Use a custom scope method for the list query.
     */
    public $scope;

    public $pivotData;
    protected $defaultAlias = 'relationWithPivotData';

    public function init()
    {
        $this->fillFromConfig([
            'nameFrom',
            'emptyOption',
            'scope',
            'pivotData'
        ]);
        if (isset($this->config->select)) {
            $this->sqlSelect = $this->config->select;
        }
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->init();
        $field = $this->makeRenderFormField();
        $optionsField = $this->makeRenderOptionsFormField();
        return $this->makePartial('field_'.$field->type, ['field' => $field, 'optionsField' => $optionsField]);
    }

    /**
     * Makes the form object used for rendering a simple field type
     */
    protected function makeRenderFormField()
    {
        return $this->renderFormField = RelationBase::noConstraints(function () {
            $field = clone $this->formField;
            $relationObject = $this->getRelationObject();

            $query = $relationObject->newQuery();

            list($model, $attribute) = $this->resolveModelAttribute($this->model, $this->valueFrom);
            $relationType = $model->getRelationType($attribute);
            $relationModel = $model->makeRelation($attribute);
            if (in_array($relationType, ['belongsToMany'])) {
                $field->type = 'relationWithPivot';
            }

            // Even though "no constraints" is applied, belongsToMany constrains the query
            // by joining its pivot table. Remove all joins from the query.
            $query->getQuery()->getQuery()->joins = [];

            // Determine if the model uses a tree trait
            $treeTraits = ['October\Rain\Database\Traits\NestedTree', 'October\Rain\Database\Traits\SimpleTree'];
            $usesTree = count(array_intersect($treeTraits, class_uses($relationModel))) > 0;

            // The "sqlSelect" config takes precedence over "nameFrom".
            // A virtual column called "selection" will contain the result.
            // Tree models must select all columns to return parent columns, etc.
            if ($this->sqlSelect) {
                $nameFrom = 'selection';
                $selectColumn = $usesTree ? '*' : $relationModel->getKeyName();
                $result = $query->select($selectColumn, Db::raw($this->sqlSelect . ' AS ' . $nameFrom));
            } else {
                $nameFrom = $this->nameFrom;
                $result = $query->getQuery()->get();
            }

            // Some simpler relations can specify a custom local or foreign "other" key,
            // which can be detected and implemented here automagically.
            $primaryKeyName = in_array($relationType, ['hasMany', 'belongsTo', 'hasOne'])
                ? $relationObject->getOtherKey()
                : $relationModel->getKeyName();

            $field->options = $usesTree
                ? $result->listsNested($nameFrom, $primaryKeyName)
                : $result->lists($nameFrom, $primaryKeyName);

            return $field;
        });
    }

    public function makeRenderOptionsFormField()
    {
        return $this->renderFormField = RelationBase::noConstraints(function () {
            $field = clone $this->formField;
            $relationObject = $this->getRelationObject();
            $query = $relationObject->newQuery();
            list($model, $attribute) = $this->resolveModelAttribute($this->model, $this->valueFrom);
            $relationType = $model->getRelationType($attribute);
            $relationModel = $model->makeRelation($attribute);



            if (in_array($relationType, ['belongsToMany'])) {
                $field->type = 'relationWithPivot';
            }

            // Even though "no constraints" is applied, belongsToMany constrains the query
            // by joining its pivot table. Remove all joins from the query.
            $query->getQuery()->getQuery()->joins = [];

            // Determine if the model uses a tree trait
            $treeTraits = ['October\Rain\Database\Traits\NestedTree', 'October\Rain\Database\Traits\SimpleTree'];
            $usesTree = count(array_intersect($treeTraits, class_uses($relationModel))) > 0;

            // The "sqlSelect" config takes precedence over "nameFrom".
            // A virtual column called "selection" will contain the result.
            // Tree models must select all columns to return parent columns, etc.
            if ($this->sqlSelect) {
                $nameFrom = 'selection';
                $selectColumn = $usesTree ? '*' : $relationModel->getKeyName();
                $result = $query->select($selectColumn, Db::raw($this->sqlSelect . ' AS ' . $nameFrom));
            } else {
                $nameFrom = $this->nameFrom;
                $result = $query->getQuery()->get();
            }

            // Some simpler relations can specify a custom local or foreign "other" key,
            // which can be detected and implemented here automagically.
            $primaryKeyName = in_array($relationType, ['hasMany', 'belongsTo', 'hasOne'])
                ? $relationObject->getOtherKey()
                : $relationModel->getKeyName();
            $field->options = $usesTree
                ? $result->listsNested($nameFrom, $primaryKeyName)
                : $result->lists($nameFrom, $primaryKeyName);

            //TODO: move fieldName, fieldType here from view
            //TODO: Get value of addidtional data from pivot should be in getValueFromData. Maybe override function from FormComponents?
            $value = $field->value;
            if ($value) {
                $field->value = [];
                $additionalDataName = $field->config['pivotData']['optionName'];

                //$this->config['data']->id current instance of $this->model passing through $configuration variable in FormComponent
                foreach ($this->model->with($field->fieldName)
                             ->where('id', $this->config['data']->id)
                             ->first()->parameters as $parameter) {
                    $field->value[$parameter->id] = $parameter->pivot->$additionalDataName === ''?1: $parameter->pivot->$additionalDataName;
                }
            }


            return $field;
        });
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        if ($this->formField->disabled || $this->formField->hidden) {
            return FormField::NO_SAVE_DATA;
        }

        if (is_string($value) && !strlen($value)) {
            return null;
        }

        if (is_array($value) && !count($value)) {
            return null;
        }

        return $value;
    }

    public function getValueFromData($data, $default)
    {
    }

    /**
     * Returns the final model and attribute name of a nested attribute. Eg:
     *
     *     list($model, $attribute) = $this->resolveAttribute('person[phone]');
     *
     * @param  string $attribute.
     * @return array
     */
    public function resolveModelAttribute($model, $attribute = null)
    {
        if ($attribute === null) {
            $attribute = $this->valueFrom ?: $this->fieldName;
        }

        $parts = is_array($attribute) ? $attribute : HtmlHelper::nameToArray($attribute);

        $last = array_pop($parts);
        foreach ($parts as $part) {
            $model = $model->{$part};
        }
        return [$model, $last];
    }
    /**
     * Returns the value as a relation object from the model,
     * supports nesting via HTML array.
     * @return Relation
     */
    protected function getRelationObject()
    {
        list($model, $attribute) = $this->resolveModelAttribute($this->model, $this->valueFrom);

        if (!$model) {
            throw new ApplicationException(Lang::get('backend::lang.model.missing_relation', [
                'class' => get_class($this->model),
                'relation' => $this->valueFrom
            ]));
        }

        if (!$model->hasRelation($attribute)) {
            throw new ApplicationException(Lang::get('backend::lang.model.missing_relation', [
                'class' => get_class($model),
                'relation' => $attribute
            ]));
        }

        return $model->{$attribute}();
    }
}
