/**
 * 打印
 * @type @exp;layui@pro;layer
 */
function log(obj) {
    console.log(obj);
}

/**
 * 后台JS主入口
 */
var layer = layui.layer,
        element = layui.element,
        laydate = layui.laydate,
        form = layui.form;
table = layui.table;

/**
 * AJAX全局设置
 */
$.ajaxSetup({
    type: "post",
    dataType: "json"
});

/**
 * 通用单图上传（停用，使用$('#xxx).thinkupload()上传）
 */
//layui.upload({
//    url: "/index.php/utils/upload/uploadPicture",
//    type: 'image',
//    ext: 'jpg|png|gif|bmp|jpeg',
//    before: function (input) {
//        var file = input.files[0];
//        var width = $(input).attr('img_width');
//        var height = $(input).attr('img_height');
//        var img_id = $(input).attr('img_id');
//        //追加参数
//        var data = {"img_width": width, "img_height": height, 'name': input.name, 'img_id': img_id};
//        extra_data(input, data);
//    },
//    success: function (data) {
//        if (data.error === 0) {
//            if (typeof ($("#" + data.img_id)) == "object") {
//                $('#' + data.img_id).attr('src', data.url);
//                $('input[name=' + data.img_id + ']').val(data.cover_id);
//            }
//        } else {
//            layer.msg(data.message);
//        }
//    }
//});

/**
 * 某个元素后面追加参数
 * @param {type} param1
 * @param {type} param2
 */
function extra_data(input, data) {
    var item = [];
    $.each(data, function (k, v) {
        item.push('<input type="hidden" name="' + k + '" value="' + v + '">');
    })
    //清除之前的
    $(input).closest('form').find('input[type=hidden]').remove();
    $(input).after(item.join(''));
}

/**
 * 通用日期时间选择
 */
$('.datetime').on('click', function () {
    laydate({
        elem: this,
        istime: true,
        format: 'YYYY-MM-DD hh:mm:ss'
    })
});

/**
 * 通用表单提交(AJAX方式)
 */
form.on('submit(*)', function (data) {
    //加载层-风格4
    var index_msg = layer.load('', {
        shade: 0.1
    });
    setTimeout(function () {
        $.ajax({
            url: data.form.action,
            type: data.form.method,
            data: $(data.form).serialize(),
            success: function (info) {
                if (info.code === 1) {
                    setTimeout(function () {
                        var index = parent.layer.getFrameIndex(window.name); //获取窗口索引
                        //判断是否是弹出框
                        if (typeof (index) != "undefined") {
                            parent.location.reload();
                        } else {
                            location.href = info.url;
                        }
                    }, 2000);
                }
                setTimeout(function () {
                    layer.close(index_msg);
                    layer.msg(info.msg);
                }, 1000);
            }
        });
    }, 300)
    return false;
});

/**
 * 通用批量处理（审核、取消审核、删除）
 */
$('.ajax-action').on('click', function () {
    var _action = $(this).data('action');
    layer.open({
        shade: false,
        content: '确定执行此操作？',
        btn: ['确定', '取消'],
        yes: function (index) {
            //加载层-风格4
            var index_msg = layer.msg('数据操作中..', {
                icon: 16,
                shade: 0.1
            });
            $.ajax({
                url: _action,
                data: $('.ajax-form').serialize(),
                success: function (info) {
                    if (info.code === 1) {
                        setTimeout(function () {
                            location.href = info.url;
                        }, 1000);
                    }
                    layer.close(index_msg);
                    layer.msg(info.msg);
                }
            });
            layer.close(index);
        }
    });

    return false;
});


//全选
form.on('checkbox(allChoose)', function (data) {
    var child = $(data.elem).parents('table').find('tbody input[type="checkbox"]:not([name="show"])');
    child.each(function (index, item) {
        if (!$(item).attr('disabled')) {
            item.checked = data.elem.checked;
        }
    });
    form.render('checkbox');
});

/**
 * 通用删除
 */
$('.ajax-delete').on('click', function () {
    var _href = $(this).attr('href');
    layer.open({
        shade: false,
        content: '确定删除？',
        btn: ['确定', '取消'],
        yes: function (index) {
            $.ajax({
                url: _href,
                type: "get",
                success: function (info) {
                    if (info.code === 1) {
                        setTimeout(function () {
                            location.href = info.url;
                        }, 1000);
                    }
                    layer.msg(info.msg);
                }
            });
            layer.close(index);
        }
    });

    return false;
});

