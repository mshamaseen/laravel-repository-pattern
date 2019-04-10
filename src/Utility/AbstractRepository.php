<?php
/**
 * Created by PhpStorm.
 * User: Hamza Alayed
 * Date: 11/29/18
 * Time: 9:38 AM.
 */

namespace Shamaseen\Repository\Generator\Utility;

use Illuminate\Container\Container as App;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
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
     * @param array $criteria
     *
     * @return Paginator
     */
    public function simplePaginate($limit = 10, $criteria = [])
    {
        return $this->filter($criteria)->simplePaginate($limit);
    }

    /**
     * @param array $criteria
     * @return Builder
     */
    public function filter($criteria = [])
    {
        $criteria= $this->order($criteria);

        /** @var Entity $latest */
        $latest = $this->model->with($this->with);
        if ('' != $this->order) {
            $latest->orderBy($this->order, $this->direction);
        }

        if (isset($criteria['search'])) {
            foreach ($this->model->searchable as $method => $columns) {
                if(method_exists($this->model,$method))
                {
                    $latest->orWhereHas($method,function ($query) use ($criteria,$columns)
                    {
                        /** @var $query Builder */
                        $query->where(function ($query2) use($criteria,$columns){
                            /** @var $query2 Builder */
                            foreach ((array) $columns as $column)
                            {
                                $query2->orWhere($column, 'like', "%" . $criteria['search'] . "%");
                            }
                        });
                    });
                }
                else
                    $latest->orWhere($columns, 'like', "%" . $criteria['search'] . "%");
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
     * prepare order for query
     *
     * @param array $criteria
     *
     * @return array
     */
    private function order($criteria=[]){

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
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function get($criteria = [])
    {
        return $this->filter($criteria)->get();
    }

    /**
     * @param $entityId
     * @param array $attributes
     *
     * @return bool|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|Entity
     */
    public function update($entityId = 0, $attributes = [])
    {
        $item = $this->model->findOrFail($entityId);

        if($item->update($attributes))
        {
            return $item;
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
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function all($columns = ['*'])
    {
        return $this->model->all($columns);
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
        return $this->model->where($criteria)->pluck($name, $entityId)->toArray();
    }

    /**
     * @param $entityId
     * @param array $columns
     *
     * @return Entity|\Illuminate\Database\Eloquent\Model
     */
    public function find($entityId = 0, $columns = ['*'])
    {
        return $this->model->with($this->with)->find($entityId,$columns);
    }

    /**
     * @param $entityId
     * @param array $columns
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @return Entity|\Illuminate\Database\Eloquent\Model
     */
    public function findOrFail($entityId = 0, $columns = ['*'])
    {
        return $this->model->with($this->with)->findOrFail($entityId,$columns);
    }
    /**
     * @param array $filter
     * @param array $columns
     *
     * @return Entity|\Illuminate\Database\Eloquent\Model
     */
    public function first($filter = [], $columns = ['*'])
    {
        return $this->model->with($this->with)->select($columns)->where($filter)->first();
    }

    /**
     * @param $haystack
     * @param $needle
     *
     * @return Entity[]|\Illuminate\Database\Eloquent\Model[]|\Illuminate\Database\Eloquent\Collection
     */
    public function search($haystack, $needle)
    {
        return $this->model->where($haystack, 'like', $needle)->get();
    }


    /**
     * @param $criteria
     * @param array $columns
     *
     * @return Entity|\Illuminate\Database\Eloquent\Model
     */
    public function findBy($criteria = [], $columns = ['*'])
    {
        return $this->model->with($this->with)->select($columns)->where($criteria)->first();
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
