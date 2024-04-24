(($) => $(() => {
  'use strict';

  interface CheckoutResponseBase {
    card?: string;
    messages?: string;
    refresh: boolean;
    reload: boolean;
  }

  interface CheckoutResponseFailure extends CheckoutResponseBase {
    result: 'failure';
  }

  interface CheckoutResponseSuccess extends CheckoutResponseBase {
    order_id: number;
    receipt: string;
    redirect: string;
    result: 'success';
  }

  interface MessageData {
    height: number;
    status: 'error' | 'finished' | 'init' | 'secure-auth-end' | 'secure-auth-error' | 'secure-auth-start';
    url: string & Location;
    width: number;
  }


  type CheckoutResponse = CheckoutResponseFailure | CheckoutResponseSuccess;

  const redirection = {receipt: ''};
  const $window = $(window);
  const $body = $(document.body);
  const $backdrop = $(document.createElement('div')).addClass('stancer-backdrop');
  // We create the frame, and set some of their attribute before wrapping it in jQuery.
  const $frame = $(document.createElement('iframe'))
  .addClass('stancer-iframe')
  .attr('allow','payment')
  .attr('sandbox', 'allow-scripts allow-forms allow-same-origin allow-top-navigation');
  /*
  * We set allow = payment; we want to authorize paymentAPI in our Iframe
  * We set sandbox = allow-scripts ; we need it because we use javascript in the payment page.
  * We set sandbox = allow-forms;  we need it because we send a form in our Iframe.
  * We set sandbox = top-navigation; we need it to be able to interact with context outside our iframe, more precisely to get the event.data and use it.
  */
  const $stancer_payment_method = $('#payment_method_stancer');
  const $cardSelect = $('#stancer-card');
  const params = Object.fromEntries(window.location.search.slice(1).split('&').map((value) => value.split('=')));
  const STANCER_SVG = '<svg:stancer-flat>';
  let messageCallback: (data: MessageData) => boolean = () => false;

  if ($cardSelect.selectWoo) {
    $cardSelect.selectWoo({
      minimumResultsForSearch: Infinity,
      width: '100%',
    });
  }

  const close = () => {
    $body.removeClass('stancer-block-scroll');
    $backdrop.detach().addClass('stancer-backdrop--hidden');
    $frame.detach();
  };
  const processResponse = ($this: JQuery<HTMLElement>, result: CheckoutResponse) => {
    try {
      if ('success' === result.result && result.redirect && result.redirect !== '') {
        $body.addClass('stancer-block-scroll');
        $backdrop.appendTo($body).removeClass('stancer-backdrop--hidden');
        $frame.appendTo($body).attr('src', result.redirect);
        redirection.receipt = result.receipt
      } else if ('failure' === result.result) {
        throw new Error('Result failure');
      } else {
        throw new Error('Invalid response');
      }
    } catch(err) {
      // Reload page
      if (result.reload) {
        window.location.reload();
        return false;
      }

      // Trigger update in case we need a fresh nonce
      if (result.refresh) {
        $body.trigger('update_checkout');
      }

      // Add new errors
      if (result.messages) {
        $('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
        $('form.checkout')
          .prepend(`<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">${result.messages}</div>`)
          .removeClass('processing')
          .unblock()
          .find('.input-text, select, input:checkbox')
            .trigger('validate')
            .trigger('blur')
        ;

        const $scrollElement = $('.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout');

        if ($scrollElement.length) {
          $.scroll_to_notices($scrollElement);
        } else {
          $.scroll_to_notices($('form.checkout'));
        }

        $body.trigger('checkout_error', [result.messages]);
      }
    } finally {
      $this.unblock();
    }
  };

  $backdrop
    .append($(document.createElement('div')).addClass('stancer-logo').append(STANCER_SVG))
    .on('click', close)
  ;

  // We don't get any messages from window, so we can't resize the iframe. (and maybe cannot load the payment form)
  $window
    .on('message', (event) => {
      const { data } = event.originalEvent as MessageEvent<MessageData>;

      // We use some of the api data structure to test if the message is a stancer payment message.
      if (typeof data.status === "undefined" || typeof data.width === "undefined" || typeof data.height === "undefined") {
        return;
      }
        if (messageCallback(data)) {
          return;
        }
      if(data.status === 'finished' && redirection.receipt != '')
        {
          window.postMessage({stopRedirection: true});
          window.location.href = redirection.receipt;
          close();
          return;
        }
      const maxHeight = $window.height() ?? 100;
      const maxWidth = $window.width() ?? 100;
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
    })
    .on('keydown', (event) => {
      if (event.code === 'Escape') {
        close();
      }
    })
  ;

  if ($('.js-stancer-change-payment-method').length) {
    $.ajax({
      url: stancer.changePaymentMethod?.url,
      type: 'POST',
      data: {
        action: 'information',
        nonce: stancer.changePaymentMethod?.nonce,
        subscription: params.change_payment_method,
      },
      dataType: 'json',
      success: (result: CheckoutResponse) => {
        if (result.card) {
          $('#payment .payment_method_stancer label[for=payment_method_stancer]').text(result.card);
        }
      },
      error: (_jqXHR, _textStatus, errorThrown) => $body.trigger('checkout_error', [errorThrown]),
    });
  }

  $body
    .on('click', '.js-stancer-place-order', function (this: HTMLElement, event): boolean {
      if (!$stancer_payment_method.is(':checked')) {
        return true;
      }

      event.preventDefault();

      const $this = $(this);
      const $form = $this.parents('form');

      $this.block({ message: null });

      $.ajax({
        url: stancer.initiate,
        type: 'POST',
        data: $form.serialize(),
        dataType: 'json',
        success: (result: CheckoutResponse) => processResponse($this, result),
        error: (_jqXHR, _textStatus, errorThrown) => {
          $body.trigger('checkout_error', [errorThrown]);
        }
      });

      return false;
    })
    .on('click', '.js-stancer-change-payment-method', function (this: HTMLElement, event): boolean {
      const $this = $(this);

      event.preventDefault();

      messageCallback = (data) => {
        if (data.status !== 'finished' && data.status !== 'error') {
          return false;
        }

        close();

        $.ajax({
          url: stancer.changePaymentMethod?.url,
          type: 'POST',
          data: {
            action: 'validate',
            nonce: stancer.changePaymentMethod?.nonce,
            subscription: params.change_payment_method,
          },
          dataType: 'json',
          success: (result: CheckoutResponse) => {
            if (result.result === 'success') {
              if (result.card) {
                $('#order_review .shop_table tfoot tr:nth-child(2) td.product-total').text(result.card);
              }

              $('#payment').empty();
            }

            if (result.messages) {
              const $message = $(document.createElement('div')).text(result.messages);

              $('.woocommerce-notices-wrapper')
                .siblings('.wc-block-components-notice-banner, .woocommerce-error, .woocommerce-info')
                  .remove()
                  .end()
                .after($message)
              ;

              if (result.result === 'success') {
                $message.addClass('woocommerce-info');
              } else {
                $message.addClass('woocommerce-error');
              }
            }
          },
        });

        return true;
      };

      $this.block({ message: null });

      $.ajax({
        url: stancer.changePaymentMethod?.url,
        type: 'POST',
        data: {
          action: 'initiate',
          nonce: stancer.changePaymentMethod?.nonce,
          subscription: params.change_payment_method,
        },
        dataType: 'json',
        success: (result: CheckoutResponse) => processResponse($this, result),
        error: (_jqXHR, _textStatus, errorThrown) => $body.trigger('checkout_error', [errorThrown]),
      });

      return false;
    })
  ;
}))(jQuery);
