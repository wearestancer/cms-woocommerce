document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  if (window.opener) {
    window.opener.location = window.location;
    window.close();
  }
});
