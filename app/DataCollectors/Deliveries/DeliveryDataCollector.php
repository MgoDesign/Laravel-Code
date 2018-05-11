<?php
namespace App\DataCollectors\Deliveries;

use App\Classes\DayPart;
use App\Classes\FreeDelivery;
use App\Classes\Level;
use App\DataCollectors\AbstractDataCollector;
use App\Directors\Addresses\AddressCreator;
use App\Directors\Addresses\AddressUpdater;
use App\Directors\Deliveries\DeliveryAdjustmentCreator;
use App\Directors\Deliveries\DeliveryAdjustmentUpdater;
use App\Directors\Deliveries\DeliveryApproveUpdater;
use App\Directors\Deliveries\DeliveryCreator;
use App\Directors\Deliveries\DeliveryDeleter;
use App\Directors\Deliveries\DeliveryFetcher;
use App\Directors\Deliveries\DeliveryRecyclingFetcher;
use App\Directors\Deliveries\DeliveryStatusFetcher;
use App\Directors\Deliveries\DeliveryTypeFetcher;
use App\Directors\Deliveries\DeliveryUpdater;
use App\Directors\Drivers\DriverFetcher;
use App\Directors\Relations\RelationFetcher;
use App\Directors\Stores\StoreFetcher;
use App\Directors\Transporters\TransporterFetcher;
use App\Directors\Transporters\TransportTypeFetcher;
use App\Models\Address;
use App\Models\Delivery;
use App\Models\Store;
use App\Models\Transporter;
use App\Service\DeliverySearchService;
use App\Services\DashboardBlockService;
use App\Services\FlagService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Class DeliveryDataCollector
 * @package App\DataCollectors\Deliveries
 */
class DeliveryDataCollector extends AbstractDataCollector
{
    
    /**
     * Return data for Delivery index
     *
     * @param Request $request
     * @return array
     */
    public function index(Request $request): array
    {
        // Determine the date
        $date = $this->setDate($request);

        $data['date'] = $date;
        
        // Check if value of Transporter ID is given
        if (groupIdentifier() == 'transporter') {
            
            // Get the id of the Transporter
            $request->merge(['transporter_id' => \Auth::user()->getAttribute('groupable_id')]);
            
            // Get Transporter info for the view
            $data['transporter'] = $this->getTransporter($request->get('transporter_id'));
            
            // Get the list of drivers
            $data['drivers'] = $this->getActiveDriversFromTransporter();
            
            // Does the driver id have a value
            if ($request->get('driver_id')) {
                $data['driver'] = $this->getDriver($request->get('driver_id'));
            }
            
            // Values for the period dropdowns
            $data['periods'] = $this->getPeriodDropDown($data['transporter']->target);
            
            // Get the deliveries for the transporter with the status 'created'
            $data['deliveries'] = $this->getDeliveriesByTransporterAndStatus($date);
            
        }
        else {
            // Default view for office
            $data['deliveries'] = $this->searchDeliveries($request->except('_token'));
        }
        
        // Time dropdown Array
        $data['timeArray'] = $this->getDeliveryTime();
        
        // Check if a Store has logged in
        if (groupIdentifier() == 'store') {
            // Determin the right date for a new Delivery
            $data['newDate'] = $this->getNewDeliveryDate($date);
            // Get store info
            $data['store'] = $this->getStore(\Auth::user()->groupable_id);
        }
        
        // Get all input values from the form
        $data['input'] = $request->all();
        
        // Get a list with relations
        $data['relations'] = $this->getRelations();
        
        // Does relation id has value
        if ($request->get('relation_id')) {
            
            // Load relation
            $data['relation'] = $this->getRelation($request->get('relation_id'));
            
            // Get stores from relation
            $data['stores'] = $this->getStoreSelection($request);
            
            // Check if Store id is given
            if ($request->get('store_id')) {
                
                // Get the Store fetcher
                $storeFetcher = new StoreFetcher();
                
                // Get Store data
                $data['store'] = $storeFetcher->getById($request->get('store_id'));
            }
        }
        
        // Get the dashboard info for the buttons
        $blocks = $this->dashboard($request);
        
        // Add the value of blocks to data
        $data['blocks'] = $blocks['blocks'];
        
        // Return the search value to the view
        $data['search'] = $request->get('search');
        
        // Return the build Array data
        return $data;
    }
    
