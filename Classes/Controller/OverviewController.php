<?php

namespace CReifenscheid\CtypeManager\Controller;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

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
 * Class OverviewController
 *
 * @package \CReifenscheid\CtypeManager\Controller\
 */
class OverviewController extends ActionController
{
    /**
     * Page repository
     *
     * @var \TYPO3\CMS\Core\Domain\Repository\PageRepository
     */
    private $pageRepository;
    
    /**
     * Constructor
     *
     * @params \TYPO3\CMS\Core\Domain\Repository\PageRepository $pageRepository
     */
    public function __construct(PageRepository $pageRepository) 
    {
        $this->pageRepository = $pageRepository;
    }
    
    /**
     * Index action
     *
     * @return void
     */
    public function indexAction() : void
    {
        // querybuilder pages all like ctype manager
        
        $this->view->assign('pages', $pages);
    }
}