$(function() {
    function processData(data){
        $("#title").html(data.title);
        var formBody = $("#formBody");
        formBody.html('');
        if(data.flash){
            if(data.flash.danger)
                formBody.append(printAlert('danger', data.flash.danger));
            if(data.flash.info)
                formBody.append(printAlert('info', data.flash.info));
            if(data.flash.success)
                formBody.append(printAlert('success', data.flash.success));
        }
        if(data.simpleData){
            $("#simpledata").html("");
            $("#simpledata").append(data.simpleData+'<br>');
        }
        $breadcrumb = $(".breadcrumb");
        $breadcrumb.html("");
        for(var index = 0; index < data.breadcrumb.length; ++index){
            $breadcrumb.append('<li><a id="'+data.breadcrumb[index].link+'">'+data.breadcrumb[index].label);
        }
        $breadcrumb.append('<li class="active">'+data.title);
        if(data.form){
            //formBody.append('<form/>');
            $form = $('<form id="#form" method="post"></form>');
            for (var index = 0; index < data.formData.length; ++index) {
                var field = data.formData[index];
                //formBody.append('<div class="form-group">');
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
            //formBody.append('</form>');
            formBody.append($form);
        }
        if(data.table){
            formBody.append('<table id="dataTable" class="display" style="font-size: 12px;"></table>');
            $('#dataTable').DataTable( {
                data: data.tableData,
                columns: data.tableColumns
            } );
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
    $("#formBody").on("submit", "form", function(e){
        e.preventDefault();
        var str = $( "form" ).serialize();
        makerequest(str);
    });
    $(".breadcrumb").on("click", "a", function(e){
        e.preventDefault();
        makerequest({link : $(this).attr("id") });
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