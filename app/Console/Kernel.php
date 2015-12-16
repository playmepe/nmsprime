<?php namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		'App\Console\Commands\Inspire',
	];


	/**
	 * check if module exists
	 *
	 * NOTE: this is a copy from BaseModel
	 *
	 * TODO: move this to a better place. Or even better: call 
	 *       scheduling stuff from a module based context
	 *
	 * @author Torsten Schmidt
	 *
	 * @param  Modulename
	 * @return true if module exists and is active otherwise false
	 */
	private function module_is_active($modulename)
	{
		$modules = \Module::enabled();

		foreach ($modules as $module)
			if ($module->getLowerName() == strtolower($modulename))
				return true;

		return false;
	}


	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		if ($this->module_is_active ('ProvBase'))
		{
			// Rebuid all Configfiles
			$schedule->command('nms:configfile')->hourly()->withoutOverlapping();

			// TODO: Reload DHCP
			$schedule->command('nms:dhcp')->hourly()->withoutOverlapping();

			// Contract
			$schedule->command('nms:contract daily')->daily();
			$schedule->command('nms:contract monthly')->monthly();
		}
	}

}
