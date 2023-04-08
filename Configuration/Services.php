<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use CReifenscheid\CtypeManager\Controller\CleanupController;
use CReifenscheid\CtypeManager\Controller\ConfigurationController;
use CReifenscheid\CtypeManager\Controller\OverviewController;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void {
    $services = $containerConfigurator->services();
    $services->defaults()
        ->private()
        ->autowire()
        ->autoconfigure();

    $services->load('CReifenscheid\\CtypeManager\\', __DIR__ . '/../Classes/');
    
    $services->set(CleanupController::class)
        ->tag('backend.controller');
        
    $services->set(ConfigurationController::class)
        ->tag('backend.controller');
        
    $services->set(OverviewController::class)
        ->tag('backend.controller'); 
};
