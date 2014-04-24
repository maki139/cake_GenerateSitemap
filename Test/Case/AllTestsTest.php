<?php
/**
 * All tests execute
 */
class AllTests extends CakeTestSuite {

	public static function suite() {
		$suite = new CakeTestSuite('All tests');
		$suite->addTestDirectoryRecursive(App::pluginPath('GenerateSitemap') . 'Test' . DS . 'Case');
		return $suite;
	}

}