<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
<script type="text/javascript">
    var markers = [
            {
                "title": 'Alibaug',
                "lat": '23.81030',
                "lng": '90.41250',                
            }
        ,
            {
                "title": 'Mumbai',
                "lat": '22.34750',
                "lng": '91.81230',               
            }         
        ,
        	{
                "title": 'Alibaug',
                "lat": '23.81030',
                "lng": '90.41250',                
            }
         ,   
           	{
                "title": 'Mumbai',
                "lat": '23.46190',
                "lng": '91.18690',                
            }     
        ,
            {
                "title": 'Mumbai',
                "lat": '22.34750',
                "lng": '91.81230',                
            }               
    ];
    window.onload = function () {
        var mapOptions = {
            center: new google.maps.LatLng(23.777176, 90.399452),
            zoom: 10,
            mapTypeId: google.maps.MapTypeId.TRANSIT
        };
        var map = new google.maps.Map(document.getElementById("dvMap"), mapOptions);
        var infoWindow = new google.maps.InfoWindow();
        var lat_lng = new Array();
        var latlngbounds = new google.maps.LatLngBounds();
        for (i = 0; i < markers.length; i++) {
            var data = markers[i]
            var myLatlng = new google.maps.LatLng(data.lat, data.lng);
            lat_lng.push(myLatlng);
            var marker = new google.maps.Marker({
                position: myLatlng,
                map: map,
                title: data.title
            });
            latlngbounds.extend(marker.position);            
        }
        map.setCenter(latlngbounds.getCenter());
        map.fitBounds(latlngbounds);
 
        //***********ROUTING****************//
 
        //Initialize the Path Array
        var path = new google.maps.MVCArray();
 
        //Initialize the Direction Service
        var service = new google.maps.DirectionsService();
 
        //Set the Path Stroke Color
        var poly = new google.maps.Polyline({ map: map, strokeColor: '#4986E7' });
 
        //Loop and Draw Path Route between the Points on MAP
        for (var i = 0; i < lat_lng.length; i++) {
            if ((i + 1) < lat_lng.length) {
                var src = lat_lng[i];
                var des = lat_lng[i + 1];
                path.push(src);
                poly.setPath(path);
                service.route({
                    origin: src,
                    destination: des,
                    travelMode: google.maps.TravelMode.DRIVING
                }, function (result, status) {
                    if (status == google.maps.DirectionsStatus.OK) {                        
                        for (var i = 0, len = result.routes[0].overview_path.length; i < len; i++) {
                            path.push(result.routes[0].overview_path[i]);
                        }
                    }
                });
            }
        }
    }
</script>
<div id="dvMap" style="width: 500px; height: 500px">
</div>