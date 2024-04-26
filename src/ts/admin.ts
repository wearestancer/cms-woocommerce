(($) => $(() => {
  type Input = JQuery<HTMLInputElement>;

  const prefix = 'woocommerce_stancer';

  const validateDescription = (description: HTMLInputElement): boolean => {
    type ExpectedReplacementSize = {
      name: string;
      min: number;
      max: number;
    };
    const params: ExpectedReplacementSize[] = [
      {
        name: 'SHOP_NAME',
        min: 2,
        max: 2,
      },
      {
        name: 'CART_ID',
        min: -6,
        max: 0,
      },
      {
        name: 'CURRENCY',
        min: -5,
        max: -5,
      },
      {
        name: 'TOTAL_AMOUNT',
        min: -9,
        max: -4,
      }
    ];
    const message = "your description might be longer than 64 or shorter than 3, do you wish to still submit your settings ?"
    let lengthMax = description.value.length;
    let lengthMin = lengthMax;
    const usedparams = params.filter((param) => description.value.includes(param.name));
    usedparams.forEach(param => {
      lengthMax += param.max;
      lengthMin += param.min;
    });
    return (lengthMax < 64 && lengthMin > 3) ? true : confirm(message);
  }

  const validateKey = (): void => {
    const testMode: Input = $(`#${prefix}_test_mode`);

    const liveKeys: Input[] = [
      $(`#${prefix}_api_live_public_key`),
      $(`#${prefix}_api_live_secret_key`),
    ];
    const requireKey = (keys: Input[]) => keys.map((key) => key.attr('required', 'required'));
    const unRequireKey = (keys: Input[]) => keys.map((key) => key.removeAttr('required'));

    testMode?.on('input', () => testMode.is(':checked',) ? unRequireKey(liveKeys) : requireKey(liveKeys));
  }

  const validateForm = (): boolean => {

    return validateDescription((document.getElementById(prefix + '_payment_description')) as HTMLInputElement)
  };



  validateKey();
  const form = document.getElementById('mainform') as HTMLFormElement;

  form.addEventListener('submit', (event: Event): void => {

    if (!validateForm()) {
      event.stopPropagation();
      event.preventDefault();
    }
  });

}))(jQuery);
