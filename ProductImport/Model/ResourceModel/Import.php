<?php
namespace BmsIndia\ProductImport\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
  
class Import extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('bms_product_import', 'id');
    }
}