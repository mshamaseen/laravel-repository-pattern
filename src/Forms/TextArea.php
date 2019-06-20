<?php
/**
 * Created by PhpStorm.
 * User: Mohammad Shanmaseen
 * Date: 09/04/19
 * Time: 12:02 م.
 */

namespace Shamaseen\Repository\Generator\Forms;

class TextArea extends Forms
{
    public function template()
    {
        $required = $this->column->getNotnull() ? 'required' : '';

        $template = str_replace(
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
            $this->getFormStub('textArea')
        );

        return $template;
    }
}
