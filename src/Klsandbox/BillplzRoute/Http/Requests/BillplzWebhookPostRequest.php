<?php

namespace Klsandbox\BillplzRoute\Http\Requests;

use App\Http\Requests\Request;

class BillplzWebhookPostRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'collection_id' => 'required',
            'paid' => 'required',
            'state' => 'required',
            'amount' => 'required',
            'due_at' => 'required',
            'paid_at' => 'required'
        ];
    }
}
