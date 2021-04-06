Vtiger.Class("VDProgressView_Js", {
    instance: false,
    getInstance: function () {
        if (VDProgressView_Js.instance == false) {
            var instance = new VDProgressView_Js();
            VDProgressView_Js.instance = instance;
            return instance;
        }
        return VDProgressView_Js.instance;
    }
    },{
    registerShowOnDetailView:function(){
        var self = this;
        var params = {};
        params['module'] = 'VDProgressView';
        params['view'] = 'ViewProgress';
        params['record'] = app.getRecordId();
        params['moduleSelected'] = app.getModuleName();
        app.request.post({data:params}).then(
            function(err,data) {
                if(err == null && data!=""){
                    var detailview_header = jQuery('.detailview-header-block > .row > .col-lg-6');
                    detailview_header.after(data);
                    $("#div_vdprogressview").fadeIn(700);
                    var current_slide = $('.slide-wrap .onView');
                    if (current_slide.length) {
                        $('.slider-wrap').animate({
                            scrollLeft:current_slide.position().left+20
                        }, 300);
                    }
                    $('.progressNext').hide();
                    $('.progressPrev').hide();
                    $('.vdProgressViewMiddleContainer').scroll(function () {
                        var $elem=$(this);
                        var newScrollLeft = $elem.scrollLeft(),
                            width=$elem.width(),
                            scrollWidth=$elem.get(0).scrollWidth;
                        var offset = 15;
                        if (scrollWidth - newScrollLeft - width == offset) {
                            $('.progressNext').hide();
                        }
                        else{
                            $('.progressNext').show();
                        }
                        if (newScrollLeft === 0) {
                            $('.progressPrev').hide();
                        }
                        else{
                            $('.progressPrev').show();
                        }
                    });
                    $('li.active').trigger('click');
                }
            },
            function(error) {
            }
        );

    },
    registerProgressViewHeaderClick:function(){
        var thisInstance = this;
        $(".detailViewContainer").on("click",".vdProgressViewHeaderColumn",function () {
            if(!$(this).children('.vdProgressViewHeaderEmpty').hasClass('vdProgressView-Active')){
                var params = {};
                var this_li = $(this);
                var fieldName = $(this).data('field-name');
                var fieldLabel = $(this).data('field-label');
                var newValue = $(this).data('value');
                params['module'] = 'VDProgressView';
                params['action'] = 'ActionAjax';
                params['mode'] = 'ChangeProgressView';
                params['record'] = app.getRecordId();
                params['moduleSelected'] = app.getModuleName();
                params['fieldName'] = fieldName;
                params['fieldLabel'] = fieldLabel;
                params['newValue'] = newValue;
                var needConfirm = $(this).data('confirm');
                if (needConfirm > 0) {
                    var confMessage = jQuery('.vdProgressViewHeaderContainer').data('confmessage');
                    app.helper.showConfirmationBox({'message' : confMessage}).then(function(e){
                            thisInstance.doChange(params);
                        },
                        function(error,err){});
                } else {
                    thisInstance.doChange(params);
                }

            }
        });
    },
    doChange: function(params) {
        app.helper.showProgress(app.vtranslate('JS_CHANGING_STATUS'));
        app.request.post({data:params}).then(
            function(err,data) {
                if(err == null && data!=""){
                    app.helper.hideProgress();
                    // this_li.closest('div.vdProgressViewHeaderContainer').find('.vdProgressViewHeaderEmpty').removeClass("vdProgressView-Active");
                    // this_li.find('.vdProgressViewHeaderEmpty').addClass("vdProgressView-Active");
                    app.helper.showSuccessNotification({
                        message : data.fieldLabel+ app.vtranslate('JS_STATUS_UPDATED')+ data.value
                    });
                    location.reload();
                    // $('li.active').trigger('click');
                }
            },
            function(error) {
            }
        );
    },
    registerNextClick:function(){
        $(".detailViewContainer").on("click",".progressNext",function () {
            var leftPos = $('.slider-wrap').scrollLeft();
            $(".slider-wrap").animate({
                scrollLeft: leftPos + 200
            }, 'fast');
        });
        $(".detailViewContainer").on("click",".progressPrev",function () {
            var leftPos = $('.slider-wrap').scrollLeft();
            $(".slider-wrap").animate({
                scrollLeft: leftPos - 200
            }, 'fast');
        });
    },
    registerEvents: function(){
        this.registerShowOnDetailView();
        this.registerProgressViewHeaderClick();
        this.registerNextClick();
    }
});

jQuery(document).ready(function () {
	
    var moduleName = app.getModuleName();
    var viewName = app.getViewName();
    if(viewName == 'Detail'){
        var instance = new VDProgressView_Js();
        instance.registerEvents();
    }
});
