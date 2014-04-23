<?php
App::uses('File', 'Utility');
App::uses('Xml', 'Utility');
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
	 * URL count
	 *
	 * @var int
	 */
	protected $count = 0;

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
	protected $config = array();

	/**
	 * Generated sitemap files
	 *
	 * @var array
	 */
	public $files = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$default = array(
			'baseUrl' => '/',
			'tmpDir' => TMP,
			'saveDir' => WEBROOT_DIR,
			'prefix' => 'sitemap'
		);

		$this->config = array_merge($default, Configure::read(__CLASS__));
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

		if ($this->count > self::MAX_COUNT) {
			$this->generate();
		}
	}

	/**
	 * Generate sitemap xml.gz
	 *
	 * @throws Exception
	 */
	public function generate() {
		$xml = Xml::fromArray(array(
			'urlset' => array(
				'xmlns:' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
				'url' => $this->url
			)
		));

		$file = new File($this->config['tmpDir'] . DS . $this->config['prefix'] . count($this->files) . ".xml.gz",
			true, 0666);

		if (!$file->write(gzencode($xml->saveXML()))) {
			throw new Exception("Write Error: ". $file->path . DS . $file->name);
		}
		$file->close();
		$this->files[] = $file;

		$this->url = array();
		$this->count = 0;
	}

	/**
	 * Publish sitemap
	 */
	public function publish() {
		if (! empty($this->url)) {
			$this->generate();
		}

		$sitemap = array();

		/** @var File $file */
		foreach ($this->files as $file) {
			$file->copy($this->config['saveDir'] . DS . $file->name);
			$sitemap[] = array(
				'loc' => $this->config['baseUrl'] . DS . $file->name,
				'lastmod' => date('Y-m-d')
			);
			$file->delete();
		}

		$xml = Xml::fromArray(array(
			'sitemapindex' => array(
				'xmlns:' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
				'sitemap' => $sitemap
			)
		));

		if ($xml->asXML($this->config['saveDir'] . DS . $this->config['prefix'] . DS . "xml")) {
			throw new Exception("Can't save sitemap index xml.");
		}
	}
}