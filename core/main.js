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
                $formElement = $('<div class="form-group">');
                if(field.label)
                    $formElement.append('<label for="'+field.name+'">'+field.label+'</label>');
                $formElement.append('<input class="form-control" type="'+field.type+'" name="'+field.name+'" value="'+field.value+'"></div>');

                $form.append($formElement);
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



});