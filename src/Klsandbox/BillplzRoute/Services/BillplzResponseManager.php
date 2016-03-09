<?php

namespace Klsandbox\BillplzRoute\Services;

use Klsandbox\BillplzRoute\Models\BillplzResponse;
use App\Models\Order;
use App\Models\Product;
use Klsandbox\OrderModel\Models\ProductPricing;
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

        curl_close($curl);

        return $return;
    }

    public function createCollection()
    {
        $data = ['title' => config('billplz.title_for_create')];
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, config('billplz.collections_url'));
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

        curl_close($curl);

        return $return;
    }

    public function webhook($input)
    {
        \Log::info('webhook called ' . print_r($input, true));
        $bill_id = $input['id'];
        $metadata_order_id = $input['metadata']['order_id'];
        $metadata_user_id = $input['metadata']['user_id'];
        $metadata_site_id = $input['metadata']['site_id'];

        unset(
            $input['id'],
            $input['metadata']['order_id'],
            $input['metadata']['user_id'],
            $input['metadata']['site_id'],
            $input['metadata']
        );

        @$input['billplz_id'] = $bill_id;
        @$input['metadata_order_id'] = $metadata_order_id;
        @$input['metadata_user_id'] = $metadata_user_id;
        @$input['metadata_site_id'] = $metadata_site_id;

        BillplzResponse::create($input);

        $product_id = ProductPricing::find(Order::find($metadata_order_id)->product_pricing_id)->product_id;

        $order = Order::find($metadata_order_id);

        if ($input['paid'] === 'false') {
            $this->orderManager->rejectOrder($order);
        } else {
            if (Product::find($product_id)->name === 'Other') {
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
