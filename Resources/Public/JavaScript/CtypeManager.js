define([
    'jquery'
], function ($) {
    'use strict';

    let CtypeManager = {};
    
    CtypeManager.switch = function (element) {
        const elementObject = $(element)
        let currentState = elementObject.attr('aria-pressed') === 'true'
        let targetId = elementObject.data('target')

        // toggle button state
        elementObject.attr('aria-pressed', currentState ? 'false' : 'true')
        elementObject.toggleClass('active')

        // toggle targets
        $('.' + targetId).each(function(){
            $(this).prop('checked', !currentState);
        });
    };
    
    CtypeManager.updateSwitch = function () {
    
    };

    // expose to global
    TYPO3.CtypeManager = CtypeManager;

    return CtypeManager;
});