<?php
/**
 * Created by PhpStorm.
 * User: Hamza Alayed
 * Date: 11/29/18
 * Time: 9:38 AM.
 */

namespace Shamaseen\Repository\Generator\Utility;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Interface EloquentInterface.
 */
interface ContractInterface
{
    /**
     * @param array $columns
     *
     * @return Collection|Entity[]
     */
    public function all($columns = ['*']);

    /**
     * @param array $data
     *
     * @return bool
     */
    public function insert($data = []);

    /**
     * @param array $data
     * @param $entityId
     *
     * @return bool
     */
    public function update($entityId, $data = []);

    /**
     * @param $entityId
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function delete($entityId);

    /**
     * @param $entityId
     * @param array $columns
     *
     *  @return Entity
     */
    public function find($entityId, $columns = ['*']);

    /**
     * @param array $criteria
     * @param array $columns
     *
     *  @return Entity
     */
    public function findBy($criteria = [], $columns = ['*']);

    /**
     * @param int $limit
     * @param array $criteria
     *
     * @return LengthAwarePaginator
     */
    public function paginate($limit = 10, $criteria = []);

    /**
     * @param int $limit
     * @param array $criteria
     *
     * @return Paginator
     */
    public function simplePaginate($limit = 10, $criteria = []);

    /**
     * @param array $criteria
     *
     * @return LengthAwarePaginator
     */
    public function get($criteria = []);

    /**
     * @param string $name
     * @param string $entityId
     * @param array $criteria
     *
     * @return array
     */
    public function pluck($name = 'name', $entityId = 'id', $criteria = []);

    /**
     * @param array $filter
     * @param array $columns
     *
     *  @return Entity
     */
    public function first($filter = [], $columns = ['*']);

    /**
     * @param array $data
     *
     * Entity|\Illuminate\Database\Eloquent\Model
     *
     * @return Entity
     */
    public function create($data = []);

    /**
     * @param array $data
     *
     * @return Entity
     */
    public function createOrFirst($data = []);

    /**
     * @param array $data
     *
     * @return Entity
     */
    public function createOrUpdate($data = []);

    /**
     * Get entity name
     *
     * @return string
     */
    public function entityName();

    /**
     * @return void
     */
    public function trash();
    /**
     * @return void
     */
    public function withTrash();

    /**
     * @param int $entityId
     * @return bool
     */
    public function restore($entityId = 0);

    /**
     * @param int $categoryId
     * @return bool
     */
    public function forceDelete($categoryId = 0);
}
