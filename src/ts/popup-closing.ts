document.addEventListener('DOMContentLoaded', () => {
  if (window.opener) {
    window.opener.location = window.location;
    window.close();
  }
});
