<?php
namespace App\Services\Payment\Providers\ApplePay;
use App\Services\Payment\PaymentInterface;
use App\Services\Payment\Providers\ApplePay\API;
use App\Models\PaymentAccount;

class ApplePay extends API implements PaymentInterface 
{
    /**
	 * Get list of Banks
	 * 
	 * @return array
	 */
	public function getBanks($options)
    {
        throw new Exception('ApplePay does not implement getBanks method');
    }

	/**
	 * Verify a transaction
	 * 
	 * @param string $reference
	 * @return array
	 */
	public function verifyTransaction($reference)
    {
        return $this->_post('verifyReceipt', [
            'receipt-data' => $reference,
            'password' => $this->secret,
        ]);
    }   

	/**
	 * Transfer funds to a recipient
	 *
	 * @param App\Models\PaymentAccount $transferData
	 * @param float $amount
	 * 
	 * @return array
	 */
	public function transferFundsToRecipient(PaymentAccount $transferData, $amount)
    {
        throw new Exception('ApplePay does not implement transferFundsToRecipient method');
    }

	/**
	 * Charge a customer
	 *
	 * @param [array] $chargeData
	 * @return array
	 */
	public function chargeCustomer($chargeData)
    {
        throw new Exception('ApplePay does not implement chargeCustomer method');
    }
}