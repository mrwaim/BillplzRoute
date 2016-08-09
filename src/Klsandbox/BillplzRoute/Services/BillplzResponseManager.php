<?php

namespace Klsandbox\BillplzRoute\Services;

use App\Models\Organization;
use App\Models\User;
use App\Services\ProductManager\ProductManagerInterface;
use App\Services\UserManager;
use Klsandbox\BillplzRoute\Models\BillplzResponse;
use Klsandbox\OrderModel\Models\OrderStatus;
use Klsandbox\OrderModel\Models\ProofOfTransfer;
use Klsandbox\OrderModel\Services\OrderManager;
use Log;

class BillplzResponseManager
{
    /**
     * @var OrderManager $orderManager
     */
    protected $orderManager;

    /**
     * @var UserManager $userManager
     */
    protected $userManager;

    /**
     * @var ProductManagerInterface $productManager
     */
    protected $productManager;

    public function __construct(OrderManager $orderManager, UserManager $userManager, ProductManagerInterface $producManager)
    {
        $this->orderManager = $orderManager;
        $this->userManager = $userManager;
        $this->productManager = $producManager;
    }

    public function getBill($bill_id, $billblz_api_key)
    {
        assert($billblz_api_key != null);

        $curl = curl_init();

        $url = config('billplz.bills_url') . '/' . $bill_id;
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POST, 0);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $billblz_api_key);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        Log::info(curl_getinfo($curl));
        $result = curl_exec($curl);
        Log::info($result);

        $originalResult = $result;
        $return = json_decode($result);

        if (!$return) {
            Log::error("Failed to decode json <$originalResult> on url:$url");

            return;
        }

        curl_close($curl);

        $bill = (array)$return;

        if (key_exists('error', $bill)) {
            \App::abort(500, 'Billplz error message:' . $bill['error']->message . ' type:' . $bill['error']->type);
        }

        if (key_exists('metadata', $bill)) {
            $bill['metadata'] = (array)$bill['metadata'];
        }

        $bill = $this->prepareBillData($bill);

        return $bill;
    }

    public function createBill($data, $billplzKey)
    {
        $data['mobile'] = $this->checkUserMobile($data['mobile']);

        Log::info('createBill', $data);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, config('billplz.bills_url'));
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

        $metadata_proof_of_transfer_id = null;
        $metadata_user_id = null;

        if (key_exists('metadata', $input)) {
            $metadata_proof_of_transfer_id = $input['metadata']['proof_of_transfer_id'];
            $metadata_user_id = $input['metadata']['user_id'];
        }

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

        return $input;
    }

    public function parseWebhookData($input)
    {
        $input = $this->prepareBillData($input);

        $metadata_proof_of_transfer_id = $input['metadata_proof_of_transfer_id'];
        assert($metadata_proof_of_transfer_id);

        /**
         * @var $proofOfTransfer ProofOfTransfer
         */
        $proofOfTransfer = ProofOfTransfer::find($metadata_proof_of_transfer_id);
        assert($proofOfTransfer);

        $billplz_api_key = $proofOfTransfer->order->organization->billplz_key;

        $verifiedBill = $this->getBill($input['billplz_id'], $billplz_api_key);

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
        $billplzId = $billplzData['billplz_id'];

        BillplzResponse::create($billplzData);

        /**
         * @var $proofOfTransfer ProofOfTransfer
         */
        $proofOfTransfer = ProofOfTransfer::find($metadata_proof_of_transfer_id);

        assert($proofOfTransfer);

        $order = $proofOfTransfer->order;

        assert($order);

        $hasOther = false;
        foreach ($order->orderItems as $orderItem) {
            if ($orderItem->product->isOtherProduct()) {
                $hasOther = true;
            }
        }

        if ($billplzData['paid'] !== 'true' && $billplzData['paid'] !== 1 && $billplzData['paid'] !== true && $billplzData['paid'] !== '1') {
            Log::info("paid not true - order:$order->id billplz_id:$billplzId");
            $this->orderManager->rejectOrder($order);
        } elseif ($billplzData['paid_amount'] == 0) {
            Log::info("paid_amount is 0 - order:$order->id billplz_id:$billplzId");
            $this->orderManager->rejectOrder($order);
        } else {
            if ($billplzData['paid_amount'] != $billplzData['amount']) {
                Log::info("paid_amount != amount - order:$order->id billplz_id:$billplzId");
                $this->orderManager->rejectOrder($order);
            } elseif ($hasOther) {
                Log::info("has other - order:$order->id billplz_id:$billplzId");
                $this->orderManager->setPaymentUploaded($order);
            } else {
                if ($order->orderStatus->id == OrderStatus::Approved()->id) {
                    Log::info("order already approved - order:$order->id billplz_id:$billplzId");
                } elseif ($order->orderStatus->id == OrderStatus::Shipped()->id) {
                    Log::info("order already shipped - order:$order->id billplz_id:$billplzId");
                } else {
                    Log::info("auto approve - order:$order->id billplz_id:$billplzId");
                    $this->orderManager->approveOrder($order->user, $order);
                }
            }
        }
    }

    /**
     * @param \Klsandbox\OrderModel\Models\Order $order
     * @param User $user
     * @param OrderPostRequest $request
     * @param UserManager $userManager
     * @param $totalAmount
     * @param ProofOfTransfer $proofOfTransfer
     * @param BillplzResponseManager $billPlzResponseManager
     * @return mixed
     */
    public function createOnlineBill(\Klsandbox\OrderModel\Models\Order $order)
    {
        $user = $order->user;
        $proofOfTransfer = $order->proofOfTransfer;
        $organization = $order->is_hq ? Organization::HQ() : $user->organization;

        $products = $order->orderItems()->get()->pluck('product')->all();
        $hasOrganizationMembership = $this->productManager->hasOrganizationMembership($products);

        if (!$organization && $hasOrganizationMembership) {
            $organization = $this->userManager->getMembershipOrganization($user);
        }

        assert($organization);
        $billplzKey = $organization->billplz_key;
        $billplzCollectionId = $organization->billplz_collection_id;

        if (config('billplz.is_test')) {
            $billplzKey = config('billplz.auth');
            $billplzCollectionId = config('billplz.collection_id');
        }

        if (!$billplzKey) {
            \App::abort(500, 'Billplz key not defined');
        }
        if (!$billplzCollectionId) {
            \App::abort(500, 'Billplz Colection id not defined');
        }

        $billData = [
            'collection_id' => $billplzCollectionId,
            'email' => $user->email,
            'name' => $user->name,
            'mobile' => null, //$user->phone,
            'amount' => $proofOfTransfer->amount * 100,
            'callback_url' => url('/billplz/webhook'),
            'redirect_url' => url('/order-management/view/' . $order->id),
            'metadata[proof_of_transfer_id]' => $proofOfTransfer->id,
            'metadata[user_id]' => $order->user_id,
        ];

        $description = [];

        foreach ($order->orderItems as $orderItem) {
            $description [] = $orderItem->product->name;
        }

        $billData['metadata[ ]'] = implode('; ', $description);

        $bill = $this->createBill($billData, $billplzKey);

        if (!$bill) {
            \App::abort(500, 'createBill failed - null');
        }

        if (property_exists($bill, 'error') && $bill->error) {
            $message = 'no message';
            if (is_array($bill->error->message)) {
                $message = implode(',', $bill->error->message);
            } elseif (is_string($bill->error->message)) {
                $message = $bill->error->message;
            }

            \App::abort(500, 'createBill failed ' . $bill->error->type . ' ' . $message);
        }

        $redirect_to = $bill->url;

        $order->bill_url = $redirect_to;
        $order->save();

        return $redirect_to;
    }

    private function checkUserMobile($mobile)
    {
        if (is_string($mobile) && $mobile{0} !== '6') {
            $mobile = '6' . $mobile;
        }

        return $mobile;
    }
}
