var geocoder, map = null;
var init = function (lt, lg) {
    setMid();
    var center = new qq.maps.LatLng(lt, lg);
    map = new qq.maps.Map(document.getElementById('map'), {
        center: center,
        zoom: 19
    });

    geocoder = new qq.maps.Geocoder({
        complete: function (result) {
            map.setCenter(result.detail.location);
            document.getElementById("lat").value = result.detail.location.lat;
            document.getElementById("lng").value = result.detail.location.lng;
        }
    });
    qq.maps.event.addListener(map, 'center_changed', function () {
                                document.getElementById('lat').value = (map.getCenter()).lat;
                                document.getElementById('lng').value = (map.getCenter()).lng;
     });
     
}

function setMid() {
    //创建自定义控件
    var middleControl = document.createElement("div");
    var left = 379;
    var top = 160;
    middleControl.style.position = "relative";
    middleControl.style.width = "36px";
    middleControl.style.left = left + "px";
    middleControl.style.top = top + "px";
    middleControl.style.height = "36px";
    middleControl.style.zIndex = "10000000000";
    middleControl.innerHTML = '<img src="/static/js/map/gps_map.png" />';
    document.getElementById("map").appendChild(middleControl);
}

$('[data-place]').keyup(function(){
       var pcd = $('input[data-pcd]').eq(0).val();
       if(pcd == ""){
         layer.msg('请先选择城市'); 
         $(this).val('').blur();
         return;
       }
       var address = $(this).val();
       geocoder.getLocation(pcd+address);
})