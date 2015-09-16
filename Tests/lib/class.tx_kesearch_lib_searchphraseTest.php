<?php
class SearchPhraseTest extends Tx_Extbase_BaseTestCase {

	/**
	 * @var tx_kesearch_lib_searchphrase
	 */
	protected $searchPhrase;





	public function setUp() {
		$searchLib = $this->getMock('tx_kesearch_lib');
		$searchLib->extConf['searchWordLength'] = 4;
		$searchLib->expects($this->any())
			->method('pi_getLL')
			->will($this->returnValue('Bitte geben Sie einen Suchbegriff ein'));

		if (TYPO3_VERSION_INTEGER >= 6002000) {
			$this->searchPhrase = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_lib_searchphrase');
		} else {
			$this->searchPhrase = t3lib_div::makeInstance('tx_kesearch_lib_searchphrase');
		}

		$this->searchPhrase->initialize($searchLib);
	}

	public function tearDown() {
		unset($this->searchPhrase);
	}





	/**
	 * Test checkAgainstDefaultValue
	 *
	 * @test
	 */
	public function checkAgainstDefaultValue() {
		$searchString = $this->searchPhrase->checkAgainstDefaultValue('Hallo');
		$this->assertEquals('hallo', $searchString);

		$searchString = $this->searchPhrase->checkAgainstDefaultValue('Bitte geben Sie einen Suchbegriff ein');
		$this->assertEquals('', $searchString);
	}

	/**
	 * Test explodeSearchPhrase
	 *
	 * @test
	 */
	public function checkExplodeSearchPhrase() {
		// the following samples are from MySQL:
		// Link: http://dev.mysql.com/doc/refman/5.1/de/fulltext-boolean.html

		$matches = $this->searchPhrase->explodeSearchPhrase('apple banana');
		$shouldMatches = array(
			'apple',
			'banana'
		);
		$this->assertEquals($shouldMatches, $matches);


		$matches = $this->searchPhrase->explodeSearchPhrase('+apple +juice');
		$shouldMatches = array(
			'+apple',
			'+juice'
		);
		$this->assertEquals($shouldMatches, $matches);


		// +tag has 4 letters. But "tag" has only 3 letters, so it must be excluded
		$matches = $this->searchPhrase->explodeSearchPhrase('+search +tag');
		$shouldMatches = array(
			'+search'
		);
		$this->assertEquals($shouldMatches, $matches);


		// an machines which are not utf8 compatible "ü" will be converted to an 2 letter long value.
		// so this method will return the value. But that's wrong!
		// on machines which are configured right, this method will results in an empty array
		$matches = $this->searchPhrase->explodeSearchPhrase('tür');
		$shouldMatches = array();
		$this->assertEquals($shouldMatches, $matches);


		$matches = $this->searchPhrase->explodeSearchPhrase('+apple macintosh');
		$shouldMatches = array(
			'+apple',
			'macintosh'
		);
		$this->assertEquals($shouldMatches, $matches);


		$matches = $this->searchPhrase->explodeSearchPhrase('+apple -macintosh');
		$shouldMatches = array(
			'+apple',
			'-macintosh'
		);
		$this->assertEquals($shouldMatches, $matches);


		$matches = $this->searchPhrase->explodeSearchPhrase('+apple ~macintosh');
		$shouldMatches = array(
			'+apple',
			'~macintosh'
		);
		$this->assertEquals($shouldMatches, $matches);


		$matches = $this->searchPhrase->explodeSearchPhrase('apple*');
		$shouldMatches = array(
			'apple*'
		);
		$this->assertEquals($shouldMatches, $matches);


		$matches = $this->searchPhrase->explodeSearchPhrase('<apple >juice');
		$shouldMatches = array(
			'<apple',
			'>juice'
		);
		$this->assertEquals($shouldMatches, $matches);


		$matches = $this->searchPhrase->explodeSearchPhrase('"some words"');
		$shouldMatches = array(
			'\"some words\"'
		);
		$this->assertEquals($shouldMatches, $matches);


		$matches = $this->searchPhrase->explodeSearchPhrase('+"find me" "and that"');
		$shouldMatches = array(
			'+\"find me\"',
			'\"and that\"'
		);
		$this->assertEquals($shouldMatches, $matches);


		$matches = $this->searchPhrase->explodeSearchPhrase('wie geht\'s');
		$shouldMatches = array(
			'geht\\\'s'
		);
		$this->assertEquals($shouldMatches, $matches);


		$matches = $this->searchPhrase->explodeSearchPhrase('"content elements" +"by several"');
		$shouldMatches = array(
			'\"content elements\"',
			'+\"by several\"'
		);
		$this->assertEquals($shouldMatches, $matches);
	}
}
?>