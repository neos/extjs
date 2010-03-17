<?php
declare(ENCODING = 'utf-8');
namespace F3\ExtJS\ExtDirect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
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
 * Testcase for the ExtDirect Request Handler
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestHandlerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function canHandleRequestReturnsTrueIfTheSapiTypeIsWebAndAnExtDirectGetParameterIsSent() {
		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment');
		$mockEnvironment->expects($this->at(0))->method('getRawGetArguments')->will($this->returnValue(array('foo' => 'bar', 'baz' => 'quux')));
		$mockEnvironment->expects($this->at(1))->method('getRawGetArguments')->will($this->returnValue(array('foo' => 'bar', 'F3_ExtJS_ExtDirectRequest' => '1')));

		$requestHandler = $this->getAccessibleMock('F3\ExtJS\ExtDirect\RequestHandler', array('dummy'), array(), '', FALSE);
		$requestHandler->_set('environment', $mockEnvironment);
		
		$this->assertFalse($requestHandler->canHandleRequest());
		$this->assertTrue($requestHandler->canHandleRequest());
	}
}
?>