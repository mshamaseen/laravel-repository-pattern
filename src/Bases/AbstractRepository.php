<?php
/**
 * Created by PhpStorm.
 * User: Hamza Alayed
 * Date: 11/29/18
 * Time: 9:38 AM.
 */

namespace Shamaseen\Repository\Generator\Bases;


use Illuminate\Container\Container as App;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class Database.
 */
abstract class AbstractRepository implements ContractInterface
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
     * @var Entity
     */
    protected $model;
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
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->makeModel();
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
     * @return Entity
     */
    public function filter($filters = [])
    {
        $filters= $this->order($filters);

        /** @var Entity $latest */
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


        if ($this->trash) {
            $latest->onlyTrashed();
        }
        if ($this->withTrash) {
            $latest->withTrashed();
        }

        return $latest->where($filters);
    }

    /**
     * prepare order for query
     *
     * @param array $filters
     *
     * @return array
     */
    private function order($filters=[]){

        if (isset($filters['order'])) {
            $this->order = $filters['order'];
            unset($filters['order']);
        }

        if (isset($filters['direction'])) {
            $this->direction = $filters['direction'];
            unset($filters['direction']);
        }
        unset($filters['page']);

        return $filters;
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

        return $item->delete();
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
     * @return Entity
     */
    public function find($entityId = 0, $columns = ['*'])
    {
        return $this->model->with($this->with)->select($columns)->where('id', $entityId)->first();
    }

    /**
     * @param array $filter
     * @param array $columns
     *
     * @return Entity
     */
    public function first($filter = [], $columns = ['*'])
    {
        return $this->model->with($this->with)->select($columns)->where($filter)->first();
    }

    /**
     * @param $haystack
     * @param $needle
     *
     * @return Entity[]|\Illuminate\Database\Eloquent\Collection
     */
    public function search($haystack, $needle)
    {
        return $this->model->where($haystack, 'like', $needle)->get();
    }


    /**
     * @param $filters
     * @param array $columns
     *
     * @return Entity
     */
    public function findBy($filters = [], $columns = ['*'])
    {
        return $this->model->with($this->with)->select($columns)->where($filters)->first();
    }

    /**
     * @param array $attributes
     *
     * @return Entity|\Illuminate\Database\Eloquent\Model
     */
    public function create($attributes = [])
    {
        return $this->model->create($attributes);
    }

    /**
     * @param array $attributes
     *
     * @return Entity|\Illuminate\Database\Eloquent\Model
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
     * @return bool
     */
    public function restore($entityId = 0)
    {
        /** @var Entity $entity */
        $entity = $this->model->withTrashed()
            ->whereId($entityId)
            ->first();
        if ($entity) {
            return $entity->restore() ?? false;
        }

        return false;
    }

    /**
     * @param int $entityId
     *
     * @return bool
     */
    public function forceDelete($entityId = 0)
    {
        /** @var Entity $entity */
        $entity = $this->model->withTrashed()
            ->whereId($entityId)
            ->first();
        if ($entity) {
            return $entity->forceDelete() ?? false;
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
