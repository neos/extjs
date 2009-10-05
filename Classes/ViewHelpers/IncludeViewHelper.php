<?php
declare(ENCODING = 'utf-8');
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
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class IncludeViewHelper extends \F3\Fluid\Core\ViewHelper\AbstractViewHelper {

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
	 * <ext:include theme="xtheme-gray-extend"/>
	 * </code>
	 *
	 * @param string $theme The theme to include, simply the name of the CSS
	 * @param boolean $debug Whether to use the debug version of ExtJS
	 * @return string HTML needed to include ExtJS
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function render($theme = 'xtheme-blue', $debug = FALSE) {
		$output = '
<link rel="stylesheet" href="Resources/Packages/ExtJS/CSS/ext-all-notheme.css" />
<link rel="stylesheet" href="Resources/Packages/ExtJS/CSS/' . $theme . '.css" />';
		if ($debug) {
			$output .= '
<script type="text/javascript" src="Resources/Packages/ExtJS/JavaScript/adapter/ext/ext-base-debug.js"></script>
<script type="text/javascript" src="Resources/Packages/ExtJS/JavaScript/ext-all-debug.js"></script>';
		} else {
			$output .= '
<script type="text/javascript" src="Resources/Packages/ExtJS/JavaScript/adapter/ext/ext-base.js"></script>
<script type="text/javascript" src="Resources/Packages/ExtJS/JavaScript/ext-all.js"></script>';
		}
		$output .= '
<script type="text/javascript">
	Ext.BLANK_IMAGE_URL = \'Resources/Packages/ExtJS/images/default/s.gif\';
	Ext.FlashComponent.EXPRESS_INSTALL_URL = \'Resources/Packages/ExtJS/Flash/expressinstall.swf\';
	Ext.chart.Chart.CHART_URL = \'Resources/Packages/ExtJS/Flash/chart.swf\';
</script>
';

		return $output;
	}
}

?>
