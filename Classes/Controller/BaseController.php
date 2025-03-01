<?php

namespace CReifenscheid\CtypeManager\Controller;

use CReifenscheid\CtypeManager\Service\ConfigurationService;
use ReflectionClass;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
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

    protected ?Typo3Version $typo3Version = null;

    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        PageRenderer $pageRenderer,
        protected readonly IconFactory $iconFactory,
        ConfigurationService $configurationService,
    ) {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->pageRenderer = $pageRenderer;
        $this->configurationService = $configurationService;

        $reflect = new ReflectionClass($this);
        $this->shortName = $reflect->getShortName();

        $this->typo3Version = new Typo3Version();
    }

    /**
     * @throws NoSuchArgumentException
     */
    protected function initializeAction(): void
    {
        parent::initializeAction();
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->pageRenderer->loadJavaScriptModule('@creifenscheid/ctype-manager/ctype-manager.js');

        // generate the dropdown menu
        $this->buildMenu($this->shortName);

        if (\method_exists($this, 'setDocHeader')) {
            $this->setDocHeader();
        }

        // definition of currently chosen page uid
        if ($this->request->hasArgument('pageUid')) {
            $this->pageUid = (int)$this->request->getArgument('pageUid');
        } elseif (array_key_exists('id', $this->request->getQueryParams())) {

            $id = $this->request->getQueryParams()['id'];
            if (\str_contains($id, '_')) {
                $_id = GeneralUtility::trimExplode('_', $id);
                $id = end($_id);
            }

            $this->pageUid = (int)$id;
        }

        // source controller definition
        $this->sourceController = $this->request->hasArgument('sourceController') && !empty($this->request->getArgument('sourceController')) ? $this->request->getArgument('sourceController') : str_replace('Controller', '', $this->shortName);
    }

    protected function buildMenu(string $currentController): void
    {
        $this->uriBuilder->setRequest($this->request);

        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier($this->request->getControllerExtensionName() . 'ModuleMenu');

        $moduleControllerActions = $this->request->getAttribute('module')->getControllerActions();

        foreach ($moduleControllerActions as $configuredController) {
            $alias = $configuredController['alias'];

            $menu->addMenuItem(
                $menu->makeMenuItem()
                    ->setTitle(LocalizationUtility::translate('LLL:EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored($this->request->getControllerExtensionName()) . '/Resources/Private/Language/locallang_mod.xlf:section.' . strtolower((string)$alias)))
                    ->setHref($this->uriBuilder->uriFor('index', null, $alias))
                    ->setActive($currentController === $alias . 'Controller')
            );
        }

        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }
}
