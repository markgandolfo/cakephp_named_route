<?php
App::uses('Router', 'Cake.Routing');
/**
 * gets a named param
 *
 * @author Mark Gandolfo
 * @example
 *
 * NamedRoute::get('add_user'); #> '/admin/
 *
 */
class NamedRoute {

	public $routes;

	/**
	 * Simply get a new instance of the NamedRoute class
	 *
	 * @author Mark Gandolfo
	 *
	 */
	function &getInstance() {
		static $instance = array();

		if (!$instance) {
			$instance[0] =& new NamedRoute();
		}
		return $instance[0];
	}

	/**
	 * Sets a named route
	 *
	 * @author Mark Gandolfo
	 * @param $route Array of named routes
	 * @example
	 *
	 * To set a controller with the common actions index, view, edit, delete, new
	 * NamedRoute::set(array('users'));
	 *
	 * this will give us the following routes
	 * index_users_path, index_users_NamedRoute, view_user_path, view_user_NamedRoute, edit_user_path, edit_user_NamedRoute, delete_user_path, delete_user_NamedRoute, new_user_path, new_user_NamedRoute
	 *
	 *
	 * To set a specific named route, for instance /users/login we might want to call the named route "login"
	 * NamedRoute::set(array('login' => array('controller => 'users', 'action' => 'login')));
	 *
	 * this will give us the following routes
	 * login_path, login_NamedRoute
	 *
	 */
	static function set($route) {
		$_this = NamedRoute::getInstance();

		foreach($route as $namedRoute => $namedRouteOptions) {
			if(( isset($namedRouteOptions['controller']) ) && ( !isset($namedRouteOptions['action']) )) {
				$controller = $namedRouteOptions['controller'];

				foreach(array('view', 'add', 'edit', 'delete') as $action) {
					$route[$action . '_' . Inflector::singularize($namedRoute)] = array('controller' => $controller, 'action' => $action);
				}

				$route[$namedRoute] = array('controller' => $controller, 'action' => 'index');
			}
		}
		$_this->routes = Set::merge($_this->routes, $route);
	}

	/**
	 * Returns the path or url of the named route
	 *
	 * @author Mark Gandolfo
	 * @example
	 *
	 * If in our routes.php file we had Router::connect('/login', array('controller' => 'users', 'action' => 'login'))
	 * from performing a
	 * url('login_path') or
	 *
	 * the returned NamedRoute would be
	 * # => '/login'
	 *
	 *
	 * If we did a
	 * url('login_NamedRoute')
	 *
	 * we should see returned
	 * # => http://domain.com/login {where domain.com is your server name}
	 *
	 *
	 * If we used a prefix
	 * url('admin.login_path')
	 *
	 * the returned string would be
	 * # => /admin/login
	 *
	 *
	 *
	 * We could also pass paramters in for our routes
	 * url('admin.edit_user_path(1));
	 * # => /admin/users/edit/1
	 *
	 * You can have unlimited amount of paramaters, just seperate them with commas
	 * url('view_articles_path(1,2,3,4);
	 * # => /articles/view/1/2/3/4
	 *
	 */
	public function get($route, $params = array()) {
		$_this = NamedRoute::getInstance();

		if(!empty($route)) {
			$routeInformation = $_this->pareseRoute($route);

			if($routeInformation) {
				$routerRoute = Router::url( $_this->routes[$routeInformation['action']] );

				// add the prefix if it exists
				if($routeInformation['prefix']) {
					$routerRoute = '/' . $routeInformation['prefix'] . $routerRoute;
				}

				// add the full domain if NamedRoute is set
				if($routeInformation['url']) {
					$routerRoute = 'http://' . $_SERVER['SERVER_NAME'] . $routerRoute;
				}

				// add paramaters if any are set
				if($routeInformation['params']) {
					$routerRoute .= '/'. $routeInformation['params'];
				}

				return $routerRoute;
			} else {
				return null;
			}
		}

		return $route;
	}


	/**
	* Parses a user entered route, and pulls out an array of information we can use later, such as the action, url/path info
	*
	* @author Mark Gandolfo
	* @param $route a named route
	* @example
	*
	* Pull the named route apart looking for information
	* $_this->pareseRoute('login_path');
	* # => array('prefix' => null, 'action' => 'login', 'url' => false, 'path' => true);
	*
	* It will also provide prefix routing information
	* $_this->pareseRoute('admin.users_url');
	* # => array('prefix' => 'admin', 'action' => 'users', 'url' => true, 'path' => false);
	*
	*/
	private function pareseRoute($route) {
		$_this = NamedRoute::getInstance();

		$routeInformation = array(
			'prefix' => false,
			'action' => false,
			'params' => false,
			'url' => false,
			'path' => false
		);

		// Are there any prefix routing options? If so store it, and remove it from the route string
		if( strrpos($route, '.') ) {
			$tokenizedRoute = String::tokenize($route, '.');
			if(count($tokenizedRoute) == 2) {
				$routeInformation['prefix'] = $tokenizedRoute[0];
				$route = $tokenizedRoute[1];
			}
		}

		// Do we have any paramaters?
		if( strrpos($route, '(') ) {
			$params = substr($route, strrpos($route, '(') + 1 );
			$params = rtrim($params, ')');
			$routeInformation['params'] = str_replace(',' ,'/', $params);

			//remove paramters from the route
			$route = substr($route, 0, strrpos($route, '('));
		}

		// Get either NamedRoute or path information from the route, pull it out and fill in the rest of the information array
		$tokenizedRoute = String::tokenize($route, '_');

		//submitted by radar
		$routeInformation[$tokenizedRoute[count($tokenizedRoute) - 1]] = true;

#		switch ($tokenizedRoute[count($tokenizedRoute) - 1 ]) {
#			case 'url':
#				$routeInformation['url'] = true;
#			break;
#			case 'path':
#				$routeInformation['path'] = true;
#			break;
#		}
		unset($tokenizedRoute[count($tokenizedRoute) - 1 ]);

		// the rest must be the action, so store it in the information array
		$routeInformation['action'] = implode('_', $tokenizedRoute);

		return $routeInformation;
	}


	/**
	 * Returns the NamedRoute object, for debugging.
	 *
	 * @author Mark Gandolfo
	 * @example
	 * print_r(NamedRoute::debug());
	 *
	 */
	public static function debug() {
		$_this = NamedRoute::getInstance();
		return $_this;
	}

	/**
	 * Returns a list of routes in a table format to quickly get a snapshot of your current named routes
	 *
	 * @author Mark Gandolfo
	 * @return String a HTML table of named routes and associated routes
	 * @example
	 *
	 * print_r(NamedRoute::routes());
	 *
	 * Output is in HTML (just printed)
	 *
	 * Route	Url				Controller		Action
	 * root		/home_page/		home_page		index
	 * login	/users/login/	users			login
	 *
	 */
	public static function routes() {
		$_this = NamedRoute::getInstance();

		$table = '<table>';
		$table .=  '<tr><th>Route</th><th>Url</th><th>Controller</th><th>Action</th></tr>';

		foreach($_this->routes as $route => $var) {
			$table .= "<tr><td>" . $route . "</td>";
			$table .= "<td>" . Router::url( $var ) . "</td>";
			$table .= "<td>" . $var['controller'] . "</td>";
			$table .= "<td>" . $var['action'] . "</td></tr>";
		}
		$table .= '</table>';

		return $table;
	}
}

/**
 * Alias function for NamedRoute::get();
 *
 * @author Mark Gandolfo
 * @param $route A named route
 * @example
 * url('login_path');
 *
 */
function url($route) {
	return NamedRoute::get($route);
}