/**
 * 清除缓存
 */
$('#clear-cache').on('click', function () {
    var _url = $(this).data('url');
    if (_url !== 'undefined') {
        $.ajax({
            url: _url,
            success: function (data) {
                if (data.code === 1) {
                    setTimeout(function () {
                        location.href = location.pathname;
                    }, 1000);
                }
                layer.msg(data.msg);
            }
        });
    }

    return false;
});

/**
 * 通用添加,和修改
 */
$('.iframe-add').on('click', function () {
    layer.close();
    var _this = $(this);
    var index = layer.open({
        type: 2,
        title: _this.attr('title') || "添加",
        anim: 3,
        area: ['80%', '90%'],
        fixed: false, //不固定
        maxmin: false,
        content: _this.attr('data-url'), //这里content是一个URL，如果你不想让iframe出现滚动条，你还可以content: ['http://sentsin.com', 'no']
    });
//    if (typeof (_this.attr('full')) !== "undefined") {
//        layer.full(index);
//    }
    layer.full(index);
})
$('.iframe-edit').on('click', function () {
    layer.close();
    var _this = $(this);
    var index = layer.open({
        type: 2,
        title: _this.attr('title') || "修改",
        anim: 3,
        area: ['80%', '90%'],
        fixed: false, //不固定
        maxmin: false,
        content: _this.attr('data-url'), //这里content是一个URL，如果你不想让iframe出现滚动条，你还可以content: ['http://sentsin.com', 'no']
    });
//    if (typeof (_this.attr('full')) !== "undefined") {
//        layer.full(index);
//    }
    layer.full(index);
})

$('body').on('click','.chose_icon', function () {
    layer.close();
    var _this = $(this);
    var index = layer.open({
        type: 2,
        title:'图标选取',
        anim: 3,
        area: ['600px', '600px'],
        fixed: true, //不固定
        maxmin: false,
        content:["http://"+location.host+"/admin.php/icons/_index"], //这里content是一个URL，如果你不想让iframe出现滚动条，你还可以content: ['http://sentsin.com', 'no']
    });
    window.iconObj = $(this);
    window.iconIndex = index;
})
//页面顶部导航栏切换
$('body').on('click',".taburl li",function(){
    location.href = $(this).data('url');
})

/*! 注册 data-phone-view 事件行为 */
$('body').on('click', '[data-phone-view]', function () {
    if (!isInclude("wechat.css")) {
        $("<link>")
                .attr({rel: "stylesheet",
                    type: "text/css",
                    href: Lawnson.css + "/wechat.css"
                }).appendTo("head");
    }
    var $container = $('<div class="mobile-preview pull-left"><div class="mobile-header">' + Lawnson.site_title + '</div><div class="mobile-body"><iframe id="phone-preview" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"></iframe></div></div>').appendTo('body');
    $container.find('iframe').attr('src', this.getAttribute('data-phone-view') || this.href);
    layer.style(layer.open({type: 1, scrollbar: !1, area: ['350px', '600px'], title: !1, closeBtn: 1, skin: 'layui-layer-nobg', shadeClose: !!1,
        content: $container,
        end: function () {
            $container.remove();
        }
    }), {boxShadow: 'none'});
});

/*! 注册 data-iframe 事件行为 */
$('body').on('click', '[data-iframe]', function () {
    var _this = $(this);
    layer.open({title: _this.attr('title') || '窗口', type: 2, area: ['800px', '530px'], fix: true, maxmin: false, content: _this.attr('data-iframe')});
});

/**
 * 字符串截取
 */
function substring(cla, len) {
    $('.' + cla).each(function () {
        var str = $(this).html();
        if (typeof (len) == "undefined")
            len = 10;
        if (str.length > len) {
            $(this).html(str.substring(0, len) + "...");
            $(this).attr('title', str);
        } else {
            $(this).html(str);
        }

    });
}

