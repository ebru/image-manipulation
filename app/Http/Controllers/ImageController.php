<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Image;

class ImageController extends Controller
{
    /**
     * Process the image.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function process(Request $request)
    {
        $imgFile = $request->file('image_file');
        $imgName = $imgFile->getClientOriginalName();

        $img = Image::make($imgFile);

        $img->save("images/{$imgName}");

        $img->greyscale();

        $img->text('The quick brown fox jumps over the lazy dog.', 50, 50, function($font) {
            $font->color('#fdf6e3');
            $font->align('center');
            $font->valign('center');
            $font->angle(45);
        });

        $img->save("images/modified_{$imgName}");

        $path = url('/') . '/images';
        $pathOriginalImage = "{$path}/{$imgName}";
        $pathModifiedImage = "{$path}/modified_{$imgName}";

        return response()->json([
            'original_image' => $pathOriginalImage,
            'modified_image' => $pathModifiedImage]);
    }
}
