<?php
namespace App\Http\Controllers\Deliveries;

use App\DataCollectors\Deliveries\DeliveryDataCollector;
use App\DataCollectors\Deliveries\MonitorDataCollector;
use App\DataCollectors\Deliveries\OrderedDeliveryDataCollector;
use App\Directors\Stores\StoreFetcher;
use App\Http\Controllers\AbstractController;
use App\Http\Requests\Delivery\DeliveryIndexRequest;
use App\Http\Requests\Delivery\DeliveryOrderConfirmedRequest;
use App\Http\Requests\Delivery\DeliveryStoreDriverTimePostRequest;
use App\Http\Requests\Delivery\DeliveryStoreOrderRequest;
use App\Http\Requests\Delivery\DeliveryStorePostRequest;
use App\Http\Requests\Delivery\DeliveryUpdatePostRequest;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class DeliveriesController
 * @package App\Http\Controllers\Deliveries
 */
class DeliveriesController extends AbstractController
{
    /**
     * Display a listing of the resource.
     *
     * @param DeliveryIndexRequest $request
     * @param DeliveryDataCollector $collector
     * @return \Illuminate\Http\Response
     */
    public function index(DeliveryIndexRequest $request, DeliveryDataCollector $collector)
    {
        // Get data with of without a search
        $data = $collector->index($request);
        return view('groups.' . groupIdentifier() . '.deliveries.index', $data);
    }
    
    /**
     * Show delivery view options on the dashboard
     *
     * @param DeliveryIndexRequest $request
     * @param DeliveryDataCollector $collector
     * @return Factory|View
     */
    public function dashboard(DeliveryIndexRequest $request, DeliveryDataCollector $collector)
    {
        
        // Office login must go to monitor
        if(groupIdentifier() == 'office') {
            // Send user to dashboard
            return redirect()->route('monitor.index');
        }
        
        // Get group options
        $data = $collector->dashboard($request);
        
        // Store and Driver needs to be treated differently
        if (groupIdentifier() == 'store' || groupIdentifier() == 'driver') {
            // Return the view
            return view('groups.' . groupIdentifier() . '.deliveries.index', $data);
        }
        
        // Return the view
        return view('groups.' . groupIdentifier() . '.dashboard.index', $data);
    }
    
    /**
     * Update deliveries with drivers and times
     *
     * @param DeliveryStoreDriverTimePostRequest $request
     * @param DeliveryDataCollector $collector
     * @return RedirectResponse
     */
    public function storeDriversAndTimes(
        DeliveryStoreDriverTimePostRequest $request,
        DeliveryDataCollector $collector
    ): RedirectResponse {
        // Store each delivery and add driver and time if available
        $collector->storeDriversAndTimes($request);
        
        // Get date from request if available
        $date['date'] = $request->get('date');
        
        // Redirect transporter to correct index and correct date if selected
        return redirect()->route('deliveries.index', $date)->with('message',
            trans('messages.deliveryStored'));
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @param DeliveryDataCollector $collector
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, DeliveryDataCollector $collector)
    {
        $data = $collector->create($request);
        return view('groups.' . groupIdentifier() . '.deliveries.edit', $data);
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param DeliveryStorePostRequest $request
     * @param DeliveryDataCollector $collector
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function store(DeliveryStorePostRequest $request, DeliveryDataCollector $collector)
    {
        // Get the date from the request for the redirect
        $date = $request->get('date');
        
        // Get freshly created delivery id
        $id = $collector->store($request);
        
        // Store needs to be treated differently
        if (groupIdentifier() == 'store') {
            
            // Send user to dashboard
            return redirect()->route('dashboard', ['date' => $date])->with('message', trans('messages.deliveryStored'));
        }
        
        // Return to show
        return redirect()->route('deliveries.show', ['id' => $id, 'date' => $date])->with('message', trans('messages.deliveryStored'));
    }
    
    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param  int $id
     * @param  DeliveryDataCollector $collector
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, int $id, DeliveryDataCollector $collector)
    {
        // Get Delivery by ID
        $data = $collector->show($request, $id);
        
        // Return show
        return view('groups.' . groupIdentifier() . '.deliveries.show', $data);
    }
    
    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param  int $id
     * @param  DeliveryDataCollector $collector
     * @return \Illuminate\Http\Response
     */
    public function modal(Request $request, int $id, DeliveryDataCollector $collector)
    {
        // Get Delivery by ID
        $data = $collector->show($request, $id);
        
        // Return show
        return view('groups.' . groupIdentifier() . '.deliveries.modal', $data);
    }
    
