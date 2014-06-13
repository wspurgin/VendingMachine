var form = $("#add_many_form");
form.submit(function(event) {
    event.preventDefault();
    console.log(manyFormToJSON($(this)));
    var href = $(this).data('api-href');
    var reload = $(this).data('href')
    $.ajax({
        type: 'POST',
        contentType: 'application/json',
        url: href,
        dataType: "json",
        data: manyFormToJSON($(this)),
        success: function(data) {
            if (data.success) {
                window.location.replace(reload);
            } else {
                alert("Could not create this entry. See console log for details");
                console.log(data.message);
            };
        },
        error: function(data) {
            alert('Errors occured during your request.');
            console.log(data);
        }
    });
});

// binds deleting handler to buttons
$("button").click(function(event) {
    /* 
     * Currently each one_to_many that needs to update additional fields must
     * implement its own update_entry handler, as it is too specific for a
     * clean, general solution.
     */
    //check if button is a form action button
    var check = $(this).data('type');
    if (check === undefined || check != 'delete_entry') {
        return;
    };
    event.preventDefault();
    var api_href = $(this).data('api-href');
    var href = $(this).data('href');
    // since the action is deleting, verify action.
    var proceed = confirm("Are you sure you want to delete this entry?");
    if (proceed) {
        var data = {};
        data['id'] = $(this).parent().parent().data('id');
        console.log(data);
        $.ajax({
            type: 'DELETE',
            url: api_href,
            contentType: 'application/json',
            data: JSON.stringify(data),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    window.location.replace(href);
                } else {
                    alert("Could not delete this entry. See console log for details");
                    console.log(data.message);
                    console.log(data);
                };
            },
            error: function(data) {
                alert('Errors occured during your request.');
            }
        });
    };
});

function manyFormToJSON(form) {
    // assmues that the form is using select multiples
    var inputs = {};
    form.children().children('select, input').each(function() {
        inputs[$(this).attr('id')] = $(this).val();
    });
    return JSON.stringify(inputs);
}