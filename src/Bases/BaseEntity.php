<?php
/**
 * Created by PhpStorm.
 * User: hamza
 * Date: 17-11-29
 * Time: 12:15 PM.
 */

namespace App\Entities;

use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Entities\BaseEntity.
 *
 * @property Collection|Activity[] $activities
 * @property array $searchable
 * @method static bool|null forceDelete()
 * @method static Builder|BaseEntity whereId($value)
 * @method static Builder|BaseEntity newModelQuery()
 * @method static Builder|BaseEntity newQuery()
 * @method static Builder|BaseEntity onlyTrashed()
 * @method static Builder|BaseEntity query()
 * @method static bool|null restore()
 * @method static Builder|BaseEntity withTrashed()
 * @method static Builder|BaseEntity withoutTrashed()
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Activitylog\Models\Activity[] $activity
 */
class BaseEntity extends Eloquent
{
    use SoftDeletes, LogsActivity;

    protected $searchable = [];
    protected $dates = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the message that needs to be logged for the given event.
     *
     * You have override this function in child model
     *
     * @param string $eventName
     *
     * @return string
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        return $eventName;
    }
}
