<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Image;
use Storage;
use App\ImageProcess;
use App\Http\Resources\ImageProcess as ImageProcessResource;

class ImageController extends Controller
{
    public function process(Request $request)
    {
        $validationResponse = $this->validateRequest($request);

        if ($validationResponse) {
            return response()->json($validationResponse);
        }

        $imageProcessDetails = $this->modifyAndStoreRequestedImage($request);

        $imageProcess = new ImageProcess();

        $imageProcess->original_image_file = $imageProcessDetails['original_image_path'];
        $imageProcess->modified_image_file = $imageProcessDetails['modified_image_path'];
        $imageProcess->filter_name = $imageProcessDetails['filter_name'];
        $imageProcess->watermark_text = $imageProcessDetails['watermark_text'];

        if ($imageProcess->save()) {
           return new ImageProcessResource($imageProcess);
        }
    }

    public function modifyAndStoreRequestedImage(Request $request) {
        $img = Image::make($request->file('image_file'));

        $imageHashName = $request->file('image_file')->hashName();

        Storage::disk('public')->put("images/original/{$imageHashName}", $img->stream());

        if ($request->filled('filter_name')) {
            $this->applyFilter($request->input('filter_name'), $img);
        }

        if ($request->filled('watermark_text')) {
            $this->applyWatermarkText($request->input('watermark_text'), $img);
        }

        Storage::disk('public')->put("images/modified/{$imageHashName}", $img->stream());

        $originalImagePath = Storage::url("images/original/{$imageHashName}");
        $modifiedImagePath = Storage::url("images/modified/{$imageHashName}");

        return [
            'original_image_path' => $originalImagePath,
            'modified_image_path' => $modifiedImagePath,
            'filter_name' => $request->input('filter_name'),
            'watermark_text' => $request->input('watermark_text')
        ];
    }

    public function applyFilter(String $filterName, \Intervention\Image\Image $img) {
        if ($filterName == 'greyscale') {
            $img->greyscale();
        }

        if ($filterName == 'blur') {
            $img->blur(15);
        }
    }

    public function applyWatermarkText(String $text, \Intervention\Image\Image $img) {
        $img->text($text, 250, 250, function($font) {
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