    /**
     * Get the Deliveries for de Transporter to print
     *
     * @param Request $request
     * @param DeliveryDataCollector $collector
     * @return \View
     */
    public function print(Request $request, DeliveryDataCollector $collector)
    {
        // Get data with of without a search
        $data = $collector->index($request);
        
        // Return view blade file
        return view('groups.' . groupIdentifier() . '.deliveries.print', $data);
    }
    
    
    /**
     * Display the list of Deliveries to be approved by the company before delivery
     *
     * @param Request $request
     * @param DeliveryDataCollector $collector
     * @return Factory|View
     */
    public function approve(Request $request, DeliveryDataCollector $collector)
    {
        // Get the data for the approve view
        $data = $collector->approve($request);
        
        // Return view blade file
        return view('groups.' . groupIdentifier() . '.deliveries.approve', $data);
    }
    
    /**
     * @param Request $request
     * @param DeliveryDataCollector $collector
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approveSave(Request $request, DeliveryDataCollector $collector)
    {
        $collector->approveSave($request);
        
        // Get date from request if available
        $data['date'] = $request->get('date');
        
        // Get transporter ID
        $data['transporter_id'] = $request->get('transporter_id');
        
        
        return redirect()->route('deliveries.approve', $data)->with('message',
            trans('messages.deliveryApproveSaved'));
    }
    
    /**
     * @param Request $request
     * @param MonitorDataCollector $collector
     * @return Factory|View
     */
    public function monitor(Request $request, MonitorDataCollector $collector)
    {
        $data = $collector->monitor($request);
        return view('groups.' . groupIdentifier() . '.deliveries.monitor', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @param  int $id
     * @param DeliveryDataCollector $collector
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, int $id, DeliveryDataCollector $collector)
    {
        // Get Delivery by ID
        $data = $collector->edit($request, $id);
        
        // Get array with delivery statuses for dropdown
        $data['status'] = $collector->getDeliveryStatuses();
        
        // Return the view
        return view('groups.' . groupIdentifier() . '.deliveries.edit', $data);
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param DeliveryUpdatePostRequest $request
     * @param int $id
     * @param DeliveryDataCollector $collector
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function update(DeliveryUpdatePostRequest $request, int $id, DeliveryDataCollector $collector) {
        
        // Execute updater
        $collector->update($request, $id);
        
        // Return to the delivery it self if you are a store user
        if(groupIdentifier() == 'office') {
            // Return user with message
            return redirect()->route('deliveries.edit', ['id' => $id])->with('message', trans('messages.deliveryUpdated'));
            
        }
        
        // Return user with message
        return redirect()->route('deliveries.index', [])->with('message', trans('messages.deliveryUpdated'));
    }
    
    /**
     * Set the flag IS_CANCELLED to TRUE
     *
     * @param Request $request
     * @param DeliveryDataCollector $collector
     * @return RedirectResponse
     * @throws \Exception
     */
    public function cancel(Request $request, DeliveryDataCollector $collector): RedirectResponse
    {
        // Execute updater
        $collector->cancel($request);
        
        // Return user with message
        return redirect()->route('deliveries.index', ['date' => $request->get('date')])->with('message', trans('messages.deliveryUpdated'));
    }

    /**
     * Store ordered delivery in database and redirect to confirm
     *
     * @param DeliveryStoreOrderRequest $request
     * @param OrderedDeliveryDataCollector $collector
     * @return RedirectResponse
     */
    public function storeOrderedDeliveries(DeliveryStoreOrderRequest $request, OrderedDeliveryDataCollector $collector)
    {
        // Store data in database
        $collector->store($request);

        // Redirect to confirmation page
        return redirect()->route('deliveries.order.confirm', ['date' => $request->get('date')->format('d-m-Y')]);
    }

    /**
     * @param string $date
     * @param OrderedDeliveryDataCollector $collector
     * @return View
     */
    public function confirmOrderedDeliveries(string $date, OrderedDeliveryDataCollector $collector)
    {
        // Get data
        $data = $collector->getDeliveriesToConfirm($date);

        return view('groups.transporter.deliveries.confirm', $data);
    }

    /**
     * Update status of confirmed deliveries
     *
     * @param DeliveryOrderConfirmedRequest $request
     * @param string $date
     * @param OrderedDeliveryDataCollector $collector
     * @return RedirectResponse
     */
    public function confirmDeliveries(DeliveryOrderConfirmedRequest $request, string $date, OrderedDeliveryDataCollector $collector)
    {
        $collector->confirmOrderedDeliveries($date);

        return redirect()->route('deliveries.index')->with('message', 'Bezorgingen zijn geaccordeerd');
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return void
     */
    public function destroy($id)
    {
        dd('ERROR: deliveries delete is not allowed. ID=' . $id);
    }
    
    /**
     * Get the Store object based on id
     *
     * @param $id
     * @return \App\Models\Store
     */
    public function getStore($id)
    {
        // Get the Store fetcher
        $fetcher = new StoreFetcher();
        
        // Return the Store
        return $fetcher->getById($id);
    }
}
