(($) => $(() => {

  const $window = $(window);
  const $body = $(document.body);
  const redirection = { receipt: '' };
  // We create the frame, and set some of their attribute before wrapping it in jQuery.
  const $frame = $(document.createElement('iframe'))
    .addClass('stancer-iframe')
    .attr('allow', 'payment')
    .attr('sandbox', 'allow-scripts allow-forms allow-same-origin allow-top-navigation');
  /*
  * We set allow = payment; we want to authorize paymentAPI in our Iframe
  * We set sandbox = allow-scripts ; we need it because we use javascript in the payment page.
  * We set sandbox = allow-forms;  we need it because we send a form in our Iframe.
  * We set sandbox = top-navigation; we need it to be able to interact with context outside our iframe, more precisely to get the event.data and use it.
  */
  const $backdrop = $(document.createElement('div')).addClass('stancer-backdrop');

  const stancer_SVG = '<svg stancer-flat></svg>';


  /**
   * Close our Iframe and hide the backdrop.
   */
  const closeIframe = (): void => {
    $body.removeClass('stancer-block-scroll');
    $backdrop.detach().addClass('stancer-backdrop--hidden');
    $('.js-stancer-place-order').removeAttr('disabled');
    $frame.detach();
  };

  /**
   * Setup the Style of our Iframe with the information send by the PP.
   *
   * @param event
   * @returns void
   */
  const iframe = (event: JQuery.TriggeredEvent): void => {
    //We know our triggered events have a originalEvent of type MessageEvent
    const { data } = event.originalEvent as MessageEvent;

    // We use some of the api data structure to test if the message is a stancer payment message.
    if (typeof data.status === "undefined" || typeof data.width === "undefined" || typeof data.height === "undefined") {
      return;
    }
    if (window.stancer_paymentMethodHasBeenChanged(data)) {
      closeIframe();
      return
    }

    if (data.status === 'finished' && redirection.receipt != '') {
      window.postMessage({ stopRedirection: true });
      window.location.href = redirection.receipt;
      closeIframe();
      return;
    }

    const maxHeight = window.innerHeight ?? 100;
    const maxWidth = window.innerWidth ?? 100;
    let height = 400;
    let radius = 10;
    let width = 400;

    if (data.status === 'secure-auth-start') {
      height = maxHeight;
      width = maxWidth;
    } else if (!['error', 'init', 'secure-auth-end', 'secure-auth-error'].includes(data.status)) {
      height = data.height;
      width = data.width;
    }

    if (height >= maxHeight) {
      height = maxHeight;
      radius = 0;
    }

    if (width >= maxWidth) {
      width = maxWidth;
      radius = 0;
    }

    document.body.style.setProperty('--stancer-iframe-height', `${height}px`);
    document.body.style.setProperty('--stancer-iframe-width', `${width}px`);
    document.body.style.setProperty('--stancer-iframe-border-radius', `${radius}px`);
  };

  /**
   * Create the iframe and it's backdrop.
   * @param result
   */
  const stancer_iframe = (result: CheckoutResponseSuccess | CheckoutResponseReact) => {
    const $logo = $(document.createElement('div')).addClass('stancer-logo').append(stancer_SVG);

    $backdrop
      .removeClass('stancer-backdrop--hidden')
      .append($logo)
      .on('click', closeIframe)
    ;

    $frame.attr('src', result.redirect);

    $body
      .addClass('stancer-block-scroll')
      .append($backdrop, $frame)

    redirection.receipt = result.receipt;

  }

  /** Window listeners to open or close our iframe.*/
  $window
    .on('message', (e) => iframe(e))
    .on('keydown', (event) => {
      if (event.code === 'Escape') {
        closeIframe();
      }
    });

  //EXPORT
  window.stancer_iframe = stancer_iframe;

}))(jQuery);
