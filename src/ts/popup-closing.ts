document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  if (window.opener) {
    const redirect = '' !== window.location.search ? '&order_payed' : '?order_payed';
    window.opener.location = window.location.href + redirect;
    window.close();
  }
});
