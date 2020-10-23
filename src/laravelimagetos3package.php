<?php

namespace jeremybrammer\laravelimagetos3package;

use Illuminate\Http\Request;
use jeremybrammer\laravelimagetos3package\Models\ImageUpload;

class laravelimagetos3package implements ImageTos3Interface
{
    public function test(){
        return "This is coming from my custom package!";
    }

    public function handUploadRequest(Request $request){
        $file = $request->file("image-upload-field");

        //Store in local storage:
        // $path = $file->store('images', 'public');
        $path = $file->store('images', 's3');
        dd($path);

        //Save to database.
        $imageUpload = new ImageUpload;
        $imageUpload->filename = "test.png";
        $imageUpload->url = "/path/to/images/";
        $imageUpload->save();

        dd($file->getSize());
    }
}
