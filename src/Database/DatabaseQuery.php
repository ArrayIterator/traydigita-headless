<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Database;

use mysqli_result;
use Stringable;
use TrayDigita\WP\Headless\Resource\Components\Database;
use TrayDigita\WP\Headless\Resource\Database\Builder\Expression;
use TrayDigita\WP\Headless\Resource\Database\Builder\QueryBuilder;
use function is_array;
use function is_numeric;
use function reset;

class DatabaseQuery
{
    private string $table;

    private WordPressDatabase $database;

    private QueryBuilder $queryBuilder;

    private ?mysqli_result $result;

    private ?int $count = null;

    private bool $unbuffered;

    public function __construct(
        WordPressDatabase|Database $database,
        string $table,
        bool $unbuffered = false
    ) {
        $this->unbuffered = $unbuffered;
        $this->database = $database instanceof Database ? $database->getDatabase() : $database;
        $this->table = $database->prefixIt($table);
        $this->queryBuilder = (QueryBuilder::new($this->table));
    }

    public function getLimit(): int
    {
        return $this->queryBuilder->getLimit();
    }

    public function getOffset(): int
    {
        return $this->queryBuilder->getOffset();
    }

    public function isUnbuffered(): bool
    {
        return $this->unbuffered;
    }

    public function expr(): Expression
    {
        return $this->queryBuilder->expr();
    }

    public function getDatabase(): WordPressDatabase
    {
        return $this->database;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function where(
        string|Stringable $where,
        string|Stringable ...$wheres
    ): static {
        $this->count = null;
        $this->queryBuilder->where($where, ...$wheres);
        return $this;
    }

    public function andWhere(
        string|Stringable $where,
        string|Stringable ...$wheres
    ): static {
        $this->count = null;
        $this->queryBuilder->andWhere($where, ...$wheres);
        return $this;
    }

    public function orWhere(
        string|Stringable $where,
        string|Stringable ...$wheres
    ): static {
        $this->count = null;
        $this->queryBuilder->orWhere($where, ...$wheres);
        return $this;
    }

    public function groupBy(string $groupBy, string ...$groupBys): static
    {
        $this->count = null;
        $this->queryBuilder->groupBy($groupBy, ...$groupBys);
        return $this;
    }

    public function addGroupBy(string $groupBy, string ...$groupBys): static
    {
        $this->count = null;
        $this->queryBuilder->addGroupBy($groupBy, ...$groupBys);
        return $this;
    }

    public function having(string $having, string ...$havings): static
    {
        $this->count = null;
        $this->queryBuilder->addGroupBy($having, ...$havings);
        return $this;
    }

    public function andHaving(string $having, string ...$havings): static
    {
        $this->count = null;
        $this->queryBuilder->andHaving($having, ...$havings);
        return $this;
    }

    public function orHaving(string $having, string ...$havings): static
    {
        $this->count = null;
        $this->queryBuilder->orHaving($having, ...$havings);
        return $this;
    }

    public function orderBy(?string $orderBy, string $order = 'ASC'): static
    {
        $this->count = null;
        $this->queryBuilder->orderBy($orderBy, $order);
        return $this;
    }

    public function addOrderBy(string $orderBy, string $order = 'ASC'): static
    {
        $this->count = null;
        $this->queryBuilder->addOrderBy($orderBy, $order);
        return $this;
    }

    public function offset(?int $offset): static
    {
        $this->count = null;
        $this->queryBuilder->offset($offset);
        return $this;
    }

    public function limit(?int $limit, ?int $offset = null): static
    {
        $this->count = null;
        $this->queryBuilder->limit($limit, $offset);
        return $this;
    }

    public function setParameter(
        string $name,
        mixed $value,
    ): static {
        $this->count = null;
        $this->queryBuilder->setParameter($name, $value);
        return $this;
    }

    /**
     * Set JOIN
     *
     * @param string $fromAlias
     * @param string $join
     * @param string $alias
     * @param string|Stringable|null $condition
     * @return $this
     */
    public function join(
        string $fromAlias,
        string $join,
        string $alias,
        string|Stringable|null $condition = null
    ): self {
        $this->count = null;
        return $this->queryBuilder->join($fromAlias, $join, $alias, $condition);
    }

    /**
     * Set INNER JOIN
     *
     * @param string $fromAlias
     * @param string $join
     * @param string $alias
     * @param string|Stringable|null $condition
     * @return $this
     */
    public function innerJoin(
        string $fromAlias,
        string $join,
        string $alias,
        string|Stringable|null $condition = null
    ): static {
        $this->count = null;
        $this->queryBuilder->join($fromAlias, $join, $alias, $condition);
        return $this;
    }

    public function outerJoin(
        string $fromAlias,
        string $join,
        string $alias,
        string|Stringable|null $condition = null
    ): static {
        $this->count = null;
        $this->queryBuilder->outerJoin($fromAlias, $join, $alias, $condition);
        return $this;
    }

    /**
     * Set LEFT JOIN
     *
     * @param string $fromAlias
     * @param string $join
     * @param string $alias
     * @param string|Stringable|null $condition
     * @return $this
     */
    public function leftJoin(
        string $fromAlias,
        string $join,
        string $alias,
        string|Stringable|null $condition = null
    ): static {
        $this->count = null;
        $this->queryBuilder->leftJoin($fromAlias, $join, $alias, $condition);
        return $this;
    }

    /**
     * Set RIGHT JOIN
     *
     * @param string $fromAlias
     * @param string $join
     * @param string $alias
     * @param string|Stringable|null $condition
     * @return $this
     */
    public function rightJoin(
        string $fromAlias,
        string $join,
        string $alias,
        string|Stringable|null $condition = null
    ): static {
        $this->count = null;
        $this->queryBuilder->rightJoin($fromAlias, $join, $alias, $condition);
        return $this;
    }

    public function getParameters(): array
    {
        return $this->queryBuilder->getParameters();
    }

    public function getSpreadParameters(): array
    {
        return $this->queryBuilder->getSpreadParameters();
    }

    public function getParameter(string $parameter): mixed
    {
        return $this->queryBuilder->getParameter($parameter);
    }

    public function removeParameter(string $parameter): static
    {
        $this->count = null;
        $this->queryBuilder->removeParameter($parameter);
        return $this;
    }

    public function getSQL(): string
    {
        return $this->queryBuilder->getSQL();
    }

    public function getCountSQL(): string
    {
        return $this->queryBuilder->getSQLForCount();
    }

    public function execute(): ?mysqli_result
    {
        if (isset($this->result)) {
            $this->getDatabase()->freeResult($this->result);
        }
        return $this->result = $this->queryBuilder->execute(
            $this->getDatabase(),
            $this->isUnbuffered()
        );
    }

    public function getResult() : ?mysqli_result
    {
        return $this->result??null;
    }

    public function getCount() : int
    {
        return $this->count ??= $this
            ->getDatabase()
            ->unBufferedQueryCallback(
                static function ($m) {
                    $count = 0;
                    if ($m instanceof mysqli_result && is_array($res = $m->fetch_assoc())) {
                        $res = reset($res);
                        $count = is_numeric($res) ? (int) $res : 0;
                    }
                    return $count;
                },
                $this->queryBuilder->getSQLForCount(),
                ...$this->queryBuilder->getSpreadParameters()
            );
    }

    public function withClonedQueryBuilder() : static
    {
        $self = clone $this;
        $self->queryBuilder = clone $self->queryBuilder;
        return $self;
    }

    public function __destruct()
    {
        if (isset($this->result)) {
            $result = $this->result;
            $this->result = null;
            $this->getDatabase()->freeResult($result);
        }
    }
}
