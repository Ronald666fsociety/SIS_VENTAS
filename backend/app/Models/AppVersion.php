<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    protected $table = 'app_versiones';
    protected $primaryKey = 'app';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['app', 'version_minima', 'url_tienda'];
}
