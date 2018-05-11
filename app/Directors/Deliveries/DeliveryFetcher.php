<?php
namespace App\Directors\Deliveries;

use App\Models\Delivery;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class DeliveryFetcher
 * @package App\Directors\Deliveries
 */
class DeliveryFetcher
{
    /**
     * @param int $paginate zero = no pagination
     * @return LengthAwarePaginator|Collection
     */
    public function getAll(int $paginate)
    {
        // Check if pagination is set
        if ($paginate !== 0) {
            return $this->getDeliveryInstance()->with('store')->orderBy('store_id',
                    'ASC')->orderBy('date', 'ASC')->paginate($paginate);
        }
        // Return Delivery collection
        return $this->getDeliveryInstance()->get();
    }
    
    /**
     * Get all Deliveries paginated
     *
     * @param $date
     * @return LengthAwarePaginator
     */
    public function getPaginated($date): LengthAwarePaginator
    {
        $date = Carbon::parse($date);
        return $this->getDeliveryInstance()->where('date',
                $date->format('Y-m-d'))->orderBy('store_id', 'ASC')->orderBy('date',
                'ASC')->orderBy('delivery_time', 'DESC')->paginate(DEFAULT_ITEMS_PER_PAGE);
    }
    
    /**
     * Get all Deliveries which have to be approved
     * @param  Carbon $date
     * @param mixed|null $transporterId
     * @return DeliveryFetcher|Delivery[]|Collection
     */
    public function getToBeApprovedDeliveries(Carbon $date, $transporterId = null): Collection
    {
        return $this->getApprovedResults($date, $transporterId, Delivery::NOT_CHECKED);
    }
    
    /**
     * Get all Deliveries which have to be approved
     *
     * @param Carbon $date
     * @param mixed|null $transporterId
     * @return DeliveryFetcher|Delivery[]|Collection
     */
    public function getDeclinedDeliveries(Carbon $date, $transporterId = null): Collection
    {
        return $this->getApprovedResults($date, $transporterId, Delivery::DECLINED);
    }
    
    /**
     * Get all Deliveries which have to be approved
     *
     * @param Carbon $date
     * @param mixed|null $transporterId
     * @return DeliveryFetcher|Delivery[]|Collection
     */
    public function getApprovedDeliveries(Carbon $date, $transporterId = null): Collection
    {
        return $this->getApprovedResults($date, $transporterId, Delivery::APPROVED);
    }
    
    /**
     * Get approved result for given status and date
     *
     * @param Carbon $date
     * @param $transporterId
     * @param mixed|null $approvedResult
     * @return Collection
     */
    protected function getApprovedResults(
        Carbon $date,
        $transporterId = null,
        int $approvedResult
    ): Collection {
        return $this->getDeliveryInstance()->where('approved_result',
            $approvedResult)->where('date', $date)->where('transporter_id',
                $transporterId)->whereHas('deliveryStatus', function (Builder $builder) {
                $builder->whereIn('identifier', ['scheduled', 'approved']);
            })->get();
        
    }
    
    
    /**
     * @param int $id
     * @return Delivery|Model
     */
    public function getById(int $id): Delivery
    {
        return $this->getDeliveryInstance()->with('address', 'deliveryType', 'deliveryStatus',
            'deliveryRecycling', 'deliveryAdjustment', 'deliveryOccupation', 'driver', 'store',
            'transporter', 'transportType')->where('id', $id)->first();
    }
    
    /**
     * Get all status with connected deliveries
     * For next day
     *
     * @param Carbon|null $date
     * @return Collection
     */
    public function getDeliveriesWithAdjustments(Carbon $date = null): Collection
    {
        // Only with adjustments
        $collection = $this->getDeliveryInstance()->with('transporter');
        
        // Check if date value is given
        if (!$date) {
            
            // If date is not given. use today
            $date = Carbon::now();
            
            // Get issues within the next MONITOR_ISSUE_MINUTES minutes.
            $collection->orWhere(function (Builder $q) use ($date) {
                // Only for today
                $q->where('date', $date->format('Y-m-d'));
                $q->whereRaw('delivery_time + INTERVAL (SELECT target FROM transporters WHERE id=deliveries.transporter_id) HOUR < "' . Carbon::now()->subMinutes(MONITOR_ISSUE_MINUTES)->format('H:i:s') . '"');
                $q->whereHas('deliveryStatus', function (Builder $q) {
                    $q->where('identifier', 'in-transit');
                });
            });
        } else {
            
            // Only adjustments of today
            $collection->where('date', $date->format('Y-m-d'));
        }
        
        // Return collection
        return $collection->orderByDesc('date')->get();
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    protected function getDeliveries()
    {
        return $this->getDeliveryInstance()->with('store', 'transporter');
    }
    
    /**
     * Get the deliveries only for given Transporter
     * @param $date
     * @return LengthAwarePaginator
     * @internal param $id
     */
    public function getDeliveriesForTransporter($date): LengthAwarePaginator
    {
        // Date parsing to db notation
        $date = Carbon::parse($date)->format('Y-m-d');
        
        return $this->getDeliveryInstance()->with('store', 'transporter')->where('date',
                $date)->whereHas('deliveryStatus', function (Builder $query) {
                $query->whereIn('identifier', ['created', 'scheduled']);
            })->orderBy('store_id', 'ASC')->orderBy('date',
                'ASC')->paginate(DEFAULT_ITEMS_PER_PAGE);
    }
    
    /**
     * @param string $date
     * @return Collection|Delivery[]
     */
    public function getDeliveriesForTransporterWithoutStatus(string $date)
    {
        // Date parsing to db notation
        $date = Carbon::parse($date)->format('Y-m-d');
        
        return $this->getDeliveryInstance()->where('date',
            $date)->doesntHave('orderedDelivery')->get();
    }
    
    /**
     * Get the deliveries only for given Store
     * @param $date
     * @return LengthAwarePaginator
     * @internal param $id
     */
    public function getDeliveriesForStore($date): LengthAwarePaginator
    {
        // Date parsing to db notation
        $date = Carbon::parse($date)->format('Y-m-d');
        
        $data = $this->getDeliveryInstance()->with('store', 'transporter')->where('date',
                $date)->orderBy('store_id', 'ASC')->orderBy('date',
                'ASC')->paginate(DEFAULT_ITEMS_PER_PAGE);
        
        return $data;
    }
    
    /** Get the values from Delivery combined with the permission check
     *
     * @return Builder|Delivery
     */
    protected function getDeliveryInstance(): Builder
    {
        return (new Delivery())->setPermissions()->newQuery();
    }
}