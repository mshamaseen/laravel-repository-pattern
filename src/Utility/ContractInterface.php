<?php
/**
 * Created by PhpStorm.
 * User: Hamza Alayed
 * Date: 11/29/18
 * Time: 9:38 AM.
 */

namespace Shamaseen\Repository\Generator\Utility;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Interface EloquentInterface.
 */
interface ContractInterface
{
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
     * @return Entity|Model|bool
     */
    public function update($entityId, $data = []);

    /**
     * @param $entityId
     *
     * @throws Exception
     *
     * @return bool
     */
    public function delete($entityId);

    /**
     * @param $entityId
     * @param array $columns
     *
     *  @return Entity|Model
     */
    public function find($entityId, $columns = ['*']);

    /**
     * @param $entityId
     * @param array $columns
     *
     *@throws ModelNotFoundException
     *
     * @return Entity|Model
     */
    public function findOrFail($entityId = 0, $columns = ['*']);

    /**
     * @param array $criteria
     * @param array $columns
     *
     *  @return Entity|Model
     */
    public function findBy($criteria = [], $columns = ['*']);

    /**
     * @param int   $limit
     * @param array $criteria
     *
     * @return LengthAwarePaginator
     */
    public function paginate($limit = 10, $criteria = []);

    /**
     * @param int   $limit
     * @param array $criteria
     *
     * @return Paginator
     */
    public function simplePaginate($limit = 10, $criteria = []);

    /**
     * @param array $criteria
     * @param array $columns
     *
     * @return LengthAwarePaginator
     */
    public function get($criteria = [],$columns = []);

    /**
     * @param string $name
     * @param string $entityId
     * @param array  $criteria
     *
     * @return array
     */
    public function pluck($name = 'name', $entityId = 'id', $criteria = []);

    /**
     * @param array $filter
     * @param array $columns
     *
     *  @return Entity|Model
     */
    public function first($filter = [], $columns = ['*']);

    /**
     * @param array $data
     *
     * @return Entity|Model
     */
    public function create($data = []);

    /**
     * @param array $data
     *
     * @return Entity|Model
     */
    public function createOrFirst($data = []);

    /**
     * @param array $data
     *
     * @return Entity|Model
     */
    public function createOrUpdate($data = []);

    /**
     * Get entity name.
     *
     * @return string
     */
    public function entityName();

    public function trash();

    public function withTrash();

    /**
     * @param int $entityId
     *
     * @return bool
     */
    public function restore($entityId = 0);

    /**
     * @param int $categoryId
     *
     * @return bool
     */
    public function forceDelete($categoryId = 0);
}
