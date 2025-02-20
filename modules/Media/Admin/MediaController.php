<?php

namespace Modules\Media\Admin;



use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Http\Response;

use Illuminate\Http\UploadedFile;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Str;

use Modules\AdminController;

use Modules\Media\Helpers\FileHelper;

use Modules\Media\Models\MediaFile;

use Intervention\Image\ImageManagerStatic as Image;

use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;



class MediaController extends Controller

{

    const AVATAR = 1;


    public function index(Response $request){



        $this->setActiveMenu(route('media.admin.index'));

        $data = [

            'page_title'=>__("Media Management"),

            'breadcrumbs'        => [

                [

                    'name' => __('Media Management'),

                    'url'  => route('media.admin.index')

                ],

            ]

        ];

        return view('Media::admin.index', $data);

    }



    public function sendError($message, $data = [])

    {

        $data['uploaded'] = 0;

        $data['error'] = [

            "message"=>$message

        ];



        return parent::sendError($message,$data);

    }



    public function sendSuccess($data = [], $message = '')

    {

        $data['uploaded'] = 1;



        if(!empty($data['data']->file_name))

        {

            $data['fileName'] = $data['data']->file_name;

            $data['url'] = FileHelper::url($data['data']->id,'full');

        }

        return parent::sendSuccess($data, $message); // TODO: Change the autogenerated stub

    }



    public function compressAllImages(){

        $files = MediaFile::get();



        if(!empty($files))

        {

            foreach ($files as $file)

            {

                if(FileHelper::isImage($file))

                {

                    if(Storage::disk('uploads')->exists('public/'.$file->file_path))

                    {

                        if(function_exists('proc_open')){

                            try{

                                    ImageOptimizer::optimize(public_path('app/public/'.$file->file_path));

                                }catch (\Exception $exception){



                            }

                        }



                    }



                }

            }

        }



        echo "Processed: ".count($files);

    }



    public function store(Request $request)

