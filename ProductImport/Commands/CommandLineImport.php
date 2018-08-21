<?php
namespace BmsIndia\ProductImport\Commands;
 
use Symfony\Component\Console\Command\Command;
use Magento\Framework\App\State;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\ImportExport\Model\ImportFactory;
use Magento\ImportExport\Model\Import\Source\CsvFactory;
use Magento\Framework\Filesystem\Directory\ReadFactory;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
 
/**
 * Command to import products.
 */
class CommandLineImport extends Command
{
    /**
     * @var State $state
     */
    private $state;
 
    /**
     * @var Import $importFactory
     */
    protected $importFactory;
 
    /**
     * @var CsvFactory
     */
    private $csvSourceFactory;
 
    /**
     * @var ReadFactory
     */
    private $readFactory;
	
	
 
    /**
     * Constructor
     *
     * @param State $state  A Magento app State instance
     * @param ImportFactory $importFactory Factory to create entiry importer
     * @param CsvFactory $csvSourceFactory Factory to read CSV files
     * @param ReadFactory $readFactory Factory to read files from filesystem
     *
     * @return void
     */
    public function __construct(
      State $state,
      ImportFactory $importFactory,
      CsvFactory $csvSourceFactory,
      ReadFactory $readFactory
	  
    ) {
        $this->state = $state;
        $this->importFactory = $importFactory;
        $this->csvSourceFactory = $csvSourceFactory;
        $this->readFactory = $readFactory;
		parent::__construct();
		
    }
 
    /**
     * Configures arguments and display options for this command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('productimport:product_import');
        $this->setDescription('Imports products into Magento from a CSV');
        $this->addArgument('import_path', InputArgument::REQUIRED, 'bms/productimport/catalog_product_new.csv');
        parent::configure();
    }
 
    /**
     * Executes the command to add products to the database.
     *
     * @param InputInterface  $input  An input instance
     * @param OutputInterface $output An output instance
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
	
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/import.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		
        // We cannot use core functions (like saving a product) unless the area
        // code is explicitly set.
        try {
            $this->state->setAreaCode('adminhtml');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // Intentionally left empty.
        }
 
        $import_path = $input->getArgument('import_path');
		$output->writeln("<info>Loading SKU information from $import_path</info>");
        $import_file = pathinfo($import_path);
		
		if(strcmp('csv',$import_file['extension'])) {
			$output->writeln("<info>Invalid File format</info>");
			return false;
		}
		//$directory = $this->_moduleReader->getModuleDir('', 'BmsIndia_ProductImport'); 
		// This is your CSV file.
		$file = $import_path;
		$logger->info($file);
		
		$lines = explode( "\n", file_get_contents( $file ));
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
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		foreach($data as $_data) {
			//if($_data['product_type']=='simple') {
				$product = $objectManager->create('\Magento\Catalog\Model\Product');
				$product->setSku($_data['sku']);
				$product->setName($_data['name']);
				$product->setPrice($_data['price']);
				$product->setTypeId($_data['product_type']);
				$product->setWebsiteIds([1]);
				$product->setStatus(Status::STATUS_ENABLED);
				$product->setAttributeSetId($product->getDefaultAttributeSetId());
				try {
					$product->save();
					$output->writeln('Created product = <comment>' . $_data['name'] . '</comment>');
				} catch (Exception $e) {
					$output->writeln('<error>Unable to create product = ' . $_data['name'] . '</error>');
				}
			//}
		}
		$log = $objectManager->create('\BmsIndia\ProductImport\Model\Import');
		$output->writeln('<info>Products have been imported!</info>');
    }
}