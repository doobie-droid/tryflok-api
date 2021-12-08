Color Schemes
3670A2 (blue) FFD506 (yellow)

SAMPLE PAYSTACK RESPONSE TO WEBHOOK FRO PURCHASE:
===============================
```
array (
  'event' => 'charge.success',
  'data' => 
  array (
    'id' => 1070363502,
    'domain' => 'test',
    'status' => 'success',
    'reference' => 'l1j7jbyxw7',
    'amount' => 200000,
    'message' => NULL,
    'gateway_response' => 'Successful',
    'paid_at' => '2021-04-05T13:45:37.000Z',
    'created_at' => '2021-04-05T13:45:24.000Z',
    'channel' => 'card',
    'currency' => 'NGN',
    'ip_address' => '197.210.64.8',
    'metadata' => [
        'payment_data' => '{"user":{"public_id":"USER_PUBLIC_ID"},"items":[{"public_id":"CONTENT_PUBLIC_ID","type":"content","price":{"public_id":"PRICE_PUBLIC_ID","amount":"2000.000","interval":"one-off",              "interval_amount":"0"}},{"public_id":"COLLECTION_PUBLIC_ID","type":"collection","price":{"public_id":"PRICE_PUBLIC_ID","amount":"2000.000","interval":"month","interval_amount":"1"}}]}',
    ],
    'log' => 
    array (
      'start_time' => 1617630334,
      'time_spent' => 3,
      'attempts' => 1,
      'errors' => 0,
      'success' => false,
      'mobile' => false,
      'input' => 
      array (
      ),
      'history' => 
      array (
        0 => 
        array (
          'type' => 'action',
          'message' => 'Attempted to pay with card',
          'time' => 3,
        ),
      ),
    ),
    'fees' => 3000,
    'fees_split' => NULL,
    'authorization' => 
    array (
      'authorization_code' => 'AUTH_nt4d4urhvd',
      'bin' => '408408',
      'last4' => '4081',
      'exp_month' => '12',
      'exp_year' => '2030',
      'channel' => 'card',
      'card_type' => 'visa',
      'bank' => 'TEST BANK',
      'country_code' => 'NG',
      'brand' => 'visa',
      'reusable' => true,
      'signature' => 'SIG_rcOqBKl0s1iQilVvAtbk',
      'account_name' => NULL,
    ),
    'customer' => 
    array (
      'id' => 42622484,
      'first_name' => NULL,
      'last_name' => NULL,
      'email' => 'fanan@yahoo.com',
      'customer_code' => 'CUS_6sjfq3zy7rjihi2',
      'phone' => NULL,
      'metadata' => NULL,
      'risk_action' => 'default',
      'international_format_phone' => NULL,
    ),
    'plan' => 
    array (
    ),
    'subaccount' => 
    array (
    ),
    'split' => 
    array (
    ),
    'order_id' => NULL,
    'paidAt' => '2021-04-05T13:45:37.000Z',
    'requested_amount' => 200000,
  ),
)  
```

SAMPLE DATA THAT GETS TO PURCHASE JOB
==========================================
```
[2021-04-06 11:53:57] local.INFO: array (
  'amount' => 2000,
  'user' => 
  (object) array(
     'public_id' => '17716066406048b15f7b9f4',
  ),
  'items' => 
  array (
    0 => 
    (object) array(
       'public_id' => '7295167416060b988189a0',
       'type' => 'content',
       'price' => 
      (object) array(
         'public_id' => '14438789396060b9883361b',
         'amount' => '2000.000',
         'interval' => 'one-off',
         'interval_amount' => '0',
      ),
    ),
    1 => 
    (object) array(
       'public_id' => '1339542399606c4985ca38f',
       'type' => 'collection',
       'price' => 
      (object) array(
         'public_id' => '1627117562606c4985e49aa',
         'amount' => '2000.000',
         'interval' => 'month',
         'interval_amount' => '1',
      ),
    ),
  ),
)  
```


TO CALCULATE HOW MUCH FLOK HAS MADE
===========================================
total_payments = SUM OF amount ON PAYMENTS TABLE
total_fees = SUM OF payment_processor_fee ON PAYMENTS TABLE
referal_bonuses = SUM OF referral_bonus ON REVENUES TABLE
net_amount = total_payments - fees
share_amount = 30% * net_amount
revenue = share_amount - referal_bonuses


