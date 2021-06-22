<?php
/**
 * Created by PhpStorm.
 * User: Mohammad Shamaseen
 * Date: 09/04/19
 * Time: 12:03 م.
 */

namespace Shamaseen\Repository\Generator\Forms;

class Input extends Forms
{
    public function template()
    {
        $required = $this->column->getNotnull() ? 'required' : '';

        return str_replace(
            [
                '{{columnName}}',
                '{{type}}',
                '{{required}}',
                '{{label}}',
            ],
            [
                $this->column->getName(),
                $this->getType(),
                $required,
                ucfirst(str_replace('_', ' ', $this->column->getName())),
            ],
            $this->getFormStub('input')
        );
    }
}
