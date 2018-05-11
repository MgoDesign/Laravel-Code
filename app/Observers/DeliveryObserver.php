<?php
namespace App\Observers;

use App\Models\Delivery;
use App\Models\DeliveryStatus;

/**
 * Class DeliveryObserver
 * @package App\Observers
 */
class DeliveryObserver
{

    // options:
    // creating = before create
    // created = after create

    // updating = before update
    // updated = after update

    // deleting = before delete
    // deleted = after delete

    public function created(Delivery $delivery)
    {
        $delivery->update(['delivery_status_id' => $this->getStatus('created')]);
    }

    /**
     * @param Delivery $delivery
     */
    public function updated(Delivery $delivery)
    {
        if (!$delivery->deliveryStatus) {

            $delivery->update(['delivery_status_id' => $this->getStatus('created')]);

        } else {

            switch ($delivery->deliveryStatus->identifier) {
                case 'created':

                    if ($delivery->driver_id && $delivery->delivery_time && $delivery->delivery_status_id != $this->getStatus('scheduled')) {
                        $delivery->update(['delivery_status_id' => $this->getStatus('scheduled')]);
                    }

                    break;

                case 'scheduled':

                    if ($delivery->approved_result == Delivery::APPROVED  && $delivery->delivery_status_id != $this->getStatus('approved')) {
                        $delivery->update(['delivery_status_id' => $this->getStatus('approved')]);
                    }

                    break;

//                case 'approved':
//
//
//
//                break;
            }
        }
    }

    /**
     * @param string $identifier
     * @return int
     */
    protected function getStatus(string $identifier): int
    {
        return DeliveryStatus::where('identifier', $identifier)->firstOrFail()->id;
    }
}