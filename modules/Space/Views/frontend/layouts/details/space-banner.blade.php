@if($row->banner_image_id)
    <div class="bravo_banner">
        {{--@if(!empty($breadcrumbs))
            <div class="container">
                <nav class="py-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-no-gutter mb-0 flex-nowrap flex-xl-wrap overflow-auto overflow-xl-visble">
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1"><a href="{{url('')}}">{{__('Home')}}</a></li>
                        @foreach($breadcrumbs as $breadcrumb)
                            <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1 {{$breadcrumb['class'] ?? ''}}">
                                @if(!empty($breadcrumb['url']))
                                    <a href="{{url($breadcrumb['url'])}}">{{$breadcrumb['name']}}</a>
                                @else
                                    {{$breadcrumb['name']}}
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            </div>
        @endif--}}

        <div class="mb-8">
            <div class="container">
                <div class="images__thumbs">
                    <div class="images__flex images__fotorama"
                         data-auto="false"
                         data-allow-full-screen="true"
                         data-thumb-width="135"
                         data-thumb-height="135"
                         data-nav="thumbs">

                        @if($row->getGallery())
                            @foreach($row->getGallery() as $key=>$item)
                                <a href="{{$item['large']}}" class="images__item">
                                    <div class="images__number"><div>+{{count($row->getGallery())}} <span>Photos</span> </div></div>
                                    <img src="{{$item['large']}}" alt="">
                                </a>
                            @endforeach
                        @endif

                    </div>
                </div>


            </div>
            {{--<div class="travel-slick-carousel u-slick u-slick__img-overlay"
                 data-arrows-classes="d-none d-md-inline-block u-slick__arrow-classic u-slick__arrow-centered--y rounded-circle"
                 data-arrow-left-classes="flaticon-back u-slick__arrow-classic-inner u-slick__arrow-classic-inner--left ml-md-4 ml-xl-8"
                 data-arrow-right-classes="flaticon-next u-slick__arrow-classic-inner u-slick__arrow-classic-inner--right mr-md-4 mr-xl-8"
                 data-infinite="true"
                 data-slides-show="1"
                 data-slides-scroll="1"
                 data-center-mode="true"
                 data-pagi-classes="d-md-none text-center u-slick__pagination mt-5 mb-0"
                 data-center-padding="450px"
                 data-responsive='[{
                        "breakpoint": 1480,
                        "settings": {
                            "centerPadding": "300px"
                        }
                    }, {
                        "breakpoint": 1199,
                        "settings": {
                            "centerPadding": "200px"
                        }
                    }, {
                        "breakpoint": 992,
                        "settings": {
                            "centerPadding": "120px"
                        }
                    }, {
                        "breakpoint": 554,
                        "settings": {
                            "centerPadding": "20px"
                        }
                    }]'>

                @if($row->getGallery())
                    @foreach($row->getGallery() as $key=>$item)
                        <div class="js-slide bg-img-hero min-height-550" style="background-image: url('{{$item['large']}}');"></div>
                    @endforeach
                @endif
            </div>--}}
        </div>
    </div>
@endif