    {
        $avatar = (int) $request->avatar;

        if(!$user_id = Auth::id()){

            return $this->sendError(__("Please log in"));

        }



        $ckEditor = $request->query('ckeditor');



        if (!$this->hasPermissionMedia()) {

            return $this->sendError('There is no permission upload');

        }

        $fileName = 'file';

        if($ckEditor) $fileName = 'upload';



        $file = $request->file($fileName);

        $file_type = $request->input('type');

        if($request->input('video')) $file_type = 'default';

        if (empty($file)) {

            return $this->sendError(__("Please select file"));

        }

        try {

            static::validateFile($file, $file_type, $avatar);

        } catch (\Exception $exception) {

            return $this->sendError($exception->getMessage());

        }

        $folder = '';

        $id = Auth::id();

        if ($id) {

//            $folder .= sprintf('%04d', (int)$id / 1000) . '/' . $id . '/';

            $folder .=  'system' . '/' . $id . '/';


        }


        $folder = $folder . date('Y/m/d');

        $newFileName = Str::slug(substr($file->getClientOriginalName(), 0, strrpos($file->getClientOriginalName(), '.')));
        if(empty($newFileName)) $newFileName = md5($file->getClientOriginalName());


        $i = 0;

        do {

            $newFileName2 = $newFileName . ($i ? $i : '');

            $testPath = $folder . '/' . $newFileName2 . '.' . $file->getClientOriginalExtension();

            $i++;

        } while (Storage::disk('uploads')->exists($testPath));

        $newFileName3 = $newFileName . '-'. md5($file->getClientOriginalName(). rand(1, 10000));
        $info = $newFileName3. '.' . $file->getClientOriginalExtension();
        $infoWebP = $newFileName3. '.webp';
        $check = $file->storeAs($folder, $info,'uploads');

        if(in_array($file->getClientOriginalExtension(), ['mp4', 'webm', 'avi', 'mov', 'wmv', 'mpeg', 'asf'])) {

            try {

                $fileObj = new MediaFile();

                $fileObj->file_name = $newFileName2;

                $fileObj->file_path = $check;

                $fileObj->file_size = $file->getSize();

                $fileObj->file_type = $file->getMimeType();

                $fileObj->file_extension = $file->getClientOriginalExtension();

            


                $fileObj->save();

                // Sizes use for uploaderAdapter:

                // https://ckeditor.com/docs/ckeditor5/latest/framework/guides/deep-dive/upload-adapter.html#the-anatomy-of-the-adapter

                $fileObj->sizes = [

                    'default' => asset('uploads/' . $fileObj->file_path),

                    '150'     => url('media/preview/'.$fileObj->id .'/thumb'),

                    '600'     => url('media/preview/'.$fileObj->id .'/medium'),

                    '1024'    => url('media/preview/'.$fileObj->id .'/large'),

                ];

                return $this->sendSuccess(['data' => $fileObj]);

            } catch (\Exception $exception) {

                Storage::disk('uploads')->delete($check);

                return $this->sendError($exception->getMessage());

            }
        }
        // Resize images uploads user
        try {

            $link = 'uploads/'. $folder. '/'. $info;
            $changeLink = $folder. '/'. $info;
            $linkWebP = 'uploads/'. $folder. '/'. $infoWebP;
            $resize = static::checkResize($file, $file_type, $avatar);

            if(count($resize ) > 0) {
                if($resize[0] == null and $resize[1] == null ){
                    $image = Image::make($file)->crop($resize[2],$resize[3])->save(public_path( $link));
                    $size = $image->filesize();
                    if( $size > 512000 ) {
                        $z = $size - 512000;
                        $x = 90 - ($z * 100 / $size);
                        Image::make($link)->save(public_path($link), $x);
                        $check = $changeLink;
                    }

                    $imageWebp = Image::make($file)->encode('webp')->crop($resize[2],$resize[3])->save(public_path($linkWebP));
                    $sizeWebp = $imageWebp->filesize();
                    if( $sizeWebp > 307200 ) {
                        $z = $sizeWebp - 307200;
                        $x = 90 - ($z * 100 / $sizeWebp);
                        Image::make($linkWebP)->save(public_path($linkWebP), $x);
                    }
                }else {

                    $image = Image::make($file)->resize($resize[0], $resize[1])->crop($resize[2],$resize[3])->save(public_path($link));
                    $size = $image->filesize();
                    if( $size > 512000 ) {
                        $z = $size - 512000;
                        $x = 90 - ($z * 100 / $size);
                        Image::make($link)->save(public_path($link), $x);
                        $check = $changeLink;
                    }

                    $imageWebp =  Image::make($file)->encode('webp')->resize($resize[0], $resize[1])->crop($resize[2],$resize[3])->save(public_path($linkWebP));
                    $sizeWebp = $imageWebp->filesize();
                    if( $sizeWebp > 307200 ) {
                        $z = $sizeWebp - 307200;
                        $x = 90 - ($z * 100 / $sizeWebp);
                        Image::make($linkWebP)->save(public_path($linkWebP), $x);
                    }
                }

            }

        } catch (\Exception $exception) {
            return $this->sendError($exception->getMessage());

        }



        // Try to compress Images
        if(function_exists('proc_open') and function_exists('escapeshellarg')){

            try{

                ImageOptimizer::optimize(public_path("uploads/".$check));

            }catch (\Exception $exception){



            }

        }



        if ($check) {

            try {

                $fileObj = new MediaFile();

                $fileObj->file_name = $newFileName2;

                $fileObj->file_path = $check;

                $fileObj->file_size = $file->getSize();

                $fileObj->file_type = $file->getMimeType();

                $fileObj->file_extension = $file->getClientOriginalExtension();

                if (FileHelper::checkMimeIsImage($file->getMimeType())) {

                    list($width, $height, $type, $attr) = getimagesize(public_path("uploads/".$check));

                    $fileObj->file_width = $width;

                    $fileObj->file_height = $height;

                }


                $fileObj->save();

                // Sizes use for uploaderAdapter:

                // https://ckeditor.com/docs/ckeditor5/latest/framework/guides/deep-dive/upload-adapter.html#the-anatomy-of-the-adapter

                $fileObj->sizes = [

                    'default' => asset('uploads/' . $fileObj->file_path),

                    '150'     => url('media/preview/'.$fileObj->id .'/thumb'),

                    '600'     => url('media/preview/'.$fileObj->id .'/medium'),

                    '1024'    => url('media/preview/'.$fileObj->id .'/large'),

                ];

                return $this->sendSuccess(['data' => $fileObj]);

            } catch (\Exception $exception) {

                Storage::disk('uploads')->delete($check);

                return $this->sendError($exception->getMessage());

            }

        }

        return $this->sendError(__("Can not store the file"));

    }


