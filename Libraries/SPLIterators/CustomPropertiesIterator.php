<?php


namespace Rdb\Modules\RdbCMSA\Libraries\SPLIterators;


/**
 * Add custom properties to iterator object that might be lost during called to some classes.
 * 
 * @since 0.0.7
 */
class CustomPropertiesIterator extends \IteratorIterator
{


    /**
     * {@inheritDoc}
     */
    public function current()
    {
        $item = $this->getInnerIterator()->current();

        // add depth property.
        $item->depth = (int) (method_exists($this, 'getDepth') ? $this->getDepth() : -1);
        // add sub path property. (file path is: /a/b/c.txt, sub path is: a/b).
        $this->subPath = (method_exists($this, 'getSubPath') ? $this->getSubPath() : '');
        // add sub path name property.
        $this->subPathName = (method_exists($this, 'getSubPathName') ? $this->getSubPathName() : '');
        // has children property.
        $this->hasChildren = (method_exists($this, 'hasChildren') ? $this->hasChildren() : false);
        // children property.
        $this->children = ($this->hasChildren && method_exists($this, 'getChildren') ? $this->getChildren() : false);

        return parent::current();
    }// current


}
