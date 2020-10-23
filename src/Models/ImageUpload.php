<?php

namespace jeremybrammer\laravelimagetos3package\Models;

use Illuminate\Database\Eloquent\Model;

class ImageUpload extends Model
{
    protected $fillable = [
        'filename',
        'url'
    ];
}