    /**
     * @param $file
     * @param string $group
     * @param $avatar
     * @return bool
     * @throws \Exception
     */

    public static function validateFile($file, $group = "default", $avatar)

    {

        $allowedExts = [

            'jpg',

            'jpeg',

            'bmp',

            'png',

            'gif',

            'zip',

            'rar',

            'pdf',

            'xls',

            'xlsx',

            'txt',

            'doc',

            'docx',

            'ppt',

            'pptx',

            'webm',

            'mp4',

            'mp3',

            'flv',

            'vob',

            'avi',

            'mov',

            'wmv',

            'svg'

        ];

        $allowedExtsImage = [

            'jpg',

            'jpeg',

            'bmp',

            'png',

            'gif',

            'svg'

        ];

        $uploadConfigs = [

            'default' => [

                'types' => $allowedExts,

                "max_size" => 400000000,

                "min_width" => env('ALLOW_IMAGE_MIN_WIDTH', 1024),

                "min_height" => env('ALLOW_IMAGE_MIN_HEIGHT', 682),

                "max_width_default" => env('ALLOW_IMAGE_MAX_WIDTH_DEFAULT', 15000),

                "max_height_default" => env('ALLOW_IMAGE_MAX_HEIGHT_DEFAULT', 15000),

                "min_width_avatar" => env('ALLOW_IMAGE_MIN_WIDTH_AVATAR', 360),

                "min_height_avatar" => env('ALLOW_IMAGE_MIN_HEIGHT_AVATAR', 240),

            ],

            'image' => [

                'types' => $allowedExtsImage,

                "max_size" => 20000000,

                "min_width" => env('ALLOW_IMAGE_MIN_WIDTH', 1024),

                "min_height" => env('ALLOW_IMAGE_MIN_HEIGHT', 682),

                "max_width_default" => env('ALLOW_IMAGE_MAX_WIDTH_DEFAULT', 15000),

                "max_height_default" => env('ALLOW_IMAGE_MAX_HEIGHT_DEFAULT', 15000),

                "min_width_avatar" => env('ALLOW_IMAGE_MIN_WIDTH_AVATAR', 360),

                "min_height_avatar" => env('ALLOW_IMAGE_MIN_HEIGHT_AVATAR', 240),

            ],

        ];

        $config = isset($uploadConfigs[$group]) ? $uploadConfigs[$group] : $uploadConfigs['default'];


        if (!in_array(strtolower($file->getClientOriginalExtension()), $config['types'])) {

            throw new \Exception(__("File type are not allowed"));

        }

        if ($file->getSize() > $config['max_size']) {

            throw new \Exception(__("Maximum upload file size is :max_size B", ['max_size' => $config['max_size']]));

        }


        if (in_array($file_extension = strtolower($file->getClientOriginalExtension()), $allowedExtsImage)) {

            if ($file_extension == "svg") {

                return static::validateSVG($file);

            }

            if (!empty($config['max_width']) or !empty($config['max_height'])) {

                $imagedata = getimagesize($file->getPathname());

                if (empty($imagedata)) {

                    throw new \Exception(__("Can not get image dimensions"));

                }

            }


            if (Auth::user()->getRoleNames()[0] != 'administrator') {

                if ($avatar == self::AVATAR) {

                    if (!empty($config['min_width_avatar']) or !empty($config['min_height_avatar'])) {

                        $imagedata = getimagesize($file->getPathname());

                        if (empty($imagedata)) {

                            throw new \Exception(__("Can not get image dimensions"));

                        }

                        if (!empty($config['min_width_avatar']) and $imagedata[0] < $config['min_width_avatar']) {

                            throw new \Exception(__("Minimum width avatar allowed is: :number", ['number' => $config['min_width_avatar']]));

                        }

                        if (!empty($config['min_height_avatar']) and $imagedata[1] < $config['min_height_avatar']) {

                            throw new \Exception(__("Min height avatar allowed is: :number", ['number' => $config['min_height_avatar']]));

                        }

                    }

                } else {

                    if (!empty($config['min_width']) or !empty($config['min_height'])) {

                        $imagedata = getimagesize($file->getPathname());

                        if (empty($imagedata)) {

                            throw new \Exception(__("Can not get image dimensions"));

                        }

                        if (!empty($config['min_width']) and $imagedata[0] < $config['min_width']) {

                            throw new \Exception(__("Minimum width allowed is: :number", ['number' => $config['min_width']]));

                        }

                        if (!empty($config['min_height']) and $imagedata[1] < $config['min_height']) {

                            throw new \Exception(__("Min height allowed is: :number", ['number' => $config['min_height']]));

                        }

                    }


                }


                if (!empty($config['max_width_default']) or !empty($config['max_height_default'])) {

                    $imagedata = getimagesize($file->getPathname());

                    if (empty($imagedata)) {

                        throw new \Exception(__("Can not get image dimensions"));

                    }

                    if (!empty($config['max_width_default']) and $imagedata[0] > $config['max_width_default']) {

                        throw new \Exception(__("Maximum width allowed is: :number", ['number' => $config['max_width_default']]));

                    }

                    if (!empty($config['max_height_default']) and $imagedata[1] > $config['max_height_default']) {

                        throw new \Exception(__("Max height allowed is: :number", ['number' => $config['max_height_default']]));

                    }

                }

            }

        }

        return true;

    }


