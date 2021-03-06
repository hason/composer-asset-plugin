<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Converter;

use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Abstract class for converter for asset package to composer package.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractPackageConverter implements PackageConverterInterface
{
    /**
     * @var AssetTypeInterface
     */
    protected $assetType;

    /**
     * Constructor.
     *
     * @param AssetTypeInterface $assetType
     */
    public function __construct(AssetTypeInterface $assetType)
    {
        $this->assetType = $assetType;
    }

    /**
     * Converts the all keys (keys, dependencies and extra keys).
     *
     * @param array $asset        The asset data
     * @param array $keys         The map of asset key and composer key
     * @param array $dependencies The map of asset dependency key and composer dependency key
     * @param array $extras       The map of asset key and composer extra key
     *
     * @return array The composer package converted
     */
    protected function convertData(array $asset, array $keys, array $dependencies, array $extras)
    {
        $composer = array();

        foreach ($keys as $assetKey => $composerKey) {
            $this->convertKey($asset, $assetKey, $composer, $composerKey);
        }

        foreach ($dependencies as $assetKey => $composerKey) {
            $this->convertDependencies($asset, $assetKey, $composer, $composerKey);
        }

        foreach ($extras as $assetKey => $composerKey) {
            $this->convertExtraKey($asset, $assetKey, $composer, $composerKey);
        }

        return $composer;
    }

    /**
     * Converts the simple key of package.
     *
     * @param array        $asset       The asset data
     * @param string       $assetKey    The asset key
     * @param array        &$composer   The composer data
     * @param string|array $composerKey The composer key or array with composer key name and closure
     *
     * @throws \InvalidArgumentException When the 'composerKey' argument of asset packager converter is not an string or an array with the composer key and closure
     */
    protected function convertKey(array $asset, $assetKey, array &$composer, $composerKey)
    {
        if (is_string($composerKey)) {
            if (isset($asset[$assetKey])) {
                $composer[$composerKey] = $asset[$assetKey];
            }

        } elseif (is_array($composerKey) && 2 === count($composerKey)
                && is_string($composerKey[0]) && $composerKey[1] instanceof \Closure) {
            $closure = $composerKey[1];
            $composerKey = $composerKey[0];
            $data = isset($asset[$assetKey]) ? $asset[$assetKey] : null;
            $previousData = isset($composer[$composerKey]) ? $composer[$composerKey] : null;
            $data = $closure($data, $previousData);

            if (null !== $data) {
                $composer[$composerKey] = $data;
            }

        } else {
            throw new \InvalidArgumentException('The "composerKey" argument of asset packager converter must be an string or an array with the composer key and closure');
        }
    }

    /**
     * Converts the extra key of package.
     *
     * @param array        $asset       The asset data
     * @param string       $assetKey    The asset extra key
     * @param array        &$composer   The composer data
     * @param string|array $composerKey The composer extra key or array with composer extra key name and closure
     * @param string       $extraKey    The extra key name
     */
    protected function convertExtraKey(array $asset, $assetKey, array &$composer, $composerKey, $extraKey = 'extra')
    {
        $extra = isset($composer[$extraKey]) ? $composer[$extraKey] : array();

        $this->convertKey($asset, $assetKey, $extra, $composerKey);

        if (count($extra) > 0) {
            $composer[$extraKey] = $extra;
        }
    }

    /**
     * Converts simple key of package.
     *
     * @param array  $asset       The asset data
     * @param string $assetKey    The asset key of dependencies
     * @param array  &$composer   The composer data
     * @param string $composerKey The composer key of dependencies
     */
    protected function convertDependencies(array $asset, $assetKey, array &$composer, $composerKey)
    {
        if (isset($asset[$assetKey]) && is_array($asset[$assetKey])) {
            $newDependencies = array();

            foreach ($asset[$assetKey] as $dependency => $version) {
                $version = $this->assetType->getVersionConverter()->convertRange($version);
                $newDependencies[$this->assetType->getComposerVendorName() . '/' . $dependency] = $version;
            }

            $composer[$composerKey] = $newDependencies;
        }
    }
}
