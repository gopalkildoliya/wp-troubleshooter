$(function() {
    function processData(data){
        $("#title").html(data.title);
        var panelBody = $(".panel-body");
        panelBody.html('');
        if(data.flash){
            if(data.flash.danger)
                panelBody.append(printAlert('danger', data.flash.danger));
            if(data.flash.info)
                panelBody.append(printAlert('info', data.flash.info));
            if(data.flash.success)
                panelBody.append(printAlert('success', data.flash.success));
        }
        if(data.simpleData){
            panelBody.append(data.simpleData+'<br>');
        }
        if(data.form){
            //panelBody.append('<form/>');
            $form = $('<form id="#form" method="post"></form>');
            for (var index = 0; index < data.formData.length; ++index) {
                var field = data.formData[index];
                //panelBody.append('<div class="form-group">');
                if(field.type=="radio")
                {
                    $formElement = $('<div class="radio">');
                    $formElement.append('<label><input type="'+field.type+'" name="'+field.name+'" value="'+field.value+'">'+field.label+'</label>');
                    $form.append($formElement);
                } else {
                    $formElement = $('<div class="form-group">');
                    if(field.label)
                        $formElement.append('<label for="'+field.name+'">'+field.label+'</label>');
                    $formElement.append('<input class="form-control" type="'+field.type+'" name="'+field.name+'" value="'+field.value+'"></div>');
                    $form.append($formElement);
                }
            }
            //panelBody.append('</form>');
            panelBody.append($form);
        }

    }

    function printAlert(type, msg){
        return '<div class="alert alert-'+type+' alert-dismissible" role="alert">'
        +'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
        +msg+'</div>';
    }

    function makerequest(formdata){
        $.post( "", formdata, function(data, status, xhr) {
            processData(data);
        }).fail(function(xhr) {
            if(xhr.status == 401) {
                makerequest({link: "/login"});
            }
        });
    }
    $(".panel-body").on("submit", "form", function(e){
        e.preventDefault();
        var str = $( "form" ).serialize();
        makerequest(str);
    });
    $("#home").on("click", function(e){
        e.preventDefault();
        var str = {link:"/home"};
        makerequest(str);
    });

    function showMyModel(title, data){
        $("#myModalLabel").html(title);
        $(".modal-body").html(data);
        $('#myModal').modal('show');
    }

    $("#quick-search").on("keyup", function(){
        var search = $("#quick-search").val();
        if(search.length<2)
            $("#quick-links").html("");
        else{
            $.post( "", { link: "/quick-search", str : search } )
                .done(function(data){
                    $("#quick-links").html("");
                    for (var index = 0; index < data.length; ++index) {
                        $("#quick-links").append("<li class=\"list-group-item quick-link-item\" id='"+data[index].link
                            +"'>" + data[index].label);
                    }
                });
        }

    });

    $("#quick-links").on("click", ".quick-link-item", function(e){
        makerequest({link : $(this).attr("id") });
        $("#quick-links").html("");
        $("#quick-search").val("");
    });

    makerequest({link:"/home"});


});