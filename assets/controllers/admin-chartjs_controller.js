import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        console.log('chart');
    }

    onChartConnect(event) {
        this.chart = event.detail.chart;
        console.log(this.chart);
    }
}