    /**
     * Get dashboard Blocks
     *
     * @param Request $request
     * @return array
     */
    public function dashboard(Request $request): array
    {
        // Determine the date
        $date = $this->setDate($request);
        
        // Call the service
        $service = new DashboardBlockService();
        
        // Generate blocks for view
        $data['blocks'] = $service->generateBlocks();
        
        // Check if a Store is logged in
        if (groupIdentifier() == 'store') {
            
            // Get store information
            $data['store'] = $this->getStore(\Auth::user()->groupable_id);
            
            // Get Deliveries of this Store
            $data['deliveries'] = $this->getDeliveriesForStore($date);
            
            // Determin the right date for a new Delivery
            $data['newDate'] = $this->getNewDeliveryDate($date);
        }
        
        // Check if a Transporter is logged in
        if (groupIdentifier() == 'transporter') {
            
            // Get store information
            $data['transporter'] = $this->getTransporter(\Auth::user()->groupable_id);
            
            // Get Deliveries of this Store
            $data['deliveries'] = $this->getDeliveriesForTransporter($date);
            
        }
        
        // Check if a Driver is logged in
        if (groupIdentifier() == 'driver') {
            
            // Get the driver fetcher
            $driverFetcher = new DriverFetcher();
            
            // Get Driver info
            $driver = $driverFetcher->getById(\Auth::user()->groupable_id);
            
            // Get store information
            $data['transporter'] = $this->getTransporter($driver->transporter_id);
            
            // Get Deliveries of this Store
            $data['deliveries'] = $this->getDeliveriesForTransporter($date);
        }
        
        // Check if a Relation is logged in
        if (groupIdentifier() == 'relation') {
            
            // Get the driver fetcher
            $relationFetcher = new RelationFetcher();
            
            // Get Relation info
            $data['relation'] = $relationFetcher->getById(\Auth::user()->groupable_id);
        }
        
        // Return data
        return $data;
    }
    
    
    /**
     * Return data for the Delivery create
     *
     * @param Request $request
     * @return array
     */
    public function create(Request $request): array
    {
        // Date setting
        $date = $this->setDate($request);
    
        // Get the store id from the request
        $storeId = $request->get('store_id');
        
        // Check if date is before today
        if ($date->lessThan(Carbon::now())) {
            $date = Carbon::now();
        }
        
        // Check if value of a Store is logged in
        if (groupIdentifier() == 'store') {
            
            // Get the Store info by groupable_id
            $store = $this->getStore(\Auth::user()->groupable_id);

            // Check if store is in time to create a delivery without extra costs
            $data['isExpress'] = $this->deadlineChecker($store, $date);
            
        }
        
        // Office view
        if (groupIdentifier() == 'office') {
            
            // Only get the store data if a store id is given
            if(isset($storeId)) {
                
                // Get store information
                $store = $this->getStore($storeId);
    
                // Get the store_id from the request
                $data['storeId'] = $storeId;
            }
    
            // For the office view, the isExpress message must be hidden
            $data['isExpress'] = false;
        }
        
        // Add store to the data array if store is given
        if(isset($store)) {
            
            // Add the Store to the data
            $data['store'] = $store;
        }
        
        // Load Countries
        $data['countries'] = $this->getCountries();
        
        // Load Delivery types
        $data['types'] = $this->getDeliveryTypes();
        
        // Load Delivery occupations
        $data['occupations'] = $this->getDeliveryOccupationSelector();
        
        // Load Delivery recycling
        $data['recycling'] = $this->getRecycling();
        
        // Load Delivery status
        $data['status'] = $this->getDeliveryStatuses();
        
        // Load stores
        $data['stores'] = $this->getStores();
        
        // Load TransporTypes
        $data['transportTypes'] = $this->getTransportTypes();
        
        // Load Transporters
        $data['transporters'] = $this->getTransporters();
        
        // Load Delivery levels
        $data['levels'] = $this->getLevels();
        
        // Load Delivery types
        $data['types'] = $this->getDeliveryTypes();
        
        // Load Delivery day parts
        $data['dayParts'] = $this->getDayParts();
        
        // Get the settings from Relation and Store
        $data['setting'] = $this->getRelationAndStoreSetting();
        
        // Free delivery options
        $data['freeOfCharge'] = $this->getFreeOfCharge();
    
        // Determin the right date for a new Delivery
        $data['newDate'] = $this->getNewDeliveryDate($date);
        
        // Return view data
        return $data;
    }
    
