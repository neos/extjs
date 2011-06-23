<?php
namespace F3\ExtJS\ViewHelpers;

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
 * Include ExtJS view helper
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @api
 */
class IncludeViewHelper extends \F3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @inject
	 * @var \F3\FLOW3\Resource\Publishing\ResourcePublisher
	 */
	protected $resourcePublisher;

	/**
	 * @inject
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Returns the HTML needed to include ExtJS, that is, CSS and JS includes.
	 *
	 * = Examples =
	 *
	 * <code title="Simple">
	 * {namespace ext=F3\ExtJS\ViewHelpers}
	 *  ...
	 * <ext:include/>
	 * </code>
	 * Renders the script and link tags needed to include everything needed to
	 * use ExtJS.
	 *
	 * <code title="Use a specific theme">
	 * <ext:include theme="gray"/>
	 * </code>
	 *
	 * @param string $theme The theme to include. The part behind ext-all- in the filename.
	 * @param boolean $debug Whether to use the debug version of ExtJS
	 * @param boolean $includeStylesheets Include ExtJS CSS files if true
	 * @return string HTML needed to include ExtJS
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Pascal Jungblut <typo3@pascalj.com>
	 * @api
	 */
	public function render($theme = '', $debug = NULL, $includeStylesheets = TRUE) {
		if ($debug === NULL) {
			$debug = ($this->objectManager->getContext() === 'Development') ?: FALSE;
		}
		$baseUri = $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/ExtJS/';
		$output = '';
		if ($includeStylesheets) {
			if ($theme != '') {
				$theme = '-' . $theme;
			}
			$output .= '
<link rel="stylesheet" href="' . $baseUri . 'CSS/ext-all' . $theme . '.css" />';
		}
		if ($debug) {
			$output .= '
<script type="text/javascript" src="' . $baseUri . 'JavaScript/ext-all-debug.js"></script>';
		} else {
			$output .= '
<script type="text/javascript" src="' . $baseUri . 'JavaScript/ext-all.js"></script>';
		}

		return $output;
	}
}

?>
