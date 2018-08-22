<?php
namespace BmsIndia\ProductImport\Commands;
 
use Symfony\Component\Console\Command\Command;
use Magento\Framework\App\State;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
 
/**
 * Command to import products.
 */
class CommandLineImport extends Command
{

	public $parsed_records = 0;
	public $created_records = 0;
	public $updated_records = 0; 
	public $process = 'created'; // default value
	public $last_inserted_value = 0;
    
    private $state;
	
	private $_objectManager;
 
    
    public function __construct(
      State $state,
	  \Magento\Framework\ObjectManagerInterface $objectmanager
    ) {
        $this->state = $state;
		$this->_objectManager = $objectmanager;
		parent::__construct();
    }
 
    // command and path configuration
    protected function configure()
    {
        $this->setName('bmsindia:product_import');
        $this->setDescription('Imports products into Magento from a CSV');
        $this->addArgument('import_path', InputArgument::REQUIRED, 'bms/productimport/catalog_product_new.csv');
        parent::configure();
    }
 
    
	//Executes the command to add products to the database.
    protected function execute(InputInterface $input, OutputInterface $output)
    {
	   try {
            $this->state->setAreaCode('adminhtml');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // Intentionally left empty.
        }
 
        $import_path = $input->getArgument('import_path');
		$output->writeln("<info>Loading SKU information from $import_path</info>");
        $import_file = pathinfo($import_path);
		
		if(strcmp('csv',$import_file['extension'])) { // check the file type
			$output->writeln("<info>Invalid File format</info>");
			return false;
		}
		// read the csv file into array
		$lines = explode( "\n", file_get_contents( $import_path ));
		$headers = str_getcsv( array_shift( $lines ) );
		$data = array();
		foreach ( $lines as $line ) {
			$row = array();
			foreach ( str_getcsv( $line ) as $key => $field )
				$row[ $headers[ $key ] ] = $field;
			$row = array_filter( $row );
			$data[] = $row;
		}
		
		$output->writeln('<info>Importing Products...</info>');
		$log = $this->_objectManager->create('\BmsIndia\ProductImport\Model\Import');
		foreach($data as $_data) {
			if(isset($_data['name'])&&isset($_data['price'])&&isset($_data['price'])) { // right now only implmented simple product import

				++$this->parsed_records;
				$productRepository = $this->_objectManager->get('Magento\Catalog\Model\Product');
				// product update
				if($productRepository->getIdBySku($_data['sku'])) { 
					++$this->updated_records;
					$product = $this->_objectManager->get('\Magento\Catalog\Model\Product')->load($productRepository->getIdBySku($_data['sku']));
					$product->setName($_data['name']);
					$this->process = 'updated';
				} 
				// create product
				else {
					$product = $this->_objectManager->create('\Magento\Catalog\Model\Product');
					$product->setSku($_data['sku']);
					$product->setName($_data['name']);
					$product->setPrice($_data['price']);
					$product->setTypeId($_data['type']);
					$product->setWebsiteIds([1]);
					$product->setStatus(Status::STATUS_ENABLED);
					$product->setAttributeSetId($product->getDefaultAttributeSetId());
					++$this->created_records;
					$this->process = 'created';
					
				}
				try {
					$product->save();
					//log the csv progress
					$total_count = $this->created_records+$this->updated_records;
					if(!$this->last_inserted_value) {
						$log_data = array('parsed_records'=>$this->parsed_records,'created_records'=>$this->created_records,'updated_records'=>$this->updated_records,'status'=>$total_count);
						$log->setData($log_data)->save();
					} else {
						$log->load($this->last_inserted_value);
						$log->setParsedRecords($this->parsed_records);
						$log->setUpdatedRecords($this->updated_records);
						$log->setCreatedRecords($this->created_records);
						$log->setStatus($total_count);
						$log->save();
					}
					$this->last_inserted_value = $log->getId();
					$output->writeln('<comment>' . $_data['name'] . '</comment>  product  '.$this->process);
				} catch (Exception $e) {
					$output->writeln('<error>Unable to create product = ' . $_data['name'] . '</error>');
				}
			}
		}
		$output->writeln('<info>Products have been imported!</info>');
    }
}