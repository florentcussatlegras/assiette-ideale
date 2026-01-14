import { useDispatch } from "stimulus-use";
import { Modal } from "tailwindcss-stimulus-components"

export default class CustomModal extends Modal {

  connect() {
    super.connect();
    // useDispatch(this);
  }

  open() {
      super.open();
      // this.dispatch('open');
  }
}