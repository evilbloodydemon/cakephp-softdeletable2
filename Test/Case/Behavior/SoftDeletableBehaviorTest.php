<?php
/**
 * Test cases for SoftDeletable Behavior
 * 
 * @filesource
 * @author Mariano Iglesias
 * @author Igor Fomin (evilbloodydemon@gmail.com)
 * @link http://github.com/evilbloodydemon/cakephp-softdeletable2
 * @license	http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Base model that to load SoftDeletable behavior on every test model.
 */
class SoftDeletableTestModel extends CakeTestModel {
	/**
	 * Behaviors for this model
	 *
	 * @var array
	 * @access public
	 */
	var $actsAs = array(
		'SoftDeletable2.SoftDeletable',
		'Containable',
	);
	var $recursive = -1;
}

/**
 * Model used in test case.
 */
class DeletableArticle extends SoftDeletableTestModel {
	/**
	 * Name for this model
	 *
	 * @var string
	 * @access public
	 */
	var $name = 'DeletableArticle';

	/**
	 * hasMany releations for this model
	 *
	 * @var array
	 * @access public
	 */
	var $hasMany = array('DeletableComment' => array('dependent' => true));
}

/**
 * Model used in test case.
 */
class DeletableComment extends SoftDeletableTestModel {
	/**
	 * Name for this model
	 *
	 * @var string
	 * @access public
	 */
	var $name = 'DeletableComment';

	/**
	 * belongsTo releations for this model
	 *
	 * @var array
	 * @access public
	 */
	var $belongsTo = array(
		'DeletableArticle' => array(
			'counterCache' => true,
		)
	);
}

/**
 * Test case for SoftDeletable Behavior
 *
 * @property DeletableArticle $DeletableArticle
 */
class SoftDeletableTestCase extends CakeTestCase {
	/**
	 * Fixtures associated with this test case
	 *
	 * @var array
	 * @access public
	 */
	var $fixtures = array('plugin.soft_deletable2.deletable_article', 'plugin.soft_deletable2.deletable_comment');

	/**
	 * Method executed before each test
	 *
	 * @access public
	 */
	function startTest() {
		$this->DeletableArticle = ClassRegistry::init('DeletableArticle');
	}

	/**
	 * Method executed after each test
	 *
	 * @access public
	 */
	function endTest() {
		unset($this->DeletableArticle);
		ClassRegistry::flush();
	}