    /**
     * @param $file
     * @param string $group
     * @param $avatar
     * @return array
     * @throws \Exception
     */
    public static function checkResize($file, $group = "default", $avatar)
    {
        if (Auth::user()->getRoleNames()[0] != 'administrator') {

            $imagedata = getimagesize($file->getPathname());

            if (empty($imagedata)) {

                throw new \Exception(__("Can not get image dimensions"));

            }

            if($imagedata[0] == 1500 and $imagedata[1] == 1000
                or $imagedata[0] == 1280 and $imagedata[1] == 853
                or $imagedata[0] == 1200 and $imagedata[1] == 800
                or $imagedata[0] == 1024 and $imagedata[1] == 682) {
                return [$imagedata[0], $imagedata[1], $imagedata[0], $imagedata[1]];
            }


            if ($imagedata[0] < 1024 and $imagedata[0] >= 600 and $imagedata[0] > $imagedata[1]) {
                $x = round($imagedata[0] * 400 / $imagedata[1]);
                if( $x < 600) {
                    $y = round($imagedata[1] * 600 / $imagedata[0]);
                    return [600, $y, 600, 400];
                }
                return [$x, 400, 600, 400];
            }

            if ($imagedata[0] < 600 and $imagedata[0] >= 360 and $imagedata[0] > $imagedata[1]) {
                $x = round($imagedata[0] * 360 / $imagedata[1]);
                if( $x < 360) {
                    $y = round($imagedata[1] * 360 / $imagedata[0]);
                    return [360, $y, 360, 240];
                }
                return [$x, 240, 360, 240];
            }

            if ($imagedata[1] < 1024 and $imagedata[1] >= 400 and $imagedata[0] < $imagedata[1]) {
                $y = round($imagedata[1] * 600 / $imagedata[0]);
                if( $y < 400) {
                    return [null, null, 600, 400];
                }

                return [600, $y, 600, 400];
            }

            if ($imagedata[1] < 400 and $imagedata[1] >= 240 and $imagedata[0] < $imagedata[1]) {
                $y = round($imagedata[1] * 360 / $imagedata[0]);
                if( $y < 240) {
                    return [null, null, 360, 240];
                }
                return [360, $y, 360, 240];
            }

            if ($imagedata[0] < 1024 and $imagedata[0] >= 600 and $imagedata[0] = $imagedata[1]) {
                return [600, 600, 600, 400];
            }

            if ($imagedata[0] < 600 and $imagedata[0] >= 360 and $imagedata[0] = $imagedata[1]) {
                return [360, 360, 360, 240];
            }



            if ($imagedata[0] >= 1500 and $imagedata[1] >= 1000 and $imagedata[0] > $imagedata[1]) {
                $x = round($imagedata[0] * 1000 / $imagedata[1]);
                if( $x < 1500) {
                    $y = round($imagedata[1] * 1500 / $imagedata[0]);
                    return [1500, $y, 1500, 1000];
                }
                return [$x, 1000, 1500, 1000];
            }

            if ($imagedata[0] >= 1280 and $imagedata[1] >= 853 and $imagedata[1] < 1000 and $imagedata[0] > $imagedata[1] ) {
                $x = round($imagedata[0] * 853 / $imagedata[1]);
                if($x < 1280) {
                    $y = round($imagedata[1] * 1280 / $imagedata[0]);
                    return  [1280, $y, 1280, 853];
                }
                return [$x, 853, 1280, 853];
            }

            if ($imagedata[0] >= 1200 and $imagedata[1] >= 800 and $imagedata[1] < 853 and $imagedata[0] > $imagedata[1] ) {
                $x = round($imagedata[0] * 800 / $imagedata[1]);
                if($x < 1200) {
                    $y = round($imagedata[1] * 1200 / $imagedata[0]);
                    return [1200, $y, 1200, 800];
                }
                    return [$x, 800, 1200, 800];
            }

            if ($imagedata[0] >= 1024 and $imagedata[1] >= 682 and $imagedata[1] < 800 and $imagedata[0] > $imagedata[1] ) {
                $x = round($imagedata[0] * 682 / $imagedata[1]);
                if($x < 1024) {
                    $y = round($imagedata[1] * 1024 / $imagedata[0]);
                    return [1024, $y, 1024, 682];
                }
                return [$x, 682, 1024, 682];
            }



            if ($imagedata[0] >= 1500 and $imagedata[1] >= 1500 and $imagedata[0] < $imagedata[1]) {
                $y = round($imagedata[1] * 1500 / $imagedata[0]);
                return [1500, $y, 1500, 1000];
            }

            if ($imagedata[0] < 1500 and $imagedata[0] >= 1280  and $imagedata[0] < $imagedata[1]) {
                $y = round($imagedata[1] * 1280 / $imagedata[0]);
                return [1280, $y, 1280, 853];
            }


            if ($imagedata[0] < 1280  and $imagedata[0] >= 1024  and $imagedata[0] < $imagedata[1]) {
                $y = round($imagedata[1] * 1024 / $imagedata[0]);
                return [1024, $y, 1024, 682];
            }



            if ($imagedata[0] >= 1500 and $imagedata[1] >= 1500 and $imagedata[0] = $imagedata[1]) {
                return [1500, 1500, 1500, 1000];
            }

            if ($imagedata[0] < 1500 and $imagedata[1] < 1500 and $imagedata[0] >= 1280  and $imagedata[0] = $imagedata[1]) {
                return [1280, 1280, 1280, 853];
            }

            if ($imagedata[0] < 1280 and $imagedata[1] < 1280 and $imagedata[0] >= 1200  and $imagedata[0] = $imagedata[1]) {
                return [1200, 1200, 1200, 800];
            }

            if ($imagedata[0] < 1200 and $imagedata[1] < 1200 and $imagedata[0] >= 1024  and $imagedata[0] = $imagedata[1]) {
                return [1024, 1024, 1024, 682];
            }


        }

        return [];

    }

