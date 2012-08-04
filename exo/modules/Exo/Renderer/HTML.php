<?php
/**
 * HTML Renderer
 * @author Guillaume VanderEst <guillaume@vanderest.org>
 */
namespace Exo\Renderer;

use Exo\Exception;
use Exo\Renderer;
use Exo\Response;
use Exo\Route;

class HTML extends Renderer
{
	const DEFAULT_TEMPLATE = 'default';

	/**
	 * Template name
	 * @var string
	 */
	protected $template;

	/**
	 * Theme name
	 * @param string
	 */
	protected $theme;

	/**
	 * Temporary storage for the full template path
	 * @var string
	 */
	protected $template_path;
	protected $theme_url;
	protected $theme_path;

	/**
	 * The namespace of the application 
	 * @var string
	 */
	protected $application_namespace;

	/**
	 * Get the URL to the theme (usually used from inside a template)
	 * @return string $url
	 */
	public function _get_theme_url()
	{
		return \Exo\APP_THEMES_URL . '/' . $this->theme;
	}

	/**
	 * Get the path to the template
	 * @return string path
	 */
	public function _get_theme_path()
	{
		return \Exo\APP_THEMES_PATH . '/' . $this->theme;
	}

	public function render($data = array())
	{
		$this->theme = $this->view->application->route->theme;
		$this->theme_path = $this->_get_theme_path();
		$this->theme_url = $this->_get_theme_url();
		$this->template_path = $this->_get_theme_path() . '/' . $this->template . '.php';
		$this->data = $data;

		if ($this->application)
		{
			// TODO: replace this with Exo\Module::get_namespace($this->application)
			$this->application_namespace = reset(explode('_', str_replace('\\', '_', get_class($this->application))));
		}

		unset($data);
		foreach ($this->data as $__key => $__value)
		{
			$$__key = $__value;
		}
		unset($__key);
		unset($__value);

		$response = new Response;
		$response->content_type = 'text/html';

		// the file does not exist, try to fall back and pass to "default.php" of theme
		if (!file_exists($this->template_path))
		{
			ob_start();
			$this->template_path = \Exo\APP_MODULES_PATH . '/' . $this->application_namespace . '/templates/' . $this->template . '.php';
			
			if (!file_exists($this->template_path))
			{
				throw new Exception('Template "' . $this->template .'" could not be loaded and a default template could not be found');
			}

			include($this->template_path);
			$this->data['content'] = $content = ob_get_clean();
			$this->template_path = $this->_get_theme_path() . '/' . self::DEFAULT_TEMPLATE . '.php';
		}

		ob_start();
		include($this->template_path);
		$output = ob_get_clean();

		$response->content = $output;
		return $response;
	}

	/**
	 * Get the URL to a route
	 * @param mixed $route
	 * @param array $arguments (optional) additional arguments
	 */
	public function url_to_route($route, $args = array())
	{
		if (!is_array($args)) { $args = array($args); }

		// put the segments together
		$segments = Route::parse_segments($route->pattern);
		$segments = array_merge($segments, $args);

		// now make it a string
		$rewrite = $this->application->request->rewrite;
		if (!$rewrite)
		{
			return \Exo\BASE_URL . '/index.php/' . implode(Route::REQUEST_SEPARATOR, $segments);
		}
		return \Exo\BASE_URL . '/' . implode(Route::REQUEST_SEPARATOR, $segments);
	}

	/**
	 * URL to the application being executed
	 * @param array $arguments (optional) additional arguments
	 * @return string
	 */
	public function url_to_self($args = array())
	{
		$request = $this->view->application->request;
		return $this->url_to_route($request->route, $args);
	}

	/**
	 * Call a method, passthrough to application
	 * @param string $method
	 * @param array $arguments (optional)
	 * @return mixed
	 */
	public function __call($method, $args = array())
	{
		if (method_exists($this->view, $method))
		{
			return call_user_func_array(array($this->view, $method), $args);
		}
		throw new Exception('The method "' . $method . '" could not be found');
	}

	/**
	 * Passthrough GET function to attempt to fetch from view
	 * @param string $attribute
	 * @return mixed
	 */
	public function __get($attribute)
	{
		return $this->view->$attribute;
	}
}
