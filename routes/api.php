<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/images', function(Request $request)
{
    $img = Image::make($request->file('image_file'));
    $img->save('images/foo.png');

    $img->text('The quick brown fox jumps over the lazy dog.', 50, 50, function($font) {
        $font->color('#fdf6e3');
        $font->align('center');
        $font->valign('center');
        $font->angle(45);
    });

    $img->save('images/foo_modified.jpg');

    $path = url('/') . '/images';
    $pathOriginalImage = "{$path}/foo.png";
    $pathModifiedImage = "{$path}/foo_modified.jpg";

    return response()->json([
        'original_image' => $pathOriginalImage,
        'modified_image' => $pathModifiedImage]);
});