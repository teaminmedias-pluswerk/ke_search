<?php
class SearchResultTest extends Tx_Extbase_BaseTestCase {
	protected $content = '<div class="csc-header csc-header-n2"><h2>1. Überblick</h2></div><p>Die Bildung nimmt einen zentralen Platz in der  öffentlichen Diskussion in Lettland ein. Nach der Unabhängigkeit begann  eine umfangreiche Bildungsreform, die bis heute anhält. Eine der  ausdrücklichen Prioritäten der lettischen Regierung ist die Integration  des Landes in den europäischen Bildungsraum. Sie ist entschlossen, die  Ziele von Lissabon und Kopenhagen zu erreichen. Auch die Mehrheit der  Bevölkerung räumt der Bildung, insbesondere im beruflichen Zusammenhang,  einen hohen Stellenwert ein. Viele Erwachsene absolvieren ein  Zweitstudium, Lehrer bezahlen Fortbildungen aus eigener Tasche und junge  Menschen holen in Abendschulen ihren Schulabschluss nach.<br />Die Fragen  der allgemeinen und der beruflichen Bildung sowie der Weiterbildung  liegen in der Zuständigkeit des Ministeriums für Bildung und  Wissenschaft (MBW), des Sozialministeriums und des  Wirtschaftsministeriums.<br />Für die Bereiche Vorschulerziehung, Primar-,  Grund- und Basisschulen, Schulen für geistig Behinderte, Internate,  Kinderheime und außerschulische Einrichtungen sind die  Selbstverwaltungen (Städte und Gemeinden) verantwortlich. Sie überwachen  die vom MBW vorgegebenen Standards. Darüber hinaus gibt es mehrere  Fachgremien, die unter Aufsicht des MBW stehen. Eine institutionell  herausragende Position nimmt für die berufliche Aus- und Weiterbildung  das Berufsbildungszentrum (PIC) ein.<br />Eine für den Hochschulbereich  vergleichbare Funktion hat das Zentrum zur Bewertung der Qualität der  Hochschulbildung. Die gesetzlichen Grundlagen für die allgemeine und  berufliche Bildung sind im Bildungsgesetz (1998), im  Berufsbildungsgesetz (1999) sowie im Hochschulgesetz (1999) formuliert.<br /><br /><strong>Bildungssystem </strong><br /><br />Die  lettische allgemeinbildende Schule umfasst zwölf Schuljahre. Für die  neunjährige Grundbildung in der allgemeinen Schule besteht Schulpflicht.  Sie ist unterteilt in eine vierjährige Primarstufe und eine fünfjährige  untere Sekundarstufe. Daran kann sich ein dreijähriger erweiterter  Bildungsgang in der oberen Sekundarstufe anschließen. Das Eintrittsalter  in die allgemeine Schule liegt bei sieben Jahren.<br /><br />Es wird ein  zehnstufiges Bewertungssystem verwendet, wobei die Noten 9 und 10 nur  für herausragende Leistungen vergeben werden. Englisch wird ab der  dritten Klasse unterrichtet, eine zweite Fremdsprache kommt in der  sechsten Klasse hinzu. Mehr als die Hälfte aller Absolventen der  allgemeinen Schule besucht die obere Sekundarstufe, wobei vier  unterschiedliche Richtungen gewählt werden können:</p><ul><li>ein allgemeines Profil ohne Schwerpunkt in einer bestimmten Fächergruppe; </li><li>ein naturwissenschaftlicher Schwerpunkt; </li><li>ein geisteswissenschaftlicher Schwerpunkt oder </li><li>ein berufsfeldbezogener Schwerpunkt.</li></ul><p>Pflichtfächer  unabhängig vom Schwerpunkt sind: Lettische Sprache und Literatur,  Mathematik, Geschichte, eine Fremdsprache, Sport, angewandte Informatik  und Grundlagen der Wirtschaft. Hinzu kommen schwerpunktbezogene  Pflichtfächer und Wahlfächer, die ca. 25 % des Unterrichts ausmachen.  Für alle Unterrichtsfächer wird Grundkursniveau und Leistungskursniveau  angeboten. Die Schüler müssen sich in mindestens einem Fach für das  Leistungskursniveau entscheiden. Um das Zeugnis über den erfolgreichen  Besuch der oberen Sekundarstufe zu erwerben, müssen fünf  Abschlussprüfungen durchlaufen werden (Lettische Sprache und Literatur,  ein jährlich wechselndes, zentral vorgegebenes Prüfungsfach und drei von  den Schülern zu wählende Fächer). Es werden zentral einheitliche  Prüfungen durchgeführt. Bei Zensuren in wenigstens zwölf Fächern nicht  unter der Note 4 berechtigt das Abgangszeugnis der zwölften Klasse zum  Studium an Universitäten und Hochschulen. Die in Lettland seit 1999 an  der allgemeinbildenden oberen Sekundarstufe erworbene Hochschulreife  wird in Deutschland uneingeschränkt als Studienzugangsberechtigung  anerkannt. </p></div>';





	public function setUp() {
		if (TYPO3_VERSION_INTEGER >= 6002000) {
			$GLOBALS['TSFE']->csConvObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_cs');
		} else {
			$GLOBALS['TSFE']->csConvObj = t3lib_div::makeInstance('t3lib_cs');
		}
	}

	public function tearDown() {
		unset($GLOBALS['TSFE']->csConvObj);
	}





