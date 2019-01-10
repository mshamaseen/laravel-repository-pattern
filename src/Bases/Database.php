<?php
/**
 * Created by PhpStorm.
 * User: Hamza Alayed
 * Date: 11/29/18
 * Time: 9:38 AM.
 */

namespace App\Repositories;

use App\Contracts\EloquentInterface;
use App\Entities\BaseEntity;
use App\Entities\Tools\FileEntity;
use App\Entities\Tools\NoteEntity;
use Illuminate\Container\Container as App;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

/**
 * Class Database.
 */
abstract class Database implements EloquentInterface
{
    protected $with = [];
    /**
     * @var App
     */
    protected $app;

    /** @var string */
    protected $order = null;

    protected $direction = 'desc';
    /**
     * @var BaseEntity
     */
    protected $model;
    /**
     * @var \Eloquent
     */
    private $activity;
    /**
     * @var NoteEntity
     */
    private $note;
    /**
     * @var FileEntity
     */
    private $file;
    /**
     * @var boolean
     */
    private $trash = false;
    /**
     * @var boolean
     */
    private $withTrash = false;

    /**
     * @param App $app
     * @param Activity $activity
     * @param NoteEntity $note
     * @param FileEntity $file
     */
    public function __construct(App $app, Activity $activity, NoteEntity $note, FileEntity $file)
    {
        $this->app = $app;
        $this->makeModel();
        $this->activity = $activity;
        $this->note = $note;
        $this->file = $file;
    }

    protected function makeModel()
    {
        $this->model = $this->app->make($this->getModelClass());
    }

    /**
     * @return string
     */
    abstract protected function getModelClass(): string;

    /**
     * @param int $limit
     * @param array $filters
     *
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate($limit = 10, $filters = [])
    {
        return $this->filter($filters)->simplePaginate($limit);
    }

    /**
     * @param array $filters
     * @return BaseEntity
     */
    public function filter($filters = [])
    {
        if (isset($filters['order'])) {
            $this->order = $filters['order'];
            unset($filters['order']);
        }

        if (isset($filters['direction'])) {
            $this->direction = $filters['direction'];
            unset($filters['direction']);
        }

        /** @var BaseEntity $latest */
        $latest = $this->model->with($this->with);
        if ('' != $this->order) {
            $latest->orderBy($this->order, $this->direction);
        }

        if (isset($filters['search'])) {
            foreach ($this->model->searchable as $item) {
                $latest->where($item, 'like', '%' . $filters['search'] . '%', 'or');
            }

            unset($filters['search']);
        }
        unset($filters['page']);

        if ($this->trash) {
            $latest->onlyTrashed();
        }
        if ($this->withTrash) {
            $latest->withTrashed();
        }

        return $latest->where($filters);
    }

    /**
     * @param int $limit
     * @param array $filters
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($limit = 10, $filters = [])
    {
        return $this->filter($filters)->paginate($limit);
    }

    /**
     * @param array $filters
     *
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function get($filters = [])
    {
        return $this->filter($filters)->get();
    }

    /**
     * @param $entityId
     * @param array $attributes
     *
     * @return bool
     */
    public function update($entityId = 0, $attributes = [])
    {
        $item = $this->model->where('id', $entityId);

        if ($item) {
            return $item->update($attributes);
        }

        return false;
    }

    /**
     * @param $entityId
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function delete($entityId = 0)
    {
        $item = $this->model->where('id', $entityId);

        if ($item && $item->delete()) {
            return true;
        }

        return false;
    }

    /**
     * @param array $attributes
     *
     * @return bool
     */
    public function insert($attributes = [])
    {
        return $this->model->insert($attributes);
    }


    /**
     * @param array $columns
     *
     * @return mixed
     */
    public function all($columns = ['*'])
    {
        return $this->model->all($columns);
    }

    /**
     * @param string $name
     * @param string $entityId
     * @param array $filters
     *
     * @return array
     */
    public function pluck($name = 'name', $entityId = 'id', $filters = [])
    {
        return $this->model->where($filters)->pluck($name, $entityId)->toArray();
    }

