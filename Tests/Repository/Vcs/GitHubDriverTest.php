<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Repository\Vcs;

use Composer\Downloader\TransportException;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Config;
use Composer\Config\ConfigSourceInterface;
use Composer\Util\ProcessExecutor;
use Composer\Util\RemoteFilesystem;
use Fxp\Composer\AssetPlugin\Repository\Vcs\GitHubDriver;

/**
 * Tests of vcs github repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class GitHubDriverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Config
     */
    private $config;

    public function setUp()
    {
        $this->config = new Config();
        $this->config->merge(array(
            'config' => array(
                'home' => sys_get_temp_dir() . '/composer-test',
            ),
        ));
    }

    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->removeDirectory(sys_get_temp_dir() . '/composer-test');
    }

    public function getAssetTypes()
    {
        return array(
            array('npm', 'package.json'),
            array('bower', 'bower.json'),
        );
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testPrivateRepository($type, $filename)
    {
        $repoUrl = 'http://github.com/francoispluchino/composer-asset-plugin';
        $repoApiUrl = 'https://api.github.com/repos/francoispluchino/composer-asset-plugin';
        $repoSshUrl = 'git@github.com:francoispluchino/composer-asset-plugin.git';
        $identifier = 'v0.0.0';
        $sha = 'SOMESHA';

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(true));

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $process = $this->getMock('Composer\Util\ProcessExecutor');
        $process->expects($this->any())
            ->method('execute')
            ->will($this->returnValue(1));

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->throwException(new TransportException('HTTP/1.1 404 Not Found', 404)));

        $io->expects($this->once())
            ->method('ask')
            ->with($this->equalTo('Username: '))
            ->will($this->returnValue('someuser'));

        $io->expects($this->once())
            ->method('askAndHideAnswer')
            ->with($this->equalTo('Password: '))
            ->will($this->returnValue('somepassword'));

        $io->expects($this->any())
            ->method('setAuthentication')
            ->with($this->equalTo('github.com'), $this->matchesRegularExpression('{someuser|abcdef}'), $this->matchesRegularExpression('{somepassword|x-oauth-basic}'));

        $remoteFilesystem->expects($this->at(1))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/authorizations'), $this->equalTo(false))
            ->will($this->returnValue('[]'));

        $remoteFilesystem->expects($this->at(2))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/authorizations'), $this->equalTo(false))
            ->will($this->returnValue('{"token": "abcdef"}'));

        $remoteFilesystem->expects($this->at(3))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->returnValue('{"master_branch": "test_master", "private": true}'));

        $configSource = $this->getMock('Composer\Config\ConfigSourceInterface');
        $authConfigSource = $this->getMock('Composer\Config\ConfigSourceInterface');

        /* @var ConfigSourceInterface $configSource */
        /* @var ConfigSourceInterface $authConfigSource */
        /* @var ProcessExecutor $process */
        /* @var RemoteFilesystem $remoteFilesystem */
        /* @var IOInterface $io */

        $this->config->setConfigSource($configSource);
        $this->config->setAuthConfigSource($authConfigSource);

        $repoConfig = array(
            'url'        => $repoUrl,
            'asset-type' => $type,
            'filename'   => $filename,
        );

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, $process, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));

        $this->assertEquals('test_master', $gitHubDriver->getRootIdentifier());

        $dist = $gitHubDriver->getDist($sha);
        $this->assertEquals('zip', $dist['type']);
        $this->assertEquals('https://api.github.com/repos/francoispluchino/composer-asset-plugin/zipball/SOMESHA', $dist['url']);
        $this->assertEquals('SOMESHA', $dist['reference']);

        $source = $gitHubDriver->getSource($sha);
        $this->assertEquals('git', $source['type']);
        $this->assertEquals($repoSshUrl, $source['url']);
        $this->assertEquals('SOMESHA', $source['reference']);
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testPublicRepository($type, $filename)
    {
        $repoUrl = 'http://github.com/francoispluchino/composer-asset-plugin';
        $repoApiUrl = 'https://api.github.com/repos/francoispluchino/composer-asset-plugin';
        $identifier = 'v0.0.0';
        $sha = 'SOMESHA';

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(true));

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->returnValue('{"master_branch": "test_master"}'));

        $repoConfig = array(
            'url'        => $repoUrl,
            'asset-type' => $type,
            'filename'   => $filename,
        );
        $repoUrl = 'https://github.com/francoispluchino/composer-asset-plugin.git';

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));

        $this->assertEquals('test_master', $gitHubDriver->getRootIdentifier());

        $dist = $gitHubDriver->getDist($sha);
        $this->assertEquals('zip', $dist['type']);
        $this->assertEquals('https://api.github.com/repos/francoispluchino/composer-asset-plugin/zipball/SOMESHA', $dist['url']);
        $this->assertEquals($sha, $dist['reference']);

        $source = $gitHubDriver->getSource($sha);
        $this->assertEquals('git', $source['type']);
        $this->assertEquals($repoUrl, $source['url']);
        $this->assertEquals($sha, $source['reference']);
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testPublicRepository2($type, $filename)
    {
        $repoUrl = 'http://github.com/francoispluchino/composer-asset-plugin';
        $repoApiUrl = 'https://api.github.com/repos/francoispluchino/composer-asset-plugin';
        $identifier = 'feature/3.2-foo';
        $sha = 'SOMESHA';

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(true));

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->returnValue('{"master_branch": "test_master"}'));

        $remoteFilesystem->expects($this->at(1))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/repos/francoispluchino/composer-asset-plugin/contents/'.$filename.'?ref=feature%2F3.2-foo'), $this->equalTo(false))
            ->will($this->returnValue('{"encoding":"base64","content":"'.base64_encode('{"support": {"source": "'.$repoUrl.'" }}').'"}'));

        $remoteFilesystem->expects($this->at(2))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/repos/francoispluchino/composer-asset-plugin/commits/feature%2F3.2-foo'), $this->equalTo(false))
            ->will($this->returnValue('{"commit": {"committer":{ "date": "2012-09-10"}}}'));

        $repoConfig = array(
            'url'        => $repoUrl,
            'asset-type' => $type,
            'filename'   => $filename,
        );
        $repoUrl = 'https://github.com/francoispluchino/composer-asset-plugin.git';

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));

        $this->assertEquals('test_master', $gitHubDriver->getRootIdentifier());

        $dist = $gitHubDriver->getDist($sha);
        $this->assertEquals('zip', $dist['type']);
        $this->assertEquals('https://api.github.com/repos/francoispluchino/composer-asset-plugin/zipball/SOMESHA', $dist['url']);
        $this->assertEquals($sha, $dist['reference']);

        $source = $gitHubDriver->getSource($sha);
        $this->assertEquals('git', $source['type']);
        $this->assertEquals($repoUrl, $source['url']);
        $this->assertEquals($sha, $source['reference']);

        $gitHubDriver->getComposerInformation($identifier);
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testPrivateRepositoryNoInteraction($type, $filename)
    {
        $repoUrl = 'http://github.com/francoispluchino/composer-asset-plugin';
        $repoApiUrl = 'https://api.github.com/repos/francoispluchino/composer-asset-plugin';
        $repoSshUrl = 'git@github.com:francoispluchino/composer-asset-plugin.git';
        $identifier = 'v0.0.0';
        $sha = 'SOMESHA';

        $process = $this->getMockBuilder('Composer\Util\ProcessExecutor')
            ->disableOriginalConstructor()
            ->getMock();

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(false));

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->throwException(new TransportException('HTTP/1.1 404 Not Found', 404)));

        // clean local clone if present
        $fs = new Filesystem();
        $fs->removeDirectory(sys_get_temp_dir() . '/composer-test');

        $process->expects($this->at(0))
            ->method('execute')
            ->with($this->equalTo('git config github.accesstoken'))
            ->will($this->returnValue(1));

        $process->expects($this->at(1))
            ->method('execute')
            ->with($this->stringContains($repoSshUrl))
            ->will($this->returnValue(0));

        $process->expects($this->at(2))
            ->method('execute')
            ->with($this->stringContains('git show-ref --tags'));

        $process->expects($this->at(3))
            ->method('splitLines')
            ->will($this->returnValue(array($sha.' refs/tags/'.$identifier)));

        $process->expects($this->at(4))
            ->method('execute')
            ->with($this->stringContains('git branch --no-color --no-abbrev -v'));

        $process->expects($this->at(5))
            ->method('splitLines')
            ->will($this->returnValue(array('  test_master     edf93f1fccaebd8764383dc12016d0a1a9672d89 Fix test & behavior')));

        $process->expects($this->at(6))
            ->method('execute')
            ->with($this->stringContains('git branch --no-color'));

        $process->expects($this->at(7))
            ->method('splitLines')
            ->will($this->returnValue(array('* test_master')));

        $repoConfig = array(
            'url'        => $repoUrl,
            'asset-type' => $type,
            'filename'   => $filename,
        );

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem */
        /* @var ProcessExecutor $process */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, $process, $remoteFilesystem);
        $gitHubDriver->initialize();

        $this->assertEquals('test_master', $gitHubDriver->getRootIdentifier());

        $dist = $gitHubDriver->getDist($sha);
        $this->assertEquals('zip', $dist['type']);
        $this->assertEquals('https://api.github.com/repos/francoispluchino/composer-asset-plugin/zipball/SOMESHA', $dist['url']);
        $this->assertEquals($sha, $dist['reference']);

        $source = $gitHubDriver->getSource($identifier);
        $this->assertEquals('git', $source['type']);
        $this->assertEquals($repoSshUrl, $source['url']);
        $this->assertEquals($identifier, $source['reference']);

        $source = $gitHubDriver->getSource($sha);
        $this->assertEquals('git', $source['type']);
        $this->assertEquals($repoSshUrl, $source['url']);
        $this->assertEquals($sha, $source['reference']);
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testGetComposerInformationWithGitDriver($type, $filename)
    {
        $repoUrl = 'https://github.com/francoispluchino/composer-asset-plugin';
        $identifier = 'v0.0.0';

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(true));

        $repoConfig = array(
            'url'        => $repoUrl,
            'asset-type' => $type,
            'filename'   => $filename,
            'no-api'     => true,
        );

        $process = $this->getMock('Composer\Util\ProcessExecutor');
        $process->expects($this->any())
            ->method('splitLines')
            ->will($this->returnValue(array()));
        $process->expects($this->any())
            ->method('execute')
            ->will($this->returnCallback(function () {
                        return 0;
                    }));

        /* @var IOInterface $io */
        /* @var ProcessExecutor $process */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, $process, null);
        $gitHubDriver->initialize();

        $this->assertNull($gitHubDriver->getComposerInformation($identifier));
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testGetComposerInformationInCache($type, $filename)
    {
        $repoUrl = 'http://github.com/francoispluchino/composer-asset-plugin';
        $repoApiUrl = 'https://api.github.com/repos/francoispluchino/composer-asset-plugin';
        $identifier = 'dev-master';
        $sha = '92bebbfdcde75ef2368317830e54b605bc938123';

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(true));

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->returnValue('{"master_branch": "test_master"}'));

        $remoteFilesystem->expects($this->at(1))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/repos/francoispluchino/composer-asset-plugin/contents/'.$filename.'?ref='.$sha), $this->equalTo(false))
            ->will($this->returnValue('{"encoding":"base64","content":"'.base64_encode('{"support": {}}').'"}'));

        $remoteFilesystem->expects($this->at(2))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/repos/francoispluchino/composer-asset-plugin/commits/'.$sha), $this->equalTo(false))
            ->will($this->returnValue('{"commit": {"committer":{ "date": "2012-09-10"}}}'));

        $repoConfig = array(
            'url'        => $repoUrl,
            'asset-type' => $type,
            'filename'   => $filename,
        );

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));
        $this->setAttribute($gitHubDriver, 'hasIssues', true);

        $composer1 = $gitHubDriver->getComposerInformation($sha);
        $composer2 = $gitHubDriver->getComposerInformation($sha);

        $this->assertSame($composer1, $composer2);
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testGetComposerInformationWithEmptyContent($type, $filename)
    {
        $this->setExpectedException('RuntimeException');

        $repoUrl = 'http://github.com/francoispluchino/composer-asset-plugin';
        $repoApiUrl = 'https://api.github.com/repos/francoispluchino/composer-asset-plugin';
        $identifier = 'v0.0.0';

        $io = $this->getMock('Composer\IO\IOInterface');

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->returnValue('{"master_branch": "test_master"}'));

        $remoteFilesystem->expects($this->at(1))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/repos/francoispluchino/composer-asset-plugin/contents/'.$filename.'?ref='.$identifier), $this->equalTo(false))
            ->will($this->returnValue('{"encoding":"base64","content":""}'));

        $repoConfig = array(
            'url'        => $repoUrl,
            'asset-type' => $type,
            'filename'   => $filename,
        );

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();

        $gitHubDriver->getComposerInformation($identifier);
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testGetComposerInformationWithTransportException($type, $filename)
    {
        $this->setExpectedException('RuntimeException');

        $repoUrl = 'http://github.com/francoispluchino/composer-asset-plugin';
        $repoApiUrl = 'https://api.github.com/repos/francoispluchino/composer-asset-plugin';
        $identifier = 'v0.0.0';

        $io = $this->getMock('Composer\IO\IOInterface');

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->returnValue('{"master_branch": "test_master"}'));

        $remoteFilesystem->expects($this->at(1))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/repos/francoispluchino/composer-asset-plugin/contents/'.$filename.'?ref='.$identifier), $this->equalTo(false))
            ->will($this->throwException(new TransportException('Mock exception code 404', 404)));

        $remoteFilesystem->expects($this->at(2))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/repos/francoispluchino/composer-asset-plugin/contents/'.$filename.'?ref='.$identifier), $this->equalTo(false))
            ->will($this->throwException(new TransportException('Mock exception code 400', 400)));

        $repoConfig = array(
            'url'        => $repoUrl,
            'asset-type' => $type,
            'filename'   => $filename,
        );

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();

        $gitHubDriver->getComposerInformation($identifier);
    }

    protected function setAttribute($object, $attribute, $value)
    {
        $attr = new \ReflectionProperty($object, $attribute);
        $attr->setAccessible(true);
        $attr->setValue($object, $value);
    }
}
