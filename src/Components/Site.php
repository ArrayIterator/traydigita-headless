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
    private string $hostname;

    private string $siteUrl;

    private string $homeUrl;

    private string $name;

    private string $description;

    private string $email;

    private string $language;

    private string $charset;

    private string $version;

    public function __construct()
    {
    }

    public function getSiteUrl(bool $force = false): string
    {
        if ($force || empty($this->siteUrl)) {
            $this->siteUrl = get_site_url();
        }
        return $this->siteUrl;
    }

    public function getHomeUrl(bool $force = false): string
    {
        if ($force || empty($this->homeUrl)) {
            $this->homeUrl = get_home_url();
        }
        return $this->homeUrl;
    }

    public function getHostname(bool $force = false): string
    {
        if ($force || empty($this->hostname)) {
            $url = $this->getSiteUrl($force);
            $parsed = wp_parse_url($url);
            $this->hostname = $parsed[PHP_URL_HOST] ?? '';
        }
        return $this->hostname;
    }

    public function getName(bool $force = false): string
    {
        if ($force || empty($this->name)) {
            $this->name = get_bloginfo('name');
        }
        return $this->name;
    }

    public function getDescription(bool $force = false): string
    {
        if ($force || empty($this->description)) {
            $this->description = get_bloginfo('description');
        }
        return $this->description;
    }

    public function getEmail(bool $force = false): string
    {
        if ($force || empty($this->email)) {
            $this->email = get_bloginfo('admin_email');
        }
        return $this->email;
    }

    public function getLanguage(bool $force = false): string
    {
        if ($force || empty($this->language)) {
            $this->language = get_bloginfo('language');
        }
        return $this->language;
    }

    public function getCharset(bool $force = false): string
    {
        if ($force || empty($this->charset)) {
            $this->charset = get_bloginfo('charset');
        }
        return $this->charset;
    }

    public function getVersion(bool $force = false): string
    {
        if ($force || empty($this->version)) {
            $this->version = get_bloginfo('version');
        }
        return $this->version;
    }

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

    public function __unset(string $name): void
    {
        // void
    }

    public function __isset(string $name): bool
    {
        return match ($name) {
            'siteUrl', 'homeUrl', 'hostname', 'name',
            'description', 'email', 'language', 'charset', 'version' => true,
            default => isset($this->$name)
        };
    }
}
