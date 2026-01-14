import { Controller } from '@hotwired/stimulus';

export default class extends Controller
{
    connect() {
        // script to open the modal
        var openmodal = document.querySelectorAll('.modal-nav-search-open')
        for (var i = 0; i < openmodal.length; i++) {
          openmodal[i].addEventListener('click', function(event){
            event.preventDefault()
            toggleModal()
            //focus the search bar
            document.getElementById("input--search-bar").focus();
          })
        }
        
        const overlay = document.querySelector('.modal-nav-search-overlay')
        overlay.addEventListener('click', toggleModal)
        
        var closemodal = document.querySelectorAll('.modal-nav-search-close')
        for (var i = 0; i < closemodal.length; i++) {
          closemodal[i].addEventListener('click', toggleModal)
        }
        
        document.onkeydown = function(evt) {
          evt = evt || window.event
          var isEscape = false
          if ("key" in evt) {
          isEscape = (evt.key === "Escape" || evt.key === "Esc")
          } else {
          isEscape = (evt.keyCode === 27)
          }
          if (isEscape && document.body.classList.contains('modal-nav-search-active')) {
          toggleModal()
          }
        };
        
        
        function toggleModal () {
          const body = document.querySelector('body')
          const modal = document.querySelector('.modal-nav-search')
          modal.classList.toggle('opacity-0')
          modal.classList.toggle('pointer-events-none')
          body.classList.toggle('modal-nav-search-active')
        }
    }
}