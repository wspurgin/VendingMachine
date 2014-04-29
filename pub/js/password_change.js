$("#change_form").submit(function(event) {
    event.preventDefault();
    var old_password = $("#old_password");
    var new_password = $("#new_password");
    var confirm_password = $("#confirm_password");
    if (confirm_password.val() != new_password.val()) {
        alert("New passwords didn't match!");
        confirm_password.focus();
    } else {
        var href = $(this).data('href');
        console.log(href);
        $.ajax({
            type: 'PATCH',
            url: href,
            dataType: 'json',
            data: formToJSON($(this)),
            success: function(data) {
                if (data.success) {
                    alert("Password changed successfully!");
                } else {
                    alert(data.message);
                    console.log(data.message);
                };
            },
            error: function(data) {
                var res = data.responseJSON;
                if (res === undefined) {
                    alert("Errors occured during your request");
                } else {
                    alert(res.message);
                }
            }
        });
    };
});