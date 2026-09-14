<?php

declare(strict_types=1);

namespace App\Support\Traits;

use Illuminate\Contracts\Database\Query\Builder as QueryBuilderContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as BaseQueryBuilder;
use Illuminate\Support\Facades\DB;

trait HandlesDatabaseDriver
{
    /**
     * Get the active database connection driver name (e.g. pgsql, mysql, sqlite).
     *
     * @param string|null $connection Optional connection name.
     * @return string Normalized driver name.
     */
    public function getDatabaseDriver(?string $connection = null): string
    {
        return strtolower(DB::connection($connection)->getDriverName());
    }

    /**
     * Determine if current database connection is PostgreSQL.
     *
     * @param string|null $connection Optional connection name.
     * @return bool True if PostgreSQL driver is active.
     */
    public function isPostgreSql(?string $connection = null): bool
    {
        return $this->getDatabaseDriver($connection) === 'pgsql';
    }

    /**
     * Determine if current database connection is MySQL / MariaDB.
     *
     * @param string|null $connection Optional connection name.
     * @return bool True if MySQL driver is active.
     */
    public function isMySql(?string $connection = null): bool
    {
        return in_array($this->getDatabaseDriver($connection), ['mysql', 'mariadb'], true);
    }

    /**
     * Determine if current database connection is SQLite.
     *
     * @param string|null $connection Optional connection name.
     * @return bool True if SQLite driver is active.
     */
    public function isSqlite(?string $connection = null): bool
    {
        return $this->getDatabaseDriver($connection) === 'sqlite';
    }

    /**
     * Get appropriate Case-Insensitive LIKE operator depending on active database driver.
     *
     * @param string|null $connection Optional connection name.
     * @return string 'ilike' for PostgreSQL, 'like' for MySQL/SQLite.
     */
    public function getCaseInsensitiveLikeOperator(?string $connection = null): string
    {
        return $this->isPostgreSql($connection) ? 'ilike' : 'like';
    }

    /**
     * Apply a case-insensitive LIKE where condition to a query builder instance.
     *
     * @param EloquentBuilder|BaseQueryBuilder|QueryBuilderContract $query Target query builder.
     * @param string $column Column name to compare.
     * @param string $value Pattern to search.
     * @param string $boolean Boolean connector ('and' or 'or').
     * @return EloquentBuilder|BaseQueryBuilder|QueryBuilderContract Query builder instance.
     */
    public function whereCaseInsensitiveLike(
        EloquentBuilder|BaseQueryBuilder|QueryBuilderContract $query,
        string $column,
        string $value,
        string $boolean = 'and'
    ): EloquentBuilder|BaseQueryBuilder|QueryBuilderContract {
        $operator = $this->getCaseInsensitiveLikeOperator();

        return $query->where($column, $operator, $value, $boolean);
    }

    /**
     * Apply an OR case-insensitive LIKE condition to a query builder instance.
     *
     * @param EloquentBuilder|BaseQueryBuilder|QueryBuilderContract $query Target query builder.
     * @param string $column Column name to compare.
     * @param string $value Pattern to search.
     * @return EloquentBuilder|BaseQueryBuilder|QueryBuilderContract Query builder instance.
     */
    public function orWhereCaseInsensitiveLike(
        EloquentBuilder|BaseQueryBuilder|QueryBuilderContract $query,
        string $column,
        string $value
    ): EloquentBuilder|BaseQueryBuilder|QueryBuilderContract {
        return $this->whereCaseInsensitiveLike($query, $column, $value, 'or');
    }
}
