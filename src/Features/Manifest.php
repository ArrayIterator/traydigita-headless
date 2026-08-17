<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Features;

use Countable;
use JsonSerializable;
use TrayDigita\WP\Headless\Resource\Utils\Filter;
use function array_values;
use function count;
use function get_bloginfo;
use function is_string;
use function ltrim;
use function preg_match;
use function strtolower;
use function trim;

final class Manifest implements JsonSerializable, Countable
{
    public const RECOMMENDED_MANIFEST_SIZE = [
        512,
        192,
        180
    ];

    public string $name;

    public ?string $short_name = null;

    public string $start_url = '/';

    public ?string $description = null;

    /**
     * @var ManifestIcon[]
     */
    public array $icons = [];

    public string $display = self::STANDALONE;

    public ?string $scope = null;

    public ?string $id = null;

    public string $orientation = self::ORIENTATION_ANY;

    public ?string $background_color = null;

    public ?string $theme_color = null;

    public ?string $lang = null;
    public const STANDALONE = 'standalone';

    public const FULLSCREEN = 'fullscreen';

    public const MINIMAL_UI = 'minimal-ui';

    public const ORIENTATION_LANDSCAPE = 'landscape';

    public const ORIENTATION_LANDSCAPE_PRIMARY = 'landscape-primary';

    public const ORIENTATION_LANDSCAPE_SECONDARY = 'landscape-secondary';

    public const ORIENTATION_LANDSCAPE_NATURAL = 'landscape-natural';

    public const ORIENTATION_PORTRAIT = 'portrait';

    public const ORIENTATION_PORTRAIT_PRIMARY = 'portrait-primary';

    public const ORIENTATION_PORTRAIT_SECONDARY = 'portrait-secondary';

    public const ORIENTATION_PORTRAIT_NATURAL = 'portrait-natural';

    public const ORIENTATION_ANY = 'any';

    public const ORIENTATION_NATURAL = 'natural';

    public const ALLOWED_DISPLAY = [
        self::STANDALONE => self::STANDALONE,
        self::MINIMAL_UI => self::MINIMAL_UI,
        self::FULLSCREEN => self::FULLSCREEN,
    ];

    public const ALLOWED_ORIENTATION = [
        self::ORIENTATION_ANY => self::ORIENTATION_ANY,
        self::ORIENTATION_LANDSCAPE => self::ORIENTATION_LANDSCAPE,
        self::ORIENTATION_PORTRAIT => self::ORIENTATION_PORTRAIT,
        self::ORIENTATION_NATURAL => self::ORIENTATION_NATURAL,
        self::ORIENTATION_LANDSCAPE_PRIMARY => self::ORIENTATION_LANDSCAPE_PRIMARY,
        self::ORIENTATION_LANDSCAPE_SECONDARY => self::ORIENTATION_LANDSCAPE_SECONDARY,
        self::ORIENTATION_PORTRAIT_PRIMARY => self::ORIENTATION_PORTRAIT_PRIMARY,
        self::ORIENTATION_PORTRAIT_SECONDARY => self::ORIENTATION_PORTRAIT_SECONDARY,
        self::ORIENTATION_PORTRAIT_NATURAL => self::ORIENTATION_PORTRAIT_NATURAL,
        self::ORIENTATION_LANDSCAPE_NATURAL => self::ORIENTATION_LANDSCAPE_NATURAL,
    ];

    public function __construct(
        ?string $name = null,
        ?string $short_name = null,
        string $description = '',
        ?string $id = null,
        string $start_url = '/',
        ?string $scope = null,
    ) {
        $name ??= get_bloginfo('name');
        $this->setName($name);
        $this->setShortName($short_name);
        $this->setDescription($description);
        $this->setStartUrl($start_url);
        $this->setScope($scope);
        $this->setId($id);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);
        return $this;
    }

    public function getShortName(): ?string
    {
        return $this->short_name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = trim($description) ?: null;
        return $this;
    }

    public function setShortName(?string $short_name): self
    {
        $short_name = is_string($short_name) ? trim($short_name) : $short_name;
        $this->short_name = $short_name ?: null;
        return $this;
    }

    public function getStartUrl(): string
    {
        return $this->start_url;
    }

    public function setStartUrl(string $start_url): self
    {
        if (preg_match('~^(?:https?:)?//[^/]+(/.*)?$~i', $start_url, $match)) {
            $start_url = $match[1] ?: '';
        }
        $start_url = '/' . ltrim($start_url, '/');
        $this->start_url = $start_url;
        return $this;
    }

    public function addIcon(ManifestIcon $icon): self
    {
        $this->icons[] = $icon;
        return $this;
    }

    public function getIcons(): array
    {
        return $this->icons;
    }

    public function getDisplay(): string
    {
        return $this->display;
    }

    public function setDisplay(string $display): self
    {
        $display = strtolower(trim($display));
        $display = self::ALLOWED_DISPLAY[$display] ?? null;
        if ($display) {
            $this->display = $display;
        }
        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    private function cleanScope(?string $scope): ?string
    {
        if (is_string($scope)) {
            $scope = trim($scope);
            if (preg_match('~^(?:https?:)?//[^/]+(/.*)?$~i', $scope, $match)) {
                $scope = $match[1] ?: '';
                if ($scope !== '') {
                    $scope = '/' . ltrim($scope, '/');
                } else {
                    $scope = null;
                }
            }
        }
        return $scope;
    }

    public function setScope(?string $scope): self
    {
        $this->scope = $this->cleanScope($scope);
        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $this->cleanScope($id);
        return $this;
    }

    public function getOrientation(): string
    {
        return $this->orientation;
    }

    public function setOrientation(string $orientation): self
    {
        $orientation = strtolower(trim($orientation));
        $orientation = self::ALLOWED_ORIENTATION[$orientation] ?? $this->orientation;
        $this->orientation = $orientation;
        return $this;
    }

    public function getBackgroundColor(): ?string
    {
        return $this->background_color;
    }

    public function setBackgroundColor(?string $background_color): self
    {
        if (is_string($background_color)) {
            $hex = Filter::colorHex($background_color);
            if (!$hex) {
                return $this;
            }
            $background_color = $hex;
        }
        $this->background_color = $background_color;
        return $this;
    }

    public function getThemeColor(): ?string
    {
        return $this->theme_color;
    }

    public function setThemeColor(?string $theme_color): self
    {
        if (is_string($theme_color)) {
            $hex = Filter::colorHex($theme_color);
            if (!$hex) {
                return $this;
            }
            $theme_color = $hex;
        }
        $this->theme_color = $theme_color;
        return $this;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function setIconsList(ManifestIcon ...$icons): self
    {
        $this->icons = array_values($icons);
        return $this;
    }

    public function setLang(?string $lang): self
    {
        $this->lang = trim($lang) ?: null;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'name' => $this->getName(),
            "short_name" => $this->getShortName(),
            "description" => $this->getDescription(),
            "start_url" => $this->getStartUrl(),
            "id" => $this->getId(),
            "scope" => $this->getScope(),
            "display" => $this->getDisplay(),
            "orientation" => $this->getOrientation(),
            "background_color" => $this->getBackgroundColor(),
            "theme_color" => $this->getThemeColor(),
            "lang" => $this->getLang(),
            'icons' => [],
        ];
        foreach ($data as $key => $item) {
            if ($item === null) {
                unset($data[$key]);
            }
        }
        foreach ($this->getIcons() as $icon) {
            if (!$icon->isValid()) {
                continue;
            }
            $data['icons'][] = $icon;
        }
        return $data;
    }

    public function count(): int
    {
        return count($this->getIcons());
    }
}
