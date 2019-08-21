<?php
/**
 * Created by PhpStorm.
 * User: Mohammad Shanmaseen
 * Date: 09/10/18
 * Time: 01:01 م.
 */

namespace Shamaseen\Repository\Generator\Utility;

use App;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Class BaseRequests.
 */
class Request extends FormRequest
{
    /**
     * @param Validator $validator
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        if (false !== strpos($this->path(), 'api')) {
            $errors = (new ValidationException($validator))->errors();
            throw new HttpResponseException(\Response::json(['success' => false, 'errors' => $errors,
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));
        }
        parent::failedValidation($validator);
    }

    public function rules()
    {
        App::setLocale($this->header('Language', 'en'));
    }
}
