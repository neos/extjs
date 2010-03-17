<?php
declare(ENCODING = 'utf-8');
namespace F3\ExtJS\ViewHelpers\ExtDirect;

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
 * @version $Id: IncludeViewHelper.php 3736 2010-01-20 15:47:11Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @api
 */
class ProviderViewHelper extends \F3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $myReflectionService;

	/**
	 * Inject the FLOW3 reflection service.
	 *
	 * @param \F3\FLOW3\Reflection\ReflectionService $reflectionService
	 */
	public function injectMyReflectionService(\F3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->myReflectionService = $reflectionService;
	}

	/**
	 * Returns the JavaScript to declare the Ext Direct provider for all
	 * controller actions that are annotated with \@extdirect
	 *
	 * = Examples =
	 *
	 * <code title="Simple">
	 * {namespace ext=F3\ExtJS\ViewHelpers}
	 *  ...
	 * <ext:extdirect.provider />
	 * </code>
	 *
	 * TODO Cache ext direct provider config
	 * @param string $namespace The base ExtJS namespace (with dots) for the direct provider methods
	 * @return string JavaScript needed to include Ext Direct provider
	 * @api
	 */
	public function render($namespace = 'F3') {
		$providerConfig = array(
			'url' => '?F3_ExtJS_ExtDirectRequest=1',
			'type' => 'remoting',
			'namespace' => $namespace,
			'actions' => array()
		);
		$controllerClassNames = $this->myReflectionService->getAllImplementationClassNamesForInterface('F3\FLOW3\MVC\Controller\ControllerInterface');
		foreach ($controllerClassNames as $controllerClassName) {
			$methodNames = $this->myReflectionService->getClassMethodNames($controllerClassName);
			foreach ($methodNames as $methodName) {
				$methodTagsValues = $this->myReflectionService->getMethodTagsValues($controllerClassName, $methodName);
				if (isset($methodTagsValues['extdirect'])) {
					$methodParameters = $this->myReflectionService->getMethodParameters($controllerClassName, $methodName);
					$extDirectAction = str_replace('\\', '_', str_replace('F3\\', '', $controllerClassName));
					$providerConfig['actions'][$extDirectAction][] = array(
						'name' => substr($methodName, 0, -6),
						'len' => count($methodParameters)
					);
				}
			}
		}

		return 'Ext.Direct.addProvider(' . json_encode($providerConfig) . ');' . chr(10);
	}
}

?>
