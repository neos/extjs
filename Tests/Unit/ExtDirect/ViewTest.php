<?php
namespace TYPO3\ExtJS\Tests\Unit\ExtDirect;

/*                                                                        *
 * This script belongs to the FLOW3 package "ExtJS".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the ExtDirect View
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ViewTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function assignErrorsConvertsErrorsToExtJSFormat() {
		$propertyError = new \TYPO3\FLOW3\Error\Error('Some error', 12345678);

		$errors = new \TYPO3\FLOW3\Error\Result();
		$errors->forProperty('title')->addError($propertyError);

		$expected = array(
			'errors' => array(
				'title' => 'Some error'
			),
			'success' => FALSE
		);
		$mockResponse = $this->getMock('TYPO3\ExtJS\ExtDirect\TransactionResponse');
		$mockResponse->expects($this->atLeastOnce())->method('setResult')->with($expected);

		$mockControllerContext = $this->getMock('TYPO3\FLOW3\MVC\Controller\ControllerContext', array('getResponse'), array(), '', FALSE);
		$mockControllerContext->expects($this->any())->method('getResponse')->will($this->returnValue($mockResponse));

		$view = $this->getMock('TYPO3\ExtJS\ExtDirect\View', array('loadConfigurationFromYamlFile'));
		$view->setControllerContext($mockControllerContext);

		$view->expects($this->any())->method('loadConfigurationFromYamlFile')->will($this->returnValue(array()));

		$view->assignErrors($errors);

		$view->render();
	}
}
?>