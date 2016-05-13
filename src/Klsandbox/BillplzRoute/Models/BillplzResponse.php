<?php

namespace Klsandbox\BillplzRoute\Models;

use Illuminate\Database\Eloquent\Model;
use Klsandbox\SiteModel\SiteExtensions;

/**
 * Klsandbox\BillplzRoute\Models\BillplzResponse
 *
 * @property integer $id
 * @property string $billplz_id
 * @property string $collection_id
 * @property integer $paid
 * @property string $state
 * @property string $amount
 * @property integer $paid_amount
 * @property string $due_at
 * @property string $email
 * @property string $mobile
 * @property string $name
 * @property integer $metadata_proof_of_transfer_id
 * @property integer $metadata_user_id
 * @property integer $metadata_site_id
 * @property string $url
 * @property string $paid_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse wherePaidAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse whereBillplzId($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse whereCollectionId($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse wherePaid($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse whereState($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse whereAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse wherePaidAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse whereDueAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse whereEmail($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse whereMobile($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse whereMetadataOrderId($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse whereMetadataUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse whereMetadataSiteId($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse whereUrl($value)
 * @mixin \Eloquent
 * @property integer $site_id
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse whereSiteId($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\BillplzRoute\Models\BillplzResponse whereMetadataProofOfTransferId($value)
 */
class BillplzResponse extends Model
{
    use SiteExtensions;

    protected $table = 'billplz_responses';
    public $timestamps = true;
    protected $fillable = [
        'billplz_id', 'collection_id', 'paid', 'state', 'amount', 'paid_amount', 'due_at', 'email', 'mobile', 'name',
        'metadata_proof_of_transfer_id', 'metadata_user_id', 'metadata_site_id', 'url', 'paid_at'
    ];

    public static function getCountUserPay($user_id, $date, $end)
    {
        return BillplzResponse
            ::forSite()
            ->where('paid', true)
            ->where('created_at', '>=', $date)
            ->where('created_at', '<=', $end)
            ->where('metadata_user_id', $user_id)
            ->count();
    }
}
