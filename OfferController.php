<?php
/** составление оффера модели с компонентами и харатеристиками **/
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Offer;
use App\Repository\Color\Repository;
use App\Services\OfferService;
use App\Http\Requests\Admin\Offer\CreateRequest;
use App\Http\Requests\Admin\Offer\UpdateRequest;


class OfferController extends Controller
{
	
	private OfferService $service;
	private Repository $repo;
	
	
	public function __construct(
        OfferService $service,
		Repository $repo

    ) {
        $this->service = $service;
		$this->repo = $repo;
    
    }	
	
   	public function index(int $id, Request $request)
	{
		$product = Product::findOrFail($id);
		
		return view('admin.product.offer.index', compact('product'));
	}
	
	public function create(int $id)
	{
		
		$product = Product::findOrFail($id);
		$session_main_form = request()->old('session_main_form');
		if(!$session_main_form){
			$session_main_form = uniqid("offer_main_image_");
		}
		
		$selectedGroups = old('groups') ?? [];
		$selectedColors = old('colors') ?? [];
		
		return view('admin.product.offer.create',
		compact('product', 'session_main_form', 'selectedGroups', 'selectedColors'));
	}		

	public function store(int $id, CreateRequest $request)
	{

		$product = Product::findOrFail($id);
		$dto = $request->getDto();
		
		
		$offer =  $this->service->create($dto); 
		
		return redirect()->route('admin.product.offer.home', ['id' => $id])
		->with('status', 'Оффер добавлен');

	}		
	
	public function edit($id, $offer_id)
	{

		$product = Product::findOrFail($id);
		$offer = Offer::findOrFail($offer_id);
		$session_main_form = request()->old('session_main_form');
		if(!$session_main_form){
			$session_main_form = uniqid("offer_main_image_");
		}	
		
		$selectedGroups = old('groups') ?? [];
		$selectedColors = old('colors') ?? [];
		if (empty($selectedColors)) {
			list($selectedGroups, $selectedColors) = $this->service->getMaterials($offer);	
		}
		
		return view('admin.product.offer.edit', 
		compact('product', 'offer', 'session_main_form', 'selectedGroups', 'selectedColors'));		

	}			
	
	public function update($id, $offer_id, UpdateRequest $request)
	{
		$offer = Offer::findOrFail($offer_id);
		
        $dto = $request->getDto();
		
		$this->service->update($offer, $dto);
		
		return redirect()->route('admin.product.offer.home', ['id' => $id])
		->with('status', 'Оффер Изменён');
	}	
	
	public function delete($id, $offer_id)
	{
		
		$offer = Offer::findOrFail($offer_id);

		$offer->delete();
		
		return redirect()->route('admin.product.offer.home', ['id' => $id])
		->with('status', 'Оффер удалён');

	}		
	
}
