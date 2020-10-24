<?php

namespace jeremybrammer\laravelimagetos3package\Models;

use Illuminate\Database\Eloquent\Model;

class ImageUpload extends Model
{
    protected $fillable = [
        'original_filename',
        'original_image_url',
        'small_image_url',
        'thumbnail_image_url'
    ];
}
