<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ImageProcess extends Model
{
    protected $fillable = ['original_image_file', 'modified_image_file', 'filter_name', 'watermark_text'];
}
