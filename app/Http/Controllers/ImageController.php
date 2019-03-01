<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Image;
use Storage;
use App\ImageProcess;
use App\Http\Resources\ImageProcess as ImageProcessResource;
use \Intervention\Image\Image as InterventionImage;

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

        $imageHashName = $request->file('image_file')->hashName();
        $originalImage = Image::make($request->file('image_file'));

        $imageProcessDetails = $this->manipulateImageRequest($request);

        $imagePathDetails = $this->storeImages($originalImage, $imageProcessDetails['modified_image'], $imageHashName);

        $imageProcess = new ImageProcess();

        $imageProcess->image_hash_name = $imageHashName;
        $imageProcess->original_image_path = $imagePathDetails['original_image_path'];
        $imageProcess->modified_image_path = $imagePathDetails['modified_image_path'];
        $imageProcess->filter_name = $imageProcessDetails['filter_name'];
        $imageProcess->watermark_text = $imageProcessDetails['watermark_text'];
        $imageProcess->watermark_image_hash_name = $imageProcessDetails['watermark_image_hash_name'];
        $imageProcess->watermark_image_path = $imageProcessDetails['watermark_image_path'];

        if ($imageProcess->save()) {
            return new ImageProcessResource($imageProcess);
        }
    }

    /**
     * Manipulate the image while applying filter/watermark
     *
     * @param Request $request
     * @param InterventionImage $image
     * @return array
     */
    public function manipulateImageRequest(Request $request): array
    {
        $image = Image::make($request->file('image_file'));

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
        return [
            'modified_image' => $image,
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
     * @param InterventionImage $image
     * @return InterventionImage
     */
    public function applyFilter(string $filterName, InterventionImage $image): InterventionImage
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
     * @param InterventionImage $image
     * @return InterventionImage
     */
    public function applyWatermarkText(string $text, InterventionImage $image): InterventionImage
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
     * @param InterventionImage $image
     * @return array
     */
    public function applyWatermarkImage(\Illuminate\Http\UploadedFile $imageFile, InterventionImage $image): array
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
     * Store the original and modified versions of the image requested
     *
     * @param InterventionImage $originalImage
     * @param InterventionImage $modifiedImage
     * @param string $imageHashName
     * @return array
     */
    public function storeImages(InterventionImage $originalImage, InterventionImage $modifiedImage, string $imageHashName): array
    {
        return [
            'original_image_path' => $this->saveImage($originalImage, $imageHashName, 'original'),
            'modified_image_path' => $this->saveImage($modifiedImage, $imageHashName, 'modified')
        ];
    }

    /**
     * Save image to public storage
     *
     * @param InterventionImage $image
     * @param string $imageHashName
     * @param string $directory
     * @return string
     */
    public function saveImage(InterventionImage $image, string $imageHashName, string $directory): string
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
