<?php

namespace App;

use DB;
use Str;
use Schema;
use Module;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model as Eloquent;
use App\Http\Controllers\NamespaceController;


/**
 *	Class to add functionality – use instead of Eloquent for your models
 */
class BaseModel extends Eloquent
{
	use SoftDeletes;

	// use to enable force delete for inherit models
	protected $force_delete = 0;

	public $voip_enabled;
	public $billing_enabled;

	protected $fillable = array();


	public $observer_enabled = true;

	/**
	 * Constructor.
	 * Used to set some helper variables.
	 *
	 * @author Patrick Reichel
	 *
	 * @param $attributes pass through to Eloquent contstructor.
	 */
	public function __construct($attributes = array()) {

		// call Eloquent constructor
		// $attributes are needed! (or e.g. seeding and creating will not work)
		parent::__construct($attributes);

		// set helper variables
		$this->voip_enabled = $this->voip_enabled();
		$this->billing_enabled = $this->billing_enabled();

	}


	// Add Comment here. ..
	protected $guarded = ['id'];


	/**
	 * Init Observer
	 */
	public static function boot()
	{
		parent::boot();

		$model_name = static::class;

		// App\Auth is booted during authentication and doesnt need/have an observe method
		// GuiLog has to be excluded to prevent an infinite loop log entry creation
		if ($model_name == 'App\Auth' || $model_name == 'App\GuiLog')
			return;

		// we simply add BaseObserver to each model
		// the real database writing part is in singleton that prevents duplicat log entries
		$model_name::observe(new BaseObserver);

	}



	/**
	 * This function is a placeholder to write Model specific adaptions to
	 * order and/or restructure the Model objects for Index View
	 *
	 * Note: for a example see Configfile Model
	 *
	 * @author Torsten Schmidt
	 *
	 * @return all objects of this model
	 */
	public function index_list ()
	{
		return $this->orderBy('id')->get();
		return $this->all();
	}


	/**
	 * Basefunction for generic use - is needed to place the related html links generically in the edit & create views
	 * Place this function in the appropriate model and return the relation to the model it belongs
	 *
	 * NOTE: this function will return null in all create contexts, because at this time no relation exists!
	 */
	public function view_belongs_to ()
	{
		return null;
	}


	/**
	 * Basefunction for returning all objects that a model can have a relation to
	 * Place this function in the model where the edit/create view shall show all related objects
	 *
	 * @author Nino Ryschawy
	 *
	 * @return an array with the appropriate hasMany()-functions of the model
	 */
	public function view_has_many ()
	{
		return array();
	}


	/**
	 * Basefunction for returning all objects that a model can have a one-to-one relation to
	 * Place this function in the model where the edit/create view shall show all related objects
	 *
	 * @author Patrick Reichel
	 *
	 * @return an array with the appropriate hasOne()-functions of the model
	 */
	public function view_has_one ()
	{
		return array();
	}


	/**
	 * Check if VoIP is enabled.
	 *
	 * TODO: - move to Contract/ContractController or use directly,
	 *         ore use fucntion directly instead of helpers variable
	 *
	 * @author Patrick Reichel
	 *
	 * @return true if one of the VoIP modules is enabled (currently only ProvVoipEnvia), else false
	 */
	public function voip_enabled() {

		$voip_modules = array(
			'ProvVoipEnvia',
		);

		foreach ($voip_modules as $module) {
			if (\PPModule::is_active($module)) {
				return True;
			}
		}

		return False;
	}


	/**
	 * Check if billing is enabled.
	 *
	 * TODO: - currently this is a dummy (= we don't have a billing module yet!!)
	 *       - move to Contract/ContractController or use directly,
	 *         ore use fucntion directly instead of helpers variable
	 *
	 * @author Patrick Reichel
	 *
	 * @return true if one of the billing modules is enabled, else false
	 */
	public function billing_enabled() {

		$billing_modules = array(
			'BillingBase',
		);

		foreach ($billing_modules as $module) {
			if (\PPModule::is_active($module)) {
				return True;
			}
		}

		return False;
	}


