<?php

namespace jeremybrammer\laravelimagetos3package;

use Illuminate\Http\Request;
use jeremybrammer\laravelimagetos3package\Models\ImageUpload;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class laravelimagetos3package implements ImageTos3Interface
{
    public function test(){

        // $s3Bucket = Config::get("filesystems.disks.s3.bucket");

        // return $s3Bucket;
        return "This is coming from my custom package!";
    }

    public function handUploadRequest(Request $request){
        $file = $request->file("image-upload-field");

        //Store in local storage:
        // $path = $file->store('images', 'public'); //Get from public storage.
        $path = $file->store('victorycto/images', 's3');
        $originalFilename = $file->getClientOriginalName();

        //Save image data to database:
        $this->storeUploadedImageData($originalFilename, $path);

        //Get presigned request url:
        // $preSignedURL = $this->preSigns3Url($path);

        // $allUploadedImages = $this->getAllUploadedImages();

        // dd($allUploadedImages);
    }

    public function storeUploadedImageData($originalFilename, $pathToFile){
        //Save to database.
        $imageUpload = new ImageUpload;
        $imageUpload->original_filename = $originalFilename;
        $imageUpload->original_image_url = $pathToFile;
        $imageUpload->save();
    }

    public function preSignS3Url($pathToS3File){
        $s3Client = Storage::disk("s3")->getDriver()->getAdapter()->getClient();
        $s3Bucket = Config::get("filesystems.disks.s3.bucket"); //For signing s3 not CloudFront urls.
        // $s3Bucket = Config::get("filesystems.disks.s3.url");

        $command = $s3Client->getCommand("GetObject", [
            "Bucket" => $s3Bucket,
            "Key" => $pathToS3File
        ]);

        $s3request = $s3Client->createPresignedRequest($command, "+20 minutes");

        return (string)$s3request->getUri();
    }

    public function getAllUploadedImages(){
        $images = ImageUpload::all();

        //Presign URLs:
        foreach($images as $image){
            $image->original_image_url_presigned = $this->preSignS3Url($image->original_image_url);
        }

        return $images;
    }

}
