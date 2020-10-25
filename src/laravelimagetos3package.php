<?php

namespace jeremybrammer\laravelimagetos3package;

use Illuminate\Http\Request;
use jeremybrammer\laravelimagetos3package\Models\ImageUpload;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Dreamonkey\CloudFrontUrlSigner\Facades\CloudFrontUrlSigner;
use Intervention\Image\Facades\Image;

class laravelimagetos3package
{
    private $resizeInstructions;

    function __construct(){
        //Set the default image sizes here.
        $this->resizeInstructions = array(
            (object)array("type" => "thumbnail", "width" => 160),
            (object)array("type" => "small", "width" => 400)
        );
    }

    //Allow the user to override the default image sizes.
    public function setWidthByImageType($imageType, $width){
        foreach($this->resizeInstructions as $instruction){
            if($instruction->type === $imageType){
                $instruction->width = $width;
                break;
            }
        }
    }

    //This takes care of images that are uploaded.
    public function handUploadRequest(Request $request, $htmlInputNameAttribute, $s3FolderPath){
        $s3FolderPath = $this->addTrailingSlashIfNecessary($s3FolderPath); //Add trailing slash to s3 folder path when needed.
        $file = $request->file($htmlInputNameAttribute); //Get the file being uploaded.
        $this->setPHPMemoryForLongRunningProcess(); //Some settings so this doesn't timeout.
        $imageGroup = $this->generateResizedImages($file); //Use file to make different sized files.
        $imageGroupUpdated = $this->storeImagesIns3($imageGroup, $s3FolderPath); //Upload them to s3 storage.
        $this->storeImageGroupInDatabase($imageGroupUpdated); //Store all the image paths in the database.
    }

    //This isn't used, because I use CloudFront, but I kept it for possible future use.
    public function preSignS3Url($pathToS3File){
        $s3Client = Storage::disk("s3")->getDriver()->getAdapter()->getClient();
        $s3Bucket = Config::get("filesystems.disks.s3.bucket"); //For signing s3 not CloudFront urls.
        $command = $s3Client->getCommand("GetObject", [
            "Bucket" => $s3Bucket,
            "Key" => $pathToS3File
        ]);
        $s3request = $s3Client->createPresignedRequest($command, "+20 minutes");
        return (string)$s3request->getUri();
    }

    //Presign CloudFront URL.
    public function preSignCloudFrontUrl($pathToS3File){
        $url = Config::get("filesystems.disks.s3.url") . "/" . $pathToS3File;
        return CloudFrontUrlSigner::sign($url, 1); //Make it available for 1 day.
    }

    //Return all previously uploaded images for the view.
    public function getAllUploadedImages(){
        $images = ImageUpload::all();
        //Presign thumbnail URLs for display:
        foreach($images as $image){
            // $image->thumbnail_image_url_presigned = $this->preSignS3Url($image->thumbnail_image_url); //Sign s3.
            $image->thumbnail_image_url_presigned = $this->preSignCloudFrontUrl($image->thumbnail_image_url); //Sign CloudFront.
        }
        return $images;
    }

    //Adds a trailing slash to the s3 folder path if needed.
    private function addTrailingSlashIfNecessary($s3FolderPath){
        return rtrim($s3FolderPath, "/") . "/";
    }

    //Store the group of images in s3.
    private function storeImagesIns3($imageGroup, $s3FolderPath){
        $output = $imageGroup; //Set the output.
        //Loop through image group and upload them all to s3.
        foreach($imageGroup as $image){
            $newFilename = md5(uniqid()).".".$image->extension; //Generate a unique name.
            $image->s3Path = $s3FolderPath . $newFilename; //This is the s3 folder path and filename together.
            if($image->type === "original"){
                //For the original file, this is faster than streaming as below for some reason.
                $image->request_file->storeAs($s3FolderPath, $newFilename, 's3'); //Store in s3 storage.
                $image->request_file = null; //Freeing up memory because things were running slow.
            } else {
                //Upload the resized images (that are stored in memory) to s3 storage.
                Storage::disk("s3")->put($s3FolderPath . $newFilename, $image->imageData->stream());
                $image->imageData->destroy();  //Free up memory.
                $image->imageData = null; //Just ditch the whole variable to free up memory.
            }
        }
        return $output; //Return the updated image group for further use.
    }

    //This does the image resizing for the image group.
    private function generateResizedImages($originalFile){
        $output = []; //Stub the output.

        $originalFilename = $originalFile->getClientOriginalName();
        $fileExtension = $originalFile->extension();

        //Set Intervention to use GD library.
        Image::configure(array("driver" => "gd"));

        //Loop over the image instructions.
        foreach($this->resizeInstructions as $outputImage){
            $outputImage->filename = $outputImage->type . "_" . $originalFilename; //Set the new filename.
            $outputImage->extension = $fileExtension;
            //Convert Laravel's request->file object to an Intervention image object.
            $outputImage->imageData = Image::make($originalFile);
            //Resize the image as per the requirement.
            $outputImage->imageData->resize($outputImage->width, null, function($constraint){
                $constraint->aspectRatio();
            });
            $output[] = $outputImage; //Push to the output array.
        }

        //Add the original file information to the output as well.
        $originalImageAsInterventionObject = Image::make($originalFile);
        $output[] = (object)array(
            "type" => "original",
            "width" => $originalImageAsInterventionObject->width(),
            "filename" => $originalFilename,
            "extension" => $fileExtension,
            "imageData" => null,
            "request_file" => $originalFile
        );
        $originalImageAsInterventionObject->destroy(); //Free up memory.

        return $output;
    }

    //Store the group of image paths/filenames in the database.
    //There are 3 fields per db row, for the original, thumbnail, and small image URLs.
    private function storeImageGroupInDatabase($imageGroupUpdated){
        $imageUpload = new ImageUpload;
        foreach($imageGroupUpdated as $image){
            switch($image->type){
                case "original":
                    $imageUpload->original_filename = $image->filename;
                    $imageUpload->original_image_url = $image->s3Path;
                break;
                case "thumbnail":
                    $imageUpload->thumbnail_image_url = $image->s3Path;
                break;
                case "small":
                    $imageUpload->small_image_url = $image->s3Path;
                break;
                default: break;
            }
        }
        $imageUpload->save();
    }

    //Some settings to keep things from timing out.
    private function setPHPMemoryForLongRunningProcess(){
        ini_set("memory_limit", "-1"); //Remove the PHP memory limit.
        $secondsToRun = (count($this->resizeInstructions)+1)*60;
        ini_set("max_execution_time", $secondsToRun); //Set script to run for a max of 60 seconds per image type.
    }

}