	/**
	 *	This returns an array with all possible enum values.
	 *	Use this instead of hardcoding it e.g. in your view (where it has to be
	 *		changed with changing/extending enum definition in database)
	 *	You can also get an array with a first empty option – use this in create forms to
	 *		show that this value is still not set
	 *	call this method via YourModel::getPossibleEnumValues('yourEnumCol')
	 *
	 *	This method is following an idea found on:
	 *		http://stackoverflow.com/questions/26991502/get-enum-options-in-laravels-eloquent
	 *
	 *	@author Patrick Reichel
	 *
	 *	@param name column name of your database defined as enum
	 *	@param with_empty_option should an empty option be added?
	 *
	 *	@return array with available enum options
	 */
	public static function getPossibleEnumValues($name, $with_empty_option=false)
	{
		// create an instance of the model to be able to get the table name
		$instance = new static;

		// get metadata for the given column and extract enum options
		$type = DB::select( DB::raw('SHOW COLUMNS FROM '.$instance->getTable().' WHERE Field = "'.$name.'"') )[0]->Type;

		// create array with enum values (all values in brackets after “enum”)
		preg_match('/^enum\((.*)\)$/', $type, $matches);

		$enum_values = array();

		// add an empty option if wanted
		if ($with_empty_option) {
			$enum_values[0] = '';
		}

		// add options extracted from database
		foreach(explode(',', $matches[1]) as $value){
			$v = trim( $value, "'" );
			$enum_values[$v] = $v;
		}

		return $enum_values;
	}

	/**
	 * Get the names of all fulltext indexed database columns.
	 * They have to be passed as a param to a MATCH-AGAINST query
	 *
	 * @param $table database to get index columns from
	 * @return comma separated string of columns
	 * @author Patrick Reichel
	 */
	protected function _getFulltextIndexColumns($table) {

		$cols = array();
		$indexes = DB::select(DB::raw('SHOW INDEX FROM '.$table));
		foreach ($indexes as $index) {
			if (($index->Key_name == $table.'_fulltext_all') && $index->Index_type == 'FULLTEXT') {
				array_push($cols, $index->Column_name);
			}
		}

		$cols = implode(',', $cols);
		return $cols;
	}


	/**
	 * Get all models
	 *
	 * @return array of all models except base models
	 * @author Patrick Reichel,
	 *         Torsten Schmidt: add modules path
	 */
	public static function get_models() {

		// models to be excluded from search
		$exclude = array(
			'BaseModel',
			'Authmeta',
			'Authcore',
			'TRCClass',	# static data; not for standalone use
			'CarrierCode', # cron updated data; not for standalone use
			'EkpCode', # cron updated data; not for standalone use
			'BookingRecords', 'Invoice', 'Sepaxml'
		);
		$result = array();

		/*
		 * Search all Models in /models Models Path
		 */
		$dir = app_path('Models');
		$models = glob($dir."/*.php");

		foreach ($models as $model) {
			$model = str_replace(app_path('Models')."/", "", $model);
			$model = str_replace(".php", "", $model);
			if (array_search($model, $exclude) === FALSE) {
				array_push($result, 'App\\'.$model);
			}
		}

		/*
		 * Search all Models in /Modules/../Entities Path
		 */
		$path = base_path('modules');
		$dirs = array();
		$modules = Module::enabled();
		foreach ($modules as $module)
			array_push($dirs, $module->getPath().'/Entities');

		foreach ($dirs as $dir)
		{
			$models = glob($dir."/*.php");

			foreach ($models as $model) {
				preg_match ("|$path/(.*?)/Entities/|", $model, $module_array);
				$module = $module_array[1];
				$model = preg_replace("|$path/(.*?)/Entities/|", "", $model);
				$model = str_replace(".php", "", $model);
				if (array_search($model, $exclude) === FALSE) {
					array_push($result, "Modules\\$module\Entities\\".$model);
				}
			}
		}

		return $result;
	}


	protected function _guess_model_name ($s)
	{
		return current(preg_grep ('|.*?'.$s.'$|i', $this->get_models()));
	}


