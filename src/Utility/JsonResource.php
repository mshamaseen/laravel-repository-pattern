<?php

namespace Shamaseen\Repository\Generator\Utility;

/**
 * Class LeadResource
 * @package App\Http\Resources\Customer\Relation\Ship
 */
class JsonResource extends \Illuminate\Http\Resources\Json\JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}