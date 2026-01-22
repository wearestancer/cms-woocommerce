<?php

use Automattic\WooCommerce\Admin\Overrides\Order;

class WC_Stancer_Capture
{

	private Order $order;
	private WC_Stancer_Payment $payment;
	private Stancer\Payment $api_payment;
	public function __construct(Order|int $order)
	{
		if(is_int($order)){
			$this->order = wc_get_order($order);
		}
		else{
			$this->order = $order;
		}
		$payment = WC_Stancer_Payment::find($this->order);
		if(!$payment){
			throw new Stancer\Exceptions\InvalidArgumentException(__('the order is not associated with a Stancer payment','stancer'));
		}
		$this->payment = $payment;
		$gateway = new WC_Stancer_Gateway();
		if(! $gateway->api_config->is_configured()){
			throw new Stancer\Exceptions\NotAuthorizedException(__('your Stancer Module is not properly setup','stancer'))
			;
		}
		$this->api_payment = new Stancer\Payment($payment->payment_id);

	}

	public function maybe_display_capture(){
		$this->payment->mark_as($this->api_payment->get_status());
		wp_enqueue_style(
			'stancer-order-style',
			plugin_dir_url(STANCER_FILE).'public/css/order.min.css',
			[],
			STANCER_ASSETS_VERSION,
		);
		printf('<mark class="tips order-status stancer-%1$s stancer-status"><span>%1$s</span></mark>',$this->payment->status);
		if($this->payment->status !== Stancer\Payment\Status::AUTHORIZED )
		{
			return;
		}

		$nonce= wp_create_nonce('stancer_capture');
		$get_data= build_query([
			'nonce'=> $nonce,
			'action'=>'stancer_capture',
			'order_id'=>$this->order->get_id(),
		]);
		printf( '
			<div class="capture-stancer-block">
			<a href="%1$s" class="button capture-stancer">Capture the payment</a>
			</div>
		',admin_url('admin-post.php').'?'.$get_data,);
	}

	public function capture_authorize_payment(){

		$this->api_payment->status = Stancer\Payment\Status::CAPTURE;
		$this->api_payment->send();
		if(in_array(
			$this->api_payment->status,
			[
				Stancer\Payment\Status::CAPTURE_SENT,
				Stancer\Payment\Status::CAPTURED,
				Stancer\Payment\Status::TO_CAPTURE
			])
		)
		{
			$this->order->payment_complete();
			$this->payment->mark_as( $this->api_payment->get_status());
		}
	}
}
