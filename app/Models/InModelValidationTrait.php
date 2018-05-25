<?php

namespace App\Models;

trait InModelValidationTrait {


	/* rules array for global in-model-validation - see ValidatingTrait
	 * @note: dont use this directly! Instead use rules() function
	 * @author Torsten Schmidt
	 */
	protected $rules = [];

	/*
	 * Init In-Model-Validation
	 * See: ValidatingTrait package
	 * @author Torsten Schmidt
	 */
	public function init_validation()
	{
		//
		$this->rules = $this->rules($this->id);
	}


	public function set_validation($data)
	{
		$this->rules = $data;
	}

}