    /**
     * Store delivery
     *
     * @param Request $request
     * @return int
     * @throws \Exception
     */
    public function store(Request $request)
    {
        // Get all inputs and values from the form
        $input = $request->all();

        // Load the address creator
        $addressCreator = new AddressCreator();

        // Create a new address
        $address = $addressCreator->create($input['address']);

        // Add address to input array
        $input['address_id'] = $address->id;

        // Create delivery
        $deliveryCreator = new DeliveryCreator();

        // Create Delivery
        $delivery = $deliveryCreator->create($input);
        
        // Return id of Delivery
        return $delivery->id;
    }
    
    /**
     * Store drivers and times for each given deliveries
     *
     * @param Request $request
     */
    public function storeDriversAndTimes(Request $request)
    {
        // Updater instance
        $updater = new DeliveryUpdater();
        
        // Check if there is anything to update
        if ($request->has('delivery')) {
            
            // Make collection of deliveries for looping
            $deliveries = collect($request->get('delivery'));
            
            // Loop through deliveries
            $deliveries->each(function ($delivery, $id) use ($updater) {
                
                // If one of the values is set, update the delivery
                if (!empty($delivery['delivery_time']) || !empty($delivery['driver_id'])) {
                    
                    // Update the delivery with time and driver if available
                    $updater->updateById($delivery, $id);
                }
                
            });
        }
    }
    
    /**
     * Get data for delivery show
     *
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function show(Request $request, int $id): array
    {
        // Date setting
        $date = $this->setDate($request);
        
        // Get Delivery data
        $delivery = $this->getDelivery($id);
        
        // Prevent an error 'string' instead of integer
        $dayValue = $delivery->day_part;
        
        // Check if dayValue is a number
        if (!is_numeric($dayValue)) {
            $dayValue = 1;
        }
        
        // Store info for reading settings
        $store = $this->getStore($delivery->getAttribute('store_id'));
        
        // Get name of day-part
        $delivery['day_part'] = $this->getDayPart($dayValue);
        
        // Return Delivery array
        return [
            'delivery' => $delivery,
            'store' => $store,
            'parameters' => 'start_date=' . $delivery->date,
            'setting' => $this->getRelationAndStoreSetting(),
            'newDate' => $this->getNewDeliveryDate($date)
        ];
    }
    
    /**
     * Get data required for edit view
     *
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function edit(Request $request, int $id): array
    {
        // Date setting
        $date = $this->setDate($request);
        
        // Load data also needed for create view
        $data = $this->create($request);
        
        // Get the Delivery based on id
        $delivery = $this->getDelivery($id);
    
        // Check the tariffs
        if($delivery->getAttribute('tariff_customer') == '') {
            $delivery->tariff_customer = $delivery->store->getAttribute('tariff_customer');
            $delivery->tariff_transporter = $delivery->store->getAttribute('tariff_transporter');
        }
        
        // Load delivery
        $data['delivery'] = $delivery;
        
        // dd($delivery);
        
        // Get the store info for the view
        if (groupIdentifier() == 'store') {
            // Get the store id from the user
            $storeId = \Auth::user()->groupable_id;
            
            // Show is_express form fields
            $data['isAfterDeadline'] = true;
        }
        else {
            // Get the store id from the delivery
            $storeId = $delivery->getAttribute('store_id');
        }
        
        // Get store info for the view
        $data['store'] = $this->getStore($storeId);
        
        // Add the store id to the array date
        $data['storeId'] = $storeId;
    
        // Get an allowed delivery date for the view
        $data['newDate'] = $this->getNewDeliveryDate($date);
        
        // Return data
        return $data;
    }
    
    /**
     * Update Delivery
     *
     * @param Request $request
     * @param int $id
     * @throws \Exception
     */
    public function update(Request $request, int $id)
    {
        // Get Delivery Model
        $delivery = $this->getDelivery($id);
        
        // Get all form objects
        $input = $request->all();
        
        // Add emptied flag values
        $service = new FlagService();
        
        // Handle the flag settings
        $input = $service->updateFlagByCheckboxes($delivery, $input);
        
        // Does the deliveryAdjustment has values
        if (isset($input['deliveryAdjustment'])) {
            // Process the Adjustment data for this Delivery
            $this->handleDeliveryAdjustment($input['deliveryAdjustment'], $delivery);
        }
        
        // Load updater
        $deliveryUpdater = new DeliveryUpdater();
        
        // save form values
        $deliveryUpdater->update($input, $delivery);
        
        // Load Address updater
        $address = new AddressUpdater();
        
        // Save Address
        $address->update($input['address'], $delivery->address_id);
    }
    
