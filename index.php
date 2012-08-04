<?php
/**
 * ExoSkeleton Entry Point
 * @author Guillaume VanderEst <guillaume@vanderest.org>
 * @package exo
 */

require_once('exo/skeleton.php');

define('Exo\BASE_PATH', realpath(__DIR__));
define('Exo\BASE_URL', (@$_SERVER['HTTPS'] ? 'https' : 'http') . '://' . @$_SERVER['HTTP_HOST'] . str_replace(realpath(@$_SERVER['DOCUMENT_ROOT']), '', Exo\BASE_PATH));
define('Exo\APP_PATH', Exo\BASE_PATH . '/app');
define('Exo\APP_URL', Exo\BASE_URL . '/app');
define('Exo\EXO_PATH', Exo\BASE_PATH . '/exo');
define('Exo\EXO_URL', Exo\BASE_URL . '/exo');
define('Exo\APP_MODULES_PATH', Exo\APP_PATH . '/modules');
define('Exo\APP_MODULES_URL', Exo\APP_URL . '/modules');
define('Exo\APP_THEMES_PATH', Exo\APP_PATH . '/themes');
define('Exo\APP_THEMES_URL', Exo\APP_URL . '/themes');
define('Exo\CACHE_PATH', Exo\EXO_PATH . '/cache');
define('Exo\ASSETS_PATH', Exo\APP_PATH . '/assets');
define('Exo\ASSETS_URL', Exo\APP_URL . '/assets');

// execute framework
Exo::execute();
