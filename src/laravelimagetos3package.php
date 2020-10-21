<?php

namespace jeremybrammer\laravelimagetos3package;

use Illuminate\Http\Request;

class laravelimagetos3package implements ImageTos3Interface
{
    public function test(){
        return "This is coming from my custom package!";
    }

    public function handUploadRequest(Request $request){
        $file = $request->file("image-upload-field");
        dd($file->getSize());
    }
}
