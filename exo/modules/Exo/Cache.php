<?php
/**
 * Cache
 * @author Guillaume VanderEst <guillaume@vanderest.org>
 */
namespace Exo;
class Cache extends Entity
{
	const MAX_AGE = 86400; // 1 day

	/**
	 * @var path to caching folder (writable)
	 */
	protected $path = NULL;

	/**
	 * Instantiate the cacher
	 * @param string $path
	 */
	public function __construct($path)
	{
		$this->path = $path;
		if (!is_dir($this->path) || !is_writable($this->path))
		{
			throw new Exception('Unable write to path "' . $this->path . '"');
		}
	}
	/**
	 * Get a cached version of the request
	 * @param Exo\Request $request
	 * @param int $age in seconds
	 * @return object response
	 */
	public function get($request, $age)
	{
		$path = $this->path . '/' . $this->get_hash($request);
		if (file_exists($path) && time() - filemtime($path) < $age)
		{
			$response = unserialize(file_get_contents($path));
			$response->http_headers[] = 'Cache-Control: max-age=' . $request->route->cache . ', must-revalidate';
			return $response;
		}
		return NULL;
	}

	/**
	 * Get the age of a file in seconds
	 * @param string $path
	 * @return int seconds
	 */
	public function get_file_age($path)
	{
		if (!file_exists($path)) { return NULL; }
		return abs(time() - filemtime($path));
	}

	/**
	 * Get cache hash
	 * @param Exo\Request $request
	 * @return string
	 */
	public function get_hash($request)
	{
		return md5($request->string . '?' . http_build_query($request->arguments));
	}

	/**
	 * Clean any old items that may still be in the cache
	 * @param void
	 * @return void
	 */
	public function clean()
	{
		$files = glob($this->path . '/*');
		if ($files)
		{
			foreach ($files as $path)
			{
				if (is_dir($path)) { continue; }

				if ($this->get_file_age($path) > self::MAX_AGE)
				{
					@unlink($path);
				}
			}
		}
	}

	/**
	 * Is the request cacheable?
	 * @param Exo\Request $request
	 * @return bool
	 */
	public function is_cacheable($request)
	{
		if ($request->method == 'get' && @$request->route->cache > 0)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Save a copy of the response to the cache
	 * @param Exo\Request $request
	 * @param Exo\Response $response
	 * @param void
	 */
	public function save($request, $response)
	{
		$path = $this->path . '/' . self::get_hash($request);
		$folder = dirname($path);

		@mkdir($folder, 0777, TRUE);
		if (!is_writable($folder)) { throw new Exception('Unable to write to cache folder: ' . $folder); }

		$this->clean();

		$string = serialize($response);
		file_put_contents($path, $string);
	}
}
