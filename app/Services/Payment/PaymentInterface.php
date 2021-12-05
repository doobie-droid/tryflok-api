<?php
namespace App\Services\Payment;
use App\Models\PaymentAccount;

interface PaymentInterface {

	/**
	 * Get list of Banks
	 * 
	 * @return array
	 */
	public function getBanks($options);

	/**
	 * Verify a transaction
	 * 
	 * @param string $reference
	 * @return array
	 */
	public function verifyTransaction($reference);

	/**
	 * Transfer funds to a recipient
	 *
	 * @param App\Models\PaymentAccount $transferData
	 * @param float $amount
	 * 
	 * @return array
	 */
	public function transferFundsToRecipient(PaymentAccount $transferData, $amount);

	/**
	 * Charge a customer
	 *
	 * @param [array] $chargeData
	 * @return array
	 */
	public function chargeCustomer($chargeData);

}
