<?php

namespace CReifenscheid\CtypeManager\Utility;

use TYPO3\CMS\Core\Utility\RootlineUtility;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2022 C. Reifenscheid
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 ***************************************************************/

/**
 * Class GeneralUtility
 *
 * @package \CReifenscheid\CtypeManager\Utility\
 */
class GeneralUtility
{
    public static function getRootline(int $uid) : array
    {
        $rootlineUtility = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(RootlineUtility::class, $uid);

        return $rootlineUtility->get();
    }
    
    /**
     * Returns all configured ctypes
     *
     * @return array
     */
    public static function getTcaCtypes() : array
    {
        return $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];
    }

    /**
     * Returns all configured ctype groups
     *
     * @return array
     */
    public static function getTcaCtypeGroups() : array
    {
        return $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemGroups'];
    }
}
