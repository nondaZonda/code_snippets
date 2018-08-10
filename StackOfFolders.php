<?php
namespace App\Utils;

use Cake\Filesystem\Folder;

class StackOfFolders
{
    /**
     * @var array - tablica obiektów: Folder
     */
    private $stack = [];
    
    public function getItems()
    {
        return $this->stack;
    }
    
    public function pushItem(Folder $item)
    {
        if ($this->hasItemInStack($item)) {
            return true;
        }
        
        array_push($this->stack, $item);
    }
    
    public function popItem()
    {
        return array_pop($this->stack);
    }
    
    /**
     * @param array $existingItems - tablica obiektów klasy: Folder
     * @param array $uniqueItems - tablica obiektów klasy: Folder
     * @return bool
     */
    public function pushDifferentItemsOnly(array $existingItems, array $uniqueItems)
    {
        if (empty($uniqueItems)) {
            return true;
        }
        
        /** @var Folder $item */
        foreach ($uniqueItems as $item) {
            if ($this->isFolderInGroup($existingItems, $item)) {
                continue;
            }
            $this->pushItem($item);
        }
        
        return true;
    }
    
    private function hasItemInStack(Folder $item)
    {
        if (empty($this->stack)) {
            return false;
        }
        
        return $this->isFolderInGroup($this->stack, $item);
    }
    
    private function isFolderInGroup(array $foldersGroup, Folder $uniqueFolder)
    {
        if (empty($foldersGroup)) {
            return false;
        }
        
        /** @var Folder $folder */
        foreach ($foldersGroup as $folder) {
            if ($folder->path == $uniqueFolder->path) {
                return true;
            }
        }
    
        return false;
    }
    
}