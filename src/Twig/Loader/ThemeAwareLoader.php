<?php

declare(strict_types=1);

namespace App\Twig\Loader;

use App\Store\StoreContext;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use function sprintf;

final class ThemeAwareLoader extends FilesystemLoader
{
    private const NAMESPACE_PREFIX = '@theme/';

    /**
     * @throws LoaderError
     */
    public function __construct(
        private readonly StoreContext $storeContext,
        string $themesRootDir,
    )
    {
        parent::__construct();
        $this->addPath($themesRootDir, 'theme');
    }

    /**
     * @throws LoaderError
     */
    protected function findTemplate(string $name, bool $throw = true): ?string
    {
        return parent::findTemplate($this->resolveThemedName($name), $throw);
    }

    private function resolveThemedName(string $name): string
    {
        if (!str_starts_with($name, self::NAMESPACE_PREFIX)) {
            return $name;
        }

        $shortname = substr($name, \strlen(self::NAMESPACE_PREFIX));
        $themeCode = $this->storeContext->get()->getTemplate()->getCode();

        return sprintf('@theme/%s/%s', $themeCode, $shortname);
    }
}
