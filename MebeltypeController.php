<?php
/**категории мебельной продукции Админка**/
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\Mebeltype\Repository;
use App\Services\MebeltypeService;
use App\Models\Mebeltype;
use App\Http\Requests\Admin\Mebeltype\CreateRequest;
use App\Http\Requests\Admin\Mebeltype\UpdateRequest;


class MebeltypeController extends Controller
{
	private MebeltypeService $service;
	private Repository $repo;
	
	
	public function __construct(
        MebeltypeService $service,
		Repository $repo

    ) {
        $this->service = $service;
		$this->repo = $repo;
    
    }
	
    public function index(Request $request, $parent_id = null)
	{
		$parentMebeltype = null;

		$data = $request->all();
		if ($parent_id) {
			$parentMebeltype = Mebeltype::findOrFail($parent_id);
			$data['parent_id'] = $parent_id;	
		}
		$mebeltypes = $this->repo->getPaginated($data);		
		
		return view('admin.mebeltype.index', compact('mebeltypes','parentMebeltype'));
	}
	
	public function products(Request $request, $parent_id )
	{
		$parentMebeltype = null;
		
		$parentMebeltype = Mebeltype::findOrFail($parent_id);
		
		$data = $request->all();
		$data['mebeltype_id'] = $parent_id;
		if (false && !$parentMebeltype->products->count()) {
			$mebeltypes = $this->repo->getPaginated($data);		
			return view('admin.mebeltype.index', compact('mebeltypes','parentMebeltype'));
		}
		$repo =  new \App\Repository\Product\Repository;
		$products = $repo->getPaginated($data);
		
		return view('admin.product.index-type', compact('products','parentMebeltype'));
	}	
	
	public function create(?int $parent_id = null)
	{
		$parentMebeltype = null;
		if ($parent_id){
			$parentMebeltype = Mebeltype::findOrFail($parent_id);
		}
		
		
		return view('admin.mebeltype.create', compact('parentMebeltype'));
	}	
	
	public function store(CreateRequest $request)
	{
		
	$dto = $request->getDto();
		
		$mebeltype =  $this->service->create($dto); 
		
		if($mebeltype->parent_id) {
			return redirect()->route('admin.mebeltype.home',['parent_id' => $mebeltype->parent_id]);
		}
		return redirect()->route('admin.mebeltype.home');
	}	
	
	public function edit($id)
	{

		$mebeltype = Mebeltype::findOrFail($id);
		
		return view('admin.mebeltype.edit', compact('mebeltype'));

	}	
	
	public function update($id, UpdateRequest $request)
	{
		
		$mebeltype = Mebeltype::findOrFail($id);
				
        $dto = $request->getDto();
		
		$this->service->update($mebeltype, $dto);
		
		if ($mebeltype->parent_id) {
			return redirect()->route('admin.mebeltype.home',['parent_id' => $mebeltype->parent_id]);
		}
		
		return redirect()->route('admin.mebeltype.home');

	}

	public function delete($id)
	{
		$mebeltype = Mebeltype::findOrFail($id);
		$mebeltype->delete();
		return redirect()->route('admin.mebeltype.home');

	}	
	
	
	public function search (Request $request) {
		
		//todo нужен репозиторий и ОРМ
		
	    $name=$request->input("name");
		$fills=$request->input('fills') ?? 0;

	    $name =preg_replace('/[^a-zA-Zа-я\s\-]/ui','',$name);
	    $result=[];
	    if(!$name) {
			return response()->json(["result"=>$result]);
		}
	    
	    $limit=25;
	    switch(mb_strlen($name)){
		    case 1:
		     $limit=5;
		    break;
		    case 2:
		    $limit=12;
		    break;
	    }

	$sql="
    select  t.id as rtd,min(t.impo) as impo,j.name as named,j.path,coalesce(j.parent_id,0) as parent_id,j.id,j.name,j.fills from(
		select t.id as rtd,t.impo,j.name as named,j.path,coalesce(j.parent_id,0) as parent_id,j.id,j.name,j.fills 
		from (
			select min(impo) as impo,path,coalesce(parent_id,0) as parent_id,id,name 
				from(
					select 1 as impo,path,coalesce(parent_id,0) as parent_id,id,name  from mebeltypes where name ~* ?
					union select 2 as impo,path,coalesce(parent_id,0) as parent_id,id,name from mebeltypes where name ~* ?
					limit $limit
				) as t 
	    
			group by path,parent_id,id,name
			order by impo,name
	    ) as t	
		inner join mebeltypes j
	    on j.path <@ t.path 
		group by t.id,t.impo,j.name,j.path,coalesce(j.parent_id,0),j.id,j.name,j.fills
	    ) as t
		left join mebeltypes j
	    on j.path @> t.path
		group by t.id,j.name,j.path,coalesce(j.parent_id,0),j.id,j.name,j.fills
	    order by impo,named
		
		";
		
		// можно добавлять продкукцию
		if($fills){
		$sql="
    select  t.id as rtd,min(t.impo) as impo,j.name as named,j.path,coalesce(j.parent_id,0) as parent_id,j.id,j.name,j.fills from(
		select t.id as rtd,t.impo,j.name as named,j.path,coalesce(j.parent_id,0) as parent_id,j.id,j.name,j.fills 
		from (
			select min(impo) as impo,path,coalesce(parent_id,0) as parent_id,id,name 
				from(
					select 1 as impo,path,coalesce(parent_id,0) as parent_id,id,name  from mebeltypes where name ~* ?
					union select 2 as impo,path,coalesce(parent_id,0) as parent_id,id,name from mebeltypes where name ~* ?
					limit $limit
				) as t 
	    
			group by path,parent_id,id,name
			order by impo,name
	    ) as t	
		inner join mebeltypes j
	    on j.path <@ t.path and j.fills=1
		group by t.id,t.impo,j.name,j.path,coalesce(j.parent_id,0),j.id,j.name,j.fills
	    ) as t
		left join mebeltypes j
	    on j.path @> t.path
		group by t.id,j.name,j.path,coalesce(j.parent_id,0),j.id,j.name,j.fills
	    order by impo,named
		
		";
		}
		
	    $sth=\DB::connection()->getPdo()->prepare($sql);
		
		$sth->execute(['^'.$name.'','[\s(\-]'.$name.'']);
		
	    $data=$sth->fetchAll(\PDO::FETCH_OBJ);
	    $levels=[];
	    $found=[];
	    $objects=[];
	    foreach($data as $d){
		    $levels[$d->id]=$d->parent_id;
		    if($d->rtd==$d->id)
		    	$found[]=$d->rtd;
		    $objects[$d->id]=$d;	
	    }
		
	    $myFunction = function($id,&$levels,&$objects,&$servicetype) use (&$myFunction){
			if(isset($objects[$id])){
			$servicetype|=$objects[$id]->servicetype ?? 0;
			return $myFunction($levels[$id] ?? 0,$levels,$objects,$servicetype).' >> '.$objects[$id]->name;
			}
		};
	    
		foreach($found as $f){
			$servicetype=0;
			$name=$myFunction($f,$levels,$objects,$servicetype);
			$result[]=["id"=>$f,"name"=>$name
			,"servicetype"=>$servicetype];
		
		}
		
	    return response()->json(["result"=>$result]);

    }
	

	


	
}
