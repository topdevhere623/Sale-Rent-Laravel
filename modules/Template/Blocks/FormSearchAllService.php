<?php
namespace Modules\Template\Blocks;

use Modules\Flight\Models\SeatType;
use Modules\Template\Blocks\BaseBlock;
use Modules\Location\Models\Location;
use Modules\Media\Helpers\FileHelper;

class FormSearchAllService extends BaseBlock
{
    function __construct()
    {
        $list_service = [];
        foreach (get_bookable_services() as $key => $service) {
            $list_service[] = ['value'   => $key,
                               'name' => ucwords($key)
            ];
            $arg[] = [
                'id'        => 'title_for_'.$key,
                'type'      => 'input',
                'inputType' => 'text',
                'label'     => __('Title for :service',['service'=>ucwords($key)])
            ];
        }
        $arg[] = [
            'id'            => 'service_types',
            'type'          => 'checklist',
            'listBox'          => 'true',
            'label'         => "<strong>".__('Service Type')."</strong>",
            'values'        => $list_service,
        ];

        $arg[] = [
            'id'        => 'title',
            'type'      => 'input',
            'inputType' => 'text',
            'label'     => __('Title')
        ];
        $arg[] = [
            'id'        => 'sub_title',
            'type'      => 'input',
            'inputType' => 'text',
            'label'     => __('Sub Title')
        ];

        $arg[] =  [
            'id'            => 'style',
            'type'          => 'radios',
            'label'         => __('Style Background'),
            'values'        => [
                [
                    'value'   => '',
                    'name' => __("Tab button Pills")
                ],
                [
                    'value'   => 'style_2',
                    'name' => __("Tab button Boxed")
                ],
                [
                    'value'   => 'style_3',
                    'name' => __("Tab button Shadow")
                ]
            ]
        ];

        $arg[] = [
            'id'    => 'bg_image',
            'type'  => 'uploader',
            'label' => __('- Style 1: Background Image Uploader')
        ];

        $arg[] = [
            'type'=> "checkbox",
            'label'=>__("Hide form search service?"),
            'id'=> "hide_form_search",
            'default'=>false
        ];

        $arg[] = [
            'type'=> "checkbox",
            'label'=>__("single form search"),
            'id'=> "single_form_search",
            'default'=>false
        ];


        $this->setOptions([
            'settings' => $arg,
            'category'=>__("Other Block")
        ]);
    }

    public function getName()
    {
        return __('Form Search All Service');
    }

    public function content($model = [])
    {
        $model['bg_image_url'] = FileHelper::url($model['bg_image'] ?? "", 'full') ?? "";
        $model['list_location'] = $model['tour_location'] =  Location::where("status","publish")->limit(1000)->orderBy('name', 'asc')->with(['translations'])->get()->toTree();
        $model['style'] = !empty($model['style']) ? $model['style'] :  "style_1";
        $model['list_slider'] = $model['list_slider'] ?? "";
        $model['modelBlock'] = $model;
        $model['seatType'] =  SeatType::get();
        $model['bg_video'] = (in_array(pathinfo($model['bg_image_url'], PATHINFO_EXTENSION), ['mp4', 'webm', 'avi', 'mov', 'wmv', 'mpeg', 'asf'])) ? true : false ;

        return view('Template::frontend.blocks.form-search-all-service.'.$model['style'], $model);
    }

    public function contentAPI($model = []){
        if (!empty($model['bg_image'])) {
            $model['bg_image_url'] = FileHelper::url($model['bg_image'], 'full');
        }
        return $model;
    }
}
