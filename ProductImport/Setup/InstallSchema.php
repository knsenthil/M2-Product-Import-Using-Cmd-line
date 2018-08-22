<?php

namespace BmsIndia\ProductImport\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        // Get BmsIndia_ProductImport table
        $tableName = $installer->getTable('bms_product_import');
        // Check if the table already exists
        if ($installer->getConnection()->isTableExists($tableName) != true) {
            // Create bms_product_import table
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'ID'
                )
				->addColumn(
                    'start_time',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false,'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                    'Start At'
                )
				->addColumn(
                    'end_time',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false,'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
                    'End At'
                )
				->addColumn(
                    'parsed_records',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false],
                    'Total Parsed Records'
                )
				->addColumn(
                    'created_records',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false],
                    'Total Created Records'
                )
				->addColumn(
                    'updated_records',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false],
                    'Total Updated Records'
                )
				 ->addColumn(
                    'status',
                     Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false, 'default' => '0'],
					'Total Inserted Records;'
                )
                ->setComment('Product Import Table')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');
            $installer->getConnection()->createTable($table);
        }
        $installer->endSetup();
    }
}
