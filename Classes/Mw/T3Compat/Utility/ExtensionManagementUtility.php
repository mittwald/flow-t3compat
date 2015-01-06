<?php
namespace Mw\T3Compat\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Mw.T3Compat".           *
 *                                                                        *
 * (C) 2015 Martin Helmich <m.helmich@mittwald.de>                        *
 *          Mittwald CM Service GmbH & Co. KG                             *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Package\PackageInterface;
use TYPO3\Flow\Package\PackageManagerInterface;

/**
 * Compatibility interface for the old `t3lib_extMgm` class.
 *
 * Where possible, maps calls to TYPO3 Flow's PackageManager class.
 *
 * @package    Mw\T3Compat
 * @subpackage Utility
 */
class ExtensionManagementUtility {

	/**
	 * @var PackageManagerInterface
	 * @Flow\Inject
	 */
	protected $packageManager;

	/**
	 * Determines if a package is active.
	 *
	 * @param string $key         The package (extension) key
	 * @param bool   $exitOnError TRUE to throw an exception when the package is not active
	 * @return bool TRUE when the package is active
	 * @throws \Exception
	 */
	public function isLoaded($key, $exitOnError = FALSE) {
		$result = $this->packageManager->isPackageActive($key);
		if ($exitOnError === TRUE && $result === FALSE) {
			throw new \Exception("Package {$key} was not loaded!");
		}
		return $result;
	}

	/**
	 * Gets the package path.
	 *
	 * @param string $key The package key
	 * @return string The package path
	 */
	public function extRelPath($key) {
		return $this->extPath($key);
	}

	/**
	 * Gets the package path.
	 *
	 * @param string $key    The package key
	 * @param string $script Relative filename inside the package path
	 * @return string The package path
	 */
	public function extPath($key, $script = '') {
		$package = $this->packageManager->getPackage($key);
		if (NULL === $package) {
			return NULL;
		}

		return $package->getPackagePath() . $script;
	}

	/**
	 * Gets the publicly accessible path of this package.
	 *
	 * This is not entirely compatible, but this method returns a path to the
	 * package's resource root. That's as good as it gets.
	 *
	 * @param string $key The package key
	 * @return string The public package path
	 */
	public function siteRelPath($key) {
		return "_Resources/Static/{$key}";
	}

	/**
	 * Gets the namespace root by a package key.
	 *
	 * @param string $key The package key
	 * @return string The namespace root
	 */
	public function getCN($key) {
		$package = $this->packageManager->getPackage($key);
		if (NULL !== $package) {
			return $package->getNamespace();
		} else {
			return NULL;
		}
	}

	/**
	 * Gets the package key by a namespace root.
	 *
	 * @param string $prefix The namespace root
	 * @return string The package key
	 */
	public function getExtensionKeyByPrefix($prefix) {
		return str_replace('\\', '.', $prefix);
	}

	/**
	 * Obsolete
	 *
	 * @deprecated
	 */
	public function clearExtensionKeyMap() { }

	/**
	 * Obsolete
	 *
	 * @deprecated
	 */
	public function getExtensionVersion($key) { }

	/**
	 * Obsolete
	 *
	 * @deprecated
	 */
	public function removeCacheFiles() { }

	/**
	 * Gets a list of the package keys of all active packages.
	 *
	 * @return array A list of the package keys of all active packages
	 */
	public function getLoadedExtensionListArray() {
		return array_map(
			function (PackageInterface $p) {
				return $p->getPackageKey();
			},
			$this->packageManager->getActivePackages()
		);
	}

	/**
	 * Gets a list of the package keys of all framework packages.
	 *
	 * @return array A list of the package keys of all framework packages
	 */
	public function getRequiredExtensionListArray() {
		return array_map(
			function (PackageInterface $p) {
				return $p->getPackageKey();
			},
			$this->packageManager->getFilteredPackages('active', 'Framework')
		);
	}
}
