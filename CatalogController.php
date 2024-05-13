<?php
/*вывод продукции на фронт*/
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repository\Category\Repository;
use App\Repository\Product\Repository as ProductRepository;
use App\Models\Category;
use App\Models\Component;
use App\Models\Product;
use App\Helpers\MetaHelper;
use App\Helpers\OfferHelper;

use App\Models\Staticpage;

use App\Http\Formatters\Product\PublicFormatter;

class CatalogController extends Controller
{
	
	private Repository $repo;
	private ProductRepository $productRepo;
	private PublicFormatter $formatter;
	
	
	public function __construct(
		Repository $repo,
		ProductRepository $productRepo,
		PublicFormatter $formatter

    ) {

		$this->repo = $repo;
		$this->productRepo = $productRepo;
		$this->formatter = $formatter;
    
    }	
	
    //Каталог (все разделы)
	public function index(Request $request)
	{
		
		$data = $request->all();
		$data['product_cnt'] = 1;

		$metahelper = MetaHelper::getByCode('catalog');
		$data['product_cnt'] = 1;
		$data["sort_by"] = 'sort';
		$data["order"] = 'asc';
		
		$categories = $this->repo->getPaginated($data);	
		return view('catalog.index', compact('metahelper', 'categories'));
	}
	
	//Раздел
	public function view($slug)
	{
		
		$parentCategory = Category::where('slug',$slug)->first();
		if (!$parentCategory) {
			
			return $this->cnc($slug);
		}
		
		$data = request()->all();
		$data['parent_id'] = $parentCategory->id;	
		$data['product_cnt'] = 1;
		$data["sort_by"] = 'sort';
		$data["order"] = 'asc';
		$categories = $this->repo->getPaginated($data);	
		
		$metahelper = MetaHelper::getByIdentity($parentCategory);

		$data = [];
		$data['parent_ids'] = $parentCategory->getBranch()->pluck('id')->all();	
		$data['per_page'] = 30;
		$data["sort_by"] = 'sort';
		$data["order"] = 'asc';

		$products = $this->productRepo->getPaginated($data);

		return view('catalog.list', compact('metahelper', 'categories','parentCategory', 'products'));
		
	}
	
	protected function cnc($slug)
	{
	
		$slug = '/catalog/' . $slug;
		
		$cnc = Staticpage::where("cnc", $slug)->firstOrFail();
		
		$data = request()->all();
		$data['per_page'] = 30;
		$data["sort_by"] = 'sort';
		$data["order"] = 'asc';
		$data["staticpage"] = $cnc->id;
		$relatives = $this->productRepo->getPaginated($data);
		if (request()->ajax) {
					
			
			$result = [
				"data" => $this->formatter->getList($relatives),
				"meta" => [
					"currentPage" => $relatives->currentPage(),
					"total" => $relatives->total(),
					"perPage" => $relatives->perPage(),
					"lastPage" => $relatives->lastPage(),
				]
			];
			return response()->json($result);
		}
		
		
		$metahelper = MetaHelper::getByIdentity($cnc);
		
		
		return view('catalog.sitepage', compact('cnc', 'metahelper', 'relatives'));

	}
	
	public function subView($slug, $preslug)
	{
		
		$product = Product::where('slug',$preslug)->firstorFail();
		
		$metahelper = MetaHelper::getByIdentity($product);

		$materials = [];

		$colorComponents = [];
		
		if ($product->offers->count()) {
			
			foreach($product->materials as $material) {
				
				if(!isset($materials[$material->component->group_id][$material->component->id])) {
					$materials[$material->component->group_id][$material->component->id] = [
						'id' => $material->component->id,
						'name' => $material->component->name,
						'group_id' => $material->component->group_id,
						'group' => Component::COMPONENT_GROUP_NAMES[$material->component->group_id] ?? '',
						'description' => $material->component->description,
						'colors' => []

					];
				}
				$price = $material->offer->price;
				
				if(!isset($materials[$material->component->group_id][$material->component->id]['colors'][$material->color->id])) {
					
					$materials[$material->component->group_id][$material->component->id]['colors'][$material->color->id] = [
						'id' =>$material->color->id,
						'name' =>$material->color->name,
						'group_id' => $material->component->group_id,
						'pic' =>$material->color->getPic("100x100"),
						'pic_origin' =>$material->color->getPic(),
						'price' => 0
					];
					
				} 
				$colorComponents[$material->color->id][$material->component->id][$material->offer_id] = $price;
				
			}			
			

			$colorComponents = json_encode($colorComponents);
			
			return view('catalog.offer', compact('product', 'metahelper', 'materials', 'colorComponents'));
		}
			
		return view('catalog.product', compact('product', 'metahelper'));

		
	}	
	
	public function getList($id)
	{
		$data = request()->all();
		$id;	
		
		$data['parent_ids'] = Category::where('id',$id)->firstOrFail()->getBranch(1)->pluck('id')->toArray();
		$data['per_page'] = 30;
		$data["sort_by"] = 'sort';
		$data["order"] = 'asc';		
		$products = $this->productRepo->getPaginated($data);
		
		
		$result = [
			"data" => $this->formatter->getList($products),
			"meta" => [
				"currentPage" => $products->currentPage(),
				"total" => $products->total(),
				"perPage" => $products->perPage(),
				"lastPage" => $products->lastPage(),
			]
		];

		return response()->json($result);
		
	}
		
	
}
