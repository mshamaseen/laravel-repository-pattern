<?php
/**
 * Created by PhpStorm.
 * User: Hamza Alayed
 * Date: 11/29/18
 * Time: 9:38 AM.
 */

namespace Shamaseen\Repository\Generator\Utility;

use Exception;
use Illuminate\Container\Container as App;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
     * @var bool
     */
    private $trash = false;

    /**
     * @var bool
     */
    private $withTrash = false;

    /**
     * @var bool
     */
    private $allowCaching = true;

    /**
     * @var array
     */
    private $cache = [];

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
     * @param array $criteria
     *
     * @return Paginator
     */
    public function simplePaginate($limit = 10, $criteria = [])
    {
        return $this->filter($criteria)->simplePaginate($limit);
    }

    /**
     * @return \Illuminate\Database\Query\Builder|Entity
     */
    public function builder()
    {
        return $this->model->query();
    }

    /**
     * @param array $criteria
     *
     * @return Builder
     */
    public function filter($criteria = [])
    {
        $criteria = $this->order($criteria);

        /** @var Entity $latest */
        $latest = $this->model->with($this->with);
        if ('' != $this->order) {
            $latest->orderBy($this->order, $this->direction);
        }

        if (isset($criteria['search'])) {
            foreach ($this->model->searchable as $method => $columns) {
                if (method_exists($this->model, $method)) {
                    $latest->orWhereHas($method, function ($query) use ($criteria, $columns) {
                        /* @var $query Builder */
                        $query->where(function ($query2) use ($criteria, $columns) {
                            /* @var $query2 Builder */
                            foreach ((array)$columns as $column) {
                                $query2->orWhere($column, 'like', '%' . $criteria['search'] . '%');
                            }
                        });
                    });
                } else {
                    $latest->orWhere($columns, 'like', '%' . $criteria['search'] . '%');
                }
            }
        }
        unset($criteria['search']);

        if ($this->trash) {
            $latest->onlyTrashed();
        }
        if ($this->withTrash) {
            $latest->withTrashed();
        }

        return $latest->where($criteria);
    }

    /**
     * prepare order for query.
     *
     * @param array $criteria
     *
     * @return array
     */
    private function order($criteria = [])
    {
        if (isset($criteria['order'])) {
            $this->order = $criteria['order'];
            unset($criteria['order']);
        }

        if (isset($criteria['direction'])) {
            $this->direction = $criteria['direction'];
            unset($criteria['direction']);
        }
        unset($criteria['page']);

        return $criteria;
    }

    /**
     * @param int $limit
     * @param array $criteria
     *
     * @return LengthAwarePaginator
     */
    public function paginate($limit = 10, $criteria = [])
    {
        return $this->filter($criteria)->paginate($limit);
    }

    /**
     * @param array $criteria
     *
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function get($criteria = [], $columns = ['*'])
    {
        return $this->filter($criteria)->get($columns);
    }

    /**
     * @param $entityId
     * @param array $attributes
     *
     * @return bool|Collection|Model|Entity
     */
    public function update($entityId = 0, $attributes = [])
    {
        $item = $this->model->findOrFail($entityId);

        if ($item->update($attributes)) {
            return $item;
        }

        return false;
    }

    /**
     * @param $entityId
     *
     * @return bool
     * @throws Exception
     *
     */
    public function delete($entityId = 0)
    {
        $item = $this->model->findOrFail($entityId);

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
     * @param string $name
     * @param string $entityId
     * @param array $criteria
     *
     * @return array
     */
    public function pluck($name = 'name', $entityId = 'id', $criteria = [])
    {
        return $this->filter($criteria)->pluck($name, $entityId)->toArray();
    }

    /**
     * @param $entityId
     * @param array $columns
     *
     * @return Entity|Model
     */
    public function find($entityId = 0, $columns = ['*'])
    {
        if ($this->allowCaching) {
            if (isset($this->cache[$entityId]))
                return $this->cache[$entityId];
        }

        $entity = $this->model->with($this->with)->find($entityId, $columns);

        if ($this->allowCaching)
            $this->cache[$entityId] = $entity;

        return $entity;
    }

    /**
     * @param $entityId
     * @param array $columns
     *
     * @return Entity|Model
     * @throws ModelNotFoundException
     *
     */
    public function findOrFail($entityId = 0, $columns = ['*'])
    {
        if ($this->allowCaching) {
            if (isset($this->cache[$entityId]))
                return $this->cache[$entityId];
        }

        $entity = $this->model->with($this->with)->findOrFail($entityId, $columns);

        if ($this->allowCaching)
            $this->cache[$entityId] = $entity;

        return $entity;
    }

    /**
     * @param array $filter
     * @param array $columns
     *
     * @return Entity|Model
     */
    public function first($filter = [], $columns = ['*'])
    {
        if ($this->allowCaching) {
            if (isset($this->cache['first']))
                return $this->cache['first'];
        }

        $entity = $this->filter($filter)->with($this->with)->select($columns)->first();

        if ($this->allowCaching)
            $this->cache['first'] = $entity;

        return $entity;
    }

    /**
     * @param array $filter
     * @param array $columns
     *
     * @return Entity|Model
     */
    public function last($filter = [], $columns = ['*'])
    {
        if ($this->allowCaching) {
            if (isset($this->cache['last']))
                return $this->cache['last'];
        }

        $entity = $this->filter($filter)->with($this->with)->select($columns)->orderBy('id', 'desc')->first();

        if ($this->allowCaching)
            $this->cache['last'] = $entity;

        return $entity;
    }

    /**
     * @param $haystack
     * @param $needle
     *
     * @return Entity[]|Model[]|Collection
     */
    public function search($haystack, $needle)
    {
        return $this->model->where($haystack, 'like', $needle)->get();
    }

    /**
     * @param $criteria
     * @param array $columns
     *
     * @return Entity|Model
     */
    public function findBy($criteria = [], $columns = ['*'])
    {
        return $this->model->with($this->with)->select($columns)->where($criteria)->first();
    }

    /**
     * @param array $attributes
     *
     * @return Entity|Model
     */
    public function create($attributes = [])
    {
        return $this->model->create($attributes);
    }

    /**
     * @param array $attributes
     *
     * @return Entity|Model
     */
    public function createOrUpdate($attributes = [])
    {
        return $this->model->updateOrCreate($attributes);
    }

    /**
     * @param array $data
     *
     * @return Model
     */
    public function createOrFirst($data = [])
    {
        return $this->model->firstOrCreate($data);
    }

    /**
     * Get entity name.
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
        /** @var Entity|null $entity */
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
        /** @var Entity|null $entity */
        $entity = $this->model->withTrashed()
            ->whereId($entityId)
            ->first();
        if ($entity) {
            return $entity->forceDelete() ?? false;
        }

        return false;
    }

    public function trash()
    {
        $this->trash = true;
        $this->withTrash = false;
    }

    public function withTrash()
    {
        $this->trash = false;
        $this->withTrash = true;
    }

    public function disableCaching()
    {
        $this->allowCaching = false;
        return $this;
    }

    public function allowCaching()
    {
        $this->allowCaching = true;
        return $this;
    }
}
