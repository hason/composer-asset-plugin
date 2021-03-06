<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Converter;

use Fxp\Composer\AssetPlugin\Converter\PackageConverterInterface;
use Fxp\Composer\AssetPlugin\Tests\Fixtures\Converter\InvalidPackageConverter;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Abstract tests of asset package converter.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractPackageConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AssetTypeInterface
     */
    protected $type;

    /**
     * @var PackageConverterInterface
     */
    protected $converter;

    /**
     * @var array
     */
    protected $asset;

    protected function setUp()
    {
        $versionConverter = $this->getMock('Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface');
        $versionConverter->expects($this->any())
            ->method('convertVersion')
            ->will($this->returnValue('VERSION_CONVERTED'));
        $versionConverter->expects($this->any())
            ->method('convertRange')
            ->will($this->returnValue('VERSION_RANGE_CONVERTED'));
        $type = $this->getMock('Fxp\Composer\AssetPlugin\Type\AssetTypeInterface');
        $type->expects($this->any())
            ->method('getComposerVendorName')
            ->will($this->returnValue('ASSET'));
        $type->expects($this->any())
            ->method('getVersionConverter')
            ->will($this->returnValue($versionConverter));

        $this->type = $type;
    }

    protected function tearDown()
    {
        $this->type = null;
        $this->converter = null;
        $this->asset = null;
    }

    public function testConversionWithInvalidKey()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->converter = new InvalidPackageConverter($this->type);

        $this->converter->convert(array(
            'name' => 'foo',
        ));
    }
}
