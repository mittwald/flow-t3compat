<?php
namespace Mw\T3Compat\Plugin;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Mw.T3Compat".           *
 *                                                                        *
 * (C) 2015 Martin Helmich <m.helmich@mittwald.de>                        *
 *          Mittwald CM Service GmbH & Co. KG                             *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ControllerContext;

/**
 * Wrapper class for plugins.
 *
 * Allows plugin classes to be called from an ordinary action controller.
 *
 * @package    Mw\T3Compat
 * @subpackage Plugin
 */
class PluginWrapper {

	/**
	 * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
	 */
	private $controllerContext;

	public function setControllerContext(ControllerContext $context) {
		$this->controllerContext = $context;
	}

	public function wrapPlugin(AbstractPlugin $plugin, array $configuration = array()) {
		$content = '';
		$plugin->__plugin_initialize($configuration, $this->controllerContext->getRequest());

		if (is_callable([$plugin, 'main'])) {
			$oldErrorReporting = error_reporting();
			error_reporting($oldErrorReporting & ~E_NOTICE);

			$content = $plugin->main($content, $configuration);

			error_reporting($oldErrorReporting);
		}

		return $content;
	}

}