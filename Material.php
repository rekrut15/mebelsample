<?php
/**
Компонента с характеристиками для оффера
**/
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;
	
	public $timestamps = false;	
	
	protected $primaryKey = null;
	public $incrementing = false;
	
	protected $fillable = [
		'component_id',
		'color_id',
		'offer_id',
		'product_id'
	];
	
	public function color()
    {
		  return $this->belongsTo(Color::class);
		  
    }	
	
	public function offer()
    {
		  return $this->belongsTo(Offer::class);
		  
    }		

	public function component()
    {
		  return $this->belongsTo(Component::class);
		  
    }	
	
}
