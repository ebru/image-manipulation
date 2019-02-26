<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Image;
use Storage;
use App\ImageProcess;
use App\Http\Resources\ImageProcess as ImageProcessResource;

class ImageController extends Controller
{
    private $image;

    public function process(Request $request)
    {
        $validationResponse = $this->validateRequest($request);

        if ($validationResponse) {
            return response()->json($validationResponse);
        }

        $this->img = Image::make($request->file('image_file'));

        $imageHashName = $request->file('image_file')->hashName();

        Storage::disk('public')->put("images/original/{$imageHashName}", $this->img->stream());
        $originalImagePath = Storage::url("images/original/{$imageHashName}");

        if ($request->filled('filter_name')) {
            $this->applyFilter($request->input('filter_name'));
        }

        if ($request->filled('watermark_text')) {
            $this->applyWatermarkText($request->input('watermark_text'));
        }

        Storage::disk('public')->put("images/modified/{$imageHashName}", $this->img->stream());
        $modifiedImagePath = Storage::url("images/modified/{$imageHashName}");

        $baseUrl = url('/');
        $pathOriginalImage = "{$baseUrl}{$originalImagePath}";
        $pathModifiedImage = "{$baseUrl}{$modifiedImagePath}";

        $imageProcess = new ImageProcess();

        $imageProcess->original_image_file = $pathOriginalImage;
        $imageProcess->modified_image_file = $pathModifiedImage;
        $imageProcess->filter_name = $request->input('filter_name');
        $imageProcess->watermark_text = $request->input('watermark_text');

        if ($imageProcess->save()) {
           return new ImageProcessResource($imageProcess);
        }
    }

    public function applyFilter(String $filterName) {
        if ($filterName == 'greyscale') {
            $this->img->greyscale();
        }

        if ($filterName == 'blur') {
            $this->img->blur(15);
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
    
    public function validateRequest(Request $request) {
        if (!$request->hasFile('image_file')) {
            return [
                'notice' => "An image file should be provided."
            ];
        }
        
        if (!$request->file('image_file')->isValid()) {
            return [
                'notice' => "There was a problem while uploading the image file."
            ];
        }

        if (!$request->has('filter_name') && !$request->has('watermark_text')) {
            return [
                'notice' => "At least a filter or watermark should be applied."
            ];
        }

        if ($request->has('filter_name')) {
            if (empty($request->input('filter_name'))) {
                return [
                    'notice' => "Filter name field cannot be empty."
                ];
            }

            if ($request->input('filter_name') != 'greyscale' && $request->input('filter_name') != 'blur') {
                return [
                    'notice' => "Only greyscale or blur can be applied as filter."
                ];
            }
        }

        if ($request->has('watermark_text')) {
            if (empty($request->input('watermark_text'))) {
                return [
                    'notice' => "Watermark text field cannot be empty."
                ];
            }
        }
    }
}
