<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model {

    use HasFactory;

    protected $fillable = [ 'url', 'regexp' ];

    public function catalogs()
    {
        return $this->hasMany(Catalog::class);
    }


}
