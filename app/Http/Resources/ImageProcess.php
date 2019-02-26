<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImageProcess extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'image' => [
                'original' => $this->original_image_file,
                'modified' => $this->modified_image_file,
                'applied' => [
                    'filter' => [
                        'name' => $this->filter_name
                    ],
                    'watermark' => [
                        'text' => $this->watermark_text
                    ]
                ]
            ]
        ];
    }
}
