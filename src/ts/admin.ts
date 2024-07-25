(($) => $(() => {
  type Input = JQuery<HTMLInputElement>;

  const prefix = 'woocommerce_stancer';

  const validateDescription = ($description: Input): boolean => {
    if (!$description.length){
      return true;
    }
    const {maxSize,minSize,confirmMessage} = window.stancer_admin;
    const length = $description.val()?.length ?? 0;
    let isValid = (length < maxSize && length > minSize);

    if (!isValid) {
      $description.prop("isValid", false);
      isValid = confirm(confirmMessage);
    }

    return isValid;
  };

  const validateKey = (): void => {
    const $testMode: Input = $(`#${prefix}_test_mode`);

    const $liveKeys: Input[] = [
      $(`#${prefix}_api_live_public_key`),
      $(`#${prefix}_api_live_secret_key`),
    ];
    const requireKey = (keys: Input[]) => keys.map((key) => key.prop('required', true));
    const unRequireKey = (keys: Input[]) => keys.map((key) => key.prop('required', false));

    $testMode?.on('input', () => $testMode.is(':checked') ? unRequireKey($liveKeys) : requireKey($liveKeys));
  }

  validateKey();

  $('#mainform').on('submit', (event: JQuery.Event): void => {
    if (!validateDescription(($(`#${prefix}_payment_description`)) as Input)) {
      event.stopPropagation();
      event.preventDefault();
    }
  });

}))(jQuery);
