<?php
namespace App\Test\TestCase\Utils;

use App\Utils\StackOfFolders;
use Cake\Core\Exception\Exception;
use Cake\Filesystem\Folder;
use Cake\TestSuite\TestCase;

class StackOfFoldersTest extends TestCase
{
    const TEST_PATH = TESTS . 'TestFiles' . DS. 'webroot' . DS . 'media' . DS;
    
    /**
     * Test subject
     *
     * @var StackOfFolders
     */
    private $StackOfFolders;
    
    public function setUp()
    {
        parent::setUp();
        $this->StackOfFolders = new StackOfFolders();
    }
    
    public function tearDown()
    {
        unset($this->StackOfFolders);
        parent::tearDown();
    }
    
    public function testGetItemsReturnsArrayOfFolderObjects()
    {
        $actualItems = $this->StackOfFolders->getItems();
        
        $this->assertInternalType('array', $actualItems);
        
        $testFolder = new Folder(self::TEST_PATH);
        $this->StackOfFolders->pushItem($testFolder);
    
        $actualItems = $this->StackOfFolders->getItems();
        foreach ($actualItems as $item) {
            $this->assertInstanceOf(Folder::class, $item);
        }
    }
    
    public function testPushItemAddItemToStack()
    {
        $actualItems = $this->StackOfFolders->getItems();
    
        $this->assertEmpty($actualItems);
    
        $testFolder = new Folder(self::TEST_PATH);
        $this->StackOfFolders->pushItem($testFolder);
        $actualItems = $this->StackOfFolders->getItems();
        $actualItem = reset($actualItems);
        
        $this->assertEquals($testFolder->path, $actualItem->path);
    }
    
    public function testPushItemDoesNotAddItemWhenTheSameObjectAlreadyInStack()
    {
        $testFolder = new Folder(self::TEST_PATH);
        $this->StackOfFolders->pushItem($testFolder);
        $expectedItemsCount = count($this->StackOfFolders->getItems());
        
        $this->StackOfFolders->pushItem($testFolder);
        $actualItemsCount = count($this->StackOfFolders->getItems());
        
        $this->assertEquals($expectedItemsCount, $actualItemsCount);
    }
    
    public function testPopItemReturnsFolderObject()
    {
        $testFolder = new Folder(self::TEST_PATH);
        $this->StackOfFolders->pushItem($testFolder);
        
        $actualItem = $this->StackOfFolders->popItem();
        
        $this->assertInstanceOf(Folder::class, $actualItem);
        $this->assertEquals($testFolder->path, $actualItem->path);
    }
    
    public function testPopItemReturnsObjectsInCorrectOrderLIFO()
    {
        $firstFolder = new Folder(self::TEST_PATH);
        $secondFolder = new Folder(self::TEST_PATH . 'second_folder', true, 0755);
        $this->StackOfFolders->pushItem($firstFolder);
        $this->StackOfFolders->pushItem($secondFolder);
        
        $actualItem = $this->StackOfFolders->popItem();
        $this->assertEquals($secondFolder->path, $actualItem->path);
    
        $actualItem = $this->StackOfFolders->popItem();
        $this->assertEquals($firstFolder->path, $actualItem->path);
    
        $secondFolder->delete();
    }
    
    public function testPopItemRemovesItemFromStack()
    {
        $actualItems = $this->StackOfFolders->getItems();
        
        $this->assertEmpty($actualItems);
        
        $testFolder = new Folder(self::TEST_PATH);
        $this->StackOfFolders->pushItem($testFolder);
        $actualItem = $this->StackOfFolders->popItem();
        
        $this->assertEquals($testFolder->path, $actualItem->path);
    
        $actualItems = $this->StackOfFolders->getItems();
    
        $this->assertEmpty($actualItems);
    }
    
    public function testPushDifferentItemsOnlyAddsItemsToStackButWithoutDuplicates()
    {
        $actualItems = $this->StackOfFolders->getItems();
    
        $this->assertEmpty($actualItems);
        
        $firstItem = new Folder(self::TEST_PATH);
        $secondItem = new Folder(self::TEST_PATH . 'second_folder' , true, 0755);
        
        $this->StackOfFolders->pushItem($firstItem);
        $existingItems = [$firstItem];
        $items = [$firstItem, $secondItem];
        $this->StackOfFolders->pushDifferentItemsOnly($existingItems, $items);
        
        $expectedItemsCount = 2;
        $actualItemsCount = count($this->StackOfFolders->getItems());
        
        $this->assertEquals($expectedItemsCount, $actualItemsCount);
        
        $secondItem->delete();
    }
    
}
