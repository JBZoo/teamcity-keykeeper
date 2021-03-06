<?php
/**
 * JBZoo TeamcityKeyKeeper
 *
 * This file is part of the JBZoo CCK package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    TeamcityKeyKeeper
 * @license    MIT
 * @copyright  Copyright (C) JBZoo.com, All rights reserved.
 * @link       https://github.com/JBZoo/TeamcityKeyKeeper
 */

namespace JBZoo\PHPUnit;

use JBZoo\Utils\Cli;
use JBZoo\Utils\Str;
use JBZoo\Utils\Sys;

/**
 * Class PackageTest
 * @package JBZoo\PHPUnit
 */
class KeyKeeperTest extends PHPUnit
{
    /**
     * @var string
     */
    protected $bin = '';

    protected $testGroup = 'phpunit';

    public static function setUpBeforeClass()
    {
        $storageFile = Sys::getHome() . '/.teamcity-keykeeper/phpuni_log.log';
        if (file_exists($storageFile)) {
            unlink($storageFile);
        }
    }

    public function setUp()
    {
        $storageFile = Sys::getHome() . '/.teamcity-keykeeper/phpunit.json';
        if (file_exists($storageFile)) {
            unlink($storageFile);
        }

        $this->bin = 'php ' . realpath(__DIR__ . '/../bin/teamcity-keykeeper');
    }

    public function testSaveAndRestoreKey()
    {
        $value = Str::random(10000);
        $name = Str::random();

        $saveResult = trim(Cli::exec("{$this->bin} key:save " .
            "--name='{$name}' --value='{$value}' --group='{$this->testGroup}'"
        ));
        $name = strtoupper($name);
        isContain("Key '{$name}' saved", $saveResult);

        $restoreResult = trim(Cli::exec("{$this->bin} key:restore --name='{$name}' --group='{$this->testGroup}'"));
        isSame("##teamcity[setParameter name='env.{$name}' value='{$value}']", $restoreResult);
    }

    public function testSpecialChars()
    {
        $value = 'qwerty1234567890-!@#$%^&*()_+.<>,;{}№';
        $name = 'qwerty1234567890';

        $saveResult = trim(Cli::exec("{$this->bin} key:save", [
            'group' => $this->testGroup,
            'name'  => $name,
            'value' => $value,
        ]));

        $name = strtoupper($name);
        isContain("Key '{$name}' saved", $saveResult);

        $restoreResult = trim(Cli::exec("{$this->bin} key:restore", ['name' => $name, 'group' => $this->testGroup]));
        isSame("##teamcity[setParameter name='env.{$name}' value='{$value}']", $restoreResult);
    }

    public function testGetAllKeys()
    {
        Cli::exec("{$this->bin} key:save --name='key1' --value='value1' --group='{$this->testGroup}'");
        Cli::exec("{$this->bin} key:save --name='key2' --value='value2' --group='{$this->testGroup}'");

        $restoreResult = trim(Cli::exec("{$this->bin} key:restore --all --group='{$this->testGroup}'"));
        isContain("##teamcity[setParameter name='env.KEY1' value='value1']", $restoreResult);
        isContain("##teamcity[setParameter name='env.KEY2' value='value2']", $restoreResult);
    }

    public function testRemoveKeysWithOption()
    {
        Cli::exec("{$this->bin} key:save --name='key' --value='value1' --group='{$this->testGroup}'");
        Cli::exec("{$this->bin} key:save --name='key' --value='' --group='{$this->testGroup}'");

        $restoreResult = trim(Cli::exec("{$this->bin} key:restore --all --group='{$this->testGroup}'"));
        isContain("##teamcity[setParameter name='env.KEY' value='']", $restoreResult);
    }

    public function testRemoveKeysWithoutOption()
    {
        Cli::exec("{$this->bin} key:save --name='key' --value='value1' --group='{$this->testGroup}'");
        Cli::exec("{$this->bin} key:save --name='key' --group='{$this->testGroup}'");

        $restoreResult = trim(Cli::exec("{$this->bin} key:restore --all --group='{$this->testGroup}'"));
        isContain("##teamcity[setParameter name='env.KEY' value='']", $restoreResult);
    }

    public function testGetCleanValue()
    {
        $key = Str::random();
        $value = 'qwerty1234567890-!@#$%^&*()_+.<>,;{}№\'"';

        Cli::exec("{$this->bin} key:save", ['name' => $key, 'value' => $value, 'group' => $this->testGroup]);

        isSame($value, Cli::exec("{$this->bin} key:get --name='{$key}' --group='{$this->testGroup}'"));
    }
}
