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
        CtypeManager.setButtonState(elementObject, currentState)

        // toggle targets
        $('.' + targetId).each(function(){
            $(this).prop('checked', !currentState)

            // toggle element state
            CtypeManager.setElementState(this)
        });

        CtypeManager.update()
    };
    
    CtypeManager.update = function (element) {

        // toggle element state
        CtypeManager.setElementState(element)

        $('.js-btn-switch').each(function(){
            let state = true
            let targetId = $(this).data('target')

            $('.' + targetId).each(function(){
                if ($(this).prop('checked') === false) {
                    state = false
                    return false
                }
            });

            CtypeManager.setButtonState($(this), state)
        });
    };

    CtypeManager.setButtonState = function (button, state) {
        if (state) {
            button.attr('aria-pressed', 'true')
            button.addClass('on')
        } else {
            button.attr('aria-pressed', 'false')
            button.removeClass('on')
        }
    }
    
    CtypeManager.setElementState = function (element) {
        if ($(element).prop('checked')) {
            $(element).parent().addClass('enabled-ctype')
        } else {
            $(element).parent().removeClass('enabled-ctype')
        }
    }

    CtypeManager.init = function() {
        console.log('Hi Mom!')
    };

    CtypeManager.init()

    // expose to global
    TYPO3.CtypeManager = CtypeManager;

    return CtypeManager;
});