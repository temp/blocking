<?php
/**
 * This file is part of the brainbits block package.
 *
 * @copyright 2012-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   http://www.brainbits.net/LICENCE     Dummy Licence
 */

namespace Brainbits\Blocking\Adapter;

use PHPUnit_Framework_TestCase as TestCase;
use org\bovigo\vfs\vfsStream;
use Brainbits\Blocking\Block;
use Brainbits\Blocking\Identifier\Identifier;

/**
 * Filesystem adapter test
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class FilesystemAdapterTest extends TestCase
{
    /**
     * @var FileAdapter
     */
    private $adapter;

    /**
     * @var string
     */
    private $root;

    public function setUp()
    {
        vfsStream::setup('blockDir');

        $this->root = vfsStream::url('blockDir');
        $this->adapter = new FilesystemAdapter($this->root);
        $this->ownerMock = $this->getMockBuilder('Brainbits\Blocking\Owner\OwnerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->ownerMock
            ->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue('dummyOwner'));
    }

    public function tearDown()
    {
        $this->ownerMock = null;
        $this->adapter = null;
        $this->root = null;
    }

    public function testWriteSucceedesOnNewFile()
    {
        $identifier = new Identifier('test', 'lock');
        $block = new Block($identifier, $this->ownerMock, new \DateTime());

        $result = $this->adapter->write($block);

        $this->assertTrue($result);
        $this->assertTrue(file_exists(vfsStream::url('blockDir/' . $identifier)));
    }

    public function testTouchSucceedesOnExistingFile()
    {
        $identifier = new Identifier('test', 'lock');
        $block = new Block($identifier, $this->ownerMock, new \DateTime());

        $this->adapter->write($block);
        $result = $this->adapter->touch($block);

        $this->assertTrue($result);
        $this->assertTrue(file_exists(vfsStream::url('blockDir/' . $identifier)));
    }

    public function testRemoveReturnsFalseOnNonexistingFile()
    {
        $identifier = new Identifier('test', 'unlock');
        $block = new Block($identifier, $this->ownerMock, new \DateTime());

        $result = $this->adapter->remove($block);

        $this->assertFalse($result);
        $this->assertFalse(file_exists(vfsStream::url('blockDir/' . $identifier)));
    }

    public function testUnblockReturnsTrueOnExistingFile()
    {
        $identifier = new Identifier('test', 'unlock');
        $block = new Block($identifier, $this->ownerMock, new \DateTime());

        $this->adapter->write($block);
        $result = $this->adapter->remove($block);

        $this->assertTrue($result);
        $this->assertFalse(file_exists(vfsStream::url('blockDir/' . $identifier)));
    }

    public function testExistsReturnsFalseOnNonexistingFile()
    {
        $identifier = new Identifier('test', 'isblocked');

        $result = $this->adapter->exists($identifier);

        $this->assertFalse($result);
    }

    public function testIsBlockedReturnsTrueOnExistingBlock()
    {
        $identifier = new Identifier('test', 'isblocked');
        $block = new Block($identifier, $this->ownerMock, new \DateTime());

        $this->adapter->write($block);
        $result = $this->adapter->exists($identifier);

        $this->assertTrue($result);
    }

    public function testGetReturnsNullOnNonexistingFile()
    {
        $identifier = new Identifier('test', 'isblocked');

        $result = $this->adapter->get($identifier);

        $this->assertNull($result);
    }

    public function testGetReturnsBlockOnExistingFile()
    {
        $identifier = new Identifier('test', 'isblocked');
        $block = new Block($identifier, $this->ownerMock, new \DateTime());

        $this->adapter->write($block);
        $result = $this->adapter->get($identifier);

        $this->assertNotNull($result);
        $this->assertInstanceOf('Brainbits\Blocking\Block', $result);
    }
}