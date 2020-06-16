<?php
namespace SJBR\SrFreecap\Utility;

/*
 *  Copyright notice
 *
 *  (c) 2009 Sebastian Kurfürst <sebastian@typo3.org>
 *  (c) 2013-2020 Stanislas Rolland <typo32020(arobas)sjbr.ca>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Localization helper which should be used to fetch appropriate words list or voice rendering language
 *
 */
class LocalizationUtility
{
	/**
	 * Key of the extension to which this class belongs
	 *
	 * @var string
	 */
	protected static $extensionKey = 'sr_freecap';

	/**
	 * Gets the location of the words list based on configured language
	 *
	 * @param string $defaultWordsList: location of the default words list
	 * @return string the location of the words list to be used
	 */
	public static function getWordsListLocation($defaultWordsList = '')
	{
		$languageKeys = static::getLanguageKeys();
		$initialWordsList = $defaultWordsList;
		if (!trim($initialWordsList)) {
			$initialWordsList = 'EXT:' . self::$extensionKey . '/Resources/Private/Captcha/Words/default_freecap_words';
		}
		$path = dirname(GeneralUtility::getFileAbsFileName($initialWordsList)) . '/';
		$wordsListLocation = $path . $languageKeys['languageKey'] . '_freecap_words';
		if (!is_file($wordsListLocation)) {
			foreach ($languageKeys['alternativeLanguageKeys'] as $language) {
				$wordsListLocation = $path . $language . '_freecap_words';
				if (is_file($wordsListLocation)) {
					break;
				}
			}
		}
		if (!is_file($wordsListLocation)) {
			$wordsListLocation = $path . 'default_freecap_words';
			if (!is_file($wordsListLocation)) {
				$wordsListLocation = '';
			}
		}
		return $wordsListLocation;
	}

	/**
	 * Gets the directory of wav files based on configured language
	 *
	 * @return string name of the directory containing the wav files to be used
	 */
	public static function getVoicesDirectory()
	{
		$languageKeys = static::getLanguageKeys();
		$path = ExtensionManagementUtility::extPath(self::$extensionKey) . 'Resources/Private/Captcha/Voices/';
		$voicesDirectory = $path . $languageKeys['languageKey'] . '/';
		if (!is_dir($voicesDirectory)) {
			foreach ($languageKeys['alternativeLanguageKeys'] as $language) {
				$voicesDirectory = $path . $language . '/';
				if (is_dir($voicesDirectory)) {
					break;
				}
			}
		}
		if (!is_dir($voicesDirectory)) {
			$voicesDirectory = $path . 'default/';
		}
		return $voicesDirectory;
	}

    /**
     * Sets the currently active language/language_alt keys.
     * Default values are "default" for language key and an empty array for language_alt key.
     *
     * @return array
     */
    protected static function getLanguageKeys(): array
    {
        $languageKeys = [
            'languageKey' => 'default',
            'alternativeLanguageKeys' => [],
        ];
        if (TYPO3_MODE === 'FE') {
            $tsfe = static::getTypoScriptFrontendController();
            $pageId = $tsfe->id;
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $currentSite = $siteFinder->getSiteByPageId($pageId);
            $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');
            // Current language
            $currentSiteLanguageId = $languageAspect->getId();
            $currentSiteLanguage = $currentSite->getLanguageById((int)$currentSiteLanguageId);
            $languageKeys['languageKey'] = $currentSiteLanguage->getTypo3Language();
            // Alternative languages
			$alternativeLanguageIds = $languageAspect->getFallbackChain();
			foreach ($alternativeLanguageIds as $alternativeLanguageId) {
				$alternativeLanguage = $currentSite->getLanguageById((int)$alternativeLanguageId);
				$languageKeys['alternativeLanguageKeys'][] = $alternativeLanguage->getTypo3Language();
			}
            if (empty($languageKeys['alternativeLanguageKeys'])) {
                $locales = GeneralUtility::makeInstance(Locales::class);
                if (in_array($languageKeys['languageKey'], $locales->getLocales())) {
                    foreach ($locales->getLocaleDependencies($languageKeys['languageKey']) as $language) {
                        $languageKeys['alternativeLanguageKeys'][] = $language;
                    }
                }
            }
        } else {
            $languageKeys['languageKey'] = $GLOBALS['BE_USER']->uc['lang'];
        }
        return $languageKeys;
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected static function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}