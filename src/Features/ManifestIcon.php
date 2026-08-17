<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Features;

use JsonSerializable;
use function array_keys;
use function implode;
use function preg_replace;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function trim;
use function wp_check_filetype;
use function wp_get_mime_types;

final class ManifestIcon implements JsonSerializable
{
    public const MASKABLE = 'maskable';

    public const ANY = 'any';

    public const MONOCHROME = 'monochrome';

    public const PURPOSE_AVAILABLE = [
        self::MASKABLE => true,
        self::MONOCHROME => true,
        self::ANY => true,
    ];

    protected array $purpose = [];

    protected string $url;

    protected ?string $mime_type = null;

    protected int $size;

    public function __construct(
        string $image,
        int $size
    ) {
        $this->setImage($image, $size);
    }

    public function setImage(string $url, int $size, ?string $mime_type = null): void
    {
        $this->size = $size;
        if (!$mime_type) {
            $image = preg_replace('~([#?].*)$~', '', $url);
            $type = wp_check_filetype($image, wp_get_mime_types());
            if (!$type || !str_starts_with($type['type'], 'image/')) {
                $type = null;
            }
            $mime_type = $type ? $type['type'] : null;
        }
        $this->url = $url;
        $this->mime_type = $mime_type;
    }

    public static function create(string $url, int $size): self
    {
        return new self($url, $size);
    }

    public function setPurpose(string $purpose): void
    {
        $purpose = strtolower(trim($purpose));
        if (!isset(self::PURPOSE_AVAILABLE[$purpose])) {
            return;
        }
        $this->purpose = [$purpose => true];
    }

    public function addPurpose(string $purpose): void
    {
        $purpose = strtolower(trim($purpose));
        if (!isset(self::PURPOSE_AVAILABLE[$purpose])) {
            return;
        }
        $this->purpose[$purpose] = true;
    }

    public function removePurpose(string $purpose): void
    {
        $purpose = strtolower(trim($purpose));
        unset($this->purpose[$purpose]);
    }

    public function getPurpose(): array
    {
        return array_keys($this->purpose);
    }

    public function jsonSerialize(): array
    {
        $purpose = implode(' ', $this->getPurpose());
        if (!$purpose) {
            $purpose = self::ANY;
        }
        return [
            'src' => $this->getUrl(),
            'sizes' => sprintf('%1$dx%1$d', $this->getSize()),
            'type' => $this->getMimeType() ?? 'image/png',
            'purpose' => $purpose
        ];
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getMimeType(): ?string
    {
        return $this->mime_type;
    }

    public function isValid(): bool
    {
        return $this->getMimeType() && $this->getSize() > 0;
    }
}
