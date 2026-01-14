var myInput = document.getElementById("psw");
var letter = document.getElementById("letter");
var capital = document.getElementById("capital");
var number = document.getElementById("number");
var length = document.getElementById("length");

// When the user clicks on the password field, show the message box
// myInput.onfocus = function() {
//   document.getElementById("message").style.display = "block";
// }

// When the user clicks outside of the password field, hide the message box
// myInput.onblur = function() {
//   document.getElementById("message").style.display = "none";
// }

// When the user starts to type something inside the password field
if (myInput) {
  myInput.onkeyup = function() {
    // Validate lowercase letters
    var lowerCaseLetters = /[a-z]/g;
    if(myInput.value.match(lowerCaseLetters)) {
      letter.classList.remove("invalid-password");
      letter.classList.add("valid-password");
    } else {
      letter.classList.remove("valid-password");
      letter.classList.add("invalid-password");
  }

    // Validate capital letters
    var upperCaseLetters = /[A-Z]/g;
    if(myInput.value.match(upperCaseLetters)) {
      capital.classList.remove("invalid-password");
      capital.classList.add("valid-password");
    } else {
      capital.classList.remove("valid-password");
      capital.classList.add("invalid-password");
    }

    // Validate numbers
    var numbers = /[0-9]/g;
    if(myInput.value.match(numbers)) {
      number.classList.remove("invalid-password");
      number.classList.add("valid-password");
    } else {
      number.classList.remove("valid-password");
      number.classList.add("invalid-password");
    }

    // Validate length
    if(myInput.value.length >= 8) {
      length.classList.remove("invalid-password");
      length.classList.add("valid-password");
    } else {
      length.classList.remove("valid-password");
      length.classList.add("invalid-password");
    }
  }
}