	/**
	 * Preselect a sql field while searching
	 *
	 * Note: If $field is 'net' or 'cluster' we perform a net and cluster specific search
	 * This requires the searched model to have a tree_id coloumn
	 *
	 * @param $field sql field for pre selection
	 * @param $field sql search value for pre selection
	 * @return sql search statement, could be included in a normal while()
	 * @author Torsten Schmidt
	 */
	private function __preselect_search($field, $value, $model)
	{
		$ret = '1';

		if ($field && $value)
		{
			$ret = $field.'='.$value;

			if(\PPModule::is_active('Hfcbase'))
			{
				if (($model[0] == 'Modules\ProvBase\Entities\Modem') && ($field == 'net' || $field == 'cluster'))
				{
					$ret = 'tree_id IN(-1';
					foreach (Modules\HfcBase\Entities\Tree::where($field, '=', $value)->get() as $tree)
						$ret .= ','.$tree->id;
					$ret .= ')';
				}
			}
		}

		return $ret;
	}


	/**
	 * Performs a fulltext search in simple mode
	 *
	 * @param $array with models to search in
	 * @param $query query to search for
	 * @param $preselect_field sql field for pre selection
	 * @param $preselect_field sql search value for pre selection
	 * @return search result: array of whereRaw() results, this means array of class Illuminate\Database\Quer\Builder objects
	 * @author Patrick Reichel,
	 *         Torsten Schmidt: add preselection, add Model checking
	 */
	protected function _doSimpleSearch($_models, $query, $preselect_field=null, $preselect_value=null)
	{
		$preselect = $this->__preselect_search($preselect_field, $preselect_value, $_models);

		/*
		 * Model Checking: Prepare $models array: skip Models without a valid SQL table
		 */
		$models = [];
		foreach ($_models as $model)
		{
			if (!class_exists($model))
				continue;

			$tmp = new $model;

			if (!property_exists($tmp, 'table'))
				continue;

			if (!Schema::hasTable($tmp->table))
				continue;

			array_push ($models, $model);
		}

		/*
		 * Perform the search
		 */
		$result = [];
		foreach ($models as $model)
		{
			// get the database table used for given model
			$tmp = new $model;
			$table = $tmp->getTable();
			$cols = $model::getTableColumns($table);

			$tmp_result = $model::whereRaw("($preselect) AND CONCAT_WS('|', ".$cols.") LIKE ?", array($query));
			if ($tmp_result)
				array_push($result, $tmp_result);

		}
		return $result;
	}

	/**
	 * Get all database fields
	 *
	 * @param table database table to get structure from
	 * @return comma separated string of columns
	 * @author Patrick Reichel
	 */
	public static function getTableColumns($table) {

		$tmp_res = array();
		$cols = DB::select( DB::raw('SHOW COLUMNS FROM '.$table));
		foreach ($cols as $col) {
			array_push($tmp_res, $table.".".$col->Field);
		}

		$fields = implode(',', $tmp_res);
		return $fields;
	}


	/**
	 * Switch to decide with search algo shall be used
	 * Here we can add other conditions (e.g. to force mode simple on mac search or %truncation)
	 */
	protected function _chooseFulltextSearchAlgo($mode, $query) {

		// search query is left truncated => simple search
		if ((\Str::startsWith($query, "%")) || (\Str::startsWith($query, "*"))) {
			$mode = 'simple';
		}

		// query contains . or : => IP or MAC => simple search
		if ((\Str::contains($query, ":")) || (\Str::contains($query, "."))) {
			$mode = 'simple';
		}

		return $mode;
	}


	/**
	 * Get results for a fulltext search
	 *
	 * @return search result array of whereRaw() results, this means array of Illuminate\Database\Quer\Builder objects
	 *
	 * @author Patrick Reichel
	 */
	public function getFulltextSearchResults($scope, $mode, $query, $preselect_field = null, $preselect_value = null) {

		// some searches cannot be performed against fulltext index
		$mode = $this->_chooseFulltextSearchAlgo($mode, $query);

		if ($mode == 'simple') {

			// replace wildcard chars
			$query = str_replace("*", "%", $query);
			// wrap with wildcards (if not given) => necessary because of the concatenation of all table rows
			if (!\Str::startsWith($query, "%")) {
				$query = "%".$query;
			}
			if (!\Str::endsWith($query, "%")) {
				$query = $query."%";
			}

			if ($scope == 'all') {
				$models = $this->get_models();
				$preselect_field = $preselect_value = null;
			}
			else {
				$models = array(get_class($this));
			}

			$result = $this->_doSimpleSearch($models, $query, $preselect_field, $preselect_value);
		}
		elseif (\Str::startsWith($mode, 'index_')) {

			if ($scope == 'all') {
				echo "Implement searching over all database tables";
			}
			else {
				$indexed_cols = $this->_getFulltextIndexColumns($this->getTable());

				# for a description of search modes check https://mariadb.com/kb/en/mariadb/fulltext-index-overview
				if ("index_natural" == $mode) {
					$mode = "IN NATURAL MODE";
				}
				elseif ("index_boolean" == $mode) {
					$mode = "IN BOOLEAN MODE";
				}
				else {
					$mode = "IN BOOLEAN MODE";
				}

				# search is against the fulltext index
				$result = [$this->whereRaw("MATCH(".$indexed_cols.") AGAINST(? ".$mode.")", array($query))];
			}
		}
		else {
			$result = null;
		}

		/* echo "$query at $scope in mode $mode<br><pre>"; */
		/* dd($result); */
		return $result;

	}

