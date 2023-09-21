jQuery(document).ready(function ($) {
  const $window = $(window);
  const $body = $(document.body);
  const $backdrop = $(document.createElement('div')).addClass('stancer-backdrop');
  const $frame = $(document.createElement('iframe')).addClass('stancer-iframe');
  const $stancer_payment_method = $("#payment_method_stancer");
  const $form = $('form.woocommerce-checkout');
  const $cardSelect = $('#stancer-card');

  if ($cardSelect.selectWoo) {
    $cardSelect.selectWoo({
      minimumResultsForSearch: Infinity,
      width: '100%',
    });
  }

  const close = () => {
    $body.removeClass('stancer-block-scroll');
    $backdrop.detach();
    $frame.detach();
  };

  $backdrop.on('click', close);

  $window
    .on('message', function (event) {
      const origin = event.originalEvent.origin;
      const data = event.originalEvent.data;

      if (origin === 'https://payment.stancer.com') {
        if (data.url) {
          if (['error', 'finished', 'secure-auth-error'].includes(data.status)) {
            window.location = data.url;
          }

          if (data.status === 'finished') {
            return;
          }
        }

        let height = 400;
        let radius = 10;
        let width = 400;

        if (data.status === 'secure-auth-start') {
          height = $window.height();
          width = $window.width();
          radius = 0;
        } else if (!['error', 'init', 'secure-auth-end', 'secure-auth-error'].includes(data.status)) {
          height = data.height;
          width = data.width;
        }

        document.body.style.setProperty('--stancer-iframe-height', height + 'px');
        document.body.style.setProperty('--stancer-iframe-width', width + 'px');
        document.body.style.setProperty('--stancer-iframe-border-radius', radius + 'px');
      }
    })
    .on('keydown', function (event) {
      if (event.which === 27) { // Escape
        close();
      }
    })
  ;

  $body.on('click', '.js-stancer-place-order', function (event) {
    if (!$stancer_payment_method.is(':checked')) {
     return true;
    }

    event.preventDefault();

    const $this = $(this);

    $this.block({ message: null });

    $.ajax({
      url: wc_checkout_params.checkout_url,
      type:'POST',
      data: $form.serialize(),
      dataType: 'json',
      success: function(result) {
        try {
          if ('success' === result.result && result.redirect && result.redirect !== '') {
            $this.unblock();
            $body.addClass('stancer-block-scroll');
            $backdrop.appendTo($body);
            $frame.appendTo($body).attr('src', result.redirect);
          } else if ('failure' === result.result) {
            throw 'Result failure';
          } else {
            throw 'Invalid response';
          }
        } catch(err) {
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
