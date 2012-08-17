<?php
/**
 * Route Configurations
 * @author Guillaume VanderEst <guillaume@vanderest.org>
 */

use Exo\Route;

// RESTful application
// defaults to get_index if no segments specified; otherwise,
// tries to find get_{segment0} or post_{segment0} depending
// on the type of request being made
Route::add('default', array(
	'class' => 'Example\Application',
	'cache' => 5 
));

Route::add('example', array(
    'pattern' => '/hello_world',
    'class' => 'Example\Application',
    'method' => 'hello_world'
));
