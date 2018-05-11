<?php
namespace App\Http\Requests\Delivery;

use App\Http\Requests\AbstractRequest;

/**
 * Class DeliveryIndexRequest
 * @package App\Http\Requests
 */
class DeliveryIndexRequest extends AbstractRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize()
	{
		return checkPermission('deliveries.index');
	}
	
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules()
	{
		return [
			//
		];
	}
}