	/**
	 * Generic function to build a list with key of id
	 * @param $array
	 * @return $ret 	list
	 */
	public function html_list ($array, $column)
	{
		$ret = array();

		foreach ($array as $a)
		{
			$ret[$a->id] = $a->{$column};
		}

		return $ret;
	}


	// Placeholder
	public static function view_headline()
	{
		return 'Need to be Set !';
	}

	// Placeholder
	public function view_index_label()
	{
		return 'Need to be Set !';
	}


	/**
	 *	Returns a array of all children objects of $this object
	 *  Note: - Must be called from object context
	 *        - this requires straight forward names of tables an
	 *          forgein key, like modem and modem_id.
	 *
	 *  NOTE: we define exceptions in an array where recursive deletion is disabled
	 *
	 *	@author Torsten Schmidt
	 *
	 *	@return array of all children objects
	 */
	public function get_all_children()
	{
		$relations = [];
		// exceptions
		$exceptions = ['configfile_id', 'salesman_id', 'costcenter_id', 'company_id', 'sepaaccount_id', 'product_id'];

		// Lookup all SQL Tables
		foreach (DB::select('SHOW TABLES') as $table)
		{
			// Lookup SQL Fields for current $table
			foreach (Schema::getColumnListing($table->Tables_in_db_lara) as $column)
			{
				// check if $column is actual table name object added by '_id'
				if ($column == $this->table.'_id')
				{
					if (in_array($column, $exceptions))
						continue;
					// get all objects with $column
					foreach (DB::select('SELECT id FROM '.$table->Tables_in_db_lara.' WHERE '.$column.'='.$this->id) as $child)
					{
						$class_child_name = $this->_guess_model_name ($table->Tables_in_db_lara);
						$class = new $class_child_name;

						array_push($relations, $class->find($child->id));
					}
				}
			}
		}

		return array_filter ($relations);
	}


	/**
	 * Local Helper to differ between soft- and force-deletes
	 * @return type mixed
	 */
	protected function _delete()
	{
		if ($this->force_delete)
			return parent::performDeleteOnModel();

		return parent::delete();
	}


	/**
	 *	Recursive delete of all children objects
	 *
	 *	@author Torsten Schmidt
	 *
	 *	@return void
	 *
	 *  @todo return state on success, should also take care of deleted children
	 */
	public function delete()
	{
		// dd( $this->get_all_children() );
		foreach ($this->get_all_children() as $child)
			$child->delete();

		$this->_delete();
	}


	/**
	 *
	 */
	public static function destroy($ids)
	{
		$instance = new static;

		foreach ($ids as $id => $help)
			$instance->findOrFail($id)->delete();
	}


	/**
	 * Checks if model is valid in specific time (used for Billing)
	 *
	 * Note: Model must have a get_start_time- & get_end_time-Function defined
	 *
	 * @param string 	$timespan			year / month / now
	 * @return Bool  						true, if model had valid dates during last month / year or is actually valid (now)
	 *
	 * @author Nino Ryschawy
	 */
	public function check_validity($timespan = 'month')
	{
		$start = $this->get_start_time();
		$end   = $this->get_end_time();


// if (get_class($this) == 'Modules\BillingBase\Entities\Item' && $this->contract->id == 500005 && $this->product->type == 'Internet')
// 	dd($this->product->name, $start < strtotime(date('Y-m-01')), !$end, $end >= strtotime(date('Y-m-01', strtotime('first day of last month'))), date('Y-m-d', $start), date('Y-m-d', $end));


		switch ($timespan)
		{
			case 'month':
				return $start < strtotime(date('Y-m-01')) && (!$end || $end >= strtotime(date('Y-m-01', strtotime('first day of last month'))));

			case 'year':
				return $start < strtotime(date('Y-01-01')) && (!$end || $end >= strtotime(date('Y-01-01'), strtotime('last year')));

			case 'now':
				// $now = time();
				$now = strtotime('today');
				return $start <= $now && (!$end || $end >= $now);

			default:
				\Log::error('Bad timespan param used in function '.__FUNCTION__);
				break;
		}
	}


}





