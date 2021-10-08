<?php
namespace App\Services\Payment;

use App\Services\Payment\PaymentInterface;
use App\Services\Payment\Providers\Paystack\Paystack;
use App\Services\Payment\Providers\Flutterwave\Flutterwave;
use App\Services\Payment\Providers\Stripe\Stripe;
use App\Services\Payment\Providers\ApplePay\ApplePay;
use App\Services\Payment\Providers\Mock\TestPaymentService;
use Illuminate\Support\Str;
use App\Models\PaymentAccount;

class Payment implements PaymentInterface {
	/**
	 * The array of created "drivers".
	 *
	 * @var array
	 */
	protected $drivers = [];
	protected $driver;
	
	public function __construct($driver = null)
	{
		$this->driver = $driver;
	}
	/**
	 * Create Instance of Paystack Driver
	 *
	 * @return App\Services\Payment\PaymentInterface
	 */
	public function createPaystackDriver()
	{
		return new Paystack();
	}

	public function createFlutterwaveDriver()
	{
		return new Flutterwave();
	}

	public function createStripeDriver()
	{
		return new Stripe();
	}

	public function createAppleDriver()
	{
		return new ApplePay();
	}
	/**
	 * Create Instance of Test Driver
	 *
	 * @return App\Services\Payment\PaymentInterface
	 */
	public function createTestDriver()
	{
		return new TestPaymentService();
	}
	/**
	 * Functions that adher to the App\Services\Payment\PaymentInterface
	 */

	/**
	 * Get list of Banks supported by payment platform
	 *
	 * @return array
	 */
	public function getBanks($options)
	{
		return $this->driver()->getBanks($options);
	}

	/**
	 * Verify a transaction
	 *
	 * @param string $reference
	 * @return array
	 */
	public function verifyTransaction($reference)
	{
		return $this->driver()->verifyTransaction($reference);
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
		return $this->driver()->transferFundsToRecipient($transferData, $amount);
	}

	/**
	 * Charge a customer
	 *
	 * @param [array] $chargeData
	 * @return array
	 */
	public function chargeCustomer($chargeData)
	{
		return $this->driver()->chargeCustomer($chargeData);
	}

	/**
	 * Create Instance of Paystack Driver
	 *
	 * @return App\Services\Payment\PaymentInterface
	 */
	public function getDefaultDriver()
	{
		return config('payment.default');
	}


	/**
	 * Get a driver instance.
	 *
	 * @param  string  $driver
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException
	 */
	public function driver()
	{
		$driver = $this->driver ?: $this->getDefaultDriver();

		if (is_null($driver)) {
				throw new \InvalidArgumentException(sprintf(
						'Unable to resolve NULL driver for [%s].', static::class
				));
		}

		// If the given driver has not been created before, we will create the instances
		// here and cache it so we can return it next time very quickly. If there is
		// already a driver created by this name, we'll just return that instance.
		if (! isset($this->drivers[$driver])) {
				$this->drivers[$driver] = $this->createDriver($driver);
		}

		return $this->drivers[$driver];
	}

	/**
	 * Create a new driver instance.
	 *
	 * @param  string  $driver
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function createDriver($driver)
	{

		$method = 'create' . Str::studly($driver) . 'Driver';

		if (method_exists($this, $method)) {
			return $this->$method();
		}

		throw new \InvalidArgumentException("Driver [$driver] not supported.");
	}

}