    /**
     * Erase a delivery, not deleting
     * @param Request $request
     * @throws \Exception
     */
    public function cancel(Request $request)
    {
        // Get Delivery Model
        $delivery = $this->getDelivery($request->get('id'));
        // Load updater
        $deliveryUpdater = new DeliveryUpdater();
        // save form values
        $deliveryUpdater->update(['is_cancelled' => true], $delivery);
    }
    
    /** Delete a Delivery
     *
     * @param int $id
     * @throws \Exception
     */
    public function destroy(int $id)
    {
        // Load deleter
        $deleter = new DeliveryDeleter();
        // Delete the Delivery
        $deleter->delete($id);
    }
    
    /** Get the list of deliveries which need to be approved for the work day after this day
     *
     * @param Request $request
     * @return mixed
     */
    public function approve(Request $request)
    {
        // Add info about the Transporter if the transporter id is given
        $transporterId = $request->get('transporter_id');
        
        // Get date from request or get today if none given
        $date = $request->get('date', Carbon::now()->format('d-m-Y'));
        
        // Add the choosen date to data
        $data['date'] = $date;
        
        // Parse to Carbon object
        $date = Carbon::parse($date);
        
        // Load fetcher
        $fetcher = new DeliveryFetcher();
        
        // Get result for Deliveries which are not checked by the office
        $data['deliveries'] = $fetcher->getToBeApprovedDeliveries($date, $transporterId);
        
        // Get result for Deliveries which are approved by the office
        $data['approved'] = $fetcher->getApprovedDeliveries($date, $transporterId);
        
        // Get result for Deliveries which are declined by the office
        $data['declined'] = $fetcher->getDeclinedDeliveries($date, $transporterId);
        
        // Get the Transporters
        $data['transporters'] = $this->getTransportersWithDeliveries($date);
        
        // If transporter id is numeric, get the info of the Transporter
        if (is_numeric($transporterId)) {
            $data['transporterArray'] = $this->getTransporter($transporterId);
        }
        
        // Return result
        return $data;
    }
    
    /**
     * Update the Delivery to the status Approved and send a sms
     * @JELTE: See DeliveryApproveUpdater for the 3 statuses: 0=nothing,1=approved,2=declined
     * @param Request $request
     * TODO: send MOLLIE sms
     */
    public function approveSave(Request $request)
    {
        // Load fetcher
        $fetcher = new DeliveryApproveUpdater();
        // Execute the update
        $fetcher->update($request->get('approved'));
    }
    
