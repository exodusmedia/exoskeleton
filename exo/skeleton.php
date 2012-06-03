<?php
/**
 * ExoSkeleton Framework
 * @author Guillaume VanderEst <guillaume@vanderest.org>
 * @package exo
 */
use Exo\Environment;
use Exo\Request;
use Exo\Response;
use Exo\Route;

class Exo
{
	const REQUEST_KEY = '_exo';
	const REQUEST_SEPARATOR = '/';
	const DEFAULT_METHOD = 'index';
	const ERROR_METHOD = 'error';

	/**
	 * Load classes
	 * @param string $class
	 * @return void
	 */
	public static function autoload($class)
	{
		$filename = preg_replace('#[^a-z0-9]#i', '/', $class) . '.php';
		$dirs = array(
			Exo\APP_PATH . '/modules',
			Exo\EXO_PATH . '/modules'
		);
		foreach ($dirs as $dir)
		{
			$path = $dir . '/' . $filename;
			if (file_exists($path))
			{
				require_once($path);
			}
		}
	}

	/**
	 * Handle an exception
	 * @param object $e
	 * @return void
	 */
	public static function exception(\Exception $e)
	{
		?>
<?php if (!headers_sent()): ?>
<!DOCTYPE html>
<html>
	<head>
		<title>Exception Encountered</title>
	</head>
	<body>
<?php endif; ?>
<style>
.exo-exception { margin: 20px; border: 1px solid #ccc; border-radius: 10px; box-shadow: 0 0 10px #ccc; padding: 10px 20px 10px 20px; }
.exo-exception * { font-family: monospace; margin: 0; font-size: 14px; line-height: 1.6; }
.exo-exception h1 { font-size: 20px; margin-bottom: 10px; }
.exo-exception table { border-collapse: collapse; margin-bottom: 10px; }
.exo-exception td, .exo-exception th { border: 1px solid #ccc; vertical-align: top; text-align: left; padding: 5px 10px 5px 10px; }
.exo-exception th { background: #eee; }
.exo-exception td { font-family: monospace; white-space: pre-wrap; }
</style>

<div class="exo-exception">
	<h1>Exception Encountered</h1>
	<table>
		<tbody>
			<tr class="heading">
				<th colspan="2">Exception Information</th>
			</tr>
			<tr>
				<th>Message</th>
				<td><?= $e->getMessage(); ?></td>
			</tr>
			<tr>
				<th>File</th>
				<td><?= $e->getFile(); ?>(<?= $e->getLine() ?>)</td>
			</tr>
			<?php if (get_class($e) == 'Exo\Exception'): ?>
				<tr>
					<th>Request</th>
					<td><?= var_dump($e->getRequest()); ?></td>
				</tr>
			<?php endif; ?>
			<tr>
				<th>Trace</th>
				<td><?php foreach ($e->getTrace() as $step): ?>
<?= $step['file'] ?> (<?= $step['line'] ?>): <?= $step['class'] . $step['type'] . $step['function'] ?>()
<?php endforeach; ?>
				</td>
			</tr>
			<tr class="heading">
				<th colspan="2">Automagic Values</th>
			</tr>
			<tr>
				<th>$_POST</th>
				<td><?= var_export($_POST); ?></td>
			</tr>
			<tr>
				<th>$_GET</th>
				<td><?= var_export($_GET); ?></td>
			</tr>
			<tr>
				<th>$_COOKIE</th>
				<td><?= var_export($_COOKIE); ?></td>
			</tr>
			<tr>
				<th>$_SESSION</th>
				<td><?= var_export($_SESSION); ?></td>
			</tr>
		</tbody>	
	</table>
</div> <!-- #exo-exception -->

	</body>
</html>
		<?php
		exit();
	}

	/**
	 * Handle the request
	 * @param void
	 * @return string appropriate output
	 */
	public static function execute()
	{
		self::register_autoloader();
		self::load_includes();

		$env = self::load_environment();

		self::register_exception_handler();

		self::load_session();
		$response = self::load_route();
		$response->send_http_headers();
		print($response->content);
	}

	/**
	 * Get themes path
	 * @param void
	 * @return string path
	 */
	public static function get_themes_path()
	{
		return realpath(EXO\APP_PATH . '/themes');
	}

	/**
	 * Load configuration files and helpers
	 * @param void
	 * @return void
	 */
	public static function load_includes()
	{
		$dirs = array(
			Exo\APP_PATH . '/config',
			Exo\EXO_PATH . '/config',
			Exo\APP_PATH . '/helpers',
			Exo\EXO_PATH . '/helpers',
		);

		foreach ($dirs as $dir)
		{
			if (is_dir($dir))
			{
				foreach (glob($dir . '/*.php') as $path)
				{
					include_once($path);
				}
			}
		}
	}

	/**
	 * Load appropriate environment
	 * @param void
	 * @return void
	 */
	public static function load_environment()
	{
		$env = Environment::get();
		
		if ($env->debug)
		{
			ini_set('display_errors', TRUE);
			error_reporting($env->debug);
		}

		return $env;
	}

	/**
	 * Load the requested route
	 * @param void
	 * @return string proper output
	 */
	public static function load_route()
	{
		$request = new Request();
		$request->string = @$_REQUEST[self::REQUEST_KEY];

		$request->segments = explode(self::REQUEST_SEPARATOR, $request->string);

		$route = Route::get($request);
		$request->route = $route;

		$request->arguments = array_slice($request->segments, count($route->segments));

		if (!$route)
		{
			throw new Exception('Route not found');
		}

		$class = $route->class;

		// if restful, use a protocol_segment0 method

		if ($route->restful)
		{
			$method = implode('_', array(
				strtolower($_SERVER['REQUEST_METHOD']),
				@$request->arguments[0] ? $request->arguments[0] : self::DEFAULT_METHOD
			));
			$request->arguments = array_slice($request->arguments, 1);
			
		} else {
			$method = @$route->method ? $route->method : self::DEFAULT_METHOD;
		}

		$object = new $class($request);
		if (!method_exists($object, $method))
		{
			if (!method_exists($object, self::ERROR_METHOD))
			{
				throw new Exception('Route method "' . $class . '->' . $method . '()" does not exist');
			}
			$method = self::ERROR_METHOD;
		}
		$response = $object->$method();

		if (!$response instanceof Response)
		{
			$response = new Response($response);
		}

		return $response;
	}

	/**
	 * Load the current session
	 * @param void
	 * @return void
	 */
	public static function load_session()
	{
		if (!session_id())
		{
			session_start();
		}
	}

	/**
	 * Register the ExoSkeleton auto-loader
	 * @param void
	 * @return void
	 */
	public static function register_autoloader()
	{
		spl_autoload_register('Exo::autoload', TRUE, TRUE);
	}

	/**
	 * Register exception handler
	 * @param void
	 * @return void
	 */
	public static function register_exception_handler()
	{
		set_exception_handler('Exo::exception');
	}
}
