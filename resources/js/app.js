import './bootstrap';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import { initBattingCreatePage } from './batting/create-page';
import { initBattingEditPage } from './batting/edit-page';
import { initOrderEditPage } from './order/edit-page';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const bootPageScripts = () => {
    initBattingCreatePage();
    initBattingEditPage();
    initOrderEditPage();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootPageScripts, { once: true });
} else {
    bootPageScripts();
}
