<?php
/**
 * Created by PhpStorm.
 * User: Mohammad Shanmaseen
 * Date: 09/04/19
 * Time: 02:44 م.
 */

namespace Shamaseen\Repository\Generator\Forms;

use DB;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Database\Eloquent\Model;
use Schema;

class FormGenerator
{
    private $inputs;

    /**
     * @param Column $column
     *
     * @return string
     */
    public function generateFormInput($column)
    {
        $fileInput = $this->getFormInputClass($column);

        return $fileInput->template();
    }

    /**
     * @param Column $column
     *
     * @return Input|TextArea
     */
    public function getFormInputClass($column)
    {
        switch ($column->getType()->getName()) {
            case 'integer':
            case 'int':
            case 'mediumint':
            case 'bigint':
            case 'decimal':
            case 'float':
            case 'double':
                return new Input($column);
            case 'enum':
                return new Input($column);
            case 'date':
            case 'datetime':
            case 'timestamp':
            case 'time':
            case 'year':
                return new Input($column);
            case 'text':
                return new TextArea($column);
            case 'boolean':
            case 'bool':
                return new Input($column);
            case 'varchat':
            default:
                return new Input($column);
        }
    }

    /**
     * @param Model  $entity
     * @param string $method
     *
     * @return string
     */
    public function generateForm($entity, $method = 'post')
    {
        $html = '<form method="post" action="#">
           <input type="hidden" name="__method" value="'.$method.'">';
        $html .= $this->getInputs($entity);
        $html .= '</form>';

        return $html;
    }

    /**
     * @param Model $entity
     *
     * @return array
     */
    public function getFillables($entity)
    {
        if (! empty($entity->getFillable())) {
            return $entity->getFillable();
        }

        $columns = Schema::getColumnListing($entity->getTable());

        foreach ($entity->getGuarded() as $guarded) {
            if (false !== ($key = array_search($guarded, $columns))) {
                unset($columns[$key]);
            }
        }

        return $columns;
    }

    /**
     * @param Model $entity
     *
     * @return string
     */
    public function getInputs($entity)
    {
        if ($this->inputs) {
            return $this->inputs;
        }

        return $this->generateInputs($entity);
    }

    /**
     * @param Model $entity
     *
     * @return string
     */
    public function generateInputs($entity)
    {
        $html = '';
        foreach ($this->getFillables($entity) as $fillable) {
            $column = DB::connection()->getDoctrineColumn($entity->getTable(), $fillable);

            $html .= $this->generateFormInput($column);
        }
        $html .= "<button type='submit' class='btn btn-primary'>Submit</button>";
        $this->inputs = $html;

        return $html;
    }
}
