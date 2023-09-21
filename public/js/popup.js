jQuery(document).ready(function ($) {
  const $stancer_payment_method = $("#payment_method_stancer");
  const $placeOrder = $('.js-stancer-place-order');
  const $form = $('form.woocommerce-checkout');
  const $cardSelect = $('#stancer-card');

  $cardSelect.selectWoo({
    minimumResultsForSearch: Infinity,
    width: '100%',
  });

  $placeOrder.on('click', function (e) {
    if (!$stancer_payment_method.is(':checked')) {
      return true;
    }

    e.preventDefault();

    const $body = $(document.body);
    const width = 550;
    const height = 855;
    const left = (screen.width - width) / 2;
    const top = Math.max((screen.height - height) / 2, 0);

    const popup = window.open(
      'about:blank',
      '_blank',
      'popup, width=' + width + ', height=' + height + ', top=' + top + ', left=' + left
    );

    $.ajax({
      url: wc_checkout_params.checkout_url,
      type:'POST',
      data: $form.serialize(),
      dataType: 'json',
      success: function(result) {
        try {
          if ('success' === result.result && result.redirect && result.redirect !== '') {
            popup.location.href = result.redirect;
          } else if ('failure' === result.result) {
            throw 'Result failure';
          } else {
            throw 'Invalid response';
          }
        } catch(err) {
          popup.close();

          // Reload page
          if (true === result.reload) {
            window.location.reload();
            return;
          }

          // Trigger update in case we need a fresh nonce
          if (true === result.refresh) {
            $body.trigger('update_checkout');
          }

          // Add new errors
          if (result.messages) {
            $('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
            // eslint-disable-next-line max-len
            $('form.checkout').prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + result.messages + '</div>');
            $('form.checkout').removeClass('processing').unblock();
            $('form.checkout').find('.input-text, select, input:checkbox').trigger('validate').trigger('blur');

            let scrollElement = $('.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout');

            if (! scrollElement.length) {
              scrollElement = $('form.checkout');
            }

            $.scroll_to_notices(scrollElement);
            $body.trigger('checkout_error' , [ result.messages ]);
          }
        }
      },
      error: function(_jqXHR, _textStatus, errorThrown) {
        $body.trigger('checkout_error' , [ errorThrown ]);
      }
    });
  })
});
