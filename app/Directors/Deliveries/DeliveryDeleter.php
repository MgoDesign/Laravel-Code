<?php
namespace App\Directors\Deliveries;

use App\Models\Delivery;

/**
 * Class DeliveryDeleter
 * @package app\Directors\Delivery
 */
class DeliveryDeleter
{
    /** Delete Delivery
     *
     * @param int $id
     * @throws \Exception
     */
    public function delete(int $id)
    {
        // Load object
        $delivery = Delivery::find($id);

        // Delete Delivery
        $delivery->delete();
    }
}