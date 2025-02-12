<?php

namespace App\Services;

use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\Log;

class PayPalService
{
    protected $provider;

    public function __construct()
    {
        $this->provider = new PayPalClient();
        $this->provider->setApiCredentials(config('paypal'));
    }

    // public function sendPayout($recipientEmail, $amount, $currency = 'USD')
    // {
    //     try {
    //         $this->provider->getAccessToken();

    //         $payoutData = [
    //             'sender_batch_header' => [
    //                 'email_subject' => 'You have received a payout!',
    //             ],
    //             'items' => [
    //                 [
    //                     'recipient_type' => 'EMAIL',
    //                     'amount' => [
    //                         'value' => number_format($amount, 2, '.', ''),
    //                         'currency' => $currency,
    //                     ],
    //                     'receiver' => $recipientEmail,
    //                     'note' => 'Payout from platform',
    //                     'sender_item_id' => uniqid(),
    //                 ],
    //             ],
    //         ];

    //         $response = $this->provider->createBatchPayout($payoutData);
    //         return $response;

    //     } catch (\Exception $e) {
    //         Log::error('PayPal Payout Error: ' . $e->getMessage());
    //         return ['error' => $e->getMessage()];
    //     }
    // }
    

    public function sendPayout($recipientEmail, $amount, $currency = 'USD')
    {
        try {
            $this->provider->getAccessToken();

            $payoutData = [
                'sender_batch_header' => [
                    'email_subject' => 'You have received a payout!',
                ],
                'items' => [
                    [
                        'recipient_type' => 'EMAIL',
                        'amount' => [
                            'value' => number_format($amount, 2, '.', ''),
                            'currency' => $currency,
                        ],
                        'receiver' => $recipientEmail,
                        'note' => 'Payout from platform',
                        'sender_item_id' => uniqid(),
                    ],
                ],
            ];

            $response = $this->provider->createBatchPayout($payoutData);

            // Return structured response
            return [
                'success' => true,
                'batch_id' => $response['batch_header']['payout_batch_id'] ?? null,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('PayPal Payout Error: ' . $e->getMessage());
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

}