    /**
     * Get the Deliveries in print view
     * @param Request $request
     * @return mixed
     */
    public function print(Request $request)
    {
        // Determine the date
        $date = $this->setDate($request);
        
        // Get transporter info if a Store is logged in
        if (groupIdentifier() == 'store') {
            // Get the Deliveries for the Store with a date selection
            $data['deliveries'] = $this->getDeliveriesForStore(Carbon::parse($date)->format('Y-m-d'));
            
            // Store info
            $data['store'] = $this->getStore(\Auth::user()->getAttribute('groupable_id'));
        }
        
        // Get transporter info if a Transporter is logged in
        if (groupIdentifier() == 'transporter') {
            
            // Get the Deliveries for the Transporter with a date selection
            $data['deliveries'] = $this->getDeliveriesForTransporter($date);
            
            // Transporter info
            $data['transporter'] = $this->getTransporter(\Auth::user()->getAttribute('groupable_id'));
        }
        // Return the values of data
        return $data;
    }
    
    /**
     * Return Delivery array for selection
     *
     * @param $date
     * @return LengthAwarePaginator
     */
    public function getDeliveries($date = null): LengthAwarePaginator
    {
        // Load fetcher
        $fetcher = new DeliveryFetcher();
        // Get paginated result
        $deliveries = $fetcher->getPaginated($date);
        // Return paginated result
        return $deliveries;
    }
    
    /**
     * Get a single Delivery
     *
     * @param int $id
     * @return \App\Models\Delivery
     */
    public function getDelivery(int $id)
    {
        // Load fetcher
        $fetcher = new DeliveryFetcher();
        // Return the Delivery object
        return $fetcher->getById($id);
    }
    
    /**
     *  Get the list of Delivery statusses
     * @return array
     */
    public function getDeliveryStatuses(): array
    {
        // Load fetcher
        $fetcher = new DeliveryStatusFetcher();
        // Get all Delivery statuses
        $statuses = $fetcher->getAll();
        // Return array with name and id
        return $statuses->pluck('name', 'id')->all();
    }
    
    /**
     * Get the list of Delivery types
     * @return array
     */
    public function getDeliveryTypes(): array
    {
        // Load fetcher
        $fetcher = new DeliveryTypeFetcher();
        // Get all Delivery types
        $types = $fetcher->getAll();
        // Return array with name and id
        return ['' => DEFAULT_EMPTY_SELECTION] + $types->pluck('name', 'id')->all();
    }
    
    
    /**
     * Get building levels
     *
     * @return array
     */
    public function getLevels(): array
    {
        $levels = new Level();
        return $levels->getLevels();
    }
    
    /**
     * Get all day parts
     *
     * @return array
     */
    public function getDayParts(): array
    {
        $parts = new DayPart();
        return $parts->getDayParts();
    }
    
    /**
     * Get the name of the day part
     * @param int $id
     * @return mixed
     */
    public function getDayPart(int $id)
    {
        // Get the array Dayparts
        $dayParts = $this->getDayParts();
        // Start with empty variable
        $name = '';
        if (isset($dayParts)) {
            foreach ($dayParts as $key => $value) {
                if ($key == $id) {
                    $name = $value;
                    break;
                }
            }
        }
        // Return the day part name
        return ($name);
    }
    
    /**
     * Get a list of Recycling options
     * @return array
     */
    public function getRecycling(): array
    {
        // Load fetcher
        $fetcher = new DeliveryRecyclingFetcher();
        // Get all Delivery recycling
        $recycling = $fetcher->getAll();
        // get all Delivery recycling with id and name
        $recyclings = $recycling->pluck('name', 'id')->all();
        // Add empty line to result
        $data = ['' => DEFAULT_EMPTY_SELECTION] + $recyclings;
        // Return array with name and id
        return $data;
    }
    
    /**
     * Get a list of Stores
     * @return array
     */
    public function getStores(): array
    {
        // Load fetcher
        $fetcher = new StoreFetcher();
        // Get all Stores
        $store = $fetcher->getAll();
        // Get all Store with id and name
        $stores = $store->pluck('name', 'id')->all();
        // Add empty line to result
        $data = ['' => DEFAULT_EMPTY_SELECTION] + $stores;
        // Return array
        return $data;
    }
    
