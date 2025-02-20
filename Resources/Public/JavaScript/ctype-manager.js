class CTypeManager {

    mainToggleIdentifier = '[data-toggle-all]';
    groupToggleIdentifier = '[data-toggle]';
    ctypeToggleIdentifier = '[data-group]';
    mainToggle = null;
    groupToggles = null;
    ctypeToggles = null;

    constructor() {
        this.mainToggle = document.querySelector(this.mainToggleIdentifier);
        this.groupToggles = document.querySelectorAll(this.groupToggleIdentifier);
        this.ctypeToggles = document.querySelectorAll(this.ctypeToggleIdentifier);

        if (this.mainToggle === null || this.groupToggles === null || this.ctypeToggles === null) {
            return;
        }

        this.initMainToggle();
        this.initGroupToggles();
        this.initCTypeToggles();
    };

    initMainToggle() {
        let that = this;

        this.mainToggle.addEventListener('click', function() {
            this.indeterminate = false;

            for (let ctypeToggle of that.ctypeToggles) {
                ctypeToggle.checked = this.checked;
            }

            for (let groupToggle of that.groupToggles) {
                groupToggle.checked = this.checked;
                groupToggle.indeterminate = false;
            }
        });
    };

    updateMainToggle() {
        let states = [];

        for (let groupToggle of this.groupToggles) {

            states.push(groupToggle.checked);

            if (groupToggle.indeterminate) {
                this.mainToggle.indeterminate = true;
                this.mainToggle.checked = false;
                return;
            }
        }

        let result = Array.from(new Set(states));
        
        if (result.length === 1) {
            this.mainToggle.checked = result[0];
            this.mainToggle.indeterminate = false;

            return;
        }

        this.mainToggle.indeterminate = true;
        this.mainToggle.checked = false;
    };

    initGroupToggles() {
        let that = this;

        for (let groupToggle of this.groupToggles) {
            groupToggle.addEventListener('click', function(){
                let currentState = groupToggle.checked === true;
                const target = groupToggle.getAttribute('data-target');
                const options = document.querySelectorAll('[data-group="' + target + '"]');

                for(let option of options) {
                    option.checked = currentState;
                }

                that.updateMainToggle();
            });
        }
    };

    updateGroupToggle(group) {
        let states = [];
        const groupToggleIdentifier = '[data-target="' + group + '"]';
        const groupCTypeIdentifier = '[data-group="' + group + '"]';
        const groupToggle = document.querySelector(groupToggleIdentifier);
        const groupCTypeToggles = document.querySelectorAll(groupCTypeIdentifier);

        for (let groupCTypeToggle of groupCTypeToggles) {
            states.push(groupCTypeToggle.checked);
        }

        let result = Array.from(new Set(states));
        
        if (result.length === 1) {
            groupToggle.checked = result[0];
            groupToggle.indeterminate = false;

            this.updateMainToggle();

            return;
        }

        groupToggle.checked = false;
        groupToggle.indeterminate = true;

        this.updateMainToggle();
    }

    initCTypeToggles() {
        const that = this;

        for (let ctypeToggle of this.ctypeToggles) {
            ctypeToggle.addEventListener('click', function() {
                const group = ctypeToggle.getAttribute('data-group');
                that.updateGroupToggle(group);
            });
        }
    };
}

export default new CTypeManager();