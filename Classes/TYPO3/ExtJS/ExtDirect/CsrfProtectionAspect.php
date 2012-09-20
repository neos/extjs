<?php
namespace TYPO3\ExtJS\ExtDirect;

/*                                                                        *
 * This script belongs to the package TYPO3.ExtJS.                        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An aspect which cares for CSRF protection of links used in the ExtDirect service.
 *
 * @FLOW3\Aspect
 */
class CsrfProtectionAspect {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Policy\PolicyService
	 */
	protected $policyService;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Mvc\Routing\RouterInterface
	 */
	protected $router;

	/**
	 * Adds a CSRF token as argument in ExtDirect requests
	 *
	 * @FLOW3\Around("method(TYPO3\ExtJS\ExtDirect\Transaction->buildRequest()) && setting(TYPO3.FLOW3.security.enable)")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return \TYPO3\FLOW3\Mvc\ActionRequest
	 */
	public function transferCsrfTokenToExtDirectRequests(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$extDirectRequest = $joinPoint->getAdviceChain()->proceed($joinPoint);

		$requestHandler = $this->bootstrap->getActiveRequestHandler();
		if ($requestHandler instanceof \TYPO3\FLOW3\Http\HttpRequestHandlerInterface) {
			$arguments = $requestHandler->getHttpRequest()->getArguments();
			if (isset($arguments['__csrfToken'])) {
				$requestArguments = $extDirectRequest->getMainRequest()->getArguments();
				$requestArguments['__csrfToken'] = $arguments['__csrfToken'];
				$extDirectRequest->getMainRequest()->setArguments($requestArguments);
			}
		}

		return $extDirectRequest;
	}
}

?>
