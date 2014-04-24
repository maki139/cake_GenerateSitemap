<?php
App::uses('GenerateSitemap', 'GenerateSitemap.Lib');
/**
 * CakeTest
 */
class GenerateSitemapTest extends CakeTestCase
{
	/**
	 * Generate urls for test
	 *
	 * @var int
	 */
	const GENERATE_TEST_URLS = 100001;

	/**
	 * Class
	 *
	 * @var GenerateSitemap
	 */
	public $GenerateSitemap = null;

	/**
	 * For test data
	 *
	 * @var array
	 */
	public $testData = array();

	public function setUp() {
		$this->GenerateSitemap = new GenerateSitemap();

		if (empty($this->testData)) {
			for ($i = 0; $i < self::GENERATE_TEST_URLS; $i++) {
				$this->testData[] = $i;
			}
		}
	}

	public function tearDown() {
		Configure::delete('GenerateSitemap');

		$this->GenerateSitemap->clear();
	}

	/**
	 * Force generate sitemap
	 */
	public function testGenerate() {
		$data1 = array(
			'loc' => $this->testData[0]
		);

		extract($data1);
		$this->GenerateSitemap->append($loc);

		$appended = $this->GenerateSitemap->url();
		$this->assertEquals($data1, $appended[0]);

		$data2 = array(
			'loc' => $this->testData[1],
			'lastmod' => date('Y-m-d'),
			'changefreq' => 'always',
			'priority' => '1.0'
		);
		extract($data2);
		$this->GenerateSitemap->append($loc, $lastmod, $changefreq, $priority);

		$appended = $this->GenerateSitemap->url();
		$this->assertEquals($data2, $appended[1]);

		$this->GenerateSitemap->generate();

		$this->assertNotEmpty($this->GenerateSitemap->files[0]);
		$file = $this->GenerateSitemap->files[0];
		$this->assertInstanceOf('File', $file);
		$this->assertGreaterThan(0, $file->size());

		// file content check
		$zp = gzopen($file->path, 'rb');
		$this->assertTrue(($zp !== false));

		try {
			$xml = Xml::build(gzread($zp, 1024 * 1000 * 10));
			gzclose($zp);
		} catch (Exception $e) {
			$this->fail('Xml parse error: Xml::build()');
		}

		$this->assertInstanceOf('SimpleXMLElement', $xml);
		$this->assertNotEmpty($xml->getNamespaces()); // root namespace

		try {
			$content = Xml::toArray($xml); // test to easy
		} catch (Exception $e) {
			$this->fail('Xml parse error: Xml::toArray()');
		}

		$expected = array(
			'urlset' => array(
				'url' => array(
					$data1,
					$data2
				)
			)
		);

		$this->assertEquals($expected, $content);
	}

	/**
	 * Generate sitemap file when max count
	 */
	public function testAutoGenerate() {
		for ($i = 0; $i < GenerateSitemap::MAX_COUNT; $i++) {
			$this->GenerateSitemap->append($this->testData[$i], date('Y-m-d'), 'always', '1.0');
		}

		// appended?
		$this->assertEquals(0, $this->GenerateSitemap->count());
		$this->assertEquals(GenerateSitemap::MAX_COUNT, $this->GenerateSitemap->count(true));

		// file exists
		$this->assertNotEmpty($this->GenerateSitemap->files[0]);
		$file = $this->GenerateSitemap->files[0];
		$this->assertInstanceOf('File', $file);
		$this->assertGreaterThan(0, $file->size());
	}

	/**
	 * Publish generated sitemap
	 */
	public function testPublish() {
		$this->GenerateSitemap->config['baseUrl'] = 'http://www.example.com/';

		foreach ($this->testData as $loc) {
			$this->GenerateSitemap->append($loc);
		}
		$this->assertEquals(self::GENERATE_TEST_URLS, $this->GenerateSitemap->count(true));

		try {
			$sitemapIndex = $this->GenerateSitemap->publish();
		} catch (Exception $e) {
			$this->fail();
		}

		// generated files count
		$expectedFiles = (int)ceil(self::GENERATE_TEST_URLS / GenerateSitemap::MAX_COUNT);
		$this->assertEquals($expectedFiles, count($this->GenerateSitemap->files));

		$this->assertEquals(count($this->GenerateSitemap->files), count($this->GenerateSitemap->published));
		$file = new File($sitemapIndex);

		try {
			$xml = Xml::build($file->read());
		} catch (Exception $e) {
			$this->fail();
		}

		$this->assertInstanceOf('SimpleXMLElement', $xml);
		$this->assertNotEmpty($xml->getNamespaces()); // root namespace

		$content = Xml::toArray($xml);

		foreach ($content['sitemapindex']['sitemap'] as $key => $sitemap) {
			$expected = 'http://www.example.com/' . $this->GenerateSitemap->published[$key]->name;
			$this->assertEquals($expected, $sitemap['loc']);
		}
	}
}