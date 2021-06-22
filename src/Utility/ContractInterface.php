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
    public function insert(array $data = []): bool;

    /**
     * @param array $data
     * @param int $entityId
     *
     * @return Entity|Model|bool
     */
    public function update(int $entityId, array $data = []);

    /**
     * @param int $entityId
     *
     * @return bool
     * @throws Exception
     *
     */
    public function delete(int $entityId = 0): bool;

    /**
     * @param int $entityId
     * @param array $columns
     *
     * @return Model|null
     */
    public function find(int $entityId = 0, array $columns = ['*']): ?Model;

    /**
     * @param int $entityId
     * @param array $columns
     *
     * @return Model|Collection|static|static[]
     * @throws ModelNotFoundException
     *
     */
    public function findOrFail(int $entityId = 0, array $columns = ['*']);

    /**
     * @param array $criteria
     * @param array $columns
     *
     * @return Model|null|object
     */
    public function findBy(array $criteria = [], array $columns = ['*']);

    /**
     * @param int   $limit
     * @param array $criteria
     *
     * @return LengthAwarePaginator
     */
    public function paginate(int $limit = 10, array $criteria = []): LengthAwarePaginator;

    /**
     * @param int   $limit
     * @param array $criteria
     *
     * @return Paginator
     */
    public function simplePaginate(int $limit = 10, array $criteria = []): Paginator;

    /**
     * @param array $criteria
     * @param array $columns
     *
     * @return LengthAwarePaginator
     */
    public function get(array $criteria = [], array $columns = []): LengthAwarePaginator;

    /**
     * @param string $name
     * @param string $entityId
     * @param array  $criteria
     *
     * @return array
     */
    public function pluck(string $name = 'name', string $entityId = 'id', array $criteria = []): array;

    /**
     * @param array $filter
     * @param array $columns
     *
     * @return Model|null|object
     */
    public function first(array $filter = [], array $columns = ['*']);

    /**
     * @param array $filter
     * @param array $columns
     *
     * @return Model|null|object
     */
    public function last(array $filter = [], array $columns = ['*']);

    /**
     * @param array $data
     *
     * @return Entity|Model
     */
    public function create(array $data = []);

    /**
     * @param array $data
     *
     * @return Model
     */
    public function createOrFirst(array $data = []): Model;

    /**
     * @param array $data
     *
     * @return Entity|Model
     */
    public function createOrUpdate(array $data = []);

    /**
     * Get entity name.
     *
     * @return string
     */
    public function entityName(): string;

    public function trash();

    public function withTrash();

    /**
     * @param int $entityId
     *
     * @return bool
     */
    public function restore(int $entityId = 0): bool;

    /**
     * @param int $entityId
     *
     * @return bool
     */
    public function forceDelete(int $entityId = 0): bool;
}
