define([
    'jquery'
], function ($) {
    'use strict';

    let CtypeManager = {};
    
    CtypeManager.switch = function (element) {
        const elementObject = $(element);
    };
    
    CtypeManager.updateSwitch = function () {
    
    };

    // expose to global
    TYPO3.CtypeManager = CtypeManager;

    return CtypeManager;
});