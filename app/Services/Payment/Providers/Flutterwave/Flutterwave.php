<?php

namespace App\Services\Payment\Providers\Flutterwave;

use App\Services\Payment\PaymentInterface;
use App\Services\Payment\Providers\Flutterwave\API;
use App\Models\PaymentAccount;

class Flutterwave extends API implements PaymentInterface {
    /**
	 * Get list of Banks supported by flutterwave
     * 
     * @param array $options ['country_code',] 
	 *
	 * @return array
	 */
	public function getBanks($options)
	{
		return $this->_get('v3/banks/' . $options['country_code']);
	}

    public function getBankBranch($id)
	{
		return $this->_get('v3/banks/' . $id . '/branches');
	}

    /**
	 * Verify a transaction
	 *
	 * @param string $id
	 * @return array
	 */
	public function verifyTransaction($id)
	{
		return $this->_get('v3/transactions/' . $id . '/verify');
	}

    /**
	 * Transfer funds to a recipient
	 *
	 * @param App\Models\PaymentAccount $transferData
	 * @param float $amount
	 *  ['amount', 'bank_code', 'account_number', 'branch_code', 'country_code', 'reference']
	 * @return array
	 */
	public function transferFundsToRecipient(PaymentAccount $transferData, $amount)
	{
        $need_branch_code = ['GH', 'UG', 'TZ'];
		//TO DO: might want to implement a currency converter among providers
        $neededData = [
            'amount' => bcmul($amount, 485,0), //convert to Naira from dollars
            'account_number' => $transferData->identifier,
            'account_bank' => $transferData->bank_code,
            'currency' => 'NGN',
            'reference' => uniqid() . date("Ymd-His"),
        ];

        if (in_array($transferData->country_code, $need_branch_code)) {
            $neededData['destination_branch_code'] = $transferData->branch_code;
        }
		$response =  $this->_post('v3/transfers', $neededData);
		return $response;
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
		$response =  $this->_get('v3/transfers/' . $id);
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
		throw new Exception('Flutterwave does not implement chargeCustomer method');
	}
}