	/**
	 * Test beforeFind callback
	 *
	 * @access public
	 */
	function testBeforeFind() {
		$SoftDeletable =& new SoftDeletableBehavior();
		$SoftDeletable->setup($this->DeletableArticle);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array());
		$expected = array('conditions' => array('DeletableArticle.deleted !=' => '1'));
		$this->assertEqual($result, $expected);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => array('DeletableArticle.id >' => '0', 'or' => array('DeletableArticle.title' => 'Title', 'DeletableArticle.id' => '5'))));
		$expected = array('conditions' => array('DeletableArticle.id >' => '0', 'or' => array('DeletableArticle.title' => 'Title', 'DeletableArticle.id' => '5'), 'DeletableArticle.deleted !=' => '1'));
		$this->assertEqual($result, $expected);

		unset($SoftDeletable);
	}

	/**
	 * Test soft delete
	 *
	 * @access public
	 */
	function testFind() {
		$this->DeletableArticle->delete(2);
		$this->DeletableArticle->delete(3);

		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$result = $this->DeletableArticle->find('all', array('conditions' => array('DeletableArticle.deleted' => 0), 'fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->enableSoftDeletable(false);
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article', 'deleted' => 1
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 1
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->enableSoftDeletable(true);
	}

	/**
	 * Test soft delete
	 *
	 * @access public
	 */
	function testSoftDelete() {
		$this->DeletableArticle->delete(2);

		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->enableSoftDeletable(false);
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article', 'deleted' => 1
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 0
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->enableSoftDeletable(true);
	}

	/**
	 * Test hard delete
	 *
	 * @access public
	 */
	function testHardDelete() {
		$result = $this->DeletableArticle->hardDelete(2);
		$this->assertTrue($result);

		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->enableSoftDeletable(false);
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 0
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->enableSoftDeletable(true);
	}

	/**
	 * Test soft delete
	 *
	 * @access public
	 */
	function testPurge() {
		$this->DeletableArticle->delete(2);

		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->enableSoftDeletable(false);
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article', 'deleted' => 1
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 0
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->enableSoftDeletable(true);

		$this->DeletableArticle->delete(3);

		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->enableSoftDeletable(false);
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article', 'deleted' => 1
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 1
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->enableSoftDeletable(true);

		$result = $this->DeletableArticle->purge();
		$this->assertTrue($result);

		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->enableSoftDeletable(false);
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->enableSoftDeletable(true);
	}

	/**
	 * Test undelete
	 *
	 * @access public
	 */
	function testUndelete() {
		$this->DeletableArticle->delete(2);

		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$result = $this->DeletableArticle->undelete(2);
		$this->assertTrue($result);

		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);
	}

	/**
	 * Test recursivity when soft deleting records
	 *
	 * @access public
	 */
	function testRecursive() {
		$result = $this->DeletableArticle->DeletableComment->find('all', array('fields' => array('id', 'comment')));
		$expected = array(
			array('DeletableComment' => array(
				'id' => 1, 'comment' => 'First Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 2, 'comment' => 'Second Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 3, 'comment' => 'Third Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 4, 'comment' => 'Fourth Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 5, 'comment' => 'First Comment for Second Article'
			)),
			array('DeletableComment' => array(
				'id' => 6, 'comment' => 'Second Comment for Second Article'
			)),
			array('DeletableComment' => array(
				'id' => 7, 'comment' => 'First Comment for Third Article'
			)),
			array('DeletableComment' => array(
				'id' => 8, 'comment' => 'Second Comment for Third Article'
			)),
			array('DeletableComment' => array(
				'id' => 9, 'comment' => 'Third Comment for Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->delete(2);

		$result = $this->DeletableArticle->DeletableComment->find('all', array('fields' => array('id', 'comment')));
		$expected = array(
			array('DeletableComment' => array(
				'id' => 1, 'comment' => 'First Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 2, 'comment' => 'Second Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 3, 'comment' => 'Third Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 4, 'comment' => 'Fourth Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 7, 'comment' => 'First Comment for Third Article'
			)),
			array('DeletableComment' => array(
				'id' => 8, 'comment' => 'Second Comment for Third Article'
			)),
			array('DeletableComment' => array(
				'id' => 9, 'comment' => 'Third Comment for Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->DeletableComment->enableSoftDeletable(false);
		$result = $this->DeletableArticle->DeletableComment->find('all', array('fields' => array('id', 'comment', 'deleted')));
		$expected = array(
			array('DeletableComment' => array(
				'id' => 1, 'comment' => 'First Comment for First Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 2, 'comment' => 'Second Comment for First Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 3, 'comment' => 'Third Comment for First Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 4, 'comment' => 'Fourth Comment for First Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 5, 'comment' => 'First Comment for Second Article', 'deleted' => 1
			)),
			array('DeletableComment' => array(
				'id' => 6, 'comment' => 'Second Comment for Second Article', 'deleted' => 1
			)),
			array('DeletableComment' => array(
				'id' => 7, 'comment' => 'First Comment for Third Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 8, 'comment' => 'Second Comment for Third Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 9, 'comment' => 'Third Comment for Third Article', 'deleted' => 0
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->DeletableComment->enableSoftDeletable(true);
	}

	function testDeletedDate() {
		$this->DeletableArticle->delete(1);
		$this->DeletableArticle->enableSoftDeletable(false);
		$result = $this->DeletableArticle->read(null, 1);
		$this->assertPattern('/\\d{4}-\\d{2}-\\d{2}/', $result['DeletableArticle']['deleted_date']);
		$this->DeletableArticle->enableSoftDeletable(true);
	}

	function testDeletedDateInt() {
		$this->DeletableArticle->Behaviors->detach('SoftDeletable');
		$this->DeletableArticle->Behaviors->attach('SoftDeletable', array(
			'field_date' => 'deleted_date_int',
		));
		$this->DeletableArticle->delete(1);
		$this->DeletableArticle->enableSoftDeletable(false);
		$result = $this->DeletableArticle->read(null, 1);
		$this->assertPattern('/\\d{10}/', $result['DeletableArticle']['deleted_date_int']);
		$this->DeletableArticle->enableSoftDeletable(true);
	}

	function testCounterCache() {
		$this->DeletableArticle->DeletableComment->delete(1);
		$result = $this->DeletableArticle->read(null, 1);
		$this->assertEqual($result['DeletableArticle']['deletable_comment_count'], 3);
	}
}

?>