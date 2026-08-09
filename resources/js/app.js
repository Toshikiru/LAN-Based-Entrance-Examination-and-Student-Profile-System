import './bootstrap';

// Side-effect import — Turbo starts itself as soon as this module runs,
// same as the CDN build it replaces.
import '@hotwired/turbo';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import Sortable from 'sortablejs';

window.Chart = Chart;
window.Sortable = Sortable;

window.Alpine = Alpine;
Alpine.start();
