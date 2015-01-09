/**
 * @source: https://github.com/New-Bamboo/example-ajax-upload
 */

// Function that will allow us to know if Ajax uploads are supported
function supportAjaxUploadWithProgress() {
    return supportFileAPI() && supportAjaxUploadProgressEvents() && supportFormData();
    // Is the File API supported?
    function supportFileAPI() {
        var fi = document.createElement('INPUT');
        fi.type = 'file';
        return 'files' in fi;
    };
    // Are progress events supported?
    function supportAjaxUploadProgressEvents() {
        var xhr = new XMLHttpRequest();
        return !! (xhr && ('upload' in xhr) && ('onprogress' in xhr.upload));
    };
    // Is FormData supported?
    function supportFormData() {
        return !! window.FormData;
    }
}
// Actually confirm support
if (supportAjaxUploadWithProgress()) {
    // Ajax uploads are supported!
    // Change the support message and enable the upload button
    var notice = document.getElementById('support-notice');
    var uploadBtn = document.getElementById('upload-button-id');
    notice.innerHTML = "Your browser supports HTML uploads. Go try me! :-)";
    uploadBtn.removeAttribute('disabled');
    // Init the Ajax form submission
    initFullFormAjaxUpload();
    // Init the single-field file upload
    initFileOnlyAjaxUpload();
}
function initFullFormAjaxUpload() {
    var form = document.getElementById('form-id');
    form.onsubmit = function() {
        // FormData receives the whole form
        var formData = new FormData(form);
        // We send the data where the form wanted
        var action = form.getAttribute('action');
        // Code common to both variants
        sendXHRequest(formData, action);
        // Avoid normal form submission
        return false;
    }
}
function initFileOnlyAjaxUpload() {
    var uploadBtn = document.getElementById('upload-button-id');
    uploadBtn.onclick = function (evt) {
        var formData = new FormData();
        // Since this is the file only, we send it to a specific location
        var action = '/convert';
        // FormData only has the file
        var fileInput = document.getElementById('file-id');
        var file = fileInput.files[0];
        formData.append('source', file);
        // Code common to both variants
        sendXHRequest(formData, action);
    }
}
// Once the FormData instance is ready and we know
// where to send the data, the code is the same
// for both variants of this technique
function sendXHRequest(formData, uri) {
    // Get an XMLHttpRequest instance
    var xhr = new XMLHttpRequest();
    // Set up events
    xhr.upload.addEventListener('loadstart', onloadstartHandler, false);
    xhr.upload.addEventListener('progress', onprogressHandler, false);
    xhr.upload.addEventListener('load', onloadHandler, false);
    xhr.addEventListener('readystatechange', onreadystatechangeHandler, false);
    // Set up request
    xhr.open('POST', uri, true);
    // Fire!
    xhr.send(formData);
}
// Handle the start of the transmission
function onloadstartHandler(evt) {
    var div = document.getElementById('upload-status');
    div.innerHTML = 'Upload started.';
}
// Handle the end of the transmission
function onloadHandler(evt) {
    var div = document.getElementById('upload-status');
    div.innerHTML += '<' + 'br>File uploaded. Waiting for response.';
}
// Handle the progress
function onprogressHandler(evt) {
    var div = document.getElementById('progress');
    var percent = evt.loaded/evt.total*100;
    div.innerHTML = 'Progress: ' + percent + '%';
}
// Handle the response from the server
function onreadystatechangeHandler(evt) {
    var status, text, readyState;
    try {
        readyState = evt.target.readyState;
        text = evt.target.responseText;
        status = evt.target.status;
    }
    catch(e) {
        return;
    }
    if (readyState == 4 && status == '200' && evt.target.responseText) {
        var status = document.getElementById('upload-status');
        var result = document.getElementById('result');

        var data = JSON.parse(evt.target.responseText);
        if (data.errorCode != 0) {
            status.innerHTML = 'Error!';
            result.innerHTML = '<div>Error</div><div class="output">' + evt.target.responseText + '</div>';
            return;
        }

        status.innerHTML = 'Success!';
        result.innerHTML = '<label> Base64: <textarea class="output">'+data.file+'</textarea></label>';
        updateList();
    }
}

function updateList() {
    $.ajax({
        url: "/list",
        context: document.body
    }).done(function(list, state, res) {
        if (state != 'success')
          return console.log(state, res);
        if (!list.length)
          return;

        var html = '<table><thead><tr><td>id</td><td>original_name</td><td>original_size</td><td>original_type</td><td>current_name</td><td>current_path</td><td>validation</td><td>from_ip</td><td>created_at</td></tr></thead><tbody>';
        for(var i=0; i<list.length; i++) {
            var item = list[i];
            html += '<tr><td>'+item.id+'</td><td>'+item.original_name+'</td><td>'+item.original_size+'</td><td>'+item.original_type+'</td><td>'+item.current_name+'</td><td>'+item.current_path+'</td><td><a href="#_" class="validate" data-current-name="'+item.current_name+'">valid</a></td><td>'+item.from_ip+'</td><td>'+item.created_at+'</td></tr>';
        }

        html += '</tbody></table>';
        $('#list-container').empty();
        $('#list-container').html(html);
    });
}

$(document).on('ready', function() {
    $("#list-container").delegate("a.validate", "click", function(event) {
        event.preventDefault();
        event.stopPropagation();
        var current_name = $(event.currentTarget).data('currentName');
        console.log('validate '+current_name);
        $(event.currentTarget).closest('td').html('wait');
        $.ajax({
            url: "/validate/"+current_name,
            context: document.body
        }).done(function(data, state, res) {
            if (state != 'success')
                return console.log(state, res);
            console.log(data.validation.result);
            var html;
            if (data.validation.result) {
                html = 'ok';
            } else {
                if (!data.validation.length)
                    html = 'not exists';
                else
                    html = 'not valid';
            }
            $(event.currentTarget).closest('td').html(html);
        });
    });

    // load list
    updateList();
});