<?php

namespace App\Services\Payment\Providers\Stripe;

use App\Services\Payment\PaymentInterface;
use App\Services\Payment\Providers\Stripe\API;
use App\Models\PaymentAccount;

class Stripe extends API implements PaymentInterface {
    /**
	 * Get list of Banks supported by flutterwave
     * 
     * @param array $options ['country_code',] 
	 *
	 * @return array
	 */
	public function getBanks($options)
	{
		throw new Exception('Stripe does not implement getBanks method');
	}

    /**
	 * Verify a transaction
	 *
	 * @param string $id
	 * @return array
	 */
	public function verifyTransaction($id)
	{
		return $this->_get('v1/charges/' . $id);
	}

    /**
	 * Transfer funds to a recipient
	 *
	 * @param App\Models\PaymentAccount $transferData
	 * @param float $amount
	 *  ['amount', 'currency_code', 'country_code', 'identifier']
	 * @return array
	 */
	public function transferFundsToRecipient(PaymentAccount $transferData, $amount)
	{
       //note that the amount supplied here is in dollars and must be converted to cents
	   $amount_in_cents = bcmul($amount, 100,0);
	   $response =  $this->_post('v1/transfers', [
		'amount' => $amount_in_cents,
		'currency' => 'usd',
		'destination' => $transferData->identifier,
	   ]);
	   return $response;
	}

	public function chargeViaToken($amount, $token)
	{
		//note that the amount provided here is in cents
		return $this->_post('v1/charges',[
			'amount' => (int) $amount,
			'currency' => 'usd',
			'source' => $token,
		]);
	}

	/**
	 * Get status of a transfer
	 * 
	 * @param integer $id
	 * 
	 * @return array
	 */
	public function getTransferStatus($id)
	{
		$response =  $this->_get('v1/transfers/' . $id);
		return $response;
	}
    /**
	 * Charge a customer
	 *
	 * @param [array] $chargeData => [authorization_code, email, amount]
	 * @return array
	 */
	public function chargeCustomer($chargeData)
	{
		throw new Exception('Stripe does not implement chargeCustomer method');
	}
}