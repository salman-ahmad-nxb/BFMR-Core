<?php

// namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Query\Builder;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use DateTimeInterface;

class Deal extends Model implements HasMedia
{
    use Sluggable, HasMediaTrait, SoftDeletes;

    /**
     * The attributes that should be appended in models.
     *
     * @var array
     */

    protected $appends = [
        'picture_url',
        'items_attached',
        'is_new',
        'tag_names',
        'color',
        'description',
        'deal_price',
        'terms_conditions',
        'commission',
        'deal_response',
        'deal_id',
        'price_low',
        'price_high',
        'picture_url',
        'valid_date',
        'old_subscribers_count',
        'multiple_items_instructions',
        't_c_status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'published_at' => 'datetime',
        'ends_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable()
    {
        return [
            'slug' => [
                'source' => 'title',
            ],
        ];
    }

    public function registerMediaCollections()
    {
        $this->addMediaCollection('pictures')->singleFile();
    }


    /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePower($query)
    {
        return $query->where('power',0);
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    /*
     *  Mutators
     *------------
     */

    public function getColorAttribute()
    {
      return $this->attributes['subtitle'];
    }
    public function getDealPriceAttribute()
    {
      return number_format($this->attributes['value'],2);
    }

    public function getValueAttribute()
    {
        return number_format($this->attributes['value'],2);
    }

    public function getDescriptionAttribute()
    {
      return '';
    }
    public function getTermsConditionsAttribute()
    {
        return $this->attributes['instructions'];
    }
    public function getCommissionAttribute()
    {
        return '';
    }
    public function getDealIdAttribute()
    {
        return $this->attributes['id'];
    }
    public function getDealResponseAttribute()
    {
        return '';
    }
    public function getPriceLowAttribute()
    {
        return $this->attributes['value'];
    }
    public function getPriceHighAttribute()
    {
        return $this->attributes['value'];
    }
    public function getImageAttribute()
    {
        if ($this->getFirstMedia('pictures')) {
            return $this->getFirstMedia('pictures')->getFullUrl();
        }
        return '';
    }



    public function getPictureUrlAttribute()
    {
        if ($this->getFirstMedia('pictures')) {
            return $this->getFirstMedia('pictures')->getFullUrl();
        }
        return '';
    }

/*    public function getItemsAttachedAttribute()
    {
        $itemsAttached = [];
        foreach ($this->dealItems as $dealItem) {
            $itemLink = $dealItem->item->itemLinks()->get();
            foreach ($itemLink as $link){
                if (array_key_exists($link->vendor->name,$itemsAttached)) {
                    array_push($itemsAttached[$link->vendor->name], [
                        'vendor_id'  => $link->vendor_id,
                        'logo_url'   => $link->vendor->logo_url,
                        'vendor'   => $link->vendor->name,
                        'item_id'    => $dealItem->item_id,
                        'item_order' => $dealItem->item_order,
                        'media'      => $dealItem->item->media,
                        'color'      => $dealItem->item->color,
                        'link_url'   => $link ? $link->link_url : '',
                    ]);
                }else{
                    $itemsAttached[$link->vendor->name]  =[];
                    array_push($itemsAttached[$link->vendor->name], [
                        'vendor_id'  => $link->vendor_id,
                        'logo_url'   => $link->vendor->logo_url,
                        'vendor'   => $link->vendor->name,
                        'item_id'    => $dealItem->item_id,
                        'item_order' => $dealItem->item_order,
                        'media'      => $dealItem->item->media,
                        'color'      => $dealItem->item->color,
                        'link_url'   => $link ? $link->link_url : '',
                    ]);
                }
            }
        }
        return $itemsAttached;
    }*/


    public function getItemsAttachedAttribute()
    {
        $itemsAttached = [];
        foreach ($this->dealVendors()->orderBy('vendor_order', 'asc')->get() as $dealVendor) {
            $vendorName = $dealVendor->vendor->name;
            if (!array_key_exists($vendorName, $itemsAttached)) {
                $itemsAttached[$vendorName] = [
                    'vendor_name' => $vendorName,
                    'item_links' => [],
                ];
            }
            if (!$this->multiple_items || !$this->sil || count($this->deal_links) == 0) {
                $itemLink = ItemLink::where('item_id', $dealVendor->item_id)->where('vendor_id', $dealVendor->vendor_id)->first();
                array_push($itemsAttached[$vendorName]['item_links'], [
                    'color' => $dealVendor->item->color,
                    'url' => $itemLink ? $itemLink->link_url : '',
                ]);
            }
        }
        return $itemsAttached;
    }


    public function getUniqueItemsAttribute()
    {
        $itemsAttached = [];
        foreach ($this->dealItemQuantities as $dealItem) {
            array_push($itemsAttached, [
                'item_id'  => $dealItem->item_id,
                'quantity' => $dealItem->quantity,
            ]);
        }
        return $itemsAttached;
    }

    public function getTagNamesAttribute()
    {
        return $this->tags()->pluck('name')->all();
    }
    public function benifits()
    {
        return $this->belongsToMany(Tag::class);
    }
    public function getValidDateAttribute()
    {
        return $this->attributes['ends_at'];
    }
    public function getOldSubscribersCountAttribute()
    {
        return '';//$this->attributes['order_deals_count'];
    }
    public function getMultipleItemsInstructionsAttribute()
    {
        $instructions = '';
        if ($this->multiple_items) {
            $deal_vendors = DealVendor::where('deal_id', $this->id)->orderBy('price', 'DESC')->groupBy('item_id')->get();
            foreach ($deal_vendors as $deal_vendor) {
                $item_value = $deal_vendor->price;
                $instructions .= "We are paying \${$item_value} for each {$deal_vendor->item->name}. ";
            }
            $deal_value = $this->value;
            $instructions .= "\${$deal_value} in total.";
        }

        return $instructions;
    }
    public function getTCStatusAttribute()
    {

        return '';
    }

    /*public function getVendorsIdsAttribute()
    {
        return $this->has('vendors') ? $this->vendors()->pluck('vendors.id')->all() : [];
    }*/

    public function getIsNewAttribute()
    {
        if (\Request::header('Authorization')) {
            $user = \JWTAuth::parseToken()->authenticate();
            if ($user) {
                $dealVisit = DealVisit::where('deal_id', $this->id)
                    ->where('user_id', $user->id)
                    ->first();
                if (!$dealVisit) {
                    $dealVisit = new DealVisit();
                    $dealVisit->deal_id = $this->id;
                    $dealVisit->user_id = $user->id;
                    $dealVisit->save();
                    return true;
                }

                $before24hours = now()->subHours(24);
                if ($dealVisit->created_at->gte($before24hours)) {
                    return true;
                }
                $dealVisit->new = false;
                $dealVisit->save();
            }
        }

        return false;
    }

    public function getAvailableAddressesAttribute($value)
    {
        $ids = json_decode($value, true);
        return ShippingAddress::whereIn('id', $ids)->get();

    }
    public function setAvailableAddressesAttribute($values)
    {
        //$this->attributes['available_addresses'] = implode(',', $values);
        //$this->attributes['available_addresses'] = json_encode($values);
        $valueStr = '[';
        foreach ($values as $i => $value) {
            $valueStr .= $value;
            if ($i < (count($values) - 1)) {
                $valueStr .= ',';
            }
        }
        $valueStr .= ']';

        $this->attributes['available_addresses'] = $valueStr;
    }

    public function getDealLinksAttribute($value)
    {
        return is_null($value) ? [] : json_decode($value, true);
    }

    public function setDealLinksAttribute($value)
    {
        $this->attributes['deal_links'] = (is_array($value) && count($value) > 0) ? json_encode($value) : null;
    }

    /*
     *  Relations
     *-------------
     */

    public function dealItems()
    {
        return $this->hasMany(DealItem::class);
    }

    public function dealVendors()
    {
        return $this->hasMany(DealVendor::class);
    }

    public function dealItemQuantities()
    {
        return $this->hasMany(DealItemQuantity::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function meta()
    {
        return $this->hasMany(DealMeta::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->where('parent_id', null);
    }

    public function commitments()
    {
        return $this->hasMany(UserCommitment::class);
    }

    public function orderDeals()
    {
        return $this->hasMany(OrderDeal::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function receivedItems()
    {
        return $this->hasMany(ReceivedItem::class);
    }

    public function primaryAddress()
    {
        return $this->belongsTo(ShippingAddress::class, 'address_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }


    /*
     *  Reusable
     *------------
     */

    public function getMetaByTitle($title)
    {
        $value = '';
        $meta = $this->meta()->where('title', $title)->first();
        if ($meta) {
            $value = $meta->value;
        }
        if ($value == '') {
            switch ($title) {
                case 'section_title':
                    $value = '<p style="text-align:center;"><span class="text-huge">Thanks for subscribing! Check out the deal details below!</span></p>';
                    break;
                case 'info_heading':
                case 'info_heading_tn':
                    $value = '<p style="text-align:center;"><span class="text-huge">DEAL DETAILS</span></p>';
                    break;
            }
        }
        return $value;
    }
}
