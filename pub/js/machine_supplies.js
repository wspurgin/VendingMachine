/**
 * summary: "This segment will bind this particular handle to all buttons with
 * type 'update_entry'"
 */
$("button").click(function(event) {
    var check = $(this).data('type');
    if (check === undefined || check != 'update_entry')
        return;
    event.preventDefault();
    // get necessary hrefs
    var api_href = $(this).data('api-href');
    var href = $(this).data('href');

    // Construct dataset
    var data = {};
    data['product'] = $(this).parent().parent().data('id');
    data['quantity'] = $(this).parent().prev().children().val();
    console.log(data);
    $.ajax({
        type: 'PUT',
        url: api_href,
        contentType: 'application/json',
        data: JSON.stringify(data),
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                window.location.replace(href);
            } else {
                alert("Could not update this entry. See console log for details");
                console.log(data.message);
                console.log(data);
            };
        },
        error: function(data) {
            alert('Errors occured during your request.');
        }
    });
});