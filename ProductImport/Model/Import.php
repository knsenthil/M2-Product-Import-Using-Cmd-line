<?php
namespace BmsIndia\ProductImport\Model;

use Magento\Framework\Model\AbstractModel;

class Import extends AbstractModel
{
	/**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('BmsIndia\ProductImport\Model\ResourceModel\Import');
    }
}