FOR WHEN WE COMPLICATE PRICING
=================================
- you can't delete a price
- you can update it
- if you need something more, you add a new price type


SUPERVISORD
===============
https://github.com/slashfan/docker-supervisor-php-workers


FLUTTERWAVE INLINE RESPONSE
==============================
{
  "provider":"flutterwave",
  "provider_response":{
    "status":"successful",
    "customer":{
      "name":"User One",
      "email":"user3@test.com"
    },
    "transaction_id":2164639,
    "tx_ref":"1621506819666",
    "flw_ref":"FLW-MOCK-e2e24df0125ea3710c7537f8bd5538b2",
    "currency":"NGN",
    "amount":9000
  },
  "user":{"public_id":"17716066406048b15f7b9f4"},
  "items":[
    {
      "public_id":"18240891946066d41d60016",
      "type":"content",
      "price":{
        "public_id":"18240891946066d41d6001p",
        "amount":"2000.000000",
        "interval":"one-off",
        "interval_amount":null
      }
    },
    {"public_id":"826963893606c9de73bc36","type":"content","price":{"public_id":"826963893606c9de73bc3p","amount":"2000.000000","interval":"one-off","interval_amount":null}},{"public_id":"678606098606ca378009e0","type":"content","price":{"public_id":"678606098606ca378009ep","amount":"2000.000000","interval":"month","interval_amount":1}},{"public_id":"1867378820606c9e5ab27c6","type":"collection","price":{"public_id":"1867378820606c9e5ab27cp","amount":"3000.000000","interval":"month","interval_amount":2}}
  ]
}
PAYSTACK INLINE RESPONSE
==============================
{
  "provider":"paystack",
  "provider_response":{
    "reference":"oFw667j3GZ",
    "trans":"1136455057",
    "status":"success",
    "message":"Approved",
    "transaction":"1136455057",
    "trxref":"oFw667j3GZ"
  },
  "user":{
    "public_id":"17716066406048b15f7b9f4"
  },
  "items":[
    {
      "public_id":"18240891946066d41d60016",
      "type":"content",
      "price":{
        "public_id":"18240891946066d41d6001p",
        "amount":"2000.000000",
        "interval":"one-off",
        "interval_amount":null
        }
      },
      {
        "public_id":"826963893606c9de73bc36","type":"content","price":{"public_id":"826963893606c9de73bc3p","amount":"2000.000000","interval":"one-off","interval_amount":null}},{"public_id":"678606098606ca378009e0","type":"content","price":{"public_id":"678606098606ca378009ep","amount":"2000.000000","interval":"month","interval_amount":1}},{"public_id":"1867378820606c9e5ab27c6","type":"collection","price":{"public_id":"1867378820606c9e5ab27cp","amount":"3000.000000","interval":"month","interval_amount":2}
    }
  ]
}

SEEDERS TO SEED ON PRODUCTION
=========================
CategorysTableSeeder
LanguagesTableSeeder
LocationsSeeder
RolesAndPermissionSeeder
ProdUsersSeeder


STRIPE PAYLOAD FROM FRONTEND
==============================
{
  "id": "tok_1J2xylAtljpdaWftHgKgqI5j",
  "object": "token",
  "card": {
    "id": "card_1J2xykAtljpdaWft8DmLd44f",
    "object": "card",
    "address_city": null,
    "address_country": null,
    "address_line1": null,
    "address_line1_check": null,
    "address_line2": null,
    "address_state": null,
    "address_zip": null,
    "address_zip_check": null,
    "brand": "Visa",
    "country": "US",
    "cvc_check": "unchecked",
    "dynamic_last4": null,
    "exp_month": 12,
    "exp_year": 2024,
    "funding": "credit",
    "last4": "4242",
    "name": null,
    "tokenization_method": null
  },
  "client_ip": "197.210.28.148",
  "created": 1623846983,
  "livemode": false,
  "type": "card",
  "used": false
}

## Install shaka packager
1. docker-compose exec api git clone https://chromium.googlesource.com/chromium/tools/depot_tools.git
2. docker-compose exec -it api export PATH=$PATH:${PWD}/depot_tools