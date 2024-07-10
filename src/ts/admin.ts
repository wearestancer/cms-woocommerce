(($) => $(() => {
  type Input = JQuery<HTMLInputElement>;

  const prefix = 'woocommerce_stancer';
  const testMode: Input = $(`#${prefix}_test_mode`);

  const liveKeys: Input[] = [
    $(`#${prefix}_api_live_public_key`),
    $(`#${prefix}_api_live_secret_key`),
  ];

  const requireKey = (keys: Input[]) => keys.map((key) => key.attr('required', 'required'));
  const unRequireKey = (keys: Input[]) => keys.map((key) => key.removeAttr('required'));

  testMode?.on('input', () => testMode.is(':checked',) ? unRequireKey(liveKeys) : requireKey(liveKeys));
}))(jQuery);
