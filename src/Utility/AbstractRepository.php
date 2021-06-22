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
use Illuminate\Contracts\Container\BindingResolutionException;
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
     * @throws BindingResolutionException
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->makeModel();
    }

    /**
     * @throws BindingResolutionException
     */
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
    public function simplePaginate(int $limit = 10, array $criteria = []): Paginator
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
    public function filter(array $criteria = []): Builder
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
    private function order(array $criteria = []): array
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
    public function paginate(int $limit = 10, array $criteria = []): LengthAwarePaginator
    {
        return $this->filter($criteria)->paginate($limit);
    }

    /**
     * @param array $criteria
     *
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function get(array $criteria = [], array $columns = ['*']): LengthAwarePaginator
    {
        return $this->filter($criteria)->get($columns);
    }

    /**
     * @param int $entityId
     * @param array $data
     *
     * @return bool|Collection|Model|Entity
     */
    public function update(int $entityId = 0, array $data = [])
    {
        $item = $this->model->findOrFail($entityId);

        if ($item->update($data)) {
            return $item;
        }

        return false;
    }

    /**
     * @param int $entityId
     *
     * @return bool
     * @throws Exception
     *
     */
    public function delete(int $entityId = 0): bool
    {
        $item = $this->model->findOrFail($entityId);

        return $item->delete();
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function insert(array $data = []): bool
    {
        return $this->model->insert($data);
    }

    /**
     * @param string $name
     * @param string $entityId
     * @param array $criteria
     *
     * @return array
     */
    public function pluck(string $name = 'name', string $entityId = 'id', array $criteria = []): array
    {
        return $this->filter($criteria)->pluck($name, $entityId)->toArray();
    }

    /**
     * @param int $entityId
     * @param array $columns
     *
     * @return Model|null
     */
    public function find(int $entityId = 0, array $columns = ['*']): ?Model
    {
        if ($this->allowCaching) {
            if (isset($this->cache[$entityId])) {
                return $this->cache[$entityId];
            }
        }

        $entity = $this->model->with($this->with)->find($entityId, $columns);

        if ($this->allowCaching) {
            $this->cache[$entityId] = $entity;
        }

        return $entity;
    }

    /**
     * @param $entityId
     * @param array $columns
     *
     * @return Model|Collection|static|static[]
     * @throws ModelNotFoundException
     *
     */
    public function findOrFail($entityId = 0, array $columns = ['*'])
    {
        if ($this->allowCaching) {
            if (isset($this->cache[$entityId])) {
                return $this->cache[$entityId];
            }
        }

        $entity = $this->model->with($this->with)->findOrFail($entityId, $columns);

        if ($this->allowCaching) {
            $this->cache[$entityId] = $entity;
        }

        return $entity;
    }

    /**
     * @param array $filter
     * @param array $columns
     *
     * @return Model|null|object
     */
    public function first(array $filter = [], array $columns = ['*'])
    {
        if ($this->allowCaching) {
            if (isset($this->cache['first'])) {
                return $this->cache['first'];
            }
        }

        $entity = $this->filter($filter)->with($this->with)->select($columns)->first();

        if ($this->allowCaching) {
            $this->cache['first'] = $entity;
        }

        return $entity;
    }

    /**
     * @param array $filter
     * @param array $columns
     *
     * @return Model|null|object
     */
    public function last(array $filter = [], array $columns = ['*'])
    {
        if ($this->allowCaching) {
            if (isset($this->cache['last'])) {
                return $this->cache['last'];
            }
        }

        $entity = $this->filter($filter)->with($this->with)->select($columns)->orderBy('id', 'desc')->first();

        if ($this->allowCaching) {
            $this->cache['last'] = $entity;
        }

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
     * @param array $criteria
     * @param array $columns
     *
     * @return Model|null|object
     */
    public function findBy(array $criteria = [], array $columns = ['*'])
    {
        return $this->model->with($this->with)->select($columns)->where($criteria)->first();
    }

    /**
     * @param array $data
     *
     * @return Entity|Model
     */
    public function create(array $data = [])
    {
        return $this->model->create($data);
    }

    /**
     * @param array $data
     *
     * @return Model
     */
    public function createOrUpdate(array $data = []): Model
    {
        return $this->model->updateOrCreate($data);
    }

    /**
     * @param array $data
     *
     * @return Model
     */
    public function createOrFirst(array $data = []): Model
    {
        return $this->model->firstOrCreate($data);
    }

    /**
     * Get entity name.
     *
     * @return string
     */
    public function entityName(): string
    {
        return $this->getModelClass();
    }

    /**
     * @param int $entityId
     *
     * @return bool
     */
    public function restore(int $entityId = 0): bool
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
    public function forceDelete(int $entityId = 0): bool
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

    public function disableCaching(): AbstractRepository
    {
        $this->allowCaching = false;
        return $this;
    }
}
