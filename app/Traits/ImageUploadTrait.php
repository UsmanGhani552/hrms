<?php
namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

trait ImageUploadTrait
{

    public function uploadImage(Request $request, string $name, string $path, ?string $oldImagePath = null, string $defaultPath = null)
    {
        if($request->hasFile($name)) {
            $oldImagePath = str_replace('/', DIRECTORY_SEPARATOR, $oldImagePath);
            if ($oldImagePath) {
                $fullPath = public_path($oldImagePath);
                if (File::exists($fullPath)) {
                    File::delete($fullPath);
                }
            }
            $file = $request->file($name);
            $originalName = $file->getClientOriginalName();
            // $extension = $file->getClientOriginalExtension();
            $filename = time(). $originalName;
            $file->move($path, $filename);
            return $filename;
        }
        return $defaultPath;
    }


    public function deleteImage(string $imagePath)
    {
        $fullPath = public_path($imagePath);
        if (File::exists($fullPath)) {
            File::delete($fullPath);
            return true;
        }
        return false;
    }
}



