<?php
namespace TYPO3\ExtJS\Tests\Unit\ExtDirect;

/*                                                                        *
 * This script belongs to the FLOW3 package "ExtJS".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the ExtDirect Request Handler
 *
 */
class RequestHandlerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var array
	 */
	protected $getSuperglobalBackup;

	public function setUp() {
		$this->getSuperglobalBackup = $_GET;
	}

	public function tearDown() {
		$_GET = $this->getSuperglobalBackup;
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function canHandleRequestReturnsTrueIfTheSapiTypeIsWebAndAnExtDirectGetParameterIsSent() {
		$requestHandler = $this->getAccessibleMock('TYPO3\ExtJS\ExtDirect\RequestHandler', array('sendResponse'), array(), '', FALSE);
		$this->assertFalse($requestHandler->canHandleRequest());
		$_GET['TYPO3_ExtJS_ExtDirectRequest'] = '1';
		$this->assertTrue($requestHandler->canHandleRequest());
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function handleRequestCatchesAndLogsExceptionsAndReturnsThemInTheTransaction() {
		$mockBootstrap = $this->getMock('TYPO3\FLOW3\Core\Bootstrap', array(), array(), '', FALSE);

		$sequence = new \TYPO3\FLOW3\Core\Booting\Sequence();
		$mockBootstrap->expects($this->once())->method('buildRuntimeSequence')->will($this->returnValue($sequence));
		$mockBootstrap->expects($this->once())->method('shutdown');

		$mockRequest = $this->getMock('TYPO3\ExtJS\ExtDirect\Request', array('getTransactions'));
		$mockTransactionRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request');
		$mockTransactionResponse = $this->getMock('TYPO3\FLOW3\MVC\Web\Response');
		$mockTransaction = $this->getMock('TYPO3\ExtJS\ExtDirect\Transaction', array('buildRequest', 'buildResponse'), array($mockRequest, 'someAction', 'someMethod', array(), 42));
		$mockTransaction->expects($this->any())->method('buildRequest')->will($this->returnValue($mockTransactionRequest));
		$mockTransaction->expects($this->any())->method('buildResponse')->will($this->returnValue($mockTransactionResponse));
		$mockRequest->expects($this->any())->method('getTransactions')->will($this->returnValue(array($mockTransaction)));

		$mockRequestBuilder = $this->getMock('TYPO3\ExtJS\ExtDirect\RequestBuilder', array('build'));
		$mockRequestBuilder->expects($this->any())->method('build')->will($this->returnValue($mockRequest));

		$mockDispatcher = $this->getMock('TYPO3\FLOW3\MVC\Dispatcher', array('dispatch'), array(), '', FALSE);
		$mockDispatcher->expects($this->once())->method('dispatch')->will($this->throwException(new \Exception('Foo', 424242)));

		$mockSystemLogger = $this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface');
		$mockSystemLogger->expects($this->once())->method('logException');

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockBootstrap->expects($this->once())->method('getObjectManager')->will($this->returnValue($mockObjectManager));

		$mockConfigurationManager = $this->getMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager
			->expects($this->once())
			->method('getConfiguration')
			->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.ExtJS')
			->will($this->returnValue(array('ExtDirect' => array( 'exposeExceptionInformation' => TRUE))));

		$objectsReturnedByObjectManager = array(
			'TYPO3\ExtJS\ExtDirect\RequestBuilder' => $mockRequestBuilder,
			'TYPO3\FLOW3\MVC\Dispatcher' => $mockDispatcher,
			'TYPO3\FLOW3\Log\SystemLoggerInterface' => $mockSystemLogger,
			'TYPO3\FLOW3\Configuration\ConfigurationManager' => $mockConfigurationManager
		);

		$mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback(function($objectName) use ($objectsReturnedByObjectManager) {
			if (isset($objectsReturnedByObjectManager[$objectName])) {
				return $objectsReturnedByObjectManager[$objectName];
			}
			throw new \Exception(sprintf('Object "%s" was not registered in the mock object manager', $objectName));
		}));

		$expectedResponse = array(
			array(
				'type' => 'exception',
				'tid' => 42,
				'message' => 'Uncaught exception #424242',
				'where' => ''
			)
		);

		$requestHandler = $this->getAccessibleMock('TYPO3\ExtJS\ExtDirect\RequestHandler', array('sendResponse'), array($mockBootstrap), '', TRUE);
		$requestHandler
			->expects($this->once())
			->method('sendResponse')
			->with($expectedResponse, $mockRequest);
		$requestHandler->handleRequest();
	}
}
?>