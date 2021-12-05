<?php

namespace App\Services\Payment\Providers\Paystack;

use App\Models\PaymentAccount;
use App\Services\Payment\PaymentInterface;
use App\Services\Payment\Providers\Paystack\AmountConverter;
use App\Services\Payment\Providers\Paystack\API;

class Paystack extends API implements PaymentInterface
{
    /**
     * Get list of Banks supported by paystack
     *
     * @return array
     */
    public function getBanks($options)
    {
        return $this->_get('bank');
    }

    /**
     * Verify a transaction
     *
     * @param string $reference
     * @return array
     */
    public function verifyTransaction($reference)
    {
        return $this->_get('transaction/verify/' . $reference);
    }

    /**
     * Transfer funds to a recipient
     *
     * @param App\Models\PaymentAccount $transferData
     * @param float $amount
     * ['amount', 'recipient_code']
     * @return array
     */
    public function transferFundsToRecipient(PaymentAccount $transferData, $amount)
    {
        throw new Exception('Paystack does not implement transferFundsToRecipient method');
    }

    /**
     * Charge a customer
     *
     * @param [array] $chargeData => [authorization_code, email, amount]
     * @return array
     */
    public function chargeCustomer($chargeData)
    {
        $chargeData['amount'] = AmountConverter::convert($chargeData['amount']);//paystack deals in kobo
        return $this->_post('/transaction/charge_authorization', $chargeData);
    }
}
