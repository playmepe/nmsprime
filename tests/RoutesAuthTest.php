<?php

/**
 * Tests if all routes use auth middleware
 *
 * @author Patrick Reichel
 */
class RoutesAuthTest extends TestCase {

	// there can be routes not using auth middleware – define them here to exclude from testing
	protected $routes_not_using_auth_middleware = [
		'Auth.logout',
		'CustomerAuth.logout',
		'ProvVoipEnvia.cron',
	];

	// some routes make problems (e.g. returning status 500 in testing
	// Solve this problems and remove routes from array
	protected $problematic_routes_to_check = [
		'Tree.delete',
		'Modem.ping',
		'Modem.monitoring',
		'Modem.log',
		'Modem.lease',
	];

	// some routes do redirect to login page instead of giving status 403
	// as these routs needs other tests you can define them here
	protected $routes_redirecting_to_login_page = [
		'admin',
		'Auth.login',
		'CustomerAuth.login',
		'CHome',
		'Home',
	];

	// there now is an API with own routes – add all available API versions here
	protected $api_versions = [
		0,
	];


	/**
	 * Constructor
	 *
	 * @author Patrick Reichel
	 */
	public function __construct() {

		return parent::__construct();

	}


	/**
	 * Creates a Laravel application used for testing
	 *
	 * @author Patrick Reichel
	 */
	public function createApplication() {

		$app = parent::createApplication();
		return $app;
	}


	/**
	 * Method to test all routes.
	 *
	 * @author Patrick Reichel
	 */
	public function testRoutesAuthMiddleware() {

		$routeCollection = Route::getRoutes();
		foreach ($routeCollection as $value) {
			$name = $value->getName();
			$method = $value->getMethods()[0];

			// no name – no test
			if (!boolval($name))
				continue;

			// route without auth middleware
			if (in_array($name, $this->routes_not_using_auth_middleware))
				continue;

			// problems with route: TODO: check for reasons
			if (in_array($name, $this->problematic_routes_to_check))
				continue;

			// check type of route
			if (strpos($name, '.api_') !== false) {
				$route_type = 'api';
			}
			else {
				$route_type = 'standard';
			}

			// check if standard or API route
			$urls = [];
			if ($route_type == 'standard') {
				$_ = URL::route($name, [1, 1, 1, 1, 1], true);
				$urls[0] = explode('?', $_)[0];
			}
			elseif ($route_type == 'api') {
				foreach ($this->api_versions as $api_version) {
					$_ = URL::route($name, [$api_version, 1, 1, 1, 1], true);
					$url = explode('?', $_)[0];
					array_push($urls, $url);
				}
			}
			else {
				// impossible: add a test that fails
				$this->assertContains($route_type, ['standard', 'api']);
			}

			foreach ($urls as $url) {
				if (in_array($name, $this->routes_redirecting_to_login_page)) {
					// special test for redirects to login page
					echo "\nTesting $name ($method: $url)";
					$this->visit($url)
						->see('Username')
						->see('Password')
						->see('Sign me in');
				}
				elseif ($route_type == 'standard') {
					// all other standard routes should return 403 if not logged in
					echo "\nTesting $name ($method: $url)";
					$this->call($method, $url, []);
					$this->assertResponseStatus(403);
					$this->see('denied');
				}
				elseif ($route_type == 'api') {
					// api routes return 401!
					echo "\nTesting $name ($method: $url)";
					if (in_array($method, ['GET', ])) {
						$this->call($method, $url, []);
						$this->assertResponseStatus(401);
						$this->see('Invalid credentials.');
					}
					else {
						$this->call($method, $url, []);
						$this->assertResponseStatus(403);
						$this->see('denied');
					}
				}
			}
		}

		// print routes with known problems
		if ($this->problematic_routes_to_check) {
			echo "\n\nThere are routes with known problems (e.g. return code is 500)";
			echo "\nSolve and remove from array!";
		}
		foreach ($this->problematic_routes_to_check as $r) {
			echo "\n	$r";
		}
	}
}
