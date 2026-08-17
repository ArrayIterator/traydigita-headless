<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use SensitiveParameter;
use TrayDigita\WP\Headless\Resource\Attributes\SensitiveData;
use TrayDigita\WP\Headless\Resource\Database\WordPressDatabase;
use wpdb as WPDB;

/**
 * @mixin WordPressDatabase
 */
#[SensitiveData('Database contain sensitive data: e.g. database credentials, user data, etc.')]
class Database
{
    #[SensitiveData('This property contains sensitive data')]
    private WordPressDatabase $database;

    #[SensitiveData('This property contains sensitive data')]
    private WordPressDatabase $alternateDatabase;

    /**
     * Database constructor.
     *
     * @param WPDB|WordPressDatabase|null $wpdb
     */
    public function __construct(#[SensitiveParameter] WPDB|WordPressDatabase $wpdb = null)
    {
        global $wpdb;
        $this->database = $wpdb instanceof WordPressDatabase ? $wpdb : new WordPressDatabase($wpdb);
    }

    /**
     * @return WordPressDatabase
     */
    public function getAlternateDatabase(): WordPressDatabase
    {
        return $this->alternateDatabase ??= clone $this->database;
    }

    /**
     * @param WordPressDatabase $alternateDatabase
     */
    public function setAlternateDatabase(#[SensitiveParameter] WordPressDatabase $alternateDatabase): void
    {
        $this->alternateDatabase = $alternateDatabase;
    }

    #[SensitiveData('This method returns sensitive data')]
    public function getDatabase(): WordPressDatabase
    {
        return $this->database;
    }

    public function setDatabase(#[SensitiveParameter] WordPressDatabase $database): void
    {
        $this->database = $database;
    }

    #[SensitiveData('This method returns sensitive data')]
    public function __call(string $name, array $arguments)
    {
        require $this->getDatabase()->$name(...$arguments);
    }

    #[SensitiveData('This method sets sensitive data')]
    public function __set(string $name, #[SensitiveParameter] mixed $value): void
    {
        $this->getDatabase()->$name = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->getDatabase()->$name);
    }

    #[SensitiveData('This method returns sensitive data')]
    public function __get(string $name)
    {
        return $this->getDatabase()->$name;
    }
}
