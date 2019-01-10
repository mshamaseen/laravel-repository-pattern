<?php
/**
 * Created by PhpStorm.
 * User: Hamza Alayed
 * Date: 11/29/18
 * Time: 9:38 AM.
 */

namespace App\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

/**
 * Interface EloquentInterface.
 */
interface EloquentInterface
{
    /**
     * @param array $columns
     *
     * @return mixed
     */
    public function all($columns = ['*']);

    /**
     * @param array $data
     *
     * @return mixed
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
     * @return mixed
     */
    public function delete($entityId);

    /**
     * @param $entityId
     * @param array $columns
     *
     * @return mixed
     */
    public function find($entityId, $columns = ['*']);

    /**
     * @param array $filters
     * @param array $columns
     *
     * @return mixed
     */
    public function findBy($filters = [], $columns = ['*']);

    /**
     * @param int $limit
     * @param array $filters
     *
     * @return LengthAwarePaginator
     */
    public function paginate($limit = 10, $filters = []);

    /**
     * @param int $limit
     * @param array $filters
     *
     * @return Paginator
     */
    public function simplePaginate($limit = 10, $filters = []);

    /**
     * @param array $filters
     *
     * @return LengthAwarePaginator
     */
    public function get($filters = []);

    /**
     * @param string $name
     * @param string $entityId
     * @param array $filters
     *
     * @return array
     */
    public function pluck($name = 'name', $entityId = 'id', $filters = []);

    /**
     * @param array $filter
     * @param array $columns
     *
     * @return mixed
     */
    public function first($filter = [], $columns = ['*']);

    /**
     * @param array $data
     *
     * Base|\Illuminate\Database\Eloquent\Model
     */
    public function create($data = []);

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function createOrFirst($data = []);

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function createOrUpdate($data = []);

    /**
     * @param int $entityId
     * @param int $perPage
     * @param int $userId
     *
     * @return mixed
     */
    public function notes($entityId = 0, $perPage = 10, $userId = 0);

    /**
     * @param int $entityId
     * @param int $perPage
     * @param int $userId
     *
     * @return mixed
     */
    public function files($entityId = 0, $perPage = 10, $userId = 0);

    /**
     * @param int $entityId
     * @param int $perPage
     * @param int $userId
     *
     * @return mixed
     */
    public function activities($entityId = 0, $perPage = 10, $userId = 0);

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
     * @return mixed
     */
    public function restore($entityId = 0);

    /**
     * @param int $categoryId
     * @return mixed
     */
    public function forceDelete($categoryId = 0);
}
