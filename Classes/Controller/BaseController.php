<?php

namespace CReifenscheid\CtypeManager\Controller;

use ReflectionClass;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2022 Christian Reifenscheid
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
 * Class BaseController
 *
 * @package \CReifenscheid\CtypeManager\Controller
 */
class BaseController extends ActionController
{
    /**
     * Configuration identifier
     */
    protected const CONFIG_ID = 'ctype-manager';

    /**
     * ModuleTemplateFactory
     *
     * @var \TYPO3\CMS\Backend\Template\ModuleTemplateFactory
     */
    protected ModuleTemplateFactory $moduleTemplateFactory;

    /**
     * ModuleTemplate
     *
     * @var \TYPO3\CMS\Backend\Template\ModuleTemplate
     */
    protected ModuleTemplate $moduleTemplate;

    /**
     * Page renderer
     *
     * @var \TYPO3\CMS\Core\Page\PageRenderer
     */
    protected PageRenderer $pageRenderer;

    /**
     * Constructor
     *
     * @param \TYPO3\CMS\Backend\Template\ModuleTemplateFactory $moduleTemplateFactory
     *
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     */
    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        PageRenderer $pageRenderer
    ) {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->pageRenderer = $pageRenderer;
    }

    /**
     * Initialize action
     *
     * @return void
     */
    public function initializeAction() : void
    {
        parent::initializeAction();
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        // load requireJS modules
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/CtypeManager/CtypeManager');

        // drop down menu
        $reflect = new ReflectionClass($this);
        $this->buildMenu($reflect->getShortName());
    }

    /**
     * Drop down menu
     *
     * @param string $currentController
     */
    protected function buildMenu(string $currentController) : void
    {
        $this->uriBuilder->setRequest($this->request);

        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('CtypeManagerModuleMenu');

        // CtypeController
        $menu->addMenuItem(
            $menu->makeMenuItem()
                ->setTitle(LocalizationUtility::translate('LLL:EXT:ctype_manager/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'))
                ->setHref($this->uriBuilder->uriFor('index', null, 'Ctype'))
                ->setActive($currentController === 'CtypeController')
        );

        // Overview controller
        $menu->addMenuItem(
            $menu->makeMenuItem()
                ->setTitle(LocalizationUtility::translate('LLL:EXT:ctype_manager/Resources/Private/Language/locallang_mod.xlf:section.overview'))
                ->setHref($this->uriBuilder->uriFor('index', null, 'Overview'))
                ->setActive($currentController === 'OverviewController')
        );

        // Cleanup controller
        $menu->addMenuItem(
            $menu->makeMenuItem()
                ->setTitle(LocalizationUtility::translate('LLL:EXT:ctype_manager/Resources/Private/Language/locallang_mod.xlf:section.cleanup'))
                ->setHref($this->uriBuilder->uriFor('index', null, 'Cleanup'))
                ->setActive($currentController === 'CleanupController')
        );

        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }
}