    /**

     *

     * @param UploadedFile $file

     * @return bool

     */

    public static function validateSVG($file){



        // validate Script

        if(strpos(strtolower($file->getContent()),'script') !== false){

            throw new \Exception(__("This file is not an allowed file"));

        }

        return true;

    }



    public function getLists(Request $request)

    {

        if (!$this->hasPermissionMedia()) {

            return $this->sendError('There is no permission upload');

        }

        $file_type = $request->input('file_type', 'image');
        $video_files = $request->input('video_file', false);

        $page = $request->input('page', 1);

        $s = $request->input('s');

        $offset = ($page - 1) * 32;

        $model = MediaFile::query();

        $model2 = MediaFile::query();

        if (!Auth::user()->hasPermissionTo("media_manage")) {

             $model->where('create_user', Auth::id());

             $model2->where('create_user', Auth::id());

        }
        if(!$video_files) {
            switch ($file_type) {
    
                case "image":
    
                    $model->whereIn('file_extension', [
    
                        'png',
    
                        'jpg',
    
                        'jpeg',
    
                        'gif',
    
                        'bmp',
    
                        'svg',
    
                    ]);
    
                    $model2->whereIn('file_extension', [
    
                        'png',
    
                        'jpg',
    
                        'jpeg',
    
                        'gif',
    
                        'bmp'
    
                    ]);
    
                    break;
    
            }
        }

        if ($s) {

            $model->where('file_name', 'like', '%' . ($s) . '%');

            $model2->where('file_name', 'like', '%' . ($s) . '%');

        }

        $files = $model->limit(32)->offset($offset)->orderBy('id', 'desc')->get();

        // Count

        $total = $model2->count();

        $totalPage = ceil($total / 32);


        if (!empty($files)) {

            foreach ($files as $file) {

                if(env('APP_PREVIEW_MEDIA_LINK')){

                    $file->thumb_size = url('media/preview/'.$file->id.'/thumb');

                    $file->full_size = url('media/preview/'.$file->id.'/full');

                    $file->medium_size = url('media/preview/'.$file->id.'/medium');

                }else{

                    $file->thumb_size = get_file_url($file,'thumb');

                    $file->full_size = get_file_url($file,'full',false);

                    $file->medium_size = get_file_url($file,'medium',false);

                }



            }

        }

        return $this->sendSuccess([

            'data'      => $files,

            'total'     => $total,

            'totalPage' => $totalPage

        ]);

    }



