<?php

use Models\Configfile;
use Models\Modem;

class ConfigfileTest extends TestCase {

	public function testConfigfileText()
	{
		$m = Configfile::first();
		$this->assertTrue(is_string($m->text_make(Modem::first())));	
		$this->assertNotEmpty($m->text_make(Modem::first()));
	}

	public function testIndex()
	{
		$this->routeContains ();
		$this->routeContains ('configfile');
	}

	public function testEdit()
	{
		$m = Configfile::orderby('id', 'DESC')->first()->id;
		$this->routeContains ("configfile/$m/edit");
	}

	public function testDelete()
	{
		$m = Configfile::orderby('id', 'DESC')->first()->id;
		$this->routeContains ("configfile/$m", 'DELETE');
	}

}
