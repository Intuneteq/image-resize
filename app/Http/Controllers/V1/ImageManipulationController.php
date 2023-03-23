<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\ImageManipulation;
use App\Http\Requests\ResizeImageRequest;
use App\Http\Requests\UpdateImageManipulationRequest;
use App\Http\Resources\V1\ImageManipulationResource;
use App\Models\Album;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ImageManipulationController extends Controller
{
    public function index()
    {
        return ImageManipulationResource::collection(ImageManipulation::paginate());
    }

    public function resize(ResizeImageRequest $request)
    {
        $all = $request->all();

        $image = $all['image'];
        unset($all['image']);

        $data = [
            'type' => ImageManipulation::TYPE_RESIZE,
            'data' => json_encode($all),
            'user_id' => null
        ];

        if (isset($all['album_id'])) {
            //TODO

            $data['album_id'] = $all['album_id'];
        }

        $dir = 'images/' . Str::random() . '/';
        $absolutePath = public_path($dir);
        File::makeDirectory($absolutePath);

        if ($image instanceof UploadedFile) {
            $data['name'] = $image->getClientOriginalName();
            $filenanme = pathinfo($data['name'], PATHINFO_FILENAME);
            $extension = $image->getClientOriginalExtension();
            $originalPath = $absolutePath . $data['name'];

            //move image to absolute path dir
            $image->move($absolutePath, $data['name']);
        } else {
            $data['name'] = pathInfo($image, PATHINFO_BASENAME);
            $filenanme = pathinfo($image, PATHINFO_FILENAME);
            $extension = pathinfo($image, PATHINFO_EXTENSION);
            $originalPath = $absolutePath . $data['name'];

            //copy image in to the absolute path
            copy($image, $absolutePath . $data['name']);
        }
        //save image in db by concatenation
        $data['path'] = $dir . $data['name'];

        $w = $all['w'];
        $h = $all['h'] ?? false;

        list($width, $height) = $this->getImageWidthandHeight($w, $h, $originalPath);
        $resizedFilename = $filenanme . '-resized' . $extension;
        $image = Image::make($originalPath);
        $image->resize($width, $height)->save($absolutePath . $resizedFilename);
        $data['output_path'] = $dir . $resizedFilename;

        $imageManipulation = ImageManipulation::create($data);
        return new ImageManipulationResource($imageManipulation);
    }

    public function byAlbum(Album $album)
    {
        $where = [
            "album_id" => $album->id
        ];
        return ImageManipulationResource::collection(ImageManipulation::where($where)->paginate());
    }

    public function show(ImageManipulation $image)
    {
        return new ImageManipulationResource($image);
    }
    public function destroy(ImageManipulation $image)
    {
        $image->delete();
        return response('', 204);
    }

    protected function getImageWidthandHeight($w, $h, string $originalPath)
    {
        $image = Image::make($originalPath);
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        if (str_ends_with($w, '%')) {
            $ratioW = (float)str_replace('%', '', $w);
            $ratioH = $h ? (float)str_replace('%', '', $h) : $ratioW;

            $newWidth = $originalWidth * $ratioW / 100;
            $newHeight = $originalHeight * $ratioW / 100;
        } else {
            $newWidth = (float)$w;
            $newHeight = $h ? (float)$h : $originalHeight * $newWidth / $originalWidth;
        }

        return [$newWidth, $newHeight];
    }
}
