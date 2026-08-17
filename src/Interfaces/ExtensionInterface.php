<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Interfaces;

interface ExtensionInterface
{
    /**
     * Unknown version of the extension
     */
    public const UNKNOWN_VERSION = '0.0.0';

    /**
     * ExtensionInterface constructor.
     *
     * @param ExtensionsInterface $extensions
     */
    public function __construct(ExtensionsInterface $extensions);

    /**
     * Check if the extension is a core extension
     *
     * @return bool
     */
    public function isCore() : bool;

    /**
     * Get the extensions collection
     *
     * @return ExtensionsInterface
     */
    public function getExtensions() : ExtensionsInterface;

    /**
     * Get the text domain of the extension
     *
     * @return string|null
     */
    public function getTextDomain(): ?string;

    /**
     * Get the domain path of the extension
     *
     * @return string|null
     */
    public function getDomainPath(): ?string;

    /**
     * Get the keywords of the extension
     *
     * @return array
     */
    public function getKeyWords(): array;

    /**
     * Get the name of the extension
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the description of the extension
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get the homepage of the extension
     *
     * @return string|null
     */
    public function getHomepage(): ?string;

    /**
     * Get the version of the extension
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Get the author of the extension
     *
     * @return string|null
     */
    public function getAuthor(): ?string;

    /**
     * Get the support URL of the extension
     *
     * @return string|null
     */
    public function getSupportUrl(): ?string;

    /**
     * Load the extension with the given extension collection
     *
     * @param ExtensionsInterface $extensions
     */
    public function boot(ExtensionsInterface $extensions);

    /**
     * Prepare the extension
     *
     * @param ExtensionsInterface $extensions
     */
    public function prepare(ExtensionsInterface $extensions);

    /**
     * Get the logo of the extension
     *
     * @return string|null
     */
    public function getLogo() : ?string;
}
