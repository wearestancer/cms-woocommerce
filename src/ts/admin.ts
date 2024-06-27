(($) => $(() => {

  type Input = JQuery<HTMLInputElement>;

  const prefix = 'woocommerce_stancer';

  const validateDescription = ($description: Input, descriptionType: string): boolean => {
    if (!$description.length || $description.attr('type') !== 'text'){
      return true;
    }
    const {
      confirmMessage,
      descriptionMessage,
      maxSize,
      minSize,
      renewalDescriptionMessage,
    } = window.stancer_admin;
    const typeMessage = descriptionType === 'descriptionMessage' ? descriptionMessage : renewalDescriptionMessage;
    const length = $description.val()?.length ?? 0;
    let isValid = (length < maxSize && length > minSize);

    if (!isValid) {
      $description.prop("isValid", false);
      isValid = confirm(`"${typeMessage}" ${confirmMessage}`);
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

    $testMode?.on('input', () => $testMode.prop('checked') ? unRequireKey($liveKeys) : requireKey($liveKeys));
  }

  validateKey();

  $('#mainform').on('submit', (event: JQuery.Event): void => {
    const $payment_description = $(`#${prefix}_payment_description`) as Input;
    const $renewal_description = $(`#${prefix}_subscription_renewal_description`) as Input;

    if (
      !validateDescription( $payment_description, 'descriptionMessage') ||
      !validateDescription( $renewal_description, 'renewalDescriptionMessage')
      ) {
      event.stopPropagation();
      event.preventDefault();
    }
  });

}))(jQuery);
