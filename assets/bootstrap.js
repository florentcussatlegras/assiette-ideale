import { startStimulusApp } from '@symfony/stimulus-bridge';
import { Autocomplete } from 'stimulus-autocomplete';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.(j|t)sx?$/
));

// Load plugins
import { Slideover, Modal } from "tailwindcss-stimulus-components";
import cash from "cash-dom";
import helper from "./helper";
import Velocity from "velocity-animate";
import * as Popper from "@popperjs/core";
import LiveController from '@symfony/ux-live-component';
import '@symfony/ux-live-component/styles/live.css';
import TextareaAutogrow from 'stimulus-textarea-autogrow';
import Notification from '@stimulus-components/notification';
import PasswordVisibility from '@stimulus-components/password-visibility'
import Popover from '@stimulus-components/popover';

// Set plugins globally
window.cash = cash;
window.helper = helper;
window.Velocity = Velocity;
window.Popper = Popper;

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

app.register('live', LiveController);
app.register('password-visibility', PasswordVisibility);
app.register('autocomplete', Autocomplete);
app.register('slideover', Slideover);
app.register('notification', Notification);
app.register('modal', Modal);
app.register('textarea-autogrow', TextareaAutogrow);


// A laisser commenter
// app.register('popover', Popover);
// app.register('alert', Alert);
// app.register('autosave', Autosave);
// // app.register('dropdown', Dropdown);
// // app.register('tabs', Tabs);
// app.register('popover', Popover);
// app.register('toggle', Toggle);
