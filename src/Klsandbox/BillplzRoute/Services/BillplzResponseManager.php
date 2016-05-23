<?php

namespace Klsandbox\BillplzRoute\Services;

use Klsandbox\BillplzRoute\Models\BillplzResponse;
use Klsandbox\OrderModel\Models\ProofOfTransfer;
use Klsandbox\OrderModel\Services\OrderManager;
use Log;

class BillplzResponseManager
{
    /**
     * @var OrderManager $orderManager
     */
    protected $orderManager;

    public function __construct(OrderManager $orderManager)
    {
        $this->orderManager = $orderManager;
    }

    public function getBill($bill_id)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, config('billplz.get_bills_url') . '/' . $bill_id);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POST, 0);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, config('billplz.auth'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        Log::info(curl_getinfo($curl));
        $result = curl_exec($curl);
        Log::info($result);

        $return = json_decode($result);

        if (!$return) {
            Log::error('Failed to decode json <' . $result . '>');

            return;
        }

        curl_close($curl);

        $bill = (array) $return;
        $bill['metadata'] = (array) $bill['metadata'];

        $bill = $this->prepareBillData($bill);

        return $bill;
    }

    public function createBill($data)
    {
        $data['mobile'] = $this->checkUserMobile($data['mobile']);
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, config('billplz.bills_url'));
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, config('billplz.auth'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        Log::info(curl_getinfo($curl));
        $result = curl_exec($curl);
        Log::info($result);

        $return = json_decode($result);

        if (!$return) {
            Log::error('Failed to decode json <' . $result . '>');
        }

        curl_close($curl);

        return $return;
    }

    public function createCollection($collectionName, $billplzKey)
    {
        $data = ['title' => $collectionName];
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, config('billplz.collections_url'));
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $billplzKey);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        Log::info(curl_getinfo($curl));

        $result = curl_exec($curl);
        Log::info($result);

        $return = json_decode($result);

        curl_close($curl);

        return $return;
    }

    public function webhook($input)
    {
        $billplzData = $this->parseWebhookData($input);
        $this->processBillplzData($billplzData);
    }

    public function prepareBillData($input)
    {
        \Log::info('**prepareBillData called ' . print_r($input, true));
        $bill_id = $input['id'];
        $metadata_proof_of_transfer_id = $input['metadata']['proof_of_transfer_id'];
        $metadata_user_id = $input['metadata']['user_id'];
        $metadata_site_id = $input['metadata']['site_id'];

        unset(
            $input['id'],
            $input['metadata']['proof_of_transfer_id'],
            $input['metadata']['user_id'],
            $input['metadata']['site_id'],
            $input['metadata']
        );

        @$input['billplz_id'] = $bill_id;
        @$input['metadata_proof_of_transfer_id'] = $metadata_proof_of_transfer_id;
        @$input['metadata_user_id'] = $metadata_user_id;
        @$input['metadata_site_id'] = $metadata_site_id;

        return $input;
    }

    public function parseWebhookData($input)
    {
        $input = $this->prepareBillData($input);

        $verifiedBill = $this->getBill($input['billplz_id']);

        if ($verifiedBill['billplz_id'] != $input['billplz_id']) {
            \App::abort(403, 'invalid billplz id');
        }

        if ($verifiedBill['paid_amount'] != $input['paid_amount']) {
            \App::abort(403, 'invalid paid_amount');
        }

        if ($verifiedBill['paid'] != $input['paid']) {
            \App::abort(403, 'invalid paid');
        }

        return $verifiedBill;
    }

    public function processBillplzData(array $billplzData)
    {
        $metadata_proof_of_transfer_id = $billplzData['metadata_proof_of_transfer_id'];

        BillplzResponse::create($billplzData);

        $proofOfTransfer = ProofOfTransfer::find($metadata_proof_of_transfer_id);
        $order = $proofOfTransfer->order;

        $hasOther = false;
        foreach ($order->orderItems as $orderItem) {
            if ($orderItem->productPricing->product->isOtherProduct()) {
                $hasOther = true;
            }
        }

        if ($billplzData['paid'] !== 'true' && $billplzData['paid'] !== 1 && $billplzData['paid'] !== true && $billplzData['paid'] !== '1') {
            Log::info("paid not true - order:$order->id");
            $this->orderManager->rejectOrder($order);
        } elseif ($billplzData['paid_amount'] == 0) {
            Log::info("paid_amount is 0 - order:$order->id");
            $this->orderManager->rejectOrder($order);
        } else {
            if ($billplzData['paid_amount'] != $billplzData['amount']) {
                Log::info("paid_amount != amount - order:$order->id");
                $this->orderManager->rejectOrder($order);
            } elseif ($hasOther) {
                $this->orderManager->setPaymentUploaded($order);
            } else {
                $this->orderManager->approveOrder($order);
            }
        }
    }

    private function checkUserMobile($mobile)
    {
        if (is_string($mobile) && $mobile{0} !== '6') {
            $mobile = '6' . $mobile;
        }

        return $mobile;
    }
}
