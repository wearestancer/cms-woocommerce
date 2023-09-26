(($) => $(() => {
  interface CheckoutResponseBase {
    messages?: string;
    refresh: boolean;
    reload: boolean;
  }

  interface CheckoutResponseFailure extends CheckoutResponseBase {
    result: 'failure';
  }

  interface CheckoutResponseSuccess extends CheckoutResponseBase {
    order_id: number;
    redirect: string;
    result: 'success';
  }

  type CheckoutResponse = CheckoutResponseFailure | CheckoutResponseSuccess;

  const $stancer_payment_method = $('#payment_method_stancer');
  const $placeOrder = $('.js-stancer-place-order');
  const $cardSelect = $('#stancer-card');

  if ($cardSelect.selectWoo) {
    $cardSelect.selectWoo({
      minimumResultsForSearch: Infinity,
      width: '100%',
    });
  }

  $placeOrder.on('click', function (event) {
    if (!$stancer_payment_method.is(':checked')) {
      return true;
    }

    event.preventDefault();

    const $this = $(this);
    const $form = $this.parents('form');
    const $body = $(document.body);
    const width = 550;
    const height = 855;
    const left = (screen.width - width) / 2;
    const top = Math.max((screen.height - height) / 2, 0);

    const popup = window.open(
      'about:blank',
      '_blank',
      `popup, width=${width}, height=${height}, top=${top}, left=${left}`,
    );

    if (!popup) {
      return;
    }

    $.ajax({
      url: wc_checkout_params.checkout_url,
      type:'POST',
      data: $form.serialize(),
      dataType: 'json',
      success: (result: CheckoutResponse) => {
        try {
          if ('success' === result.result && result.redirect && result.redirect !== '') {
            popup.location.href = result.redirect;
          } else if ('failure' === result.result) {
            throw new Error('Result failure');
          } else {
            throw new Error('Invalid response');
          }
        } catch(err) {
          popup.close();

          // Reload page
          if (result.reload) {
            window.location.reload();
            return;
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
        }
      },
      error: (_jqXHR, _textStatus, errorThrown) => {
        $body.trigger('checkout_error', [errorThrown]);
      }
    });
  })
}))(jQuery);
