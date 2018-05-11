<?php
namespace App\Directors\Deliveries;

use App\Directors\Stores\StoreFetcher;
use App\Models\Delivery;
use App\Models\DeliveryType;
use App\Traits\ModifiedCreatedDirectorTrait;

/**
 * Class DeliveryCreator
 * @package app\Directors\Deliveries
 */
class DeliveryCreator
{
    use ModifiedCreatedDirectorTrait;

    /**
     * @param array $input
     * @return Delivery
     * @throws \Exception
     */
    public function create($input): Delivery
    {
        if(!isset($input['store_id']) && groupIdentifier() == 'store') {
            $input['store_id'] = \Auth::user()->groupable_id;
        }
        if(!isset($input['type_id'])) {
            $input['type_id'] = DeliveryType::where('name', 'Standaard')->first()->id;
        }


        // Add created_by user to input
        $input = $this->addCreatedByUser($input);
        // Load Store fetcher
	    $storeFetcher = new StoreFetcher();
	    // Get Store
	    $store = $storeFetcher->getById($input['store_id']);
	    // Add Transporter id to input
	    $input['transporter_id'] = $store->transporter_id;
	    // Default setting for 'approved_result'
	    $input['approved_result'] = 0;
        // Create Delivery in database
        $delivery = Delivery::create($input);
        if($delivery instanceof Delivery) {
            return $delivery;
        }
        throw new \Exception('Delivery could not be created');
    }
}