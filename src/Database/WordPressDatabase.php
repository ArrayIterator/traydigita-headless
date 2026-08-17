<?php
/** @noinspection PhpComposerExtensionStubsInspection */
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Database;

use mysqli;
use mysqli_result;
use ReflectionObject;
use TrayDigita\WP\Headless\Resource\Utils\Callback;
use function explode;
use function is_string;
use function preg_match;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function substr;
use const ARRAY_A;
use const MYSQLI_STORE_RESULT;
use const MYSQLI_USE_RESULT;

/**
 * @mixin WPDB
 * @property-read WPDB $wpdb
 * @property-read mysqli|null $dbh
 * @property-read string $database_name
 * @property-read array<string, array{
 *       name: string,
 *       rows: positive-int,
 *       collation: string,
 *       columns: array<string, array{
 *           name: string,
 *           ordinal_position: positive-int,
 *           nullable: boolean,
 *           type: ?string,
 *           charset: ?string,
 *           collation: ?string,
 *           primary: boolean,
 *           index: boolean,
 *           privileges: array<int, "select","insert","update","references">,
 *       }>
 *   }> $schema
 * @property-read string $prefix
 * @property-read string $comments
 * @property-read string $commentmeta
 * @property-read string $links
 * @property-read string $posts
 * @property-read string $postmeta
 * @property-read string $options
 * @property-read string $terms
 * @property-read string $term_taxonomy
 * @property-read string $term_relationships
 * @property-read string $termmeta
 * @property-read string $users
 * @property-read string $usermeta
 * @property-read string $blogs
 * @property-read string $blogmeta
 * @property-read string $signups
 * @property-read string $site
 * @property-read string $sitemeta
 * @property-read string $registration_log
 */
class WordPressDatabase
{
    private string $database_name;

    private null|mysqli|false $dbh;

    private ReflectionObject $ref;

    /**
     * @var array<string, array{
     *     name: string,
     *     tables: array<string, array{
     *          name: string,
     *          rows: positive-int,
     *          collation: string,
     *          columns: array<string, array{
     *              name: string,
     *              nullable: boolean,
     *              ordinal_position: positive-int,
     *              type: ?string,
     *              charset: ?string,
     *              collation: ?string,
     *              primary: boolean,
     *              index: boolean,
     *              privileges: array<int, "select","insert","update","references">,
     *          }>
     *      }>
     * }>
     */
    private static array $table_schemas = [];

    /**
     * @var array<string, array{
     *      name: string,
     *      rows: positive-int,
     *      collation: string,
     *      columns: array<string, array{
     *          name: string,
     *          ordinal_position: positive-int,
     *          nullable: boolean,
     *          type: ?string,
     *          charset: ?string,
     *          collation: ?string,
     *          primary: boolean,
     *          index: boolean,
     *          privileges: array<int, "select","insert","update","references">,
     *      }>
     *  }>
     */
    private array $schema;

    private array $dataset;

    private array $keySet;

    public function __construct(private WPDB $wpdb)
    {
    }

    private function getReflection(): ReflectionObject
    {
        return $this->ref ??= new ReflectionObject($this->wpdb->wpdb());
    }

    public function getDatabaseName(): string
    {
        if (isset($this->database_name)) {
            return $this->database_name;
        }
        $dName = $this->{'dbname'};
        if (is_string($dName)) {
            return $this->database_name = $dName;
        }
        return $this->database_name = $this->unBufferedQueryCallback(
            static function ($result) {
                return ($result->fetch_assoc() ?? [])['db_name'] ?? '';
            },
            'SELECT DATABASE() as `db_name`'
        );
    }

    /**
     * @param callable(int|bool|mysqli_result) : mixed $callback
     * @param string $query
     * @param ...$args
     * @return mixed
     */
    public function unBufferedQueryCallback(
        callable $callback,
        string $query,
        ...$args
    ): mixed {
        try {
            $result = $this->unbufferedQuery($query, ...$args);
            return $callback($result);
        } finally {
            // make sure always closed
            $this->freeResult($result ?? null);
        }
    }

    public function isMySQLiResult(mixed $result): bool
    {
        return $result instanceof mysqli_result && isset($result->num_rows);
    }

    public function filterMySQLiResult(mixed $result): ?mysqli_result
    {
        return $this->isMySQLiResult($result) ? $result : null;
    }

