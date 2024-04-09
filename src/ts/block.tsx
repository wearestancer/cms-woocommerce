/**
 * This function create An Iframe for our payments
 *
 * @param event the Stancer message to create an Iframe.
 * @returns
 */

const iframe = (event: MessageEvent) => {
  const { data } = event;

  if (typeof data.status === 'undefined' || typeof data.width === 'undefined' || typeof data.height === 'undefined') {
    return;
  }
  if (data.url) {
    if (['error', 'finished', 'secure-auth-error'].includes(data.status)) {
      window.location = data.url;
    }

    if (data.status === 'finished') {
      return;
    }
  }

  const maxHeight = window.innerHeight ?? 100;
  const maxWidth = window.innerWidth ?? 100;
  let height = 400;
  let radius = 10;
  let width = 400;

  if (data.status === 'secure-auth-start') {
    height = maxHeight;
    width = maxWidth;
  } else if (!['error', 'init', 'secure-auth-end', 'secure-auth-error'].includes(data.status)) {
    height = data.height;
    width = data.width;
  }

  if (height >= maxHeight) {
    height = maxHeight;
    radius = 0;
  }

  if (width >= maxWidth) {
    width = maxWidth;
    radius = 0;
  }

  document.body.style.setProperty('--stancer-iframe-height', `${height}px`);
  document.body.style.setProperty('--stancer-iframe-width', `${width}px`);
  document.body.style.setProperty('--stancer-iframe-border-radius', `${radius}px`);

  window.addEventListener('keydown', (event) => {
    if (event.code === 'Escape') {
      document.body.style.setProperty('--stancer-iframe-hidden', 'true');
    }
  });
};

/**
 * This function register the stancer payment method
 */
