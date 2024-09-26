(($) => $(() => {

  /**
   * shortcode checkout handler.
   *
   * @param button
   */
  const placeOrder = (button: string = '.js-stancer-place-order') => {
    const data = () => $(button).parents('form').serialize();
    const canSubmit = () => $('#payment_method_stancer').is(':checked');
    window.stancer_onSubmit(
      {
        button: button,
        route: {
          path: '',
          url: stancer_data.initiate
        },
        data,
      }, canSubmit);
  };
  window.stancer_changePaymentMethodOrCallback(placeOrder)()

}))(jQuery);
