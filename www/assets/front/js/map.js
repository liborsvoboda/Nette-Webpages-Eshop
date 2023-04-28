(function () {
    // var 
    mapboxgl.accessToken = "pk.eyJ1IjoiYXV0b2RldmVsbyIsImEiOiJjanc2OWlmZ2gwcjk3M3ptczc3ZGE5ZG1jIn0.dZ5ZuVN7nteJiXg3UhZjKA";


    var map = new mapboxgl.Map({
        container: "place_map",
        style: "mapbox://styles/mapbox/streets-v11",
        // center: [17.285995, 49.430380],
        center: (window.innerWidth <= 768) ? [17.285995, 49.430380] : [17.285995, 19.430380],
        // zoom: 4.2 // for 320px width, 6 for 1350px width (maximum)
        minZoom: 2
    });
    var geocoder = new MapboxGeocoder({
        accessToken: mapboxgl.accessToken,
        marker: {
            color: 'orange'
        },
        mapboxgl: mapboxgl
    });
    map.addControl(geocoder);


    // Make point clusters on zoom
    // @see https://docs.mapbox.com/mapbox-gl-js/example/cluster-html/
    // @see https://docs.mapbox.com/mapbox-gl-js/example/cluster/
    var langId = document.body.dataset.langId;
    var source = '/places';
    if (langId == 2) source += '-cs';
    source += '.json';
    map.on('load', function () {
        // Add a clustered GeoJSON source for places
        map.addSource('places', {
            "type": "geojson",
            "data": source,
            "cluster": true,
            "clusterMaxZoom": 40,
            "clusterRadius": 30
        });
        // Circle and symbol layers for rendering individual place (unclustered points)
        map.addLayer({
            "id": "place_circle",
            "type": "circle",
            "source": "places",
            "filter": ["!=", "cluster", true],
            "paint": {
                "circle-color": '#009ee2',
                "circle-opacity": 0.9,
                "circle-radius": 10
            }
        });

        // Country fill
        map.addLayer({
            'id': 'countries',
            'source': {
                'type': 'vector',
                'url': 'mapbox://byfrost-articles.74qv0xp0'
            },
            'source-layer': 'ne_10m_admin_0_countries-76t9ly',
            'type': 'fill',
            'paint': {
                'fill-color': '#dc3545',
                'fill-opacity': 0.35
            }
        });
        var countryCodes = [
            'AUT', 'DEU', 'CHL', 'FRA', 'ESP', 'PRT', 'GHA', 'LBN', 'KWT', 'SAU', 'BHR', 'JOR', 'IRQ', 'NLD', 'BEL', 'HUN', 'POL', 'RUS', 'BLR', 'UKR', 'EST', 'LVA', 'LTU', 'GRC', 'ITA', 'CYP', 'CHN', 'KOR', 'BGR', 'SVN', 'XKX', 'MNE', 'MDA', 'TWN', 'ALB', 'BIH', 'MLT', 'JPN', 'ROU', 'HRV', 'ARE', 'BHR', 'AUS',
            'CHE', 'ITA', 'PRT', 'SWE', 'FIN'
        ];
        countryCodes.push((langId == 2) ? 'SVK' : 'CZE');
        // countryCodes.push('CZE');
        map.setFilter('countries', ['in', 'ADM0_A3_IS'].concat(countryCodes));

        // objects for caching and keeping track of HTML marker objects (for performance)
        var markers = {};
        var markersOnScreen = {};
        function updateMarkers() {
            var newMarkers = {};
            var features = map.querySourceFeatures('places');

            // for every cluster on the screen, create an HTML marker for it (if we didn't yet),
            // and add it to the map if it's not there already
            for (var i = 0; i < features.length; i++) {
                var coords = features[i].geometry.coordinates;
                var props = features[i].properties;
                if (!props.cluster) continue;
                var id = props.cluster_id;

                var marker = markers[id];
                if (!marker) {
                    var el = createDonutChart(props);
                    marker = markers[id] = new mapboxgl
                        .Marker({ element: el })
                        .setLngLat(coords);
                }
                newMarkers[id] = marker;

                if (!markersOnScreen[id])
                    marker.addTo(map);
            }
            // for every marker we've added previously, remove those that are no longer visible
            for (id in markersOnScreen) {
                if (!newMarkers[id]) markersOnScreen[id].remove();
            }
            markersOnScreen = newMarkers;
        }

        // after the GeoJSON data is loaded, update markers on the screen and do so on every map move/moveend
        map.on('data', function (e) {
            // if (e.sourceId !== 'places' || !e.isSourceLoaded) return;
            if (e.sourceId !== 'places') return;
            map.on('move', updateMarkers);
            map.on('moveend', updateMarkers);
            updateMarkers();
        });

        // When a click event occurs on a feature in the places layer, open a popup at the
        // location of the feature, with description HTML from its properties.
        map.on('click', 'place_circle', function (e) {
            var coordinates = e.features[0].geometry.coordinates.slice();
            var description = e.features[0].properties.description;

            // Ensure that if the map is zoomed out such that multiple
            // copies of the feature are visible, the popup appears
            // over the copy being pointed to.
            while (Math.abs(e.lngLat.lng - coordinates[0]) > 180) {
                coordinates[0] += e.lngLat.lng > coordinates[0] ? 360 : -360;
            }

            new mapboxgl.Popup()
                .setLngLat(coordinates)
                .setHTML(description)
                .addTo(map);
        });

        // Change the cursor to a pointer when the mouse is over the places layer.
        map.on('mouseenter', 'place_circle', function () {
            map.getCanvas().style.cursor = 'pointer';
        });

        // Change it back to a pointer when it leaves.
        map.on('mouseleave', 'place_circle', function () {
            map.getCanvas().style.cursor = '';
        });
    });

    // code for creating an SVG donut chart from feature properties
    function createDonutChart(props) {
        var fontSize = props.point_count >= 50 ? 18 : props.point_count >= 20 ? 16 : props.point_count >= 10 ? 14 : 12;
        var r = props.point_count >= 50 ? 50 : props.point_count >= 20 ? 32 : props.point_count >= 10 ? 24 : 18;
        var r0 = Math.round(r * 0.6);
        var w = r * 2;

        var html = '<svg width="' + w + '" height="' + w + '" viewbox="0 0 ' + w + ' ' + w +
            '" text-anchor="middle" style="font: ' + fontSize + 'px sans-serif">' +
            '<circle cx="' + r + '" cy="' + r + '" r="' + r0 +
            '" fill="white" /><text dominant-baseline="central" transform="translate(' +
            r + ', ' + r + ')">' + props.point_count + '</text></svg>';

        var el = document.createElement('div');
        el.innerHTML = html;
        return el.firstChild;
    }

})();