/**
 * Base Observer Class - Logging of all User Interaction
 *
 * @author Nino Ryschawy
 */
class BaseObserver
{


	public function created($model)
	{
		$this->add_log_entry($model,__FUNCTION__);

		// TODO: analyze impacts of different return values
		//		without return (= return null): all is running, but multiple log entries are created
		//		return false: only one log entry per change, but created of e.g. PhonenumberObserver is never called (checked this using dd()
		//		returne true: one log entry, other observers are called
		// that are our observations so far – we definitely should check if there are other side effects!!
		// possible hint: the BaseObserver is registered before the model's observers
		return true;
	}


	public function updated($model)
	{
		$this->add_log_entry($model,__FUNCTION__);

		// TODO: analyze impacts of different return values
		//		⇒ see comment at created
		return true;
	}


	public function deleted($model)
	{
		$this->add_log_entry($model,__FUNCTION__);

		// TODO: analyze impacts of different return values
		//		⇒ see comment at created
		return true;
	}


	/**
	 * Create Log Entry on fired Event
	 */
	private function add_log_entry($model, $action)
	{
		$user = \Auth::user();

		$model_name = get_class($model);
		$model_name = explode('\\',$model_name);
		$model_name = array_pop($model_name);

		$text = '';

		// if really updated (and not updated by model->save() in observer->created() like in contract)
		if (($action == 'updated') && (!$model->wasRecentlyCreated))
		{
			// $attributes = $model->getDirty();
			// unset($attributes['updated_at']);

			// skip following attributes - TODO:
			$ignore = array(
				'updated_at',
			);

			// hide the changed data (but log the fact of change)
			$hide = array(
				'password',
			);


			// get changed attributes
			$arr = [];

			foreach ($model['attributes'] as $key => $value)
			{
				if (in_array($key, $ignore))
					continue;

				$original = $model['original'][$key];
				if ($original != $value)
					if (in_array($key, $hide)) {
						$arr[] = $key;
					}
					else {
						$arr[] = $key.': '.$original.'->'.$value;
					}
			}

			$text = implode(', ', $arr);
		}

		$data = [
			'authuser_id' => $user ? $user->id : 0,
			'username' 	=> $user ? $user->first_name.' '.$user->last_name : 'cronjob',
			'method' 	=> $action,
			'model' 	=> $model_name,
			'model_id'  => $model->id,
			'text' 		=> $text,
		];

		GuiLog::log_changes($data);

		// dd($model->getObservableEvents(), $model->getEventDispatcher(), $model->getEventDispatcher()->getListeners('eloquent.created: Modules\ProvBase\Entities\Cmts')[0]);
	}

}





/**
 * Systemd Observer Class - Handles changes on Model Gateways - restarts system services
 *
 * TODO: place it somewhere else ..
 */
class SystemdObserver
{
	// insert all services that need to be restarted after a model changed there configuration in that array
	private $services = array('dhcpd');


	public function created($model)
	{
		\Log::debug("systemd: observer called from create context");

		if (!is_dir(storage_path('systemd')))
			mkdir(storage_path('systemd'));

		foreach ($this->services as $service)
		{
			touch(storage_path('systemd/'.$service));
		}
	}


	public function updated($model)
	{
		\Log::debug("systemd: observer called from update context");

		if (!is_dir(storage_path('systemd')))
			mkdir(storage_path('systemd'));

		foreach ($this->services as $service)
		{
			touch(storage_path('systemd/'.$service));
		}
	}


	public function deleted($model)
	{
		\Log::debug("systemd: observer called from delete context");

		if (!is_dir(storage_path('systemd')))
			mkdir(storage_path('systemd'));

		foreach ($this->services as $service)
		{
			touch(storage_path('systemd/'.$service));
		}
	}
}
