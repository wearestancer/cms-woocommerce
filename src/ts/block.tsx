/**
 * This function register the stancer payment method
*/
const main = () => {

  // Declare Interfaces.
  interface IframeProps {
    data: () => CompletePaymentData
  };
  interface LabelProp {
    components?: {
      PaymentMethodLabel: PaymentMethodLabel,
    };
  }
  interface PaymentMethodLabelProps {
    icon: '' | string | SVGElement;
    text: string;
  }

  //Declare Types.
  type MutableRefObject<T> = React.MutableRefObject<T>;
  type PaymentMethodLabel = (arg0: Partial<PaymentMethodLabelProps>) => JSX.Element;
  type SetReactResponse = React.Dispatch<React.SetStateAction<CheckoutResponseReact>>;

  // Declare WordPress related globals.
  const wordPress = window.wp;
  const settings = window.wcSettings.paymentMethodData['stancer'];
  const { registerPaymentMethod } = window.wc.wcBlocksRegistry;

  // Declare React function & wordpress,woocommerce constants.

  const useEffect = React.useEffect;
  const useState = React.useState;
  const useRef = React.useRef;

  /**
   *  Set the Button Label
   *
   * @returns string
   */
  const buttonLabel = (): string => {
    return wordPress.htmlEntities.decodeEntities(settings.label);
  };

  /**
   * Listen to the Button prevent the click event and call the api ourself to put the stancer url in our iframe
   *
   * @param data
   * @param setPaymentUrl
   * @param IsisClosed
   * @returns void
   */
  const buttonListener = (data: MutableRefObject<CompletePaymentData> | {}, button: Element): void => useEffect(() => {

    button.addEventListener('click', (e: Event) => {

      if (!('current' in data)) {
        return;
      }
      const checkedPaymentMethod = document.querySelector('.wc-block-components-radio-control__option-checked');

      if (
        checkedPaymentMethod?.getAttribute('for') !== null &&
        !checkedPaymentMethod?.getAttribute('for')?.includes('stancer') &&
        button.innerHTML !== wordPress.htmlEntities.decodeEntities(settings.label)
      ) {
        return;
      }

      e.preventDefault();
      e.stopImmediatePropagation();

      callApi(data.current)
        .then(
          (response) => {
            // Block checkout doesn't give us an easy acess to the checkout data that we sent.
            const receipt = response.payment_result.payment_details.filter((object: { key: string, value: string }) => {
              return object.key == 'receipt';
            })[0].value ?? '';

            window.stancer_iframe(
              {
                redirect: response.payment_result.redirect_url,
                result: response.payment_result.payment_status,
                receipt: receipt,
              },
            );
          }
        );
    });
  }, []);

  /**
   *
   * @param data CompletePaymentData the data needed for our api call in the good format.
   * @returns Promise<BlockApiResponse>
   */
  const callApi = async (data: CompletePaymentData): Promise<BlockApiResponse> => {
    const response = await wordPress.apiFetch(
      {
        path: '/wc/store/v1/checkout',
        method: 'POST',
        data: data,
      },
    )
    return response;
  }

  /**
   * Create a reactNode with our description, we also have our Iframe logic in here to get the props.
   *
   * @returns ReactNode
   */
  const Content = (props: StancerPaymentInterface): React.ReactNode => {
    const { activePaymentMethod, billing, shippingData } = props;

    if (
      activePaymentMethod !== 'stancer' ||
      settings.page_type !== 'pip'
    ) {
      return <Description />;
    }

    const formdata = () => {
      const formdata: CompletePaymentData = {
        billing_address: billing?.billingAddress,
        payment_method: activePaymentMethod,
        shipping_address: shippingData?.shippingAddress,
      };
      return formdata;
    }
    return <div>
      <Description />
      <Iframe data={formdata} />
    </div>;
  };

  /**
 * Get the description of our payment module
 *
 * @returns ReactNode
 */
  const Description = (): React.ReactNode => {
    return wordPress.htmlEntities.decodeEntities(settings.description);
  };

  /**
   * Call a buttonListener, when we get the paymentUrl we create the Iframe
   *
   * @param param IframeProps
   * @returns ReactNode
   */
  const Iframe: React.FC<IframeProps> = ({ data }: IframeProps): React.ReactNode => {
    const [result, setResult]: [CheckoutResponseReact, SetReactResponse] = useState(
      {
        redirect: '',
        receipt: '',
        result: '',
      });
    const activeData = useRef({});
    activeData.current = data();
    const button = document.querySelector('.wc-block-components-checkout-place-order-button');
    if (button !== null) {
      buttonListener(activeData, button);
    }
    return <div />;
  }

  /**
   * Set the Label to be displayed
   * With Logo as defined by the user
   *
   * @param props
   * @returns ReactNode
   */
  const Label = ({ components }: LabelProp): React.ReactNode => {
    const PaymentMethodLabel = components?.PaymentMethodLabel;

    if (PaymentMethodLabel == undefined) {
      throw new Error('Label not Found');
    }

    return <div className="payment_method_stancer" >
      <PaymentMethodLabel text={settings.title + ' '} />
      <img className={settings.logo.class} src={settings.logo.url} />
    </div>;
  };

  const options = {
    ariaLabel: settings.title ?? 'stancer',
    canMakePayment: () => true,
    content: <Content />,
    edit: <Content />,
    label: <Label />,
    name: 'stancer',
    paymentMethodId: 'stancer',
    placeOrderButtonLabel: buttonLabel(),
    supports: { features: settings.supports },
  };

  registerPaymentMethod(options);
}

main()
