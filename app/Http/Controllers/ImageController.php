<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Image;

class ImageController extends Controller
{
    private $img;

    public function process(Request $request)
    {
        $imgFile = $request->file('image_file');
        $imgName = $imgFile->getClientOriginalName();

        $this->img = Image::make($imgFile);
        $this->img->save("images/{$imgName}");

        if ($request->filled('filter_name')) {
            $this->applyFilter($request->input('filter_name'));
        }

        if ($request->filled('watermark_text')) {
            $this->applyWatermarkText($request->input('watermark_text'));
        }

        $this->img->save("images/modified_{$imgName}");

        $path = url('/') . '/images';
        $pathOriginalImage = "{$path}/{$imgName}";
        $pathModifiedImage = "{$path}/modified_{$imgName}";

        return response()->json([
            'original_image' => $pathOriginalImage,
            'modified_image' => $pathModifiedImage,
            'applied' => [
                'filter_name' => $request->input('filter_name'),
                'watermark_text' => $request->input('watermark_text')
            ]
        ]);
    }

    public function applyFilter(String $filterName) {
        if($filterName == 'greyscale') {
            $this->img->greyscale();
        }
    }

    public function applyWatermarkText(String $text) {
        $this->img->text($text, 250, 250, function($font) {
            $font->color('#fdf6e3');
            $font->align('center');
            $font->valign('center');
            $font->angle(45);
        });
    }
}
