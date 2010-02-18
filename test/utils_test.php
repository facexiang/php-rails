<?php

require_once('../I18n.php');

use I18n\Utils;

class Utils_Test extends PHPUnit_Framework_TestCase
{
	public function test_array_flatten()
	{
		$this->assertEquals(array(), I18n\array_flatten(array()));
		$this->assertEquals(array(1), I18n\array_flatten(array(1)));
		$this->assertEquals(array(1), I18n\array_flatten(array(array(1))));
		$this->assertEquals(array(1, 2), I18n\array_flatten(array(array(1, 2))));
		$this->assertEquals(array(1, 2), I18n\array_flatten(array(array(1), 2)));
		$this->assertEquals(array(1, 2), I18n\array_flatten(array(1, array(2))));
		$this->assertEquals(array(1, 2, 3), I18n\array_flatten(array(1, array(2), 3)));
		$this->assertEquals(array(1, 2, 3, 4), I18n\array_flatten(array(1, array(2, 3), 4)));
		$this->assertEquals(array(1, 2, 3, 4, 5), I18n\array_flatten(array(array(1, 2, 3), array(4, 5))));
	}
}
?>