    /**
     * Get Deliveries belonging to the Store based on id
     *
     * @param $date
     * @return LengthAwarePaginator
     */
    public function getDeliveriesForStore($date)
    {
        // Load the fetcher
        $fetcher = new DeliveryFetcher();
        // Get the Deliveries only for this transporter
        $data = $fetcher->getDeliveriesForStore($date);
        // Return the data
        return $data;
    }
    
    /**
     * Get all Transporters
     *
     * @return array
     */
    public function getTransporters(): array
    {
        // Load fetcher
        $fetcher = new TransporterFetcher();
        // Get all Transporters
        $transporter = $fetcher->getAll();
        // Get all Transporters with id and name
        $transporters = $transporter->pluck('name', 'id')->all();
        // Add empty line to result
        $data = ['' => DEFAULT_EMPTY_SELECTION] + $transporters;
        // Return array
        return $data;
    }
    
    /**
     * Get all active Transporters
     *
     * @return array
     */
    public function getActiveTransporters(): array
    {
        // Load fetcher
        $fetcher = new TransporterFetcher();
        // Get all Transporters
        $transporter = $fetcher->getAllActive();
        // Get all Transporters with id and name
        $transporters = $transporter->pluck('name', 'id')->all();
        // Add empty line to result
        $data = ['' => DEFAULT_EMPTY_SELECTION] + $transporters;
        // Return array
        return $data;
    }
    
    /**
     * Get the Transporter based on id
     * @param int $id
     * @return \App\Models\Transporter
     */
    public function getTransporter(int $id): Transporter
    {
        // Load the fetcher
        $fetcher = new TransporterFetcher();
        // Get the Transporter
        return $fetcher->getById($id);
    }
    
    /**
     * Get a list of with Transporters with a total of Deliveries and a
     * count of uncompleted deliveries and completed delivers
     *
     * @param Carbon $date
     * @return mixed
     *
     * @todo: the return must be filled with:
     *      - transporter: total,
     *      - not-completed (no driver_id, no delivery_time)
     *      - completed
     *      = date selection
     */
    public function getTransportersWithDeliveries(Carbon $date)
    {
        // Load fetcher
        $fetcher = new TransporterFetcher();
        // Get all Transporters
        $data = $fetcher->getAllActiveWithDeliveries($date);
        // Return array
        return $data;
    }
    
    /**
     * Get Deliveries belonging to the Transporter based on id
     * @param $date
     * @return LengthAwarePaginator
     * @internal param $id
     */
    public function getDeliveriesForTransporter($date)
    {
        // Load the fetcher
        $fetcher = new DeliveryFetcher();
        // Get the Deliveries only for this transporter
        $data = $fetcher->getDeliveriesForTransporter($date);
        // Return the data
        return $data;
    }
    
    /**
     * Get the Drivers belonging to the Transporter based on id
     * @return array
     * @internal param $id
     */
    public function getDriversFromTransporter(): array
    {
        // Load Driver fetcher
        $fetcher = new DriverFetcher();
        
        // Get the Drivers only for this transporter
        $drivers = $fetcher->getAll();
        
        // Get all Drivers with id and name
        $data = ['' => DEFAULT_EMPTY_SELECTION] + $drivers->pluck('name', 'id')->all();
        
        // Return the data
        return $data;
    }
    
    /**
     * Get the active drivers for the logged in Transporter
     * @return array
     */
    public function getActiveDriversFromTransporter(): array
    {
        // Load Driver fetcher
        $fetcher = new DriverFetcher();
        
        // Return all active drivers for this Transporter
        return $fetcher->getAllActive()->all();
    }
    
    /**
     * Get the Transport Types
     * @return array
     */
    public function getTransportTypes(): array
    {
        // Load fetcher
        $fetcher = new TransportTypeFetcher();
        // Get all TransportTypes
        $transportType = $fetcher->getAll();
        // Get all TransportTypes with id and name
        $data = $transportType->pluck('name', 'id')->all();
        // Return array
        return $data;
    }
    
