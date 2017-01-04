<?php

namespace Modules\HfcReq\Entities;

class NetElement extends \BaseModel {

	// The associated SQL table for this Model
	public $table = 'netelement';


	public $kml_path = 'app/data/hfcbase/kml/static';
    private $max_parents = 25;

	// Add your validation rules here
	public static function rules($id = null)
	{
		return array(
			'name' 			=> 'required|string',
			'ip' 			=> 'ip',
			'pos' 			=> 'geopos',
			'community_ro' 	=> 'regex:/(^[A-Za-z0-9]+$)+/',
			'community_rw' 	=> 'regex:/(^[A-Za-z0-9]+$)+/',
			'devicetype_id'	=> 'required|exists:devicetype,id|min:1'
		);
	}

	// Name of View
	public static function view_headline()
	{
		return 'NetElement';
	}

	// link title in index view
	public function view_index_label()
	{
		$bsclass = 'success';

		if ($this->state == 'YELLOW')
			$bsclass = 'warning';
		if ($this->state == 'RED')
			$bsclass = 'danger';

		// TODO: complete list
		return ['index' => [$this->id, $this->type, $this->name, $this->ip, $this->state, $this->pos],
		        'index_header' => ['ID', 'Type', 'Name', 'IP', 'State', 'Position'],
		        'bsclass' => $bsclass,
		        'header' => $this->id.':'.$this->type.':'.$this->name];
	}

    public function modems()
    {
        if (\PPModule::is_active('ProvBase'))
            return $this->hasMany('Modules\ProvBase\Entities\Modem');

        return null;
    }

   	// Relation to MPRs Modem Positioning Rules
	public function mprs()
	{
		if (\PPModule::is_active('HfcCustomer'))
			return $this->hasMany('Modules\HfcCustomer\Entities\Mpr', 'netelement_id');

		return null;
	}


	/*
	 * Relation Views
	 */
	public function view_has_many()
	{
		if (\PPModule::is_active('HfcCustomer'))
			return array(
					'Mpr' => $this->mprs
				);

		return array();
	}

	public function netelementtype()
	{
		return $this->belongsTo('Modules\HfcReq\Entities\NetElementType');
	}

	public function view_belongs_to ()
	{
		return $this->netelementtype;
	}

	/**
	 * TODO: make one function
	 * returns a list of possible parent configfiles
	 * Nearly the same like html_list method of BaseModel but needs zero element in front
	 */
	public function parents_list ()
	{
		$parents = array('0' => 'Null');
		foreach (NetElement::all() as $p)
		{
			if ($p->id != $this->id)
				$parents[$p->id] = $p->name;
		}
		return $parents;
	}

	public function parents_list_all ()
	{
		$parents = array('0' => 'Null');
		foreach (NetElement::all() as $p)
		{
			$parents[$p->id] = $p->name;
		}
		return $parents;
	}

	public function get_parent ()
	{
        if (!isset($this->parent) || $this->parent < 1)
            return 0;

		return NetElement::find($this->parent);
	}


    // TODO: rename, avoid recursion
    public function get_non_location_parent($layer='')
    {
        return $this->get_parent();


        $p = $this->get_parent();

        if ($p->type == 'LOCATION')
            return get_non_location_parent($p);
        else
            return $p;
    }

    public function get_children ()
    {
        return NetElement::whereRaw('parent = '.$this->id)->get();
    }


    public static function get_all_net ()
    {
    	return [];
    	return NetElement::where('type', '=', 'NET')->get();
    }

    public function get_all_cluster_to_net ()
    {
    	return NetElement::where('type', '=', 'CLUSTER')->where('net','=',$this->id)->get();
    }

	/**
	 * Returns all available firmware files (via directory listing)
	 * @author Patrick Reichel
	 */
	public function kml_files()
	{
		// get all available files
		$kml_files_raw = glob(storage_path($this->kml_path.'/*'));
		$kml_files = array(null => "None");
		// extract filename
		foreach ($kml_files_raw as $file) {
			if (is_file($file)) {
				$parts = explode("/", $file);
				$filename = array_pop($parts);
				$kml_files[$filename] = $filename;
			}
		}
		return $kml_files;
	}


    /*
     * Helpers from NMS
     */
	private function _get_native_helper ($type = 'NET')
    {
		$p = $this;
		$i = 0;

		do
		{
            if (!is_object($p))
                return 0;

			if ($p->type == $type)
				return $p->id;

            $p = $p->get_parent();
		} while ($i++ < $this->max_parents);
    }

    public function get_native_cluster ()
    {
        return $this->_get_native_helper('CLUSTER');
    }

    public function get_native_net ()
    {
        return $this->_get_native_helper('NET');
    }

    // TODO: depracted, remove
    public function get_layer_level($layer='')
    {
		return 0;
    }


	/**
	 * Build net and cluster index for $this NetElement Objects
	 */
    public function relation_index_build ()
    {
        $tree->net     = $tree->get_native_net();
        $tree->cluster = $tree->get_native_cluster();
    }


	/**
	 * Build net and cluster index for all NetElement Objects
	 *
	 * @params call_from_cmd: set if called from artisan cmd for state info
	 */
    public static function relation_index_build_all ($call_from_cmd = 0)
    {
    	$trees = NetElement::all();

		\Log::info('nms: build net and cluster index of all tree objects');

		$i = 1;
		$num = count ($trees);

		foreach ($trees as $tree)
		{
			$debug = "nms: tree - rebuild net and cluster index $i of $num - id ".$tree->id;
	        \Log::debug($debug);

	        $tree->update(['net' => $tree->get_native_net(), 'cluster' => $tree->get_native_cluster()]);

	        if ($call_from_cmd == 1)
	        	echo "$debug\r"; $i++;

	        if ($call_from_cmd == 2)
	        	echo "\n$debug - net:".$tree->net.', clu:'.$tree->cluster;

		}

		echo "\n";
    }

}