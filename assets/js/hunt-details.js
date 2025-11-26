import { RiddleManager } from "riddle-manager";

class HuntDetailsManager {
    constructor() {
        this.init();
    }

    init() {
        new RiddleManager();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new HuntDetailsManager();
});