function isInclude(name) {
    var js = /js$/i.test(name);
    var es = document.getElementsByTagName(js ? 'script' : 'link');
    for (var i = 0; i < es.length; i++)
        if (es[i][js ? 'src' : 'href'].indexOf(name) != -1)
            return true;
    return false;
}
function loading(suc) {
    //加载层-风格4
    var loadingIndex = layer.load('', {
        shade: 0.1
    });
    if (typeof (suc) == 'function') {
        setTimeout(function () {
            suc(loadingIndex);
        }, 300)
    }
}
function closeLoading(loadingIndex) {
    layer.close(loadingIndex);
}

$('body').on('click', '[img_view]', function () {
    layer.closeAll('photos');
    var data = [
        {src:$(this).attr('src')}
    ];
    layer.photos({
        photos: {
          data:data
        }
    });
})

//颜色选取
$('body').on('click', '[data-color]', function () {
   var self = $(this);
   var table = $('body').find('[select-table]');
   if(typeof(table.html())==="undefined"){
       table = '<div select-table style="border:1px solid #ccc;width:200px;padding:3px;">';
       table += '<table>';
            table+= '<tr>';
                table+= '<td style="padding:2px;" dis="#63b359" va="Color010"><div style="background-color:#63b359 ;height: 22px;margin-top: 4px;width: 22px;display:inline-block;"></div></td>';
                table+= '<td style="padding:2px;" dis="#2c9f67" va="Color020"><div style="background-color:#2c9f67 ;height: 22px;margin-top: 4px;width: 22px;display:inline-block;"></div></td>';
                table+= '<td style="padding:2px;" dis="#509fc9" va="Color030"><div style="background-color:#509fc9 ;height: 22px;margin-top: 4px;width: 22px;display:inline-block;"></div></td>';
                table+= '<td style="padding:2px;" dis="#5885cf" va="Color040"><div style="background-color:#5885cf ;height: 22px;margin-top: 4px;width: 22px;display:inline-block;"></div></td>';
                table+= '<td style="padding:2px;" dis="#9062c0" va="Color050"><div style="background-color:#9062c0 ;height: 22px;margin-top: 4px;width: 22px;display:inline-block;"></div></td>';
                table+= '<td style="padding:2px;" dis="#d09a45" va="Color060"><div style="background-color:#d09a45 ;height: 22px;margin-top: 4px;width: 22px;display:inline-block;"></div></td>';
                table+= '<td style="padding:2px;" dis="#e4b138" va="Color070"><div style="background-color:#e4b138 ;height: 22px;margin-top: 4px;width: 22px;display:inline-block;"></div></td>';
            table+= '</tr>';
            table+= '<tr>';
                table+= '<td style="padding:2px;" dis="#ee903c" va="Color080"><div style="background-color:#ee903c ;height: 22px;margin-top: 4px;width: 22px;display:inline-block;"></div></td>';
                table+= '<td style="padding:2px;" dis="#f08500" va="Color081"><div style="background-color:#f08500 ;height: 22px;margin-top: 4px;width: 22px;display:inline-block;"></div></td>';
                table+= '<td style="padding:2px;" dis="#a9d92d" va="Color082"><div style="background-color:#a9d92d ;height: 22px;margin-top: 4px;width: 22px;display:inline-block;"></div></td>';
                table+= '<td style="padding:2px;" dis="#dd6549" va="Color090"><div style="background-color:#dd6549 ;height: 22px;margin-top: 4px;width: 22px;display:inline-block;"></div></td>';
                table+= '<td style="padding:2px;" dis="#cc463d" va="Color100"><div style="background-color:#cc463d ;height: 22px;margin-top: 4px;width: 22px;display:inline-block;"></div></td>';
                table+= '<td style="padding:2px;" dis="#cf3e36" va="Color101"><div style="background-color:#cf3e36 ;height: 22px;margin-top: 4px;width: 22px;display:inline-block;"></div></td>';
                table+= '<td style="padding:2px;" dis="#5E6671" va="Color102"><div style="background-color:#5E6671 ;height: 22px;margin-top: 4px;width: 22px;display:inline-block;"></div></td>';
            table+= '</tr>';
        table+= '</table>';
    table+= '</div> ';   
    console.log(table);
    self.after(table);
   }
   $('[select-table]').show();
   $('[select-table] td').on('click',function(){
       self.css('background',$(this).attr('dis'));
       self.val($(this).attr('dis'));
       $('[select-table]').hide();
   })   
});