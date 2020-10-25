# laravelimagetos3package

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
<!-- [![StyleCI][ico-styleci]][link-styleci] -->
<!-- This is where your description should go. Take a look at [contributing.md](contributing.md) to see a to do list. -->

A simple Laravel package, to handle image uploads.  This package will create a database migration for images, and upload them, resize them, store them on s3, and pre-sign CloudFront URLs!

# Links:

- Example usage found in this repository: https://github.com/jeremybrammer/LaravelSandbox
- The link to the package's GitHub repository: https://github.com/jeremybrammer/laravelimagetos3package

# Installation Steps for this repository:

Install the composer project:
```console
composer install
```

Require the package.
```console
composer require jeremybrammer/laravelimagetos3package
```

Publish the package's config files. It publishes a config file for a dependency.
```console
php artisan vendor:publish --provider="jeremybrammer\laravelimagetos3package\laravelimagetos3packageServiceProvider"
```

Migrate the database to get the new image uploads database table going.
```console
php artisan migrate
```

Change/Add the following lines in the .env file:
```
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
AWS_URL=
CLOUDFRONT_PRIVATE_KEY_PATH=keys/my_key.pem
CLOUDFRONT_KEY_PAIR_ID=
```

(Add your CloudFront key to /storage/keys/my_key.pem.  This should be .gitignored already).

Depending on your server configuration, increase nginx.conf and php.ini settings as needed to allow larger image uploads, and memory limits.

# Package Usage in Controllers:

Include Laravel's Request class, and the following classes, models, and facades from my package.
``` php
use Illuminate\Http\Request;
use jeremybrammer\laravelimagetos3package\laravelimagetos3package;
use jeremybrammer\laravelimagetos3package\Models\ImageUpload;
use jeremybrammer\laravelimagetos3package\Facades\LaravelImageToS3PackageFacade;
```

Gets all previously uploaded images and pre-signs the CloudFront URLs for the thumbnails.
``` php
LaravelImageToS3PackageFacade::getAllUploadedImages(); 
```

Optionally override the image size settings in the upload service.
``` php
LaravelImageToS3PackageFacade::setWidthByImageType("thumbnail", 100);
LaravelImageToS3PackageFacade::setWidthByImageType("small", 200);
```

Call the upload handler with the request, html image field name attribute, and folder in s3 to store them.
``` php
LaravelImageToS3PackageFacade::handUploadRequest($request, "image-upload-field", "victorycto/images");
```

A controller example to view individual images that uses my eloquent model with route-model-binding:
``` php
public function view(ImageUpload $imageUpload, $imagetype){
    //Use route-model binding for the image object, and an image type to get the proper size.
    switch($imagetype){
        case "thumbnail": $url = $imageUpload->thumbnail_image_url; break;
        case "small": $url = $imageUpload->small_image_url; break;
        case "original": $url = $imageUpload->original_image_url; break;
        default: return; break;
    }
    // $imageURL = $this->imagetos3->preSignS3Url($imageUpload->original_image_url); //Sign s3 URL.
    $imageURL = LaravelImageToS3PackageFacade::preSignCloudFrontUrl($url); //Sign CloudFront URL.
    return view("imageuploads.view", ["imageURL" => $imageURL]);
}
```

Enjoy!

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

## Credits

- [author name][link-author]
- [All Contributors][link-contributors]

## License

license. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/jeremybrammer/laravelimagetos3package.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/jeremybrammer/laravelimagetos3package.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/jeremybrammer/laravelimagetos3package/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/jeremybrammer/laravelimagetos3package
[link-downloads]: https://packagist.org/packages/jeremybrammer/laravelimagetos3package
[link-travis]: https://travis-ci.org/jeremybrammer/laravelimagetos3package
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/jeremybrammer
[link-contributors]: ../../contributors
