
import { Controller } from '@hotwired/stimulus';

export default class AlertMessage extends Controller
{
    static targets = ['alert'];

    connect() {
        console.log('hello alert');
    }

    close() {

    }
}