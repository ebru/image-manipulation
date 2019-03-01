<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Image;
use Storage;
use App\ImageProcess;
use App\Http\Resources\ImageProcess as ImageProcessResource;

class ImageController extends Controller
{
    /**
     * Processing of the requested image
     *
     * @param Request $request
     * @return ImageProcessResource
     */
    public function process(Request $request)
    {
        $validationResponse = $this->validateRequest($request);

        if ($validationResponse) {
            return response()->json($validationResponse)
                ->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        $imageProcessDetails = $this->modifyAndStoreRequestedImage($request);

        $imageProcess = new ImageProcess();

        $imageProcess->image_hash_name = $imageProcessDetails['image_hash_name'];
        $imageProcess->original_image_path = $imageProcessDetails['original_image_path'];
        $imageProcess->modified_image_path = $imageProcessDetails['modified_image_path'];
        $imageProcess->filter_name = $imageProcessDetails['filter_name'];
        $imageProcess->watermark_text = $imageProcessDetails['watermark_text'];
        $imageProcess->watermark_image_hash_name = $imageProcessDetails['watermark_image_hash_name'];
        $imageProcess->watermark_image_path = $imageProcessDetails['watermark_image_path'];

        if ($imageProcess->save()) {
            return new ImageProcessResource($imageProcess);
        }
    }

    public function modifyAndStoreRequestedImage(Request $request): Array
    {
        $image = Image::make($request->file('image_file'));
        $imageHashName = $request->file('image_file')->hashName();
        
        $originalImagePath = $this->saveImage($image, $imageHashName, 'original');

        if ($request->filled('filter_name')) {
            $image = $this->applyFilter($request->input('filter_name'), $image);
        }

        if ($request->filled('watermark_text')) {
            $image = $this->applyWatermarkText($request->input('watermark_text'), $image);
        }

        if ($request->hasFile('watermark_image')) {
            $watermarkImageDetails = $this->applyWatermarkImage($request->file('watermark_image'), $image);
        } else {
            $watermarkImageDetails = [
                'watermark_image_hash_name' => null,
                'watermark_image_path' => null
            ];
        }

        $modifiedImagePath = $this->saveImage($image, $imageHashName, 'modified');

        return [
            'image_hash_name' => $imageHashName,
            'original_image_path' => $originalImagePath,
            'modified_image_path' => $modifiedImagePath,
            'filter_name' => $request->input('filter_name'),
            'watermark_text' => $request->input('watermark_text'),
            'watermark_image_hash_name' => $watermarkImageDetails['watermark_image_hash_name'],
            'watermark_image_path' => $watermarkImageDetails['watermark_image_path']
        ];
    }

    /**
     * Apply filter to image passed
     *
     * @param string $filterName
     * @param \Intervention\Image\Image $image
     * @return \Intervention\Image\Image
     */
    public function applyFilter(string $filterName, \Intervention\Image\Image $image): \Intervention\Image\Image
    {
        if ($filterName == 'greyscale') {
            $image->greyscale();
        }

        if ($filterName == 'blur') {
            $image->blur(15);
        }

        return $image;
    }

    /**
     * Apply watermark text to image passed
     *
     * @param string $text
     * @param \Intervention\Image\Image $image
     * @return \Intervention\Image\Image
     */
    public function applyWatermarkText(string $text, \Intervention\Image\Image $image): \Intervention\Image\Image
    {
        $image->text($text, 20, 20, function ($font) {
            $font->file(5);
            $font->size(24);
            $font->color('#fdf6e3');
            $font->align('left');
            $font->valign('top');
        });

        return $image;
    }

    /**
     * Apply watermark image to image passed
     *
     * @param \Illuminate\Http\UploadedFile $imageFile
     * @param \Intervention\Image\Image $image
     * @return array
     */
    public function applyWatermarkImage(\Illuminate\Http\UploadedFile $imageFile, \Intervention\Image\Image $image): array
    {
        $watermarkImage = Image::make($imageFile);
        $watermarkImageHashName = $imageFile->hashName();

        $watermarkImagePath = $this->saveImage($watermarkImage, $watermarkImageHashName, 'watermarks');

        $image->insert($watermarkImage, 'center');

        return [
            'watermark_image_hash_name' => $watermarkImageHashName,
            'watermark_image_path' => $watermarkImagePath
        ];
    }

    /**
     * Save image to public storage
     *
     * @param \Intervention\Image\Image $image
     * @param string $imageHashName
     * @param string $directory
     * @return string
     */
    public function saveImage(\Intervention\Image\Image $image, string $imageHashName, string $directory): string
    {
        Storage::disk('public')->put("images/{$directory}/{$imageHashName}", $image->stream());

        return Storage::url("images/{$directory}/{$imageHashName}");
    }
    
    /**
     * Validate the request
     *
     * @param Request $request
     * @return array
     */
    public function validateRequest(Request $request): array
    {
        if (!$request->hasFile('image_file')) {
            return [
                'error' => "An image file should be provided."
            ];
        }
        
        if (!$request->file('image_file')->isValid()) {
            return [
                'error' => "There was a problem while uploading the image file."
            ];
        }

        if (!$request->has('filter_name') && !$request->has('watermark_text') && !$request->hasFile('watermark_image')) {
            return [
                'error' => "At least a filter or watermark should be applied."
            ];
        }

        if ($request->has('filter_name')) {
            if (empty($request->input('filter_name'))) {
                return [
                    'error' => "Filter name field cannot be empty."
                ];
            }

            if ($request->input('filter_name') != 'greyscale' && $request->input('filter_name') != 'blur') {
                return [
                    'error' => "Only greyscale or blur can be applied as filter."
                ];
            }
        }

        if ($request->has('watermark_text')) {
            if (empty($request->input('watermark_text'))) {
                return [
                    'error' => "Watermark text field cannot be empty."
                ];
            }
        }

        return [];
    }
}
