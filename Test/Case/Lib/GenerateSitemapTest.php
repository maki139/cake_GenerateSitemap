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
	 * @var array
	 */
	public $testData = array();

	public function setUp() {
		$this->GenerateSitemap = new GenerateSitemap();

		if (empty($this->testData)) {
			for ($i = 0; $i < self::GENERATE_TEST_URLS; $i++) {
				$this->testData[] = 'http://www.example.com/' . $i;
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
		$this->assertNotEmpty($xml->getNamespaces()); // urlset's namespace

		try {
			$content = Xml::toArray($xml); // test to easy
		} catch (Exception $E) {
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
}