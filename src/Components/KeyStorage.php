<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use SensitiveParameter;
use TrayDigita\WP\Headless\Resource\Attributes\SensitiveData;
use TrayDigita\WP\Headless\Resource\Utils\Random;
use function defined;
use function is_string;
use function trim;

/**
 * @template T of string
 * @property string $authKey
 * @property string $authSalt
 */
#[SensitiveData('This class contains sensitive data')]
class KeyStorage
{
    public const AUTH_KEY_NAME = 'authKey';

    public const AUTH_SALT_NAME = 'authSalt';

    /**
     * @var array<string, string> $data
     */
    #[SensitiveData('This property contains sensitive data')]
    private array $data;

    /**
     * KeyStorage constructor.
     *
     * @param Options $option
     */
    public function __construct(public readonly Options $option)
    {
    }

    /**
     * @return array<string, string>
     */
    #[SensitiveData('This method returns sensitive data')]
    public function getData(): array
    {
        return $this->data ??= [];
    }

    #[SensitiveData('This method returns sensitive data')]
    public function getAuthKey(): string
    {
        if (($auth_key = $this->getData()[self::AUTH_KEY_NAME] ?? null)) {
            return $auth_key;
        }
        $auth_key = defined('AUTH_KEY') && is_string(AUTH_KEY) && trim(AUTH_KEY) !== ''
            ? AUTH_KEY
            : $this->option->getOption('AUTH_KEY');
        if (!is_string($auth_key) || trim($auth_key) === '') {
            $auth_key = Random::char();
            $this->option->setOption('AUTH_KEY', $auth_key);
        }
        return $this->data['authKey'] = $auth_key;
    }

    #[SensitiveData('This method returns sensitive data')]
    public function getAuthSalt(): string
    {
        if (($auth_salt = $this->getData()[self::AUTH_SALT_NAME] ?? null)) {
            return $auth_salt;
        }
        $auth_salt = defined('AUTH_SALT')
            && is_string(AUTH_SALT) && trim(AUTH_SALT) !== ''
            ? AUTH_SALT
            : $this->option->getOption('AUTH_SALT');
        if (!is_string($auth_salt) || trim($auth_salt) === '') {
            $auth_salt = Random::char();
            $this->option->setOption('AUTH_SALT', $auth_salt);
        }
        return $this->data[self::AUTH_SALT_NAME] = $auth_salt;
    }

    public function get(string $key)
    {
        return match ($key) {
            self::AUTH_KEY_NAME => $this->getAuthKey(),
            self::AUTH_SALT_NAME => $this->getAuthSalt(),
            default => $this->getData()[$key] ?? null,
        };
    }

    #[SensitiveData('This method sets sensitive data')]
    public function set(string $key, #[SensitiveParameter] string $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return match ($key) {
            self::AUTH_KEY_NAME, self::AUTH_SALT_NAME => true,
            default => isset($this->getData()[$key]),
        };
    }

    #[SensitiveData('This method returns sensitive data')]
    public function __get(string $name)
    {
        return $this->get($name);
    }

    #[SensitiveData('This method sets sensitive data')]
    public function __set(string $name, #[SensitiveParameter] $value): void
    {
        if (!is_string($value)) {
            return;
        }
        $this->set($name, $value);
    }

    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    public function __debugInfo(): ?array
    {
        return [
            'data' => '<redacted>'
        ];
    }
}
