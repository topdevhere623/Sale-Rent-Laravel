(function ($) {
    "use strict"
    
    //var geocoder = new google.maps.Geocoder;
    
    window.BravoMapEngine = function (id,configs) {
        switch (myTravel.map_provider) {
            case "osm":
                return new OsmMapEngine(id,configs);
                break;
            case "gmap":
                return new GmapEngine(id,configs);
                break;
        }
    };

    function BaseMapEngine(id,options){
        var defaults = {};
    }

    BaseMapEngine.prototype.getOption = function (key) {

        if(typeof this.options[key] == 'undefined'){

            if(typeof this.defaults[key] != 'undefined'){
                return this.defaults[key];
            }
            return null;

        }
        return this.options[key];

    };


    function OsmMapEngine(id,options){
        this.defaults = {
            fitBounds:true
        };
        var el = {};
        this.map = null;
        this.id = id;
        this.options = options;
        this.markers = [];
        this.bounds = null;

        this.init();

        return this;
    }

    OsmMapEngine.prototype = new BaseMapEngine();

    OsmMapEngine.prototype.initScripts = function (func) {
        func();
        return;

        if(typeof window.bc_osm_script_inited != 'undefined') return;
        if(this.getOption('disableScripts')){
            func();
            return;
        }

        var head= document.getElementsByTagName('head')[0];
        var script= document.createElement('script');
        script.type= 'text/javascript';
        script.src= myTravel.url_root+'/libs/leaflet1.4.0/leaflet.js';
        head.appendChild(script);

        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = myTravel.url_root+'/libs/leaflet1.4.0/leaflet.css';
        head.appendChild(link);

        window.bc_osm_script_inited = true;


        script.onload = function(){
            func();
        }
    };

    OsmMapEngine.prototype.init = function () {

        var me = this;

        this.el  = $('#'+this.id);

        this.initScripts(function () {

            var center = me.getOption('center');
            var zoom = me.getOption('zoom');

            me.map = L.map(me.id).setView(center, zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(me.map);

            var rd = me.getOption('ready');
            if(typeof rd == "function"){
                rd(me);
            }

        });

    };

    OsmMapEngine.prototype.addMarker = function (latLng,options) {

        var m = L.marker(latLng,options).addTo(this.map);

        this.markers.push(m);

    };
    OsmMapEngine.prototype.addMarker2 = function (marker) {

        var options = {
            icon_options:{
                iconUrl:''
            }
        };
            options.icon_options.iconUrl = marker.marker
        if(options.icon_options){
            options.icon = L.icon(options.icon_options);
        }

        var m = L.marker([marker.lat,marker.lng],options).addTo(this.map);

        this.markers.push(m);

    };

    OsmMapEngine.prototype.addMarkers = function (markers) {

        for(var i = 0 ; i < markers.length; i++){

            this.addMarker(markers[i][0],markers[i][1]);

        }

        if(this.getOption('fitBounds'))
        {
            this.bounds = [];
            for (var key in this.markers) {
                var marker = this.markers[key];
                this.bounds.push([ marker._latlng.lat , marker._latlng.lng ])
            }
            try {
                this.map.fitBounds(this.bounds);
            }catch (e) {
                console.log(e);
            }
            this.map.invalidateSize();
        }

    };
    OsmMapEngine.prototype.addMarkers2 = function (markers) {
        for(var i = 0 ; i < markers.length; i++){
            this.addMarker2(markers[i]);
        }
        if(this.getOption('fitBounds'))
        {
            this.bounds = [];
            for (var key in this.markers) {
                var marker = this.markers[key];
                this.bounds.push([ marker._latlng.lat , marker._latlng.lng ])
            }
            try {
                this.map.fitBounds(this.bounds);
            }catch (e) {
                console.log(e);
            }
            this.map.invalidateSize();
        }
    };

    OsmMapEngine.prototype.clearMarkers = function (markers) {

        for(var i = 0; i < this.markers.length; i++){

            this.map.removeLayer(this.markers[i]);

        }

        this.markers = [];

    };

    OsmMapEngine.prototype.on = function (type,func) {

        switch (type) {
            case "click":
                return this.map.on(type,function(e){
                    func([
                        e.latlng.lat,
                        e.latlng.lng,
                    ])
                });
            case "zoom_changed":
                return this.map.on('zoomend',function(e){
                    func(e.target.getZoom())
                });
            break;
        }

    };

    OsmMapEngine.prototype.searchBox = function (classSearchBox ,func) {
        classSearchBox.hide();
    }

    function GmapEngine(id,options){

		this.defaults = {
            fitBounds:true
        };
        var el = {};
        this.map = null;
        this.id = id;
        this.options = options;
        this.markersPositions = [];
        this.markers = [];
        var bounds = null;
        this.infoboxs = [];

        this.init();

        return this;

    }

    GmapEngine.prototype = new BaseMapEngine();

    GmapEngine.prototype.initScripts = function (func) {

        func();
        return;
        if(typeof window.bc_gmap_script_inited != 'undefined') return;
        if(this.getOption('disableScripts')){
            func();
            return;
        }

        var head= document.getElementsByTagName('head')[0];
        var script= document.createElement('script');
        script.type= 'text/javascript';
        script.src= 'https://maps.googleapis.com/maps/api/js?key='+myTravel.map_gmap_key+'&libraries=places';
        head.appendChild(script);

		var script2 = document.createElement('script');
		script2.type= 'text/javascript';
		script2.src= myTravel.url+'/libs/infobox.js';
		head.appendChild(script2);

        window.bc_gmap_script_inited = true;

        script.onload = function(){
            func();
        }
    };

    GmapEngine.prototype.init = function () {

        var me = this;

        this.el  = $('#'+this.id);

        this.initScripts(function () {

            var center = me.getOption('center');
            var zoom = me.getOption('zoom');

            me.map = new google.maps.Map(document.getElementById(me.id), {
                center: {lat:center[0],lng:center[1]},
                zoom: zoom,
                //maxZoom:15
            });
            me.placesService = new google.maps.places.PlacesService(me.map);

            var rd = me.getOption('ready');
            if(typeof rd == "function"){
                rd(me);
            }

        });

    };

    GmapEngine.prototype.addMarker = function (latLng,options) {


        var m = new google.maps.Marker({
            position: {
                lat:latLng[0],
                lng:latLng[1]
            },
            map: this.map,
            icon: options.icon_options.iconUrl
        });

        this.markers.push(m);

    };

    GmapEngine.prototype.addMarker2 = function (marker) {

        var m = new google.maps.Marker({
            position: {
                lat:marker.lat,
                lng:marker.lng
            },
            map: this.map,
            icon: marker.marker
        });

        if(marker.infobox){
			var ibOptions = {
				content: '',
				disableAutoPan: true
				, maxWidth: 0
				, pixelOffset: new google.maps.Size(-135, -35)
				, zIndex: null
				, boxStyle: {
					padding: "0px 0px 0px 0px",
					width: "270px",
				},
				closeBoxURL: "",
				cancelBubble: true,
				infoBoxClearance: new google.maps.Size(1, 1),
				isHidden: false,
				pane: "floatPane",
				enableEventPropagation: true,
				alignBottom: true
			};

			var boxText = document.createElement("div");

			boxText.style.cssText = "border-radius: 5px; background: #fff; padding: 0px;";
			boxText.innerHTML = marker.infobox;

			ibOptions.content = boxText;

            // Close Old
			for(var i = 0 ; i < this.infoboxs.length; i++){

				this.infoboxs[i].close();
			}

            var ib =  new InfoBox(ibOptions);
            this.infoboxs.push(ib);


			var me = this;
			m.addListener('click', function() {
			    //
                for(var i = 0 ; i < me.infoboxs.length ; i++){
                    me.infoboxs[i].close();
                }
			    ib.open(me.map,this);
			    me.map.panTo(ib.getPosition());

                if(window.lazyLoadInstance){
                    window.lazyLoadInstance.update();
                }
			});


        }

        this.markers.push(m);
        this.markersPositions.push(m.getPosition());

    };

    GmapEngine.prototype.addMarkers = function (markers) {

        for(var i = 0 ; i < markers.length; i++){

            this.addMarker(markers[i][0], markers[i][1]);
        }

        if(this.getOption('fitBounds'))
        {
            this.bounds = new google.maps.LatLngBounds();

            for(var i = 0; i < this.markers.length; i++){

                this.bounds.extend(this.markers[i]);

            }

            this.map.fitBounds(this.bounds);
        }

    };
    GmapEngine.prototype.addMarkers2 = function (markers) {

        for(var i = 0 ; i < markers.length; i++){

            this.addMarker2(markers[i]);

        }

        if(this.getOption('fitBounds'))
        {
            this.bounds = new google.maps.LatLngBounds();

            for(var i = 0; i < this.markersPositions.length; i++){

                this.bounds.extend(this.markersPositions[i]);

            }

            this.map.fitBounds(this.bounds);
        }

    };

    GmapEngine.prototype.clearMarkers = function (markers) {

        if(this.markers.length > 0){
            for(var i = 0; i < this.markers.length; i++){
                this.markers[i].setMap(null);
            }
        }

        this.markers = [];
        this.markersPositions = [];

        this.infoboxs = [];

    };

    GmapEngine.prototype.on = function (type,func) {
        switch (type) {
            case "click":
                return this.map.addListener(type,function(e){
                    let zoom = this.getZoom();
                    func([
                        e.latLng.lat(),
                        e.latLng.lng(),
                        zoom,
                    ])
                });
            break;
            case "zoom_changed":
                return this.map.addListener(type,function(e){
                    let zoom = this.getZoom();
                    func(
                        zoom
                    )
                });
            break;
        }
    };

    GmapEngine.prototype.searchBox_old = function (classSearchBox, func) {
        var me = this;
        var searchBox = new google.maps.places.SearchBox(classSearchBox[0]);
        google.maps.event.addListener(searchBox, 'places_changed', function() {
            var places = searchBox.getPlaces();
            if (places.length == 0) {
                return;
            }
            var bounds = new google.maps.LatLngBounds();
            for (var i = 0, place ; place = places[i]; i++) {
                if (!place.geometry) {
                    console.log("Returned place contains no geometry");
                    return;
                }
                if (place.geometry.viewport) {
                    bounds.union(place.geometry.viewport);
                } else {
                    bounds.extend(place.geometry.location);
                }
                if(i===0){
                    func([
                        place.geometry.location.lat(),
                        place.geometry.location.lng(),
                        me.map.getZoom(),
                        place.address_components,
                        place
                    ]);
                }
            }
            me.map.fitBounds(bounds);
        });
    }
    
    GmapEngine.prototype.searchBox = function (classSearchBox, func, options) {
        var me = this;
        if(!options) {
            options = {};
        }
        var searchBox = new google.maps.places.Autocomplete(classSearchBox[0], options);
        google.maps.event.addListener(searchBox, 'place_changed', function() {
            var place = searchBox.getPlace();
            console.log({
                place: place
            });
            if (!place || place.length == 0) {
                return;
            }
            var bounds = new google.maps.LatLngBounds();
            if (!place.geometry) {
                console.log("Returned place contains no geometry");
                return;
            }
            if (place.geometry.viewport) {
                bounds.union(place.geometry.viewport);
            } else {
                bounds.extend(place.geometry.location);
            }
            func([
                place.geometry.location.lat(),
                place.geometry.location.lng(),
                me.map.getZoom(),
                place.address_components,
                place
            ]);
            me.map.fitBounds(bounds);
        });
    }
    GmapEngine.prototype.details = function (place_id, func) {
        var me = this;
        me.placesService.getDetails({
            placeId: place_id,
        }, (place, status) => {
            if (
              status === 'OK' &&
              place &&
              place.geometry &&
              place.geometry.location
            ) {
                var bounds = new google.maps.LatLngBounds();
                if (place.geometry.viewport) {
                    bounds.union(place.geometry.viewport);
                } else {
                    bounds.extend(place.geometry.location);
                }
                func([
                    place.geometry.location.lat(),
                    place.geometry.location.lng(),
                    me.map.getZoom(),
                    place.address_components,
                    place
                ]);
                me.map.fitBounds(bounds);
            }
        });
    }

})(jQuery);
