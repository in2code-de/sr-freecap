<?php
defined('TYPO3_MODE') or die();

call_user_func(
    function($extKey)
    {
		/**
		 * Registers a Backend Module
		 */
		// GDlib is a requirement for the Font Maker module
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']) {
			\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
				\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($extKey),
				// Make module a submodule of 'tools'
				'tools',
				// Submodule key
				'FontMaker',
				// Position
				'',
				// An array holding the controller-action combinations that are accessible
				[
					\SJBR\SrFreecap\Controller\FontMakerController::class => 'new,create'
				],
				[
					'access' => 'user,group',
					'icon' => 'EXT:sr_freecap/Resources/Public/Icons/Extension.svg',
					'labels' => 'LLL:EXT:sr_freecap/Resources/Private/Language/locallang_mod.xlf'
				]
			);
			// Add module configuration setup
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($extKey, 'setup', '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $extKey . '/Configuration/TypoScript/FontMaker/setup.typoscript">');
		}
	},
	'sr_freecap'
);