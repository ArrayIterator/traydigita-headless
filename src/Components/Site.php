<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use function get_bloginfo;
use function get_home_url;
use function get_site_url;
use function wp_parse_url;
use const PHP_URL_HOST;

/**
 * @property-read string $hostname
 * @property-read string $siteUrl
 * @property-read string $homeUrl
 * @property-read string $name
 * @property-read string $description
 * @property-read string $email
 */
class Site
{
    /**
     * The site's hostname, parsed from the site URL.
     *
     * @var string
     */
    private string $hostname;

    /**
     * The site URL as returned by get_site_url().
     *
     * @var string
     */
    private string $siteUrl;

    /**
     * The home URL as returned by get_home_url().
     *
     * @var string
     */
    private string $homeUrl;

    /**
     * The site name (blog name).
     *
     * @var string
     */
    private string $name;

    /**
     * The site description (tagline).
     *
     * @var string
     */
    private string $description;

    /**
     * The admin email address of the site.
     *
     * @var string
     */
    private string $email;

    /**
     * The site language locale.
     *
     * @var string
     */
    private string $language;

    /**
     * The site charset.
     *
     * @var string
     */
    private string $charset;

    /**
     * The WordPress version.
     *
     * @var string
     */
    private string $version;

    /**
     * Site constructor.
     */
    public function __construct()
    {
    }

    /**
     * Get the site URL, optionally forcing a refresh.
     *
     * @param bool $force Force re-fetching the value instead of using the cached one.
     * @return string
     */
    public function getSiteUrl(bool $force = false): string
    {
        if ($force || empty($this->siteUrl)) {
            $this->siteUrl = get_site_url();
        }
        return $this->siteUrl;
    }

    /**
     * Get the home URL, optionally forcing a refresh.
     *
     * @param bool $force Force re-fetching the value instead of using the cached one.
     * @return string
     */
    public function getHomeUrl(bool $force = false): string
    {
        if ($force || empty($this->homeUrl)) {
            $this->homeUrl = get_home_url();
        }
        return $this->homeUrl;
    }

    /**
     * Get the site hostname parsed from the site URL, optionally forcing a refresh.
     *
     * @param bool $force Force re-fetching the value instead of using the cached one.
     * @return string
     */
    public function getHostname(bool $force = false): string
    {
        if ($force || empty($this->hostname)) {
            $url = $this->getSiteUrl($force);
            $parsed = wp_parse_url($url);
            $this->hostname = $parsed[PHP_URL_HOST] ?? '';
        }
        return $this->hostname;
    }

    /**
     * Get the site name (blog name), optionally forcing a refresh.
     *
     * @param bool $force Force re-fetching the value instead of using the cached one.
     * @return string
     */
    public function getName(bool $force = false): string
    {
        if ($force || empty($this->name)) {
            $this->name = get_bloginfo('name');
        }
        return $this->name;
    }

    /**
     * Get the site description (tagline), optionally forcing a refresh.
     *
     * @param bool $force Force re-fetching the value instead of using the cached one.
     * @return string
     */
    public function getDescription(bool $force = false): string
    {
        if ($force || empty($this->description)) {
            $this->description = get_bloginfo('description');
        }
        return $this->description;
    }

    /**
     * Get the admin email address of the site, optionally forcing a refresh.
     *
     * @param bool $force Force re-fetching the value instead of using the cached one.
     * @return string
     */
    public function getEmail(bool $force = false): string
    {
        if ($force || empty($this->email)) {
            $this->email = get_bloginfo('admin_email');
        }
        return $this->email;
    }

    /**
     * Get the site language locale, optionally forcing a refresh.
     *
     * @param bool $force Force re-fetching the value instead of using the cached one.
     * @return string
     */
    public function getLanguage(bool $force = false): string
    {
        if ($force || empty($this->language)) {
            $this->language = get_bloginfo('language');
        }
        return $this->language;
    }

    /**
     * Get the site charset, optionally forcing a refresh.
     *
     * @param bool $force Force re-fetching the value instead of using the cached one.
     * @return string
     */
    public function getCharset(bool $force = false): string
    {
        if ($force || empty($this->charset)) {
            $this->charset = get_bloginfo('charset');
        }
        return $this->charset;
    }

    /**
     * Get the WordPress version, optionally forcing a refresh.
     *
     * @param bool $force Force re-fetching the value instead of using the cached one.
     * @return string
     */
    public function getVersion(bool $force = false): string
    {
        if ($force || empty($this->version)) {
            $this->version = get_bloginfo('version');
        }
        return $this->version;
    }

    /**
     * Magic getter to access properties via their public-facing property names.
     *
     * @param string $name The property name to retrieve.
     * @return mixed
     */
    public function __get(string $name)
    {
        return match ($name) {
            'siteUrl' => $this->getSiteUrl(),
            'homeUrl' => $this->getHomeUrl(),
            'hostname' => $this->getHostname(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'email' => $this->getEmail(),
            'language' => $this->getLanguage(),
            'charset' => $this->getCharset(),
            'version' => $this->getVersion(),
            default => $this->$name ?? null
        };
    }

    /**
     * Magic un-setter, intentionally a no-op since properties are managed internally.
     *
     * @param string $name The property name to unset.
     * @return void
     */
    public function __unset(string $name): void
    {
        // void
    }

    /**
     * Magic isset check for public-facing property names.
     *
     * @param string $name The property name to check.
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return match ($name) {
            'siteUrl', 'homeUrl', 'hostname', 'name',
            'description', 'email', 'language', 'charset', 'version' => true,
            default => isset($this->$name)
        };
    }
}
