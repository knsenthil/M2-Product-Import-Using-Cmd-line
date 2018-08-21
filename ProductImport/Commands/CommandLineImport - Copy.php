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
        $import_file = pathinfo($import_path);
 
        $import = $this->importFactory->create();
        $import->setData(
            array(
                'entity' => 'catalog_product',
                'behavior' => 'append',
                'validation_strategy' => 'validation-stop-on-errors',
            )
        );
 
        $read_file = $this->readFactory->create($import_file['dirname']);
        $csvSource = $this->csvSourceFactory->create(
            array(
                'file' => $import_file['basename'],
                'directory' => $read_file,
            )
        );
		$logger->info(print_r($csvSource,1));
 
        $validate = $import->validateSource($csvSource);
		$logger->info($validate.'--- validate');
        if (!$validate) {
          $output->writeln('<error>Unable to validate the CSV.</error>');
        }
 
        $result = $import->importSource();
		$logger->info(print_r($result,1));
		$logger->info(print_r($import->getProcessedRowsCount(),1));
		$logger->info(print_r($import->getProcessedEntitiesCount(),1));
		
		
		
		
		/*$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/import.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info(print_r($result,1));*/

		
		
        if ($result) {
          $import->invalidateIndex();
        }
 
        $output->writeln("<info>Finished importing products from $import_path</info>");
    }
}