<?php
namespace SJBR\SrFreecap\Domain\Repository;

/*
 *  Copyright notice
 *
 *  (c) 2012-2021 Stanislas Rolland <typo3AAAA(arobas)sjbr.ca>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */

use SJBR\SrFreecap\Domain\Model\Word;
use SJBR\SrFreecap\Domain\Session\SessionStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Word repository in session storage
 */
class WordRepository extends Repository
{
	/**
	 * The session sorage handler
	 * @var SessionStorage
	 */
	protected $sessionStorage;

	/**
	 * Constructor
	 */
	public function __construct(SessionStorage $sessionStorage)
	{
		// Get an instance of the session storage handler
		$this->sessionStorage = $sessionStorage;
	}
 
	/**
	 * Returns the object stored in the user's session
	 *
	 * @return Word the stored object
	 */
	public function getWord()
	{
		$word = $this->sessionStorage->restoreFromSession();
		// If no Word object is found in session data, initialize a new one
		if (!is_object($word)) {
			$word = new Word();
		}
		return $word;
	}
 
	/**
	 * Writes the object into the user's session
	 *
	 * @param Word the object to be stored
	 * @return WordRepository
	 */
	public function setWord(Word $object)
	{
		$this->sessionStorage->writeToSession($object);
		return $this;
	}
 
	/**
	 * Cleans up the session: removes the stored object from the user's session
	 *
	 * @return WordRepository
	 */
	public function cleanUpWord()
	{
		$this->sessionStorage->cleanUpSession();
		return $this;
	}
}