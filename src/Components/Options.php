<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

class Options
{
    /**
     * Constructor for Options class
     *
     * @param SiteOptions $options The SiteOptions instance for managing site options
     * @param SiteTransients $transients The SiteTransients instance for managing site transients
     */
    public function __construct(
        public readonly SiteOptions $options,
        public readonly SiteTransients $transients
    ) {
    }

    /**
     * Get an option value by name
     *
     * @param string $name The name of the option
     * @param mixed $default The default value to return if the option does not exist
     * @return mixed The value of the option or the default value
     */
    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options->get($name, $default);
    }

    /**
     * Set an option value by name
     *
     * @param string $name The name of the option
     * @param mixed $value The value to set for the option
     */
    public function setOption(string $name, mixed $value): void
    {
        $this->options->set($name, $value);
    }

    /**
     * Delete an option by name
     *
     * @param string $name The name of the option to delete
     */
    public function deleteOption(string $name): void
    {
        $this->options->delete($name);
    }

    /**
     * Get a transient value by name
     *
     * @param string $name The name of the transient
     * @param mixed $default The default value to return if the transient does not exist
     * @return mixed The value of the transient or the default value
     */
    public function getTransient(string $name, mixed $default = null): mixed
    {
        return $this->transients->get($name, $default);
    }

    /**
     * Set a transient value by name with an optional expiration time
     *
     * @param string $name The name of the transient
     * @param mixed $value The value to set for the transient
     * @param int $expired The expiration time in seconds (0 for no expiration)
     */
    public function setTransient(string $name, mixed $value, int $expired = 0): void
    {
        $this->transients->set($name, $value, $expired);
    }

    /**
     * Delete a transient by name
     *
     * @param string $name The name of the transient to delete
     */
    public function deleteTransient(string $name): void
    {
        $this->transients->delete($name);
    }
}
