<?php
/**
 * Created by PhpStorm.
 * User: shamaseen
 * Date: 09/10/18
 * Time: 01:01 م.
 */

namespace Shamaseen\Repository\Generator\Bases;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Class BaseRequests
 * @package App\Http\Requests
 */
class Requests extends FormRequest
{

    /**
     * @param Validator $validator
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {

        if (strpos($this->path(),'api') !== false) {
            $errors = (new ValidationException($validator))->errors();
            throw new HttpResponseException(response()->json(['success' => false, 'errors' => $errors,
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));
        }
    }

    public function rules(){
        \App::setLocale($this->header('Language','en'));
    }
}