    /**
     * @param $entityId
     * @param array $columns
     *
     * @return BaseEntity
     */
    public function find($entityId = 0, $columns = ['*'])
    {
        return $this->model->with($this->with)->select($columns)->where('id', $entityId)->first();
    }

    /**
     * @param array $filter
     * @param array $columns
     *
     * @return BaseEntity
     */
    public function first($filter = [], $columns = ['*'])
    {
        return $this->model->with($this->with)->select($columns)->where($filter)->first();
    }

    /**
     * @param $haystack
     * @param $needle
     *
     * @return BaseEntity[]|\Illuminate\Database\Eloquent\Collection
     */
    public function search($haystack, $needle)
    {
        return $this->model->where($haystack, 'like', $needle)->get();
    }


    /**
     * @param $filters
     * @param array $columns
     *
     * @return BaseEntity
     */
    public function findBy($filters = [], $columns = ['*'])
    {
        return $this->model->with($this->with)->select($columns)->where($filters)->first();
    }

    /**
     * @param array $attributes
     *
     * @return BaseEntity|\Illuminate\Database\Eloquent\Model
     */
    public function create($attributes = [])
    {
        return $this->model->create($attributes);
    }

    /**
     * @param array $attributes
     *
     * @return BaseEntity|\Illuminate\Database\Eloquent\Model
     */
    public function createOrUpdate($attributes = [])
    {
        return $this->model->updateOrCreate($attributes);
    }

    /**
     * @param array $data
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createOrFirst($data = [])
    {
        return $this->model->firstOrCreate($data);
    }

    /**
     * @param int $entityId
     * @param int $perPage
     *
     * @param int $userId
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function activities($entityId = 0, $perPage = 10, $userId = 0)
    {
        /** @var \Eloquent $activities */
        $activities = $this->activity->where(
            'subject_type',
            $this->getModelClass()
        )->orderBy('created_at', 'desc');

        if (0 != $entityId) {
            $activities->where('subject_id', $entityId);
        }
        if (0 != $userId) {
            $activities->where('causer_id', $userId);
        }

        return $activities
            ->simplePaginate($perPage, ['*'], 'activity_page');
    }

    /**
     * @param int $entityId
     * @param int $perPage
     *
     * @param int $userId
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function notes($entityId = 0, $perPage = 10, $userId = 0)
    {
        $notes = $this->note->where('notable_type', $this->getModelClass())
            ->orderBy('id', 'desc');
        if (0 != $entityId) {
            $notes->where('notable_id', $entityId);
        }
        if (0 != $userId) {
            $notes->where('user_id', $userId);
        }

        return $notes
            ->simplePaginate($perPage, ['*'], 'note_page');
    }

    /**
     * @param int $entityId
     * @param int $perPage
     *
     * @param int $userId
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function files($entityId = 0, $perPage = 10, $userId = 0)
    {
        $files = $this->file->where('uploadable_type', $this->getModelClass())
            ->orderBy('id', 'desc')
            ->with('User');
        if (0 != $entityId) {
            $files->where('uploadable_id', $entityId);
        }
        if (0 != $userId) {
            $files->where('user_id', $userId);
        }

        return $files->simplePaginate($perPage, ['*'], 'file_page');
    }

    /**
     * Get entity name
     *
     * @return string
     */
    public function entityName()
    {
        return $this->getModelClass();
    }

    /**
     * @param int $entityId
     *
     * @return bool|null
     */
    public function restore($entityId = 0)
    {
        /** @var BaseEntity $entity */
        $entity = $this->model->withTrashed()
            ->whereId($entityId)
            ->first();
        if ($entity) {
            return $entity->restore();
        }

        return false;
    }

    /**
     * @param int $entityId
     *
     * @return bool|null
     */
    public function forceDelete($entityId = 0)
    {
        /** @var BaseEntity $entity */
        $entity = $this->model->withTrashed()
            ->whereId($entityId)
            ->first();
        if ($entity) {
            return $entity->forceDelete();
        }

        return false;
    }

    /**
     * @return void
     */
    public function trash()
    {
        $this->trash = true;
        $this->withTrash = false;
    }

    /**
     * @return void
     */
    public function withTrash()
    {
        $this->trash = false;
        $this->withTrash = true;
    }
}
