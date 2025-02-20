class CTypeManager {

    switchIdentifier = '[data-switch]';
    mainSwitchIdentifier = '[data-target="js-all"]';
    switches = null;
    mainSwitch = null;

    constructor() {
        const that = this;
        that.mainSwitch = document.querySelector(that.mainSwitchIdentifier);
        that.switches = document.querySelectorAll(that.switchIdentifier);

        for (let switchElement of that.switches) {
            switchElement.addEventListener('click', function(){
                let currentState = switchElement.checked === true;
                const targetId = switchElement.getAttribute('data-target');
                const options = document.querySelectorAll('.' + targetId);

                for(let option of options) {
                    option.checked = currentState;
                }

                that.updateMainSwitch();
            });
        }
    };

    updateMainSwitch() {
        let states = {};

        for (let switchElement of that.switches) {
            states.push(switchElement.checked);
        }

        /**
         * each switch can either be true, false or indeterminate https://css-tricks.com/indeterminate-checkboxes/
         * 
         * all == true: main = true
         * all == false : main == false
         * one == indeterminate = main = indeterminate
         */
    };
}

export default new CTypeManager();