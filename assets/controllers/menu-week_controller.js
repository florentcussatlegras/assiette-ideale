import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static values = {
        url: String
    }

    async reloadMenu(event) {
        const btnReload = event.currentTarget;
        const format = btnReload.dataset.format;

        const params = new URLSearchParams({
            'startingDate': btnReload.dataset.startingDate,
            'ajax': 1,
            'format': format,
        });

        document.getElementById('titleMenuWeek').style.opacity = .5;
        document.getElementById('tableMenuWeek').style.opacity = .5;
        const response = await fetch(`${this.urlValue}?${params.toString()}`);
        if(format === 'mobile') {
            document.getElementById('wrapperMenuWeekMobile').innerHTML = await response.text();
        }else{
            document.getElementById('wrapperMenuWeekDesktop').innerHTML = await response.text();
        }
        document.getElementById('titleMenuWeek').style.opacity = 1;
        document.getElementById('tableMenuWeek').style.opacity = 1;
    }

}