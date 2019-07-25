var geocoder, map = null;
function setTencentMap(lt, lg,mapid,maplat,maplng){
    setMid(mapid);
    var center = new qq.maps.LatLng(lt, lg);
    map = new qq.maps.Map(document.getElementById(mapid), {
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
        document.getElementById(maplat).value = (map.getCenter()).lat;
        document.getElementById(maplng).value = (map.getCenter()).lng;
     });
     
     //实例化自动完成
    var ap = new qq.maps.place.Autocomplete(document.getElementById('place'));
    //调用Poi检索类。用于进行本地检索、周边检索等服务。
    var searchService = new qq.maps.SearchService({
        map : map,
         //检索成功的回调函数
        complete: function(results) {
            //设置回调函数参数
            var pois = results.detail.pois;
            var position = pois[0];
            map.setCenter(position.latLng);
            document.getElementById(maplat).value = position.latLng.lat;
            document.getElementById(maplng).value = position.latLng.lng;
        },
    });
    //添加监听事件
    qq.maps.event.addListener(ap, "confirm", function(res){
        searchService.setPageCapacity(1);
        searchService.search(res.value);
    });
}

function setMid(mapid) {
    //创建自定义控件
    var middleControl = document.createElement("div");
    var left = 518;
    var top = 162;
    middleControl.style.position = "relative";
    middleControl.style.width = "36px";
    middleControl.style.left = left + "px";
    middleControl.style.top = top + "px";
    middleControl.style.height = "36px";
    middleControl.style.zIndex = "10000000000";
    middleControl.innerHTML = '<img src="/static/js/map/gps_map.png" />';
    document.getElementById(mapid).appendChild(middleControl);
}



function codeAddress(address) {
    geocoder.getLocation(address);
}