    /**
     * Search for Deliveries
     * @param array $search
     * @return LengthAwarePaginator
     */
    protected function searchDeliveries(array $search): LengthAwarePaginator
    {
        // Load the DeliverySearch serice
        $service = new DeliverySearchService($search);
        
        // Execute the search
        $service->search();
        
        // Return the search result
        return $service->getResult();
    }
    
    /**
     * Get a selection of Stores
     * @param Request $request
     * @return array
     */
    protected function getStoreSelection(Request $request): array
    {
        // Get the Store fetcher
        $storeFetcher = new StoreFetcher();
        // Get the stores belonging the the chosen Relation
        $stores = $storeFetcher->getAllStoresFromRelation($request->get('relation_id'));
        // Return only name and id
        $stores = ['' => DEFAULT_EMPTY_SELECTION] + $stores->pluck('name', 'id')->all();
        // return stores
        return $stores;
    }
    
    /**
     * Get the Store object based on id
     * @param int $id
     * @return Store
     */
    protected function getStore(int $id): Store
    {
        // Load the fetcher
        $fetcher = new StoreFetcher();
        // Get relation by id from request
        return $fetcher->getById($id);
    }
    
    /**
     * Get the Delivery Time Options
     *
     * @param int $id
     * @return array
     */
    protected function getDeliveryTime(int $id = null)
    {
        // Start with empty value
        $target = DEFAULT_DELIVERY_TARGET;
        // Load the fetcher
        $fetcher = new DeliveryFetcher();
        // Check if id has a value
        if ($id) {
            // Get the Delivery with the related Transporter info
            $delivery = $fetcher->getById($id);
            // Get the transporter
            $transporter = $this->getTransporter($delivery->transporter_id);
            // Target, the time between start- and end-time
            $target = $transporter->target;
        }
        // Return the array with Time options
        return $this->getPeriodDropDown($target);
    }
    
    /**
     * Create or update a DeliveryAdjustment if values are given
     *
     * @param array $input
     * @param Delivery $delivery
     * @throws \Exception
     */
    protected function handleDeliveryAdjustment(array $input, Delivery $delivery)
    {
        // Check if values are given
        if ($input) {
            
            // Set the delivery id
            $input['delivery_id'] = $delivery->id;
            
            // Check if the array has values
            $filter = array_filter($input);
            
            // Check if filter has value
            if ($filter) {
                // The Delivery has a deliveryAdjustment, so do an update
                if ($delivery->deliveryAdjustment) {
                    
                    // Get the update fetcher
                    $fetcher = new DeliveryAdjustmentUpdater();
                    
                    // Update the record
                    $fetcher->update($input, $delivery->deliveryAdjustment->id);
                    
                } // The Delivery has no deliveryAdjustment, so do a create
                else {
                    
                    // Get the create fetcher
                    $fetcher = new DeliveryAdjustmentCreator();
                    
                    // Create the record
                    $fetcher->create($input);
                }
            }
        }
    }
    
    /**
     * Check if the Store has choosen moment after the deadline setting
     *
     * @param Store $store
     * @param $date
     * @return bool
     */
    protected function deadlineChecker(Store $store, $date = ''): bool
    {
        // Deadline for the Store to add deliveries without separation chars
        $deadLine = Carbon::parse($store->deadline)->format('Hi');
        
        // Current selected date without the separation chars
        $date = Carbon::parse($date)->format('dmY');
        
        // Selected date with deadline time
        $selectedDate = $date . $deadLine;
        
        // Current date and time
        $currentDateTime = Carbon::parse(now())->format('dmYHs');
        
        // Difference between selected date and the current date and time
        $difference = $selectedDate - $currentDateTime;
        
        // Check if current time is after the Store his deadline setting and current day is
        if ($difference < 1) {
            // Show is_express form fields
            return true;
        }
        // No need to show the message
        return false;
    }
    
