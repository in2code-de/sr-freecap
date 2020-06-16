<?php
namespace SJBR\SrFreecap\Http;

/*
 * Copyright notice
 *
 * 2010 Daniel Lienert <daniel@lienert.cc>, Michael Knoll <mimi@kaktusteam.de>
 * 2012-2020 Stanislas Rolland <typo32020(arobas)sjbr.ca>
 * All rights reserved
 *
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Error\Http\BadRequestException;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Extbase\Mvc\Dispatcher;
use TYPO3\CMS\Extbase\Mvc\Web\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Response;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Dispatch the eid request
 */
class EidDispatcher
{
	/**
	 * Array of all request Arguments
	 *
	 * @var array
	 */
	protected $requestArguments = [];

	/**
	 * Extbase Object Manager
	 * @var ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var string
	 */
	protected $vendorName = 'SJBR';

	/**
	 * @var string
	 */
	protected $extensionName = 'SrFreecap';

	/**
	 * @var string
	 */
	protected $pluginName;

	/**
	 * @var string
	 */
	protected $controllerName;

	/**
	 * @var string
	 */
	protected $actionName;

	/**
	 * @var string
	 */
	protected $formatName;

	/**
	 * @var array
	 */
	protected $arguments = [];

	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		$this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
	}

	/**
	 * Initializes and dispatches actions
	 *
	 * Call this function if you want to use this dispatcher "standalone"
	 */
	public function initAndDispatch()
	{
		return $this->initTypoScriptConfiguration()
			->initLanguage()
			->initCallArguments()
			->dispatch();
	}

	/**
	 * Builds an extbase context and returns the response
	 *
	 */
	protected function dispatch()
	{
		$bootstrap = $this->objectManager->get(Bootstrap::class);
		$configuration['vendorName'] = $this->vendorName;
		$configuration['extensionName'] = $this->extensionName;
		$configuration['pluginName'] = $this->pluginName;
		$bootstrap->initialize($configuration);
		$request = $this->buildRequest();
		$response = $this->objectManager->get(Response::class);
		$dispatcher = $this->objectManager->get(Dispatcher::class);
		$dispatcher->dispatch($request, $response);
		try {
			$dispatcher->dispatch($request, $response);
		} catch (\Exception $e) {
			throw new BadRequestException('An argument is missing or invalid', 1394587024);
		}
		// Output was already sent
		return new NullResponse();
	}

	/**
	 * Get the TypoScript configuration
	 *
	 * @return \SJBR\SrFreecap\Http\EidDispatcher
	 */
	protected function initTypoScriptConfiguration()
	{
		$controller = $this->getTypoScriptFrontendController();
		$controller->type = 0;
		$context = GeneralUtility::makeInstance(Context::class);
		$controller->rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $controller->id, $controller->MP, $context)->get();
		$controller->getConfigArray();
		return $this;
	}

	/**
	 * Set  language and locale
	 *
	 * @return \SJBR\SrFreecap\Http\EidDispatcher
	 */
	protected function initLanguage()
	{
		$controller = $this->getTypoScriptFrontendController();
		$controller->settingLanguage();
		$siteLanguage = $controller->getLanguage();
		$locales = GeneralUtility::makeInstance(Locales::class);
		$locales->setSystemLocaleFromSiteLanguage($siteLanguage);
		return $this;
	}

	/**
	 * Build a request object
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Request $request
	 */
	protected function buildRequest()
	{
		$controllerClassName = $this->vendorName . '\\' . $this->extensionName . '\\' . 'Controller' . '\\' . $this->pluginName . 'Controller';
		$request = $this->objectManager->get(RequestInterface::class, $controllerClassName);
		$request->setControllerObjectName($controllerClassName);
		$request->setPluginName($this->pluginName);
		$request->setControllerActionName($this->actionName);
		$request->setFormat($this->formatName);
		$request->setArguments($this->arguments);
		return $request;
	}

	/**
	 * Prepare the call arguments
	 *
	 * @return \SJBR\SrFreecap\Http\EidDispatcher
	 */
	public function initCallArguments() {
		$request = GeneralUtility::_GP('request');
		if ($request) {
			$this->setRequestArgumentsFromJSON($request);
		} else {
			$this->setRequestArgumentsFromGetPost();
		}
		return $this->setPluginName($this->requestArguments['pluginName'])
			->setControllerName()
			->setActionName($this->requestArguments['actionName'])
			->setFormatName($this->requestArguments['formatName'])
			->setArguments($this->requestArguments['arguments']);
	}

	/**
	 * Set the request array from JSON
	 *
	 * @param string $request
	 */
	protected function setRequestArgumentsFromJSON($request)
	{
		$requestArray = json_decode($request, true);
		if (is_array($requestArray)) {
			ArrayUtility::mergeRecursiveWithOverrule($this->requestArguments, $requestArray);
		}
	}

	/**
	 * Set the request array from the getPost array
	 */
	protected function setRequestArgumentsFromGetPost()
	{
		$validArguments = ['pluginName', 'actionName', 'formatName', 'arguments'];
		foreach ($validArguments as $argument) {
			if (GeneralUtility::_GP($argument)) {
				$this->requestArguments[$argument] = GeneralUtility::_GP($argument);
			} else if (GeneralUtility::_GP('amp;' . $argument)) {
				// Something went wrong...
				$this->requestArguments[$argument] = GeneralUtility::_GP('amp;' . $argument);
			} else if ($argument !== 'arguments') {
				throw new BadRequestException('An argument is missing', 1394587023);
			}
		}
	}

	/**
	 * @param string $pluginName
	 * @return \SJBR\SrFreecap\Http\EidDispatcher
	 */
	protected function setPluginName($pluginName = 'ImageGenerator')
	{
		$this->pluginName = htmlspecialchars((string)$pluginName);
		return $this;
	}

	/**
	 * @return \SJBR\SrFreecap\Http\EidDispatcher
	 */
	protected function setControllerName()
	{
		$this->controllerName = $this->pluginName;
		return $this;
	}

	/**
	 * @param string $actionName
	 * @return \SJBR\SrFreecap\Http\EidDispatcher
	 */
	protected function setActionName($actionName = 'show')
	{
		$this->actionName = htmlspecialchars((string)$actionName);
		return $this;
	}

	/**
	 * @param string $formatName
	 * @return \SJBR\SrFreecap\Http\EidDispatcher
	 */
	protected function setFormatName($formatName = 'txt')
	{
		$this->formatName = htmlspecialchars((string)$formatName);
		return $this;
	}

	/**
	 * @param array $arguments
	 * @return \SJBR\SrFreecap\Http\EidDispatcher
	 */
	protected function setArguments($arguments)
	{
		if (!is_array($arguments)) {
			$this->arguments = array();
		} else {
			$this->arguments = $arguments;
		}
		return $this;
	}

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}