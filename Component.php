<?php
/**модель оффер**/
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
	
	public const COMPONENT_CARCAS = 10;
	public const COMPONENT_TEXTILE = 20;
	public const COMPONENT_TABLETOP = 30;
	public const COMPONENT_ARMREST = 40;
	public const COMPONENT_WEAVING = 50;
	
	
	public const COMPONENT_GROUP_NAMES = [
		self::COMPONENT_CARCAS => 'Каркас',
		self::COMPONENT_TEXTILE => 'Ткань',
		self::COMPONENT_TABLETOP => 'Столешница',
		self::COMPONENT_ARMREST => 'Накладка на подлокотник',
		self::COMPONENT_WEAVING => 'Плетение'
	];

	public $timestamps = false;
	
    use HasFactory;
	
    public function colors()
    {
        return $this->hasMany(Color::class);
    }	
	
	public function delete()
	{
		
		foreach ($this->colors as $color) {
			$color->delete();	
		}
		
		parent::delete();

	}	
	
	
}
