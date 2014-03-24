var form = $("#object_form");
form.submit(function(event) {
    event.preventDefault();

    var href = $(this).data('href');
    $.ajax({
        type: 'PUT',
        contentType: 'application/json',
        url: href,
        dataType: "json",
        data: formToJSON($(this)),
        success: function(data) {
            if (data.success) {
                alert(data.message);
                window.location.replace(href);
            } else {
                alert("Could not update this entry. See console log for details");
                console.log(data.message);
            };
        },
        error: function(data) {
            alert('Errors occured during your request.');
        }
    });
});

$("#delete-entry").click(function(event) {
    var proceed = confirm("Are you sure you want to delete this entry?");
    var href = $(this).data('href');
    event.preventDefault();
    if (proceed) {
        $.ajax({
            type: 'DELETE',
            url: href,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    console.log(data.href);
                    window.location.replace(data.href);
                } else {
                    alert("Could not delete this entry. See console log for details");
                    console.log(data.message);
                };
            },
            error: function(data) {
                alert('Errors occured during your request.');
            }
        });
    } else {
        // do
    };
});

$("#button-link").click(function(event) {
    var href = $(this).data('href');
    var type = $(this).data('action');
    event.preventDefault();
    $.ajax({
        type: type,
        url: href,
        dataType: 'json',
        success: function(data) {
            if (data.success)
                alert(data.message);
            else
                alert("Could not reset password: " + data.message);
        },
        error: function(data) {
            console.log(data);
        }
    })
});