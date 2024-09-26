(($) => $(() => {

  const $body = $(document.body);

  //This is unused for now, it's not deleted cause it could be usefull when we will let people register their cards.
  const $cardSelect = $('#stancer-card');

  if ($cardSelect.selectWoo) {
    $cardSelect.selectWoo({
      minimumResultsForSearch: Infinity,
      width: '100%',
    });
  }

  /**
   * Call our backend for information.
   * We Try to call with wordpress API requests, if it's not available we fallback on AJAX requests
   *
   * @param arg0: ServerCallData
   * @returns void
   */
  const callServer = ({ data, responseCallBack, route }: ServerCallData): void => {
    if (typeof data == 'function') {
      data = data()
    }
    if (window.wp !== undefined && window.wp.apiFetch !== undefined && route.path !== '') {
      window.wp.apiFetch({
        path: route.path,
        method: 'POST',
        data: data
      })
        .then(response => responseCallBack(response))
    }
    else {
      if (route.url === undefined) {
        throw new Error('No URL given with shortcodeAjaxrequest')
      }
      $.ajax({
        url: route.url,
        type: 'POST',
        data: data,
        dataType: 'json',
        success: (data: CheckoutResponse, _textStatus, _jqXHR,) => responseCallBack(data),
        error: (_jqXHR, _textStatus, errorThrown) => $body.trigger('checkout_error', [errorThrown]),
      });

    }
  }

  /**
   * Submit handler on checkout form submisssion.
   * To be look like an <form onSubmit>
   *
   * @param param0
   * @param canSubmit
   */
  const onSubmit: SubmitType = ({ button, route, data }, canSubmit = () => true) => {
    $body.on('click', button, (event): boolean => {
      if (!canSubmit()) {
        return true;
      }

      event.preventDefault();

      const $button = $(button);

      $button.prop('disabled', true);
      $button.block({ message: null });

      const responseCallBack = (response: CheckoutResponse) => {
        response.result == 'success' ? window.stancer_iframe(response) : processResponseFailure(response);
        $button.prop('disabled', false)
        $button.unblock();
      }
      callServer({ data, route, responseCallBack })
      return false;
    });
  }

  /**
   * The function that handle a failed backend call.
   *
   * @param result
   * @returns
   */
  const processResponseFailure = (result: CheckoutResponseFailure) => {
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
      const $scrollElement = $(document.createElement('div'))
        .addClass('woocommerce-NoticeGroup, woocommerce-NoticeGroup-checkout')
        .html(result.messages)
        ;
      const $form = $('form.checkout');

      $form.remove('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message');

      $form
        .prepend($scrollElement)
        .removeClass('processing')
        .unblock()
        ;

      $('.input-text, select, input:checkbox', $form)
        .trigger('validate')
        .trigger('blur')
        ;

      $.scroll_to_notices($scrollElement);

      $body.trigger('checkout_error', [result.messages]);
    }
  }

  //EXPORT
  window.stancer_callServer = callServer;
  window.stancer_onSubmit = onSubmit;
}))(jQuery);
