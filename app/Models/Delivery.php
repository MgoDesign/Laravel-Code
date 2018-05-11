<?php
namespace App\Models;

use App\Traits\DateConversionTrait;
use App\Traits\ModifiedCreatedModelTrait;
use App\Traits\StringToHtmlConversion;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Delivery
 *
 * @property int $id
 * @property int $type_id
 * @property int|null $occupation_id
 * @property int $address_id
 * @property int $store_id
 * @property int $transporter_id
 * @property int|null $transport_type_id
 * @property int|null $driver_id
 * @property int|null $delivery_recycling_id
 * @property int|null $delivery_status_id
 * @property string $date
 * @property string $delivery_time
 * @property string|null $delivered
 * @property string $employee
 * @property string|null $invoice
 * @property string $selling_value
 * @property string|null $day_part
 * @property int|null $delivery_level
 * @property int|null $free_of_charge
 * @property int|null $return_quantity
 * @property int|null $device_number
 * @property float|null $occupation_price
 * @property string $sum_to_pay
 * @property int|null $tariff_customer
 * @property int|null $tariff_transporter
 * @property int|null $pallet_quantity
 * @property float|null $max_cargo_length
 * @property float|null $extra_km
 * @property int|null $extra_product_quantity
 * @property string|null $comments
 * @property string|null $notes
 * @property string|null $notes_az
 * @property string|null $permission_name
 * @property string|null $permission_name_az
 * @property string|null $express_permission_name
 * @property string|null $express_permission_name_az
 * @property string|null $cancelled
 * @property int|null $cancelled_by
 * @property float|null $transport_price
 * @property string|null $transport_notes
 * @property string|null $read_by_transporter
 * @property int|null $order_by
 * @property string|null $signature_receiver
 * @property string|null $name_receiver
 * @property string|null $position_receiver
 * @property string|null $completed
 * @property int|null $flags
 * @property int $approved_result
 * @property int|null $approved_by
 * @property string|null $approved_at
 * @property int $created_by
 * @property int|null $modified_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \App\Models\Address $address
 * @property-read \App\Models\User $checkedBy
 * @property-read \App\Models\User $createdBy
 * @property-read \App\Models\DeliveryAdjustment $deliveryAdjustment
 * @property-read \App\Models\DeliveryOccupation|null $deliveryOccupation
 * @property-read \App\Models\DeliveryRecycling|null $deliveryRecycling
 * @property-read \App\Models\DeliveryStatus|null $deliveryStatus
 * @property-read \App\Models\DeliveryType $deliveryType
 * @property-read \App\Models\Driver|null $driver
 * @property-read string $content
 * @property-read string $end_time
 * @property-read \App\Models\User|null $modifiedBy
 * @property-read \App\Models\OrderedDelivery $orderedDelivery
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read \App\Models\Store $store
 * @property-read \App\Models\TransportType|null $transportType
 * @property-read \App\Models\Transporter $transporter
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery callInAdvance($enabled = true)
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery hasAgreed($enabled = true)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery hasElevator($enabled = true)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery inHouse($enabled = true)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery isCancelled($enabled = true)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery isDirect($enabled = true)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery isExpress($enabled = true)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery isRetry($enabled = true)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery isReturn($enabled = true)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Delivery onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AbstractModel orderByFlag($flag, $asc = 4)
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery toApartment($enabled = true)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery toOtherLocation($enabled = true)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereAddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereApprovedResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereCancelled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereCancelledBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereDayPart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereDeliveryLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereDeliveryRecyclingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereDeliveryStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereDeliveryTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereDeviceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereDriverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereEmployee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereExpressPermissionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereExpressPermissionNameAz($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereExtraKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereExtraProductQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereFlags($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereFreeOfCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereInvoice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereMaxCargoLength($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereModifiedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereNameReceiver($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereNotesAz($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereOccupationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereOccupationPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereOrderBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery wherePalletQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery wherePermissionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery wherePermissionNameAz($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery wherePositionReceiver($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereReadByTransporter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereReturnQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereSellingValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereSignatureReceiver($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereSumToPay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereTariffCustomer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereTariffTransporter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereTransportNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereTransportPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereTransportTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereTransporterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Delivery withPalletHandling($enabled = true)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Delivery withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Delivery withoutTrashed()
 * @mixin \Eloquent
 */
class Delivery extends AbstractModel
{
    // approved_result
    const NOT_CHECKED = 0;
    const APPROVED = 1;
    const DECLINED = 2;
    
    
    // Define flags
    const CALL_IN_ADVANCE = 1 << 0;
    const TO_APARTMENT = 1 << 1;
    const TO_OTHER_LOCATION = 1 << 2;
    const WITH_PALLET_HANDLING = 1 << 3;
    const IN_HOUSE = 1 << 4;
    const HAS_ELEVATOR = 1 << 5;
    const IS_DIRECT = 1 << 6;
    const HAS_AGREED = 1 << 7;
    const IS_EXPRESS = 1 << 8;
    const IS_RETURN = 1 << 9;
    const IS_RETRY = 1 << 10;
    const IS_CANCELLED = 1 << 11;
    const IS_APPROVED = 1 << 12;
    
    
    // Hold flags here
    protected $flagsAliases = [
        self::CALL_IN_ADVANCE => 'call_in_advance',
        self::TO_APARTMENT => 'to_apartment',
        self::TO_OTHER_LOCATION => 'to_other_location',
        self::WITH_PALLET_HANDLING => 'with_pallet_handling',
        self::IN_HOUSE => 'in_house',
        self::HAS_ELEVATOR => 'has_elevator',
        self::IS_DIRECT => 'is_direct',
        self::HAS_AGREED => 'has_agreed',
        self::IS_EXPRESS => 'is_express',
        self::IS_RETURN => 'is_return',
        self::IS_RETRY => 'is_retry',
        self::IS_CANCELLED => 'is_cancelled',
        self::IS_APPROVED => 'is_approved'
    ];
    
    use ModifiedCreatedModelTrait;
    use DateConversionTrait;
    use StringToHtmlConversion;
    use SoftDeletes;
    
    protected $appends = [
        'end_time'
    ];
    
    protected $fillable = [
        'address_id',
        'type_id',
        'occupation_id',
        'store_id',
        'transporter_id',
        'transport_type_id',
        'driver_id',
        'delivery_recycling_id',
        'delivery_status_id',
        'date',
        'delivery_time',
        'delivered',
        'employee',
        'invoice',
        'selling_value',
        'day_part',
        'delivery_level',
        'free_of_charge',
        'content',
        'return_number',
        'device_number',
        'type',
        'occupation_id',
        'occupation_price',
        'sum_to_pay',
        'pallet_number',
        'max_cargo_length',
        'extra_km',
        'extra_charge_tariff',
        'extra_product_number',
        'comments',
        'notes',
        'permission_name',
        'permission_name_az',
        'cancelled',
        'cancelled_by',
        'transport_price',
        'transport_notes',
        'read_by_transporter',
        'signature_customer',
        'completed',
        'flags',
        'approved_result',
        'approved_by',
        'created_by',
        'modified_by',
        'tariff_customer',
        'tariff_transporter'
    ];
    
    
    /**
     * From Dutch date to database date
     *
     * @param string $value
     */
    public function setDateAttribute(string $value)
    {
        // Create date object
        $date = Carbon::createFromFormat('d-m-Y', $value);
        
        // Reformat date to database date
        $value = $date->format('Y-m-d');
        
        // Set attribute in array
        $this->attributes['date'] = $value;
    }
    
    /**
     * From database date to Dutch date
     *
     * @param string $date
     * @return string
     */
    public function getDateAttribute(string $date)
    {
        // Create date object
        $date = Carbon::createFromFormat('Y-m-d', $date);
        
        // Reformat date to Dutch date
        return $date->format('d-m-Y');
    }
    
    /**
     * Convert the value of content from string to html
     *
     * @param string $value
     * @return string
     */
    public function getContentAttribute(string $value)
    {
        // Transfer the content of the delivery to html
        //$value = $this->toHtml($value);
        // Return the converted string
        return $value;
    }
    
    /**
     * Take the delivery time and remove the seconds
     *
     * @param string|null $deliveryTime
     * @return string
     *
     */
    public function getDeliveryTimeAttribute($deliveryTime)
    {
        // Check if startTime has value
        if ($deliveryTime) {
            // Get target time
            $time = Carbon::parse($deliveryTime);
            // return time in correct format
            return $time->format('H:i');
        }
        return null;
    }
    
    /**
     * Get end time of delivery based on the target time of the Transporter
     *
     * @return string
     */
    public function getEndTimeAttribute(): string
    {
        // Check if startTime has value
        if ($this->delivery_time) {
            // Delivery target in hours
            $target = $this->transporter->target;
            
            // Get target time
            $endTime = Carbon::parse($this->delivery_time)->addHours($target);
            // return time in correct format
            return $endTime->format('H:i');
        }
        // Return empty string if failed to make an end time
        return '';
    }

    /**
     * Dutch format sum to pay
     *
     * @param float|null $value
     * @return string
     */
    public function getSumToPayAttribute($value)
    {
        if ($value) {
            return number_format($value, 2, ',', '.');
        }
        return number_format(0, 2, ',', '.');
    }

    /**
     * Reformat Dutch format to database format
     *
     * @param string $value
     */
    public function setSumToPayAttribute(string $value)
    {
        $this->attributes['sum_to_pay'] = str_replace(',', '.', str_replace('.', '', $value));
    }

    /**
     * Dutch format selling value
     *
     * @param float|null $value
     * @return string
     */
    public function getSellingValueAttribute($value)
    {
        if ($value) {
            return number_format($value, 2, ',', '.');
        }
        return number_format(0, 2, ',', '.');
    }
    
    /**
     * Reformat Dutch format to database format
     *
     * @param string $value
     */
    public function setSellingValueAttribute(string $value)
    {
        $this->attributes['selling_value'] = str_replace(',', '.', str_replace('.', '', $value));
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * @return BelongsToMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * @return HasOne
     */
    public function deliveryAdjustment()
    {
        // return $this->belongsTo(DeliveryAdjustment::class);
        return $this->hasOne(DeliveryAdjustment::class);
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function deliveryRecycling()
    {
        return $this->belongsTo(DeliveryRecycling::class);
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function deliveryStatus()
    {
        return $this->belongsTo(DeliveryStatus::class);
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function deliveryType()
    {
        return $this->belongsTo(DeliveryType::class, 'type_id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function deliveryOccupation()
    {
        return $this->belongsTo(DeliveryOccupation::class, 'occupation_id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transportType()
    {
        return $this->belongsTo(TransportType::class);
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    /**
     * @return HasOne
     */
    public function orderedDelivery(): HasOne
    {
        return $this->hasOne(OrderedDelivery::class);
    }

    /* SCOPES */
    
    /**
     * @param Builder $query
     * @param bool $enabled
     * @return Builder
     */
    public function scopeCallInAdvance(Builder $query, bool $enabled = true)
    {
        $flag = self::CALL_IN_ADVANCE;
        return $this->checkFlagState($query, $flag, $enabled);
    }
    
    public function scopeToApartment(Builder $query, bool $enabled = true)
    {
        $flag = self::TO_APARTMENT;
        return $this->checkFlagState($query, $flag, $enabled);
    }
    
    public function scopeToOtherLocation(Builder $query, bool $enabled = true)
    {
        $flag = self::TO_OTHER_LOCATION;
        return $this->checkFlagState($query, $flag, $enabled);
    }
    
    public function scopeWithPalletHandling(Builder $query, bool $enabled = true)
    {
        $flag = self::WITH_PALLET_HANDLING;
        return $this->checkFlagState($query, $flag, $enabled);
    }
    
    public function scopeInHouse(Builder $query, bool $enabled = true)
    {
        $flag = self::IN_HOUSE;
        return $this->checkFlagState($query, $flag, $enabled);
    }
    
    public function scopeHasElevator(Builder $query, bool $enabled = true)
    {
        $flag = self::HAS_ELEVATOR;
        return $this->checkFlagState($query, $flag, $enabled);
    }
    
    public function scopeIsDirect(Builder $query, bool $enabled = true)
    {
        $flag = self::IS_DIRECT;
        return $this->checkFlagState($query, $flag, $enabled);
    }
    
    public function scopeHasAgreed(Builder $query, bool $enabled = true)
    {
        $flag = self::HAS_AGREED;
        return $this->checkFlagState($query, $flag, $enabled);
    }
    
    public function scopeIsExpress(Builder $query, bool $enabled = true)
    {
        $flag = self::IS_EXPRESS;
        return $this->checkFlagState($query, $flag, $enabled);
    }
    
    public function scopeIsReturn(Builder $query, bool $enabled = true)
    {
        $flag = self::IS_RETURN;
        return $this->checkFlagState($query, $flag, $enabled);
    }
    
    public function scopeIsRetry(Builder $query, bool $enabled = true)
    {
        $flag = self::IS_RETRY;
        return $this->checkFlagState($query, $flag, $enabled);
    }
    
    public function scopeIsCancelled(Builder $query, bool $enabled = true)
    {
        $flag = self::IS_CANCELLED;
        return $this->checkFlagState($query, $flag, $enabled);
    }

    
    
    /* /SCOPES */
    
}
