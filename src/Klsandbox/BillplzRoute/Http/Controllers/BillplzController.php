<?php

namespace Klsandbox\BillplzRoute\Http\Controllers;

use App\Http\Controllers\Controller;
use Klsandbox\BillplzRoute\Http\Requests\BillplzWebhookPostRequest;
use Klsandbox\BillplzRoute\Services\BillplzResponseManager;

class BillplzController extends Controller
{
    /**
     * @var BillplzResponseManager $billPlzResponseManager
     */
    protected $billPlzResponseManager;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(BillplzResponseManager $billplzResponseManager)
    {
        $this->billPlzResponseManager = $billplzResponseManager;
    }

    public function postWebhook(BillplzWebhookPostRequest $request)
    {
        $this->billPlzResponseManager->webhook($request->input());
    }
}