    /**
     * Get the settings for the logged in Store or based on the store id
     *
     * @return array
     */
    protected function getRelationAndStoreSetting(): array
    {
        // Get the flag service
        $service = new FlagService();
        
        // Get the settings for the view
        $settings = $service->getRelationAndStoreSetting();
        
        // Return the setting Array
        return $settings;
    }
    
    /**
     * Get a list of free delivery options
     * @return array
     */
    protected function getFreeOfCharge(): array
    {
        // get the free delivery options
        $freeDelivery = new FreeDelivery();
        
        // Return the options
        return ['' => DEFAULT_EMPTY_SELECTION] + $freeDelivery->getFreeDelivery();
    }
    
    /**
     * Get the Driver Model based on id
     * @param $id
     * @return \App\Models\Driver|\Illuminate\Database\Eloquent\Model
     */
    protected function getDriver($id): Model
    {
        // Get the fetcher for the driver
        $fetcher = new DriverFetcher();
        
        // Return the Driver model
        return $fetcher->getById($id);
    }
    
    /**
     * Get a new Delivery insert date for the button 'nieuw'
     *
     * @param $date
     * @return string
     */
    protected function getNewDeliveryDate(Carbon $date): string
    {
        // Get today's date
        $today = Carbon::parse(now());
        
        // Check if the choosen date is allowed for the logged in Store
        $dateCheck = $this->dayCanHaveDelivery($date);
        
        // Is the current date the same as today, add a day
        if ($date->format('d-m-Y') <= $today->format('d-m-Y') || !$dateCheck) {
            
            // Add a day to today's date
            $date = Carbon::parse($date)->addDay(1);
            
            // Try this date
            $date = $this->getNewDeliveryDate($date);
        }
        
        // Return the date value
        return Carbon::parse($date)->format('d-m-Y');
    }
    
    /**
     * Is it allowed to add a Delivery on the choosen day
     *
     * @param $date
     * @return bool
     */
    protected function dayCanHaveDelivery($date)
    {
        // Get the store id from the user
        if(groupIdentifier() == 'store') {
            // Get the Store fetcher
            $fetcher = new StoreFetcher();
    
            // Get the store data
            $store = $fetcher->getById(\Auth::user()->getAttribute('groupable_id'));
        
            // LOOP PREFENTION in getNewDeliveryDate
            // Start the counter
            $totalDeliveryCount = 0;
            
            // Loop the 7 days of the week
            for ($i = 1; $i < 7; $i++) {
                
                // Get the day name
                $dayName = Carbon::now()->subDays($i)->format('l');
                
                // Sum of all allowed deliveries
                $totalDeliveryCount = $totalDeliveryCount + $store->getAttribute(strtolower($dayName));
            }
            
            // Throw an exeption
            if ($totalDeliveryCount == 0) {
                dd('This Store is disabled: no allowed delivery insert days');
            }
            // /LOOP PREFENTION
            
            // Get the day name from the date
            $day = Carbon::parse($date)->format('l');
            
            // Check if the day name of the the new date is allowed for adding a delivery
            if ($store->getAttribute(strtolower($day)) == 0) {
                return false;
            }
        }
        
        // Return the true value
        return true;
    }
    
    
    /**
     * Get period drop down
     *
     * @param int $target
     * @return array
     */
    protected function getPeriodDropDown(int $target): array
    {
        // Empty start
        $data = [];
        
        // Loop through the 24 hours of the day
        for ($i = 0; $i < 24; ($i = $i + $target)) {
            
            // Set first time
            $firstTime = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
            
            // Set end time
            $endTime = str_pad(($i + $target), 2, '0', STR_PAD_LEFT) . ':00';
            
            // Add time as first time in database
            $data[$firstTime] = $firstTime . ' - ' . $endTime;
        }
        
        // Return the 24 hours
        return $data;
    }
    
    /**
     * @param string $date
     * @return Delivery[]|Collection
     */
    protected function getDeliveriesByTransporterAndStatus(string $date)
    {
        $fetcher = new DeliveryFetcher();
        return $fetcher->getDeliveriesForTransporterWithoutStatus($date);
    }
}