    /**
     * @param mysqli_result|mixed $result
     * @return void
     */
    public function freeResult(mixed $result): void
    {
        Callback::apply(static fn ($result) => $result?->free(), $this->filterMySQLiResult($result));
    }

    public function hasTable(string $table): bool
    {
        $table = strtolower($table);
        if (str_starts_with($table, '`') && str_ends_with($table, '`')) {
            $table = substr($table, 1, -1);
        }
        return isset($this->getSchema()[$table]);
    }

    public function getColumnDefinitions(string $table, string $column): ?array
    {
        $table = strtolower($table);
        if (str_starts_with($table, '`') && str_ends_with($table, '`')) {
            $table = substr($table, 1, -1);
        }
        $schema = $this->getSchema()[$table] ?? null;
        if (!$schema) {
            return null;
        }
        return $schema['columns'][strtolower($column)] ?? null;
    }

    public function hasColumn(string $table, string $column): bool
    {
        return $this->getColumnDefinitions($table, $column) !== null;
    }

    public function isDateTimeColumn(string $table, string $column): bool
    {
        $columns = $this->getColumnDefinitions($table, $column);
        if (!$columns) {
            return false;
        }
        return $columns['type'] === 'datetime';
    }

    public function getSchema(): array
    {
        if (isset($this->schema)) {
            return $this->schema;
        }
        $dbName = $this->getDatabaseName();
        $lower = strtolower($dbName);
        if (isset(self::$table_schemas[$lower])) {
            return $this->schema = self::$table_schemas[$lower]['tables'];
        }

        $this->schema = $this->unBufferedQueryCallback(
            static function ($result) {
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $table_name = $row['table_name'];
                    $rows[strtolower($table_name)] = [
                        'name' => $table_name,
                        'collation' => $row['table_collation'],
                        'rows' => (int)$row['table_rows'],
                        'columns' => [],
                    ];
                }
                return $rows;
            },
            <<<'MYSQL'
SELECT
    TABLE_NAME AS table_name,
    TABLE_COLLATION AS table_collation,
    TABLE_ROWS AS table_rows
FROM
    `information_schema`.`TABLES`
WHERE
    TABLE_SCHEMA = %s
MYSQL,
            $dbName
        );
        self::$table_schemas[$lower] = [
            'name' => $dbName,
            'tables' => &$this->schema
        ];
        $result = $this->unbufferedQuery(
            <<<'MYSQL'
SELECT
    TABLE_NAME as table_name,
    COLUMN_NAME as column_name,
    ORDINAL_POSITION as ordinal_position,
    IS_NULLABLE as is_nullable,
    PRIVILEGES as privileges,
    COLUMN_KEY as column_key,
    COLUMN_TYPE as column_type,
    CHARACTER_SET_NAME AS column_charset,
    COLLATION_NAME AS column_collation,
    TABLE_SCHEMA AS db_name
FROM
    `information_schema`.`COLUMNS`
WHERE
    `TABLE_SCHEMA` = %s
ORDER
    by TABLE_NAME,
       ORDINAL_POSITION
MYSQL,
            $dbName
        );
        while ($row = $result->fetch_assoc()) {
            $table_name = $row['table_name'];
            $lowerName = strtolower($table_name);
            if (!isset($this->schema[strtolower($table_name)])) {
                continue;
            }
            $columnName = $row['column_name'];
            $lowerColumn = strtolower($columnName);
            $this->schema[$lowerName]['columns'][$lowerColumn] = [
                'name' => $columnName,
                'type' => strtolower($row['column_type'] ?? ''),
                'nullable' => strtolower((string)$row['is_nullable']) !== 'no',
                'charset' => $row['column_charset'],
                'index' => !empty($row['column_key']),
                'primary' => $row['column_key'] === 'PRI',
                'collation' => $row['column_collation'],
                'ordinal_position' => (int)$row['ordinal_position'],
                'privileges' => explode(',', (string)$row['privileges'])
            ];
        }
        return $this->schema;
    }

    public function withCloned(): static
    {
        return new static(static::cloneWPDB($this->wpdb));
    }

    public function getPrefix(): string
    {
        return $this->getWPDB()->prefix;
    }

    public function prefixIt(string $table): string
    {
        $prefix = $this->getPrefix();
        if (str_starts_with($table, $prefix) && $this->hasTable($table)) {
            return $table;
        }
        return $prefix . $table;
    }

    public static function isUnBufferedQuery(mixed $result): bool
    {
        return $result instanceof mysqli_result && $result->type === MYSQLI_USE_RESULT;
    }

    /**
     * Clone a WPDB instance
     *
     * @param WPDB|\wpdb|WordPressDatabase $wpdb
     * @return WPDB
     */
    public static function cloneWPDB(WPDB|\wpdb|WordPressDatabase $wpdb): WPDB
    {
        $new_wpdb = new \wpdb(
            $wpdb->{'dbuser'},
            $wpdb->{'dbpassword'},
            $wpdb->{'dbname'},
            $wpdb->{'dbhost'}
        );
        // setup data
        $new_wpdb->set_prefix($new_wpdb->prefix);
        return new WPDB($new_wpdb);
    }

    /**
     * Get the WPDB instance
     *
     * @return WPDB
     */
    public function getWPDB(): WPDB
    {
        return $this->wpdb;
    }

    /**
     * Get the mysqli database handle
     *
     * @return mysqli|null
     */
    public function getDbh(): ?mysqli
    {
        if (isset($this->dbh)) {
            return $this->dbh ?: null;
        }
        $dbh = $this->getWPDB()->{'dbh'};
        $this->dbh = $dbh ?: false;
        return $this->dbh ?: null;
    }

    /**
     * Get the dataset value
     *
     * @param string|null $name
     * @return mixed
     */
    public function getData(?string $name = null): mixed
    {
        if (!isset($name)) {
            return $this->dataset ?? [];
        }
        if (!isset($this->dataset)) {
            return null;
        }
        return $this->dataset[$name] ?? null;
    }

    /**
     * Set the dataset value
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setData(string $name, mixed $value): void
    {
        $this->dataset ??= [];
        $this->dataset[$name] = $value;
    }

    public function __clone(): void
    {
        $this->dbh = null;
        $this->wpdb = self::cloneWPDB($this);
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'database_name':
                return $this->getDatabaseName();
            case 'schema':
                return $this->getSchema();
            case 'prefix':
                return $this->getPrefix();
            case 'wpdb':
                return $this->getWPDB();
            case 'dbh':
                return $this->getDbh();
            case 'dataset':
                return $this->getData();
            case 'keySet':
                return $this->keySet ?? [];
            case 'posts':
            case 'postmeta':
            case 'comments':
            case 'commentmeta':
            case 'links':
            case 'options':
            case 'terms':
            case 'term_taxonomy':
            case 'term_relationships':
            case 'termmeta':
            case 'users':
            case 'usermeta':
            case 'blogs':
            case 'blogmeta':
            case 'signups':
            case 'site':
            case 'sitemeta':
            case 'registration_log':
                return $this->prefixIt($this->getWPDB()->$name);
            default:
                if ($this->getReflection()->hasProperty($name)) {
                    return $this->getWPDB()->$name;
                }
                return $this->getData($name);
        }
    }

    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'database_name':
            case 'schema':
            case 'prefix':
            case 'wpdb':
            case 'dbh':
            case 'dataset':
            case 'keySet':
            case 'posts':
            case 'postmeta':
            case 'comments':
            case 'commentmeta':
            case 'links':
            case 'options':
            case 'terms':
            case 'term_taxonomy':
            case 'term_relationships':
            case 'termmeta':
            case 'users':
            case 'usermeta':
            case 'blogs':
            case 'blogmeta':
            case 'signups':
            case 'site':
            case 'sitemeta':
            case 'registration_log':
                return;
        }
        $this->keySet[$name] = true;
        $this->dataset[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return match ($name) {
            'database_name',
            'wpdb',
            'prefix',
            'schema',
            'posts',
            'postmeta',
            'comments',
            'commentmeta',
            'links',
            'options',
            'terms',
            'term_taxonomy',
            'term_relationships',
            'termmeta',
            'users',
            'usermeta',
            'blogs',
            'blogmeta',
            'signups',
            'site',
            'sitemeta',
            'registration_log'
            => true,
            'dbh' => isset($this->dbh),
            default => isset($this->dataset[$name]) || $this->getReflection()->hasProperty($name)
        };
    }

    public function __unset(string $name): void
    {
        unset($this->dataset[$name], $this->keySet[$name]);
    }

    public function __call(string $name, array $arguments)
    {
        return $this->getWPDB()->$name(...$arguments);
    }

    public function prepareQuery(string $query, ...$args): string
    {
        return $this->getWPDB()->prepare($query, ...$args);
    }

    public function getVar(?string $query = null, int $x = 0, int $y = 0): ?string
    {
        return $this->getWPDB()->get_var($query, $x, $y);
    }

    public function getVarZeroPosition(string $query, ...$args): ?string
    {
        return $this->getVar($this->prepareQuery($query, ...$args));
    }

    public function getLastError(): string
    {
        return $this->getWPDB()->last_error ?: '';
    }

    public function getCharsetCollate(): string
    {
        return $this->getWPDB()->get_charset_collate();
    }

    public function quoteIdentifier(string|array $table): string|array
    {
        return DatabaseUtil::quoteIdentifier($table);
    }

    public function doPrepareQuery(string $query, ...$args): string
    {
        if (!str_contains($query, '%')
            || !preg_match(
                '~(?:[1-9][0-9]*[$])?[-+0-9]*(?: |0|\'.)?[-+0-9]*(?:\.[0-9]+)?~',
                $query
            )
        ) {
            return $query;
        }
        return $this->prepareQuery($query, $args);
    }

    public function getResults(string $query, bool $returnAssoc = false, ...$args): array|object|null
    {
        return $this->getWPDB()->get_results($this->doPrepareQuery($query, ...$args), $returnAssoc ? ARRAY_A : OBJECT);
    }

    public function getRow(string $query, bool $returnAssoc = false, int $y = 0, ...$args): array|object|null
    {
        $query = $this->doPrepareQuery($query, ...$args);
        return $this->getWPDB()->get_row($query, $returnAssoc ? ARRAY_A : OBJECT, $y);
    }

    public function query(string $query, ...$args): bool|int
    {
        return $this->getWPDB()->query($this->doPrepareQuery($query, ...$args));
    }

    public function executeQuery(string $query, ...$args): ?mysqli_result
    {
        $this->query($this->doPrepareQuery($query, ...$args));
        $result = $this->{'result'} ?? null;
        return $result instanceof mysqli_result ? $result : null;
    }

    public function directQuery(string $query, int $result_mode = MYSQLI_STORE_RESULT, ...$args): mysqli_result|bool
    {
        return $this->getDbh()->query($this->doPrepareQuery($query, ...$args), $result_mode);
    }

    public function unbufferedQuery(string $query, ...$args): mysqli_result|bool
    {
        return $this->directQuery($query, MYSQLI_USE_RESULT, ...$args);
    }

    public function getTableComments(): string
    {
        return $this->prefixIt($this->comments);
    }

    public function getTableCommentMeta(): string
    {
        return $this->prefixIt($this->commentmeta);
    }

    public function getTableLinks(): string
    {
        return $this->prefixIt($this->links);
    }

    public function getTablePosts(): string
    {
        return $this->prefixIt($this->posts);
    }

    public function getTablePostMeta(): string
    {
        return $this->prefixIt($this->postmeta);
    }

    public function getTableOptions(): string
    {
        return $this->prefixIt($this->options);
    }

    public function getTableTerms(): string
    {
        return $this->prefixIt($this->terms);
    }

    public function getTableTermTaxonomy(): string
    {
        return $this->prefixIt($this->term_taxonomy);
    }

    public function getTableTermRelationships(): string
    {
        return $this->prefixIt($this->term_relationships);
    }

    public function getTableTermMeta(): string
    {
        return $this->prefixIt($this->termmeta);
    }

    public function getTableUsers(): string
    {
        return $this->prefixIt($this->users);
    }

    public function getTableUserMeta(): string
    {
        return $this->prefixIt($this->usermeta);
    }

    public function getTableBlogs(): string
    {
        return $this->prefixIt($this->blogs);
    }

    public function getTableBlogMeta(): string
    {
        return $this->prefixIt($this->blogmeta);
    }

    public function getTableSignups(): string
    {
        return $this->prefixIt($this->signups);
    }

    public function getTableSite(): string
    {
        return $this->prefixIt($this->site);
    }

    public function getTableSiteMeta(): string
    {
        return $this->prefixIt($this->sitemeta);
    }

    public function getTableRegistrationLog(): string
    {
        return $this->prefixIt($this->registration_log);
    }
}
