<?php
namespace TYPO3\ExtJS\ViewHelpers\ExtDirect;

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
 * Ext Direct Provider view helper
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @api
 */
class ProviderViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $localReflectionService;

	/**
	 * Inject the Reflection service
	 *
	 * A _private_ property "reflectionService" already exists in the AbstractViewHelper,
	 * therefore we need to switch to another property name.
	 *
	 * @param \TYPO3\FLOW3\Reflection\ReflectionService $reflectionService Reflection service
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function injectLocalReflectionService(\TYPO3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->localReflectionService = $reflectionService;
	}

	/**
	 * Injects the security context
	 *
	 * @param \TYPO3\FLOW3\Security\Context $securityContext The security context
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectSecurityContext(\TYPO3\FLOW3\Security\Context $securityContext) {
		$this->securityContext = $securityContext;
	}

	/**
	 * Returns the JavaScript to declare the Ext Direct provider for all
	 * controller actions that are annotated with @extdirect
	 *
	 * = Examples =
	 *
	 * <code title="Simple">
	 * {namespace ext=TYPO3\ExtJS\ViewHelpers}
	 *  ...
	 * <script type="text/javascript">
	 * <ext:extdirect.provider />
	 * </script>
	 *  ...
	 * </code>
	 *
	 * TODO Cache ext direct provider config
	 * @param string $namespace The base ExtJS namespace (with dots) for the direct provider methods
	 * @return string JavaScript needed to include Ext Direct provider
	 * @api
	 */
	public function render($namespace = NULL) {
		$providerConfig = array(
			'url' => '?TYPO3_ExtJS_ExtDirectRequest=1&__csrfToken=' . $this->securityContext->getCsrfProtectionToken(),
			'type' => 'remoting',
			'actions' => array()
		);
		if (!empty($namespace)) {
			$providerConfig['namespace'] = $namespace;
		}
		$controllerClassNames = $this->localReflectionService->getAllImplementationClassNamesForInterface('TYPO3\FLOW3\MVC\Controller\ControllerInterface');
		foreach ($controllerClassNames as $controllerClassName) {
			$methodNames = get_class_methods($controllerClassName);
			foreach ($methodNames as $methodName) {
				$methodTagsValues = $this->localReflectionService->getMethodTagsValues($controllerClassName, $methodName);
				if (isset($methodTagsValues['extdirect'])) {
					$methodParameters = $this->localReflectionService->getMethodParameters($controllerClassName, $methodName);
					$requiredMethodParametersCount = 0;
					foreach ($methodParameters as $methodParameter) {
						if ($methodParameter['optional'] === TRUE) {
							break;
						}
						$requiredMethodParametersCount ++;
					}
					$extDirectAction = str_replace('\\', '_', $controllerClassName);

					$providerConfig['actions'][$extDirectAction][] = array(
						'name' => substr($methodName, 0, -6),
						'len' => $requiredMethodParametersCount
					);
				}
			}
		}

		return 'Ext.Direct.addProvider(' . json_encode($providerConfig) . ');' . chr(10);
	}
}

?>