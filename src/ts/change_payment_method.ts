(($) => $(() => {

  const params = Object.fromEntries(window.location.search.slice(1).split('&').map((value) => value.split('=')));
  const $body = $(document.body);

  /**
   * Initiate the payment method change.
   * @param button
   */
  const changePaymentMethod = (button: string = '.js-stancer-change-payment-method') => {
    informationPaymentChange();
    const data: ChangePaymentData = {
      nonce: stancer_data.changePaymentMethod?.nonce,
      subscription: params.change_payment_method,
      action: 'initiate',
    };
    const path = '/stancer/v1/change_payment_method/initiate'
    const url = stancer_data.changePaymentMethod?.url
    const route = {path, url};
    window.stancer_onSubmit({ button, route, data })
  };

  const changePaymentCallback = (callback: () => void) => {
    return isChangePaymentMethod() ? changePaymentMethod : callback;
  }

  /**
   * call Backend for payment change information
   */
  const informationPaymentChange = () => {
    const success = (result: CheckoutResponseSuccess) => $('#payment .payment_method_stancer label[for=payment_method_stancer]').text(result.card ?? '');
    const failure = (result: CheckoutResponseFailure) => $body.trigger('checkout_error', result.reason);
    window.stancer_callServer(
      {
        data: {
          nonce: stancer_data.changePaymentMethod?.nonce,
          subscription: params.change_payment_method,
          action: 'information'
        },
        responseCallBack(result) {
          result.result == 'success' ? success(result) : failure(result);
        },
        route: {
          path: '/stancer/v1/change_payment_method/information',
          url: stancer_data.changePaymentMethod?.url,
        }
      }
    );
  }

  /**
   * Check if we are in a change payment method context
   * @param button
   * @returns
   */
  const isChangePaymentMethod = (button: string = '.js-stancer-change-payment-method') => {
    return $(button).length ? true : false
  };

  /**
   *  Validate the payment and return if the payment change has been validated.
   *
   * @param data
   * @returns
   */
  const paymentMethodHasBeenChanged = (data: MessageData): boolean => {
    return isChangePaymentMethod() && validatePaymentChange(data)
  }

  /**
   * Validate The payment change with the pp information, and backend information.
   * @param data
   * @returns boolean
   */
  const validatePaymentChange = (data: MessageData): boolean => {
    if (data.status !== 'finished' && data.status !== 'error') {
      return false;
    }

    const success = (result: CheckoutResponseSuccess) => {
      if (result.card) {
        $('#order_review .shop_table tfoot tr:nth-child(2) td.product-total').text(result.card);
      }
      $('#payment').empty();
    };

    const failure = (result: CheckoutResponseFailure) => '';

    const after = (result: CheckoutResponse) => {
      if (result.messages) {

        const $message = $(document.createElement('div')).html(result.messages);
        const $notice = $('.woocommerce-notices-wrapper');

        $notice.remove('.wc-block-components-notice-banner, .woocommerce-error, .woocommerce-info');
        $message.addClass(result.result === 'success' ? 'woocommerce-info' : 'woocommerce-error');
        $notice.append($message);
      }
    }

    const callData: ChangePaymentData = {
      nonce: stancer_data.changePaymentMethod?.nonce,
      subscription: params.change_payment_method,
      action: 'validate'
    }

    window.stancer_callServer(
      {
        data: callData,
        responseCallBack(result) {
          result.result == 'success' ? success(result) : failure(result);
          after(result);
        },
        route: {
          path: '/stancer/v1/change_payment_method/validate',
          url: stancer_data.changePaymentMethod?.url,
        }
      }
    );
    return true;

  };

  //EXPORT
  window.stancer_paymentMethodHasBeenChanged = paymentMethodHasBeenChanged;
  window.stancer_changePaymentMethodOrCallback = changePaymentCallback;

}))(jQuery);
