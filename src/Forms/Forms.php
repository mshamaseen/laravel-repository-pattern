<?php
/**
 * Created by PhpStorm.
 * User: shanmaseen
 * Date: 09/04/19
 * Time: 12:02 م
 */
namespace Shamaseen\Repository\Generator\Forms;

use Doctrine\DBAL\Schema\Column;

abstract class Forms
{
    /**
     * @var Column
     */
    protected $column;

    public $type = 'text';

    /**
     * Forms constructor.
     * @param Column $column
     */
    public function __construct($column)
    {
        $this->column = $column;
    }

    abstract function template();

    function getType()
    {

        switch ($this->column->getName())
        {
            case "email":
                return 'email';
                break;
            case "password":
                return "password";
                break;
        }

        switch ($this->column->getType())
        {
            case "integer":
            case "int":
            case "mediumint":
            case "bigint":
            case "decimal":
            case "float":
            case "double":
                return 'number';
                break;

            case "time":
                return 'time';
                break;

            case "date":
            case "datetime":
            case "timestamp":
            case "year":
                return 'date';
                break;

            case "boolean":
            case "bool":
            case "varchat":
            case "enum":
            case "text":
            default:
                return 'text';
                break;
        }
    }

    function getFormStub($type)
    {
        return file_get_contents(\Config::get('repository.stubs_path') . "/fields-".$type.".stub");
    }
}