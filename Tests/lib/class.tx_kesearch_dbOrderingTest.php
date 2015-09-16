<?php
class DatabaseOrderingTest extends Tx_Extbase_BaseTestCase {

	var $conf = array();
	
	/**
	 * @var tx_kesearch_pi1
	 */
	var $pObj;
	
	/**
	 * @var tx_kesearch_lib_div
	 */
	var $div;
	
	
	
	
	
	
	public function setUp() {
		$this->pObj = $this->getMock('tx_kesearch_pi1');
		$this->pObj->expects($this->any())->method('pi_getLL')->will($this->returnValue('Suchbegriff'));
		
		$this->div = new tx_kesearch_lib_div;
	}
	
	public function tearDown() {
		unset($this->pObj);
		unset($this->div);
	}
	
	
	
	
	
	/**
	 * Test ordering if no searchword was given
	 * - test in case of visible in FE
	 * - and not visible in FE
	 *
	 * @test
	 */
	public function checkOrderingWithoutSearchword() {
		$this->pObj->sword = '';
		$this->pObj->conf = array(
			'sortWithoutSearchword' => 'sortdate desc',
			'showSortInFrontend' => false,
			'sortByAdmin' => 'sortdate desc'
		);
		
		$this->pObj->piVars = $this->div->cleanPiVars($this->pObj->piVars);
		
		$db = new tx_kesearch_db($this->pObj);
		
		$this->assertEquals('sortdate desc', $db->getOrdering());

		$this->pObj->sword = '';
		$this->pObj->conf = array(
			'sortWithoutSearchword' => 'sortdate desc',
			'showSortInFrontend' => true,
			'sortByAdmin' => 'sortdate desc'
		);
		
		$this->pObj->piVars = $this->div->cleanPiVars($this->pObj->piVars);
		
		$db = new tx_kesearch_db($this->pObj);
		
		$this->assertEquals('sortdate desc', $db->getOrdering());
	}
	
	
	/**
	 * Test ordering if a searchword was given
	 * - show sorting in FE is forbidden
	 * - admin presorts the result
	 *
	 * @test
	 */
	public function checkOrderingWithSearchwordPresortedByAdmin() {
		$this->pObj->sword = 'Hallo';
		$this->pObj->conf = array(
			'sortWithoutSearchword' => 'tstamp asc',
			'showSortInFrontend' => false,
			'sortByAdmin' => 'sortdate desc'
		);
		
		$this->pObj->piVars = $this->div->cleanPiVars($this->pObj->piVars);
		
		$db = new tx_kesearch_db($this->pObj);
		
		$this->assertEquals('sortdate desc', $db->getOrdering());
	}
	
	
	/**
	 * Test ordering if a searchword was given
	 * - show sorting in FE is allowed
	 * - admin presorts are uninteresting
	 * - FE-User is allowed to choose between sortdate,tstamp and title
	 * - no piVars are given
	 *
	 * @test
	 */
	public function checkOrderingWithSearchwordAndUserCanSortWithoutPiVars() {
		$this->pObj->sword = 'Hallo';
		$this->pObj->conf = array(
			'sortWithoutSearchword' => 'tstamp asc',
			'showSortInFrontend' => true,
			'sortByVisitor' => 'sortdate,tstamp,title',
			'sortByAdmin' => 'sortdate desc'
		);
		
		$this->pObj->piVars = $this->div->cleanPiVars($this->pObj->piVars);
		
		$db = new tx_kesearch_db($this->pObj);
		
		$this->assertEquals('tstamp asc', $db->getOrdering());
	}
	
	
	/**
	 * Test ordering if a searchword was given
	 * - show sorting in FE is allowed
	 * - admin presorts are uninteresting
	 * - FE-User is allowed to choose between sortdate,tstamp and title
	 * - unallowed piVars are given
	 *
	 * @test
	 */
	public function checkOrderingWithSearchwordAndUserCanSortWithUnallowedPiVars() {
		$this->pObj->sword = 'Hallo';
		$this->pObj->conf = array(
			'sortWithoutSearchword' => 'tstamp asc',
			'showSortInFrontend' => true,
			'sortByVisitor' => 'sortdate,tstamp,title',
			'sortByAdmin' => 'sortdate desc'
		);
		$this->pObj->piVars = array(
			'orderByField' => 'content',
			'orderByDir' => 'asc',
		);
		
		$this->pObj->piVars = $this->div->cleanPiVars($this->pObj->piVars);
		
		$db = new tx_kesearch_db($this->pObj);
		
		$this->assertEquals('tstamp asc', $db->getOrdering());
	}
	
	
	/**
	 * Test ordering if a searchword was given
	 * - show sorting in FE is allowed
	 * - admin presorts are uninteresting
	 * - FE-User is allowed to choose between sortdate,tstamp and title
	 * - piVars are given but orderDir is wrong
	 *
	 * @test
	 */
	public function checkOrderingWithSearchwordAndUserCanSortWithUnallowedPiVarForDirection() {
		$this->pObj->sword = 'Hallo';
		$this->pObj->conf = array(
			'sortWithoutSearchword' => 'tstamp asc',
			'showSortInFrontend' => true,
			'sortByVisitor' => 'sortdate,tstamp,title',
			'sortByAdmin' => 'sortdate desc'
		);
		$this->pObj->piVars = array(
			'orderByField' => 'title',
			'orderByDir' => 'trallala',
		);
		
		$this->pObj->piVars = $this->div->cleanPiVars($this->pObj->piVars);
		
		$db = new tx_kesearch_db($this->pObj);
		
		// orderdirections fallback is "asc"
		$this->assertEquals('title asc', $db->getOrdering());
	}
	
	
	/**
	 * Test ordering if a searchword was given
	 * - show sorting in FE is allowed
	 * - admin presorts are uninteresting
	 * - FE-User is allowed to choose between sortdate,tstamp and title
	 * - allowed piVars are given
	 *
	 * @test
	 */
	public function checkOrderingWithSearchwordAndUserCanSortWithAllowedPiVars() {
		$this->pObj->sword = 'Hallo';
		$this->pObj->conf = array(
			'sortWithoutSearchword' => 'tstamp asc',
			'showSortInFrontend' => true,
			'sortByVisitor' => 'sortdate,tstamp,title',
			'sortByAdmin' => 'sortdate desc'
		);
		$this->pObj->piVars = array(
			'orderByField' => 'title',
			'orderByDir' => 'asc',
		);
		
		$this->pObj->piVars = $this->div->cleanPiVars($this->pObj->piVars);
		
		$db = new tx_kesearch_db($this->pObj);
		
		$this->assertEquals('title asc', $db->getOrdering());
	}
	
}
?>