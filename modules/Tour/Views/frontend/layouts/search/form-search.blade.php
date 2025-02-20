<form action="{{ route("tour.search") }}" name="tour_search_form" class="form bravo_form d-flex mb-1 py-2" method="get">

    <div class="g-field-search">

        <div class="row d-block nav-select d-flex align-items-end">

            @php $tour_search_fields = setting_item_array('tour_search_fields');

            $tour_search_fields = array_values(\Illuminate\Support\Arr::sort($tour_search_fields, function ($value) {

                return $value['position'] ?? 0;

            }));

            @endphp

            @if(!empty($tour_search_fields))

                @foreach($tour_search_fields as $field)

                    @php $field['title'] = $field['title_'.app()->getLocale()] ?? $field['title'] ?? "" @endphp
                    <div class="col-md-{{ $field['size'] ?? "6" }} mb-4 mb-lg-0 text-left">

                        @switch($field['field'])

                            @case ('service_name')

                                @include('Tour::frontend.layouts.search.fields.service_name', ['title' => $field['title']])

                            @break

                            @case ('location')

                                @include('Tour::frontend.layouts.search.fields.location', ['title' => $field['title']])

                            @break

                            @case ('date')

                                @include('Tour::frontend.layouts.search.fields.date', ['title' => $field['title']])

                            @break

                            {{--@case ('category')

                                @include('Tour::frontend.layouts.search.fields.category')

                            @break--}}

                            @case ('guests')

                            @include('Tour::frontend.layouts.search.fields.guests', ['title' => $field['title']])

                            @break

                            @case ('attr')

                                @include('Tour::frontend.layouts.search.fields.attr', ['title' => $field['title']])

                            @break

                        @endswitch

                    </div>

                @endforeach

            @endif

        </div>

    </div>

    <div class="g-button-submit align-self-lg-end">

        <button id="tour_search_button" type="button" class="btn btn-primary btn-md border-radius-3 mb-xl-0 mb-lg-1 transition-3d-hover">

            <i class="flaticon-magnifying-glass font-size-20 mr-2"></i>{{ __("Search") }}

        </button>

    </div>

</form>

