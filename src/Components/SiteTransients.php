<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use TrayDigita\WP\Headless\Resource\Interfaces\OptionInterface;
use TrayDigita\WP\Headless\Resource\Traits\OptionTrait;
use function doing_action;
use function is_array;

final class SiteTransients implements OptionInterface
{
    use OptionTrait {
        __construct as private constructOption;
        set as private setOption;
        get as private getOption;
        all as private allOptions;
        hookUpdate as private hookUpdateOption;
    }

    /**
     * The name of the option in the database.
     * @var string
     */
    public const OPTION_NAME = 'site_transient';

    public function __construct()
    {
        $this->constructOption();
        $this->expiration = 0;
    }

    /**
     * @inheritdoc
     */
    public function hookUpdate(mixed $value): mixed
    {
        if (!doing_action($this->hookUpdate)) {
            return $value;
        }

        $value = $this->hookUpdateOption($value);
        if (!is_array($value)) {
            return null;
        }
        foreach ($value as $key => $val) {
            if (!$val instanceof Dependencies\Data\TransientData
                || $val->expired
            ) {
                unset($value[$key]);
            }
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    protected function convertRefresh(array $options): array
    {
        foreach ($options as $key => $value) {
            if (!$value instanceof Dependencies\Data\TransientData
                || $value->expired
            ) {
                $this->delete($key);
                unset($options[$key]);
            }
        }
        return $options;
    }

    /**
     * @inheritdoc
     */
    public function all(): array
    {
        $transients = $this->allOptions();
        foreach ($transients as $key => $value) {
            if (!$value instanceof Dependencies\Data\TransientData
                || $value->expired
            ) {
                $this->delete($key);
                unset($transients[$key]);
                continue;
            }
            $transients[$key] = $value->value;
        }
        return $transients;
    }

    /**
     * @inheritdoc
     */
    public function get(string $name, mixed $default = null): mixed
    {
        $value = $this->getOption($name, $default);
        if ($value instanceof Dependencies\Data\TransientData) {
            if ($value->expired) {
                $this->delete($name);
                return $default;
            }
            return $value->value;
        }
        return $default;
    }

    /**
     * @inheritdoc
     */
    public function set(string $name, mixed $value, int $expired = 0): void
    {
        if ($value instanceof Dependencies\Data\TransientData) {
            $this->setOption($name, $value->withExpiration($expired));
        } else {
            $this->setOption($name, new Dependencies\Data\TransientData($name, $value, $expired + time()));
        }
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return self::TYPE_SITE_TRANSIENT;
    }

    public function getOptionName(): string
    {
        return sprintf('%s%s', self::PREFIX, self::OPTION_NAME);
    }
}
