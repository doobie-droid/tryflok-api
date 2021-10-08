<?php

namespace App\Services\Payment\Providers\Mock;

use App\Services\Payment\PaymentInterface;
use App\Models\PaymentAccount;

class TestPaymentService implements PaymentInterface {
	
	/**
	 * Get Banks supported by test payment
	 *
	 * @return array
	 */
	public function getBanks($options)
	{
		return response()->json([
			'status' => true,
			'message' => 'Banks retrieved',
			'data' => [
				[
					"name" => "Test Bank 1",
					"slug" => "test-bank-1",
					"code" => "001",
				],
				[
					"name" => "Test Bank 2",
					"slug" => "test-bank-2",
					"code" => "002",
				],
			]
		], 200)->getData();
	}

	/**
	 * Verify a transaction
	 *
	 * @param string $reference
	 * @return array
	 */
	public function verifyTransaction($reference)
	{
		return response()->json([
			'status' => true,
			'message' => 'Verification successful',
			'data' => [
				"status" => "success",
				"authorization" => [
					"authorization_code"=>"AUTH_8dfhjjdt",
					"card_type" => "visa",
					"last4" => "1381",
					"exp_month" => "08",
					"exp_year" => "2018",
					"bin" => "412345",
					"bank" => "TEST BANK",
					"channel" => "card",
					"signature" => "SIG_idyuhgd87dUYSHO92D",
					"reusable" => true,
					"country_code" => "NG",
				],
				"customer" => [
					"id" => 84312,
         "customer_code" => "CUS_hdhye17yj8qd2tx",
         "first_name" => "BoJack",
         "last_name" => "Horseman",
         "email" => "bojack@horseman.com"
				],
			]
		], 200)->getData();
	}

	/**
	 * Returns the recipients cde
	 *
	 * @param [array] $receipientData
	 * @return array
	 */
	public function getTransferRecipient($receipientData)
	{
		return response()->json([
			'status' => true,
			'data' => [
				'recipient_code' => 'xyz788w',
			],
		],200)->getData();
	}

	/**
	 * Transfer funds to a recipient
	 *
	 * @param App\Models\PaymentAccount $transferData
	 * @param float $amount
	 * @return array
	 */
	public function transferFundsToRecipient(PaymentAccount $transferData, $amount)
	{
		return response()->json([
			'status' => true,
			'data' => [
				'transfer_code' => 'trCd09Opl',
				'reference' => 'pol0Dr34',
				'amount' => $transferData['amount'] ,
			]
		],400)->getData();
	}

	/**
	 * Charge a customer
	 *
	 * @param [array] $chargeData
	 * @return array
	 */
	public function chargeCustomer($chargeData)
	{
		return response()->json([
			'status' => true,
			'data' => [
				'reference' => 'ref902837',
				'status' => 'success',
			]
		],200)->getData();
	}
}