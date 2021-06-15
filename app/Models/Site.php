<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model {

    use HasFactory;

    protected $fillable = [ 'url', 'regexp' ];

    public function getUrlAttribute(): string
    {
        $url = parse_url($this->product_json);

        return $url['scheme'] . '://' . $url['host'];
    }

    public function getCatalogJsonPathAttribute(): string
    {
        $url = parse_url($this->product_json);
        $path = str_replace('/products.json', '', $url['path']);

        return $path . '.json';
    }

    public function getProductJsonPathAttribute(): string
    {
        $url = parse_url($this->product_json);

        return $url['path'];
    }

    public function getHandlerAttribute()
    {
        $url = parse_url($this->product_json);

        return last(explode('/', str_replace('/products.json', '', $url['path'])));
    }


    public function catalogs()
    {
        return $this->hasMany(Catalog::class);
    }


}
