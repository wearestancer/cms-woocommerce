(($) => $(() => {
  'use strict';
  type Input = JQuery<HTMLInputElement>;

  const prefix = 'woocommerce_stancer';
  const adminData = window.stancer_admin;
  type Input = JQuery<HTMLInputElement>;
  const validateDescription = ($description: Input): boolean => {

    const params = adminData.descriptionDiff;
    const message = adminData.confirmMessage;

    let lengthMax = $description.val()?.length ?? 0;
    let lengthMin = lengthMax;
    const usedparams = params.filter((param) => $description.val()?.includes(param.name));
    usedparams.forEach(param => {
      lengthMax += param.max;
      lengthMin += param.min;
    });
    return (lengthMax < 64 && lengthMin > 3) ? true : window.confirm(message);
  };

  const validateKey = (): void => {
    const testMode: Input = $(`#${prefix}_test_mode`);

    const liveKeys: Input[] = [
      $(`#${prefix}_api_live_public_key`),
      $(`#${prefix}_api_live_secret_key`),
    ];
    const requireKey = (keys: Input[]) => keys.map((key) => key.attr('required', 'required'));
    const unRequireKey = (keys: Input[]) => keys.map((key) => key.removeAttr('required'));

    testMode?.on('input', () => testMode.is(':checked') ? unRequireKey(liveKeys) : requireKey(liveKeys));
  }

  const validateForm = (): boolean => {

    return validateDescription(($('#'+prefix + '_payment_description')) as JQuery<HTMLInputElement>)
  };

  validateKey();

  $('#mainform').on('submit', (event: JQuery.Event): void => {
    if (!validateDescription(($('#'+prefix + '_payment_description')) as Input)) {
      event.stopPropagation();
      event.preventDefault();
    }
  });

}))(jQuery);
