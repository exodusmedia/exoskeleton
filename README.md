ExoSkeleton PHP 5.3 Framework
=============================

Live Example: http://exodus.io/framework/

Features
--------
- Entity passthrough class for property definition
- RESTful application interfacing
- Basic templating inclusion via View class
- Routing definition and loading and application arguments
- Environment definition and loading
- Database interface classes extending PDO
- Data output formatting (html, xml, json, etc.)
- Caching of requests on a per-application basis

Coming Maybe
------------
- Basic authentication support

Dreams
------
- Event handling and proxying

HOW TO USE THIS DAMN THING
--------------------------
Add route to app/config/routes.php:

	Route::add('example', array(
		'pattern' => '/example',
		'class' => 'Example\Application',
		'method' => 'hello_world'
	));

Create a new class in app/modules/Example/Application.php

	namespace Example;
	class Application
	{
		public function hello_world()
		{
			return 'Hello World';
		}
	}