	/**
	 * Test if the words in array are found in string
	 *
	 * @test
	 */
	public function checkIsArrayOfWordsInString() {
		if (TYPO3_VERSION_INTEGER >= 6002000) {
			$lib = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_lib');
		} else {
			$lib = t3lib_div::makeInstance('tx_kesearch_lib');
		}
		$sr = new tx_kesearch_lib_searchresult($lib);

		// check if one word can be found
		$wordArray = array(
			'Abgangszeugnis'
		);
		$result = $sr->isArrayOfWordsInString($wordArray, $this->content);
		$this->assertEquals(TRUE, $result);

		// check if some of the words in the array can be found
		// there are some valid words in this array
		$wordArray = array(
			'Abgangszeugnis',
			'Schwerpunkt',
			'Wahlfächer',
			'Trallala'
		);
		$result = $sr->isArrayOfWordsInString($wordArray, $this->content);
		$this->assertEquals(TRUE, $result);

		// check if some of the words in the array can be found
		// no word in this array is valid
		$wordArray = array(
			'Abgangszaugnis',
			'Schwärpunkt',
			'Wahlfaecher',
			'Trallala'
		);
		$result = $sr->isArrayOfWordsInString($wordArray, $this->content);
		$this->assertEquals(FALSE, $result);

		// check if all of the words in the array can be found
		$wordArray = array(
			'Abgangszeugnis',
			'Schwerpunkt',
			'Wahlfächer',
			'Trallala'
		);
		$result = $sr->isArrayOfWordsInString($wordArray, $this->content, TRUE);
		$this->assertEquals(FALSE, $result);
	}

	/**
	 * Test if highlighting works correct
	 *
	 * @test
	 */
	public function checkHighlightArrayOfWordsInContent() {
		if (TYPO3_VERSION_INTEGER >= 6002000) {
			$lib = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_lib');
		} else {
			$lib = t3lib_div::makeInstance('tx_kesearch_lib');
		}

		$lib->conf['resultChars'] = 300;
		$lib->swords = array(
			'Abgangszeugnis'
		);

		if (TYPO3_VERSION_INTEGER >= 6002000) {
			$lib->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tslib_cObj');
		} else {
			$lib->cObj = t3lib_div::makeInstance('tslib_cObj');
		}
		$sr = new tx_kesearch_lib_searchresult($lib);

		// highlight one word
		$wordArray = array(
			'Abgangszeugnis'
		);
		$result = $sr->highlightArrayOfWordsInContent($wordArray, $this->content);
		$this->assertContains('<span class="hit">', $result);

		// highlight one word
		// but in this case the word doesn't exists
		$wordArray = array(
			'Abgangszaugnis'
		);
		$result = $sr->highlightArrayOfWordsInContent($wordArray, $this->content);
		$this->assertNotContains('<span class="hit">', $result);

		// highlight a word in a teaser
		$wordArray = array(
			'Abgangszeugnis'
		);
		$result = $sr->highlightArrayOfWordsInContent($wordArray, $sr->buildTeaserContent($this->content));
		$this->assertContains('<span class="hit">', $result);
	}

	/**
	 * Test if building the teaser works correct
	 *
	 * @test
	 */
	public function checkBuildTeaserContent() {
		if (TYPO3_VERSION_INTEGER >= 6002000) {
			$lib = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_lib');
		} else {
			$lib = t3lib_div::makeInstance('tx_kesearch_lib');
		}
		$lib->conf['resultChars'] = 300;
		$lib->swords = array(
			'Abgangszeugnis'
		);

		if (TYPO3_VERSION_INTEGER >= 6002000) {
			$lib->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tslib_cObj');
		} else {
			$lib->cObj = t3lib_div::makeInstance('tslib_cObj');
		}
		$sr = new tx_kesearch_lib_searchresult($lib);

		// test without highlighting
		$result = $sr->buildTeaserContent($this->content);
		$resultShouldBe = '... Fächer). Es werden zentral einheitliche Prüfungen durchgeführt. Bei Zensuren in wenigstens zwölf Fächern nicht unter der Note 4 berechtigt das Abgangszeugnis der zwölften Klasse zum Studium an Universitäten und Hochschulen. Die in Lettland seit 1999 an der allgemeinbildenden oberen Sekundarstufe...';
		// in some cases there are many spaces one after the other which makes this assertion fail.
		// That's why I replace them with single spaces
		$result = preg_replace('/[ ]+/', ' ', $result);
		$resultShouldBe = preg_replace('/[ ]+/', ' ', $resultShouldBe);
		$this->assertEquals($resultShouldBe, $result);

		// test with highlighting
		$lib->conf['highlightSword'] = TRUE;
		$result = $sr->buildTeaserContent($this->content);
		$resultShouldBe = '... Fächer). Es werden zentral einheitliche Prüfungen durchgeführt. Bei Zensuren in wenigstens zwölf Fächern nicht unter der Note 4 berechtigt das Abgangszeugnis der zwölften Klasse zum Studium an Universitäten und Hochschulen. Die in Lettland seit 1999 an der allgemeinbildenden oberen Sekundarstufe...';
		// in some cases there are many spaces one after the other which makes this assertion fail.
		// That's why I replace them with single spaces
		$result = preg_replace('/[ ]+/', ' ', $result);
		$resultShouldBe = preg_replace('/[ ]+/', ' ', $resultShouldBe);
		$this->assertEquals($resultShouldBe, $result);
	}
}
?>