<?php
/* иерархия категорий медели */
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mebeltype extends Model
{
    use HasFactory;
	protected $fillable=["path", "name", "product_cnt"];

	public function parent_(){
	    
	    return $this->belongsTo(Mebeltype::class,'parent_id');
	
	}
	
    public function childs(){
	    
	    return $this->hasMany(Mebeltype::class,'parent_id');
	
	}
	
    public function products(){
	    
	    return $this->hasMany(Product::class);
	
	}	
	
	public function updateParentId($parentId) {
		
		
		$sql="
		update mebeltypes 
		set path = r.path::ltree 
		from (
		select id,
		path from (
		WITH RECURSIVE r AS (
		   SELECT id,
		   path::text as path,
		   parent_id,
		   name
		   FROM mebeltypes
		   WHERE id = :id
		   UNION
		   SELECT mebeltypes.id,
		   r.path::text || '.' || mebeltypes.id::text as path,
		   mebeltypes.parent_id,
		   mebeltypes.name
		   FROM mebeltypes
			  JOIN r
				  ON mebeltypes.parent_id = r.id
		)
		SELECT * FROM r
		) r
		) as r
		where mebeltypes.id = r.id
		";
		$sth =  \DB::connection()->getPdo()->prepare($sql);	
		$sth->bindValue(':id', $this->id, \PDO::PARAM_INT);
		$sth->execute();
		
	}			
	
	
	public function getFullName($imp =">>"){
		
		return implode($imp, self::where('path','@>',$this->path)
		->orderBy('path','asc')
		->get()
		->pluck('name')
		->toArray());
		
	}	
		
	public function getBreadCrumbs($meta = 'mebeltypes'){
		
		$items=[];
			$name = 'Все типы мебели';
			$url = route('admin.mebeltype.home');
			$home = 'home';
			switch($meta) {
				case 'products':
				$name = 'Все товары';
				$url = route('admin.product.home');
				$home = 'products';
				break;
				default :
				break;
			}
		
			$items[]=[
				"id"=>0,
				"name"=> $name,
				"url"=>$url,
				"status"=>1,
			];	
		// идём вверх от узла (все родители)			
		$mebeltypes=self::where('path','@>',$this->path)->orderBy("path")->get();
		foreach($mebeltypes as $mt){
			$items[]=[
				"id"=>$mt->id,
				"name"=>$mt->name,
				"url"=>route('admin.mebeltype.' . $home,['parent_id'=>$mt->id]),
				"status"=>1,
			];	
		}
		return $items;
		
	}
		
	public function delete()
	{
		
		foreach ($this->childs as $child) {
			$child->delete();
		}
		
		parent::delete();

	}	
	
}
