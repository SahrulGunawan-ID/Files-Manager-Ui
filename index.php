<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pro File Manager Mobile</title>
    <link rel='stylesheet' href='assets/semantic.min.css'>
    <style>
        :root { --bg: #1b1c1d; --text: #ffffff; --seg: #252627; --border: #3d3e3f; }
        body.light-mode { --bg: #f4f4f4; --text: #1b1c1d; --seg: #ffffff; --border: #d4d4d5; }
        
        body { background-color: var(--bg); color: var(--text); padding: 10px; transition: 0.3s; }
        .ui.text.container { width: 100% !important; max-width: 700px !important; margin: 0 auto !important; }
        .ui.segment { background: var(--seg) !important; color: var(--text) !important; border: 1px solid var(--border) !important; border-radius: 10px !important; }
        
        /* Upload Zone */
        #fileInput { display: none; }
        #fileNameDisplay { display: block; margin: 5px 0; font-size: 11px; color: #00e676; min-height: 15px; }

        /* File List Item - Fix Sejajar */
        .file-item { 
            display: flex !important; 
            align-items: center !important; 
            justify-content: space-between !important;
            padding: 12px 5px !important; 
            border-bottom: 1px solid var(--border);
        }
        .file-item:last-child { border-bottom: none; }
        
        .file-info { flex: 1; margin: 0 10px; min-width: 0; }
        .file-info .header { 
            display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; 
            color: var(--text); font-weight: bold; font-size: 0.95em; 
        }


.file-info .header { 
    display: block; 
    white-space: nowrap; 
    overflow: hidden; 
    text-overflow: ellipsis; 
    color: #ffffff !important; /* Memaksa warna teks menjadi putih */
    font-weight: bold; 
    font-size: 0.95em; 
}



        /* Tombol Aksi Sejajar Horizontal */
        .file-item .actions { display: flex; gap: 5px; align-items: center; }
        
        .disk-info { font-weight: bold; color: #00e676; font-size: 1.1rem; margin-bottom: 10px; }
        .bulk-actions { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; }
        .inverted-text { color: var(--text) !important; }
    </style>
</head>
<body class="dark-mode">

<div class="ui text container">
    <div class="ui grid middle aligned" style="margin-bottom: 5px;">
        <div class="ten wide column">
            <div class="disk-info"><i class="database icon"></i> <span id="totalDiskUsage">0.00 MB</span></div>
        </div>
        <div class="six wide column right aligned">
            <button class="ui circular icon button grey" id="themeSwitcher"><i class="moon icon"></i></button>
        </div>
    </div>

    <div class="ui segment">
        <div class="ui action input fluid">
            <input type="text" id="fakePath" placeholder="KETUK PILIH FILES" readonly onclick="$('#fileInput').click()">
            <button class="ui teal icon button" onclick="$('#fileInput').click()"><i class="search icon"></i></button>
            <button class="ui blue icon button" id="btnUploadAction" disabled><i class="upload icon"></i> Upload</button>
        </div>
        <input type="file" id="fileInput">
        <small id="fileNameDisplay"></small>

        <div id="progressContainer" style="display:none; margin-top: 10px;">
            <div class="ui progress success tiny" id="uploadProgress"><div class="bar"></div></div>
            <div id="statsText" style="font-size:10px; text-align: center; color: #888; margin-top: -10px;"></div>
        </div>

        <div class="ui divider"></div>

        <div class="bulk-actions" id="bulkControls" style="display:none;">
            <div class="ui checkbox">
                <input type="checkbox" id="selectAll">
                <label class="inverted-text">Pilih Semua</label>
            </div>
            <button class="ui red mini button" id="btnDeleteSelected"><i class="trash icon"></i> Hapus</button>
        </div>

        <div id="fileListContainer" class="ui list">
            <div class="item inverted-text">Memuat file...</div>
        </div>
    </div>
</div>

<script src='assets/jquery-3.6.0.min.js'></script>
<script src='assets/semantic.min.js'></script>

<script>
$(document).ready(function() {
    let startTime;

    // Switch Tema
    $('#themeSwitcher').click(function() {
        $('body').toggleClass('light-mode');
        $(this).find('i').toggleClass('moon sun');
    });

    // Handle Pilih File
    $('#fileInput').change(function() {
        let file = this.files[0];
        if (file) {
            $('#fakePath').val(file.name);
            $('#fileNameDisplay').text("Ukuran: " + (file.size/1024/1024).toFixed(2) + " MB");
            $('#btnUploadAction').prop('disabled', false);
        }
    });

    // Action Upload
    $('#btnUploadAction').click(function() {
        let file = $('#fileInput').prop('files')[0];
        if(!file) return;

        let formData = new FormData();
        formData.append('file', file);
        
        $(this).addClass('loading disabled');
        $('#progressContainer').show();
        startTime = new Date().getTime();

        $.ajax({
            url: 'file_action.php',
            type: 'POST',
            data: formData,
            processData: false, contentType: false,
            xhr: function() {
                let xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        let pc = Math.round((evt.loaded / evt.total) * 100);
                        let duration = (new Date().getTime() - startTime) / 1000;
                        let speed = (evt.loaded / duration / 1024).toFixed(0);
                        $('#uploadProgress').progress({percent: pc});
                        $('#statsText').text(`${speed} KB/s | ${pc}% | ${(evt.loaded/1048576).toFixed(1)}MB / ${(evt.total/1048576).toFixed(1)}MB`);
                        
                        let pg = $('#uploadProgress');
                        pg.removeClass('red yellow blue');
                        if(pc <= 30) pg.addClass('red');
                        else if(pc <= 70) pg.addClass('yellow');
                        else pg.addClass('blue');
                    }
                }, false);
                return xhr;
            },
            success: function() {
                $('#btnUploadAction').removeClass('loading disabled').prop('disabled', true);
                $('#progressContainer').hide();
                $('#fakePath').val('');
                $('#fileNameDisplay').text('');
                loadFiles();
                alert('Berhasil di Upload!');
            }
        });
    });

    // Load Data
    function loadFiles() {
        $.getJSON('file_action.php?action=list', function(data) {
            let html = '';
            if(data.length > 0) $('#bulkControls').show(); else $('#bulkControls').hide();
            
            data.forEach(function(file) {
                html += `
                <div class="item file-item">
                    <div class="ui checkbox">
                        <input type="checkbox" class="file-check" value="${file.name}">
                        <label></label>
                    </div>
                    <i class="large file alternate outline icon inverted-text"></i>
                    <div class="file-info">
                        <span class="header">${file.name}</span>
                        <div class="description" style="font-size:10px; color:#888;">${file.size} | ${file.time}</div>
                    </div>
                    <div class="actions">
                        <a href="uploads/${file.name}" download class="ui icon button blue mini"><i class="download icon"></i></a>
                        <button class="ui icon button red mini btn-del" data-name="${file.name}"><i class="trash icon"></i></button>
                    </div>
                </div>`;
            });
            $('#fileListContainer').html(html || '<div class="item inverted-text">Folder Kosong</div>');
            updateDisk();
        });
    }

    function updateDisk() {
        $.getJSON('file_action.php?action=info', d => $('#totalDiskUsage').text(d.total_size));
    }

    // Delete Single
    $(document).on('click', '.btn-del', function() {
        if(confirm('Hapus file ini?')) {
            $.post('file_action.php', { delete: $(this).data('name') }, () => loadFiles());
        }
    });

    // Select All
    $('#selectAll').change(function() {
        $('.file-check').prop('checked', $(this).prop('checked'));
    });

    // Delete Selected
    $('#btnDeleteSelected').click(function() {
        let selected = [];
        $('.file-check:checked').each(function() { selected.push($(this).val()); });
        if(selected.length === 0) return;
        if(confirm('Hapus ' + selected.length + ' file?')) {
            $.post('file_action.php', { delete: selected }, () => {
                $('#selectAll').prop('checked', false);
                loadFiles();
            });
        }
    });

    loadFiles();
});
</script>
</body>
</html>
