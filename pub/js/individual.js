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

function formToJSON(form) {
    var inputs = {}
    $.each(form.serializeArray(), function(i, input) {
        inputs[input.name] = input.value;
    });

    return JSON.stringify(inputs);
}