document.addEventListener("DOMContentLoaded", function () {
  if (window.opener) {
    window.opener.location = window.location;
    window.close();
  }
});
