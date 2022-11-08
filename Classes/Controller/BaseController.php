<?php

namespace CReifenscheid\CtypeManager\Controller;

use CReifenscheid\CtypeManager\Service\ConfigurationService;
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
     * Uid of currently chosen page
     */
    protected ?int $pageUid = null;

    protected string $shortName = '';

    /**
     * Controller a request came from, to get back to it after the process has finished
     */
    protected string $sourceController = '';

    protected ModuleTemplateFactory $moduleTemplateFactory;

    protected ModuleTemplate $moduleTemplate;

    protected PageRenderer $pageRenderer;

    protected ConfigurationService $configurationService;

    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        PageRenderer $pageRenderer,
        ConfigurationService $configurationService
    ) {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->pageRenderer = $pageRenderer;
        $this->configurationService = $configurationService;

        $reflect = new ReflectionClass($this);
        $this->shortName = $reflect->getShortName();
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    protected function initializeAction() : void
    {
        parent::initializeAction();
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/CtypeManager/CtypeManager');

        // generate the dropdown menu
        $this->buildMenu($this->shortName);

        // definition of currently chosen page uid
        if ($this->request->hasArgument('pageUid')) {
            $this->pageUid = (int)$this->request->getArgument('pageUid');
        } elseif (array_key_exists('id', $this->request->getQueryParams())) {
            $this->pageUid = $this->request->getQueryParams()['id'];
        }

        // source controller definition
        $this->sourceController = $this->request->hasArgument('sourceController') && !empty($this->request->getArgument('sourceController')) ? $this->request->getArgument('sourceController') : str_replace('Controller', '', $this->shortName);
    }

    protected function buildMenu(string $currentController) : void
    {
        $this->uriBuilder->setRequest($this->request);

        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('CtypeManagerModuleMenu');

        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$this->request->getControllerExtensionName()]['modules'][$this->request->getPluginName()]['controllers'] as $configuredController) {
            $alias = $configuredController['alias'];

            $menu->addMenuItem(
                $menu->makeMenuItem()
                    ->setTitle(LocalizationUtility::translate('LLL:EXT:ctype_manager/Resources/Private/Language/locallang_mod.xlf:section.' . strtolower($alias)))
                    ->setHref($this->uriBuilder->uriFor('index', null, $alias))
                    ->setActive($currentController === $alias . 'Controller')
            );
        }

        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }
}