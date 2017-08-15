<?php

namespace Modules\ProvVoipEnvia\Console;

use Log;
use Illuminate\Console\Command;
use \Modules\ProvBase\Entities\Contract;
use \Modules\ProvVoipEnvia\Http\Controllers\ProvVoipEnviaController;

/**
 * Class to get Envia contracts by customer
 */
class EnviaContractGetterCommand extends Command {

	// get some methods used by several updaters
	use \App\Console\Commands\DatabaseUpdaterTrait;

	/**
	 * The console command name.
	 */
	protected $name = 'provvoipenvia:get_envia_contracts_by_customer';

	/**
	 * The console command description.
	 */
	protected $description = 'Get Envia contracts by customer';

	/**
	 * The signature (defining the optional argument)
	 */
	protected $signature = 'provvoipenvia:get_envia_contracts_by_customer';

	/**
	 * Array holding the contracts (ours, not Envia contracts) to get Envia contracts for
	 */
	protected $contracts_to_get_envia_contracts_for = array();

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct() {

		// this comes from config/app.php (key 'url')
		$this->base_url = \Config::get('app.url');

		parent::__construct();
	}

	/** 
	 * Execute the console command
	 */
	public function fire() {

		Log::info($this->description);

		echo "\n";
		$this->_get_contracts();

		echo "\n";
		$this->_get_envia_contracts();
	}


	/**
	 * Collect all of our contracts we want to get Envia contracts for
	 *
	 * @author Patrick Reichel
	 */
	protected function _get_contracts() {

		Log::debug(__METHOD__." started");

		$contracts = Contract::all();

		foreach ($contracts as $contract) {

			$phonenumbers = $contract->related_phonenumbers();

			// TODO: maybe we can reduce the data by some conditions – ATM we use all contracts with phonenumbers attached
			if (boolval($phonenumbers)) {
				array_push($this->contracts_to_get_envia_contracts_for, $contract->id);
			}

		}

	}


	/**
	 * Get all the Envia contracts for our contracts
	 *
	 * @author Patrick Reichel
	 */
	protected function _get_envia_contracts() {

		Log::debug(__METHOD__." started");

		foreach ($this->contracts_to_get_envia_contracts_for as $contract_id) {

			try {
				// get the relative URL to execute the cron job for updating the current order_id
				$url_suffix = \URL::route("ProvVoipEnvia.cron", array('job' => 'customer_get_contracts', 'contract_id' => $contract_id, 'really' => 'True'), false);

				$url = $this->base_url.$url_suffix;

				$this->_perform_curl_request($url);
			}
			catch (Exception $ex) {
				Log::error("Exception getting Envia contract for contract ".$contract_id."): ".$ex->getMessage()." => ".$ex->getTraceAsString());
			}
		}

	}

}
