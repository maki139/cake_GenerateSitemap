<?php
App::uses('File', 'Utility');
App::uses('Xml', 'Utility');
App::uses('GenerateSitemapGenerateSitemapException', __CLASS__ . '.Lib/Error');
/**
 * Class Generate Sitemap
 */
class GenerateSitemap {

	/**
	 * URL max count (limited by protocol)
	 *
	 * @var int
	 */
	const MAX_COUNT = 50000;

	/**
	 * XML urlset namespace
	 *
	 * @var string
	 */
	const URLSET_NS = 'http://www.sitemaps.org/schemas/sitemap/0.9';

	/**
	 * URL count
	 *
	 * @var int
	 */
	protected $count = 0;

	/**
	 * All URL count
	 *
	 * @var int
	 */
	protected $total = 0;

	/**
	 * URL lists
	 *
	 * @var array
	 */
	protected $url = array();

	/**
	 * Configure settings
	 *
	 * @var array
	 */
	public $config = array();

	/**
	 * Generated sitemap files
	 *
	 * @var array [] => File
	 */
	public $files = array();

	/**
	 * Published files
	 *
	 * @var array [] => File
	 */
	public $published = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$default = array(
			'baseUrl' => '/',
			'tmpDir' => TMP,
			'saveDir' => APP . WEBROOT_DIR,
			'prefix' => 'sitemap'
		);

		$this->config = array_merge($default, Configure::read(__CLASS__) ?: array());
	}

	/**
	 * Destructor
	 *
	 * Delete tmp files
	 */
	public function __destruct() {
		foreach ($this->files as $file) {
			$file->delete();
		}
	}

	/**
	 * Appended urls count
	 *
	 * @param bool $sum true: return total urls, false: in xml urls
	 * @return int
	 */
	public function count($total = false) {
		if ($total) {
			return $this->total;
		}
		return $this->count;
	}

	/**
	 * Get urls
	 *
	 * @return array
	 */
	public function url() {
		return $this->url;
	}

	/**
	 * URL append
	 *
	 * @param string $loc URL Max length is 2048.
	 * @param string $lastmod W3C Datetime or YYYY-MM-DD format
	 * @param string $changefreq Options: always / hourly / daily / weekly / monthly / yearly / never
	 * @param float $priority Between 0.0 AND 1.0
	 */
	public function append($loc, $lastmod = null, $changefreq = null, $priority = null) {
		$data = array(
			'loc' => $loc
		);

		if (! is_null($lastmod)) {
			$data['lastmod'] = $lastmod;
		}

		if (! is_null($changefreq)) {
			$data['changefreq'] = $changefreq;
		}

		if (! is_null($priority)) {
			$data['priority'] = $priority;
		}

		$this->url[] = $data;
		++$this->count;
		++$this->total;

		if ($this->count >= self::MAX_COUNT) {
			$this->generate();
		}
	}

	/**
	 * Generate sitemap xml.gz
	 *
	 * @throws GenerateSitemapException
	 */
	public function generate() {
		$xml = Xml::fromArray(array(
			'urlset' => array(
				'xmlns:' => self::URLSET_NS,
				'url' => $this->url
			)
		));

		$filename = $this->config['tmpDir'] . DS . $this->config['prefix'] . count($this->files) . ".xml.gz";
		$zp = gzopen($filename, 'wb');
		if ($zp === false) {
			throw new GenerateSitemapException('Open file error: ' . $filename);
		}
		gzwrite($zp, $xml->saveXML());
		gzclose($zp);

		$this->files[] = new File($filename);

		$this->url = array();
		$this->count = 0;
	}

	/**
	 * Publish sitemap
	 *
	 * @throws GenerateSitemapException
	 * @return string Generated sitemap index file path
	 */
	public function publish() {
		if (! empty($this->url)) {
			$this->generate();
		}

		$sitemap = array();

		foreach ($this->files as $key => $file) {
			$publishPath = $this->config['saveDir'] . DS . $file->name;
			$file->copy($publishPath);
			$sitemap[] = array(
				'loc' => $this->config['baseUrl'] . $file->name,
				'lastmod' => date('c')
			);
			$file->delete();

			$this->published[$key] = new File($publishPath);
		}

		$xml = Xml::fromArray(array(
			'sitemapindex' => array(
				'xmlns:' => self::URLSET_NS,
				'sitemap' => $sitemap
			)
		));

		$filename = $this->config['saveDir'] . DS . $this->config['prefix'] . ".xml";
		if (! $xml->asXML($filename)) {
			throw new GenerateSitemapException("Can't save sitemap index xml.");
		}

		return $filename;
	}

	/**
	 * Clear all saveDir sitemap
	 */
	public function clear() {
		$folder = new Folder($this->config['saveDir']);
		$files = $folder->find("{$this->config['prefix']}.*");

		foreach ($files as $file) {
			$file = new File($folder->pwd() . DS . $file);
			$file->delete();
		}
	}
}