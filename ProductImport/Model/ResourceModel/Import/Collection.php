<?php
  
namespace BmsIndia\ProductImport\Model\ResourceModel\Import;
  
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
  
class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'BmsIndia\ProductImport\Model\Import',
            'BmsIndia\ProductImport\Model\ResourceModel\Import'
        );
    }
}