const main = () => {

  // Declare Interfaces.
  interface BackDropProps {
    closer: CloserUseState
  };
  interface CloserUseState {
    isClosed: boolean,
    CloseIframe: () => void
  };
  interface IframeProps {
    data: CompletePaymentData
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
  type MutableRefObject<T> = React.MutableRefObject<T>
  type PaymentMethodLabel = (arg0: Partial<PaymentMethodLabelProps>) => JSX.Element;
  type SetIsClosed = (T: boolean) => void;
  type SetStateAction<T> = React.Dispatch<React.SetStateAction<T>>

  // Declare wooCommerce and WordPress.
  const wooCommerce = window.wc;
  const wordPress = window.wp;

  // Declare React function & wordpress,woocommerce constants.
  const apiFetch = wordPress.apiFetch as ({ }) => Promise<CheckoutResponse>;
  const useEffect = React.useEffect;
  const useState = React.useState;
  const useRef = React.useRef;
  const settings = window.wcSettings.paymentMethodData['stancer'];
  const { registerPaymentMethod } = wooCommerce.wcBlocksRegistry;

  /**
   *
   * @param data CompletePaymentData the data needed for our api call in the good format.
   * @param setPaymentUrl the useState setter, set the url on Api sucess
   */
  async function callApi(data: MutableRefObject<object>, setPaymentUrl: SetStateAction<string>) {
    const response = await apiFetch(
      {
        path: '/wc/store/v1/checkout',
        method: 'POST',
        data: data.current,
      },
    );
    if (response.payment_result.payment_status === 'success') {
      setPaymentUrl(response.payment_result.redirect_url);
    }
    else if (response.payment_result.payment_status === 'failure') {
      throw new Error('Result failure');
    }
    else {
      throw new Error('Invalid response');
    }
  }

  // React Fragments

  /**
   * Create the blur backdrop on Iframe Creation
   * we get a useState to close the Iframe if we click on the backdrop
   *
   * @param closer BackDropProps
   * @returns ReactNode
   */
  const Backdrop: React.FC<BackDropProps> = ({ closer }: BackDropProps): React.ReactNode => {
    return <div className="stancer-backdrop" hidden={closer.isClosed} onClick={closer.CloseIframe}></div>;
  };

  /**
   *  Set the Button Label
   *
   * @returns string
   */
  const ButtonLabel = (): string => {
    return wordPress.htmlEntities.decodeEntities(settings.label);
  };

  /**
   * Create a reactNode with our description, we also have our Iframe logic in here to get the props.
   *
   * @returns ReactNode
   */
  const Content = (props: StancerPaymentInterface ): React.ReactNode => {
    if (props.activePaymentMethod !== 'stancer' || settings.page_type !== 'pip') {
      return <Description />;
    }
    const { activePaymentMethod, billing, shippingData } = props;
    if (billing == undefined || shippingData == undefined){
      return <Description />
    }
    const formdata: CompletePaymentData = {
      billing_address: billing.billingAddress,
      payment_method: activePaymentMethod,
      shipping_address: shippingData.shippingAddress,
    };

    return <div>
      <Description />
      <Iframe data={formdata as CompletePaymentData} />
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

    const [paymentUrl, setPaymentUrl] = useState('loading');
    const [isClosed, setIsClosed] = useState(false);
    const ischanger = useRef(false);
    const activeData = useRef({});
    activeData.current === data ? ischanger.current = false : ischanger.current = true;

    activeData.current = data;
    const CloseIframe = () => {
      setIsClosed(!isClosed);
    };
    const iframeCloser: CloserUseState = { isClosed, CloseIframe };
    buttonListener(activeData, setPaymentUrl, setIsClosed);
    if (paymentUrl === 'loading' || typeof paymentUrl === 'undefined') {
      return null;
    }

    return <div>
      <iframe
        src={paymentUrl}
        className="stancer-iframe"
        hidden={isClosed}
        allow="payment"
        sandbox="allow-scripts allow-forms allow-top-navigation"
      ></iframe>
      <Backdrop closer={iframeCloser} />
    </div>;
  };

  /**
   * Set the Label to be displayed
   * With Logo as defined by the user
   *
   * @param props
   * @returns ReactNode
   */
  const Label = ({ components } : LabelProp): React.ReactNode => {
    const  PaymentMethodLabel  = components?.PaymentMethodLabel;
    if (PaymentMethodLabel ==  undefined){
      throw new Error('Label not Found');
    }
    return <div className="payment_method_stancer" >
      <PaymentMethodLabel text={settings.title + ' '} />
      <img className={settings.logo.class} src={settings.logo.url} />
    </div>;
  };

  /**
   * Listen to the Button prevent the click event and call the api ourself to put the stancer url in our iframe
   *
   * @param data
   * @param setPaymentUrl
   * @param IsisClosed
   * @returns void
   */
  const buttonListener = (data: MutableRefObject<object>, setPaymentUrl: SetStateAction<string>, setIsClosed: SetIsClosed): void => useEffect(() => {
    const paymentButton = getPaymentButton();
    paymentButton?.addEventListener('click', (e: Event) => {
      e.preventDefault();
      e.stopImmediatePropagation();
      setIsClosed(false);
      callApi(data, setPaymentUrl);
    });
  }, []);

  /**
   * Get the payment Button "Place Order" by it's className (we don't have access to an id)
   * Check if it's the valid button and return it
   * @returns HTMLButtonELement
   */
  const getPaymentButton = (): HTMLButtonElement | void => {
    const button = document.querySelector('.wc-block-components-checkout-place-order-button');
    return button as HTMLButtonElement;
  };

  // Declare Block Constants to register our payment method.
  const supports = {
    features: ['products'],
  };

  const options = {
    name: 'stancer',
    content: <Content /> ,
    edit: <Content />,
    canMakePayment: () => true,
    supports: supports,
    paymentMethodId: 'stancer',
    ariaLabel: settings.title ?? 'stancer',
    label: <Label />,
    placeOrderButtonLabel: ButtonLabel(),
  };

  registerPaymentMethod(options);

  // Add the Iframe.

  window.addEventListener('message', (event: MessageEvent) => iframe(event));

}
window.addEventListener('load', main);