    /**

     * Check Permission Media

     *

     * @return bool

     */

    private function hasPermissionMedia()

    {

        if(Auth::id()){

            return true;

        }

        if (Auth::user()->hasPermissionTo("media_upload")) {

            return true;

        }

        if (Auth::user()->hasPermissionTo("media_manage")) {

            return true;

        }

        return false;

    }



    public function ckeditorBrowser(){

        return view('Media::ckeditor');

    }



    public function removeFiles(Request $request){

        if(is_demo_mode()){

            return $this->sendError(__("Can not remove!"));

        }

        $file_ids = $request->input('file_ids');

        if(empty($file_ids)){

            return $this->sendError(__("Please select file"));

        }

        if (!$this->hasPermissionMedia()) {

            return $this->sendError(__("You don't have permission delete the file!"));

        }

        $model = MediaFile::query()->whereIn("id",$file_ids);

        if (!Auth::user()->hasPermissionTo("media_manage")) {

            $model->where('create_user', Auth::id());

        }

        $files = $model->get();

        $storage = Storage::disk('uploads');

        if(!empty($files->count())){

            foreach ($files as $file){

                if($storage->exists($file->file_path)){

                    $storage->delete($file->file_path);

                    $substr = substr($file->file_path, 0, strripos($file->file_path, '.'));

                    $fileWebp =  $substr . '.webp';

                    if($storage->exists($fileWebp))
                    {
                        $storage->delete($fileWebp);
                    }
                }

                $size_mores = FileHelper::$defaultSize;

                if(!empty($size_mores)){

                    foreach ($size_mores as $size){

                        $file_size = substr($file->file_path, 0, strrpos($file->file_path, '.')) . '-' . $size[0] . '.' . $file->file_extension;

                        if($storage->exists($file_size)){

                            $storage->delete($file_size);

                        }

                    }

                }

                $file->forceDelete();

            }

            return $this->sendSuccess([],__("Delete the file success!"));

        }

        return $this->sendError(__("File not found!"));

    }

}

