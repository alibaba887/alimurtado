<div class="page-title">
    <div class="breadcrumb-env">
        <ul class="user-info-menu left-links list-inline list-unstyled">
            <li class="hidden-sm hidden-xs">
                <a href="#" data-toggle="sidebar">
                    <i class="fa-bars"></i>
                </a>
            </li>
        </ul>
        <ol class="breadcrumb bc-1" >
            <li>
                <a href="<?php echo site_url('admin/dashboard') ?>"><i class="fa-home"></i><?php echo lang('global_home') ?></a>
            </li>
            <li class="active">
                <strong> <?php echo lang('global_blog') ?></strong>
            </li>
        </ol>

    </div>

</div>

<!-- Auto-Publish Cron Widget -->
<div class="panel panel-default" style="border-left: 4px solid #667eea; box-shadow: 0 1px 4px rgba(0,0,0,0.06); margin-bottom: 25px;">
    <div class="panel-body" style="padding: 18px 22px;">
        <div class="row" style="display: flex; align-items: center; flex-wrap: wrap;">
            <div class="col-md-7 col-sm-12">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; flex-wrap: wrap;">
                    <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #2c2e2f;">
                        <i class="fa fa-clock-o" style="color: #667eea; margin-right: 4px;"></i> Auto-Publish Cron Job
                    </h4>
                    <span id="cron-status-badge" class="badge <?php echo $cron_status == '1' ? 'badge-success' : 'badge-danger'; ?>" style="font-size: 12px; padding: 4px 10px; border-radius: 12px; font-weight: 600; <?php echo $cron_status == '1' ? 'background-color: #27ae60;' : 'background-color: #c0392b;'; ?>">
                        <i class="fa <?php echo $cron_status == '1' ? 'fa-check-circle' : 'fa-power-off'; ?>"></i> <span id="cron-status-text"><?php echo $cron_status == '1' ? 'AKTIF (ON)' : 'NONAKTIF (OFF)'; ?></span>
                    </span>
                    <span class="badge" style="font-size: 12px; padding: 4px 10px; border-radius: 12px; background-color: #2980b9; color: #fff; font-weight: 600;">
                        <i class="fa fa-database"></i> <span id="queue-count-badge"><?php echo number_format($queue_count); ?></span> Antrean
                    </span>
                </div>
                <div style="font-size: 12px; color: #666; line-height: 1.6;">
                    <span>Jadwal Otomatis: <strong style="color: #2c3e50;"><i class="fa fa-calendar"></i> Setiap 6 Jam sekali</strong> (4 artikel / hari)</span>
                    <br>
                    <span>Terakhir diproses: <strong id="cron-last-run-val"><?php echo !empty($cron_last_run) ? $cron_last_run : 'Belum pernah'; ?></strong></span>
                    <span id="cron-last-title-container" style="<?php echo empty($cron_last_title) || $cron_last_title == '-' ? 'display:none;' : ''; ?>"> | Judul: "<strong id="cron-last-title-val"><?php echo htmlspecialchars(mb_substr($cron_last_title, 0, 50)) . (mb_strlen($cron_last_title) > 50 ? '...' : ''); ?></strong>"</span>
                </div>
            </div>
            <div class="col-md-5 col-sm-12 text-right" style="margin-top: 10px;">
                <button type="button" id="btn-toggle-cron" class="btn <?php echo $cron_status == '1' ? 'btn-danger' : 'btn-success'; ?> btn-sm" onclick="toggleCronStatus()" style="font-weight: 600; min-width: 165px; margin-right: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <i class="fa <?php echo $cron_status == '1' ? 'fa-power-off' : 'fa-play'; ?>" id="btn-toggle-icon"></i>
                    <span id="btn-toggle-text"><?php echo $cron_status == '1' ? 'Matikan Auto-Publish' : 'Nyalakan Auto-Publish'; ?></span>
                </button>
                <button type="button" id="btn-manual-queue" class="btn btn-blue btn-sm" onclick="processQueueManual()" style="font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" <?php echo $queue_count == 0 ? 'disabled' : ''; ?>>
                    <i class="fa fa-bolt" id="btn-manual-icon"></i>
                    <span id="btn-manual-text">⚡ Proses 1 Sekarang</span>
                </button>
            </div>
        </div>

        <!-- Alert Notification Box for Cron Action -->
        <div id="cron-alert-box" style="display: none; margin-top: 15px;"></div>
    </div>
</div>

<!-- Admin Table-->
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"> <?php echo lang('global_blog') ?></h3>
        <div class="panel-options">
            <button type="button" class="btn btn-purple btn-sm" data-toggle="modal" data-target="#modal-ai-generator" style="background-color: #7c38bc; border-color: #7c38bc; color: #fff; font-weight: bold; margin-right: 5px;">
                <i class="fa fa-magic"></i> ✨ Buat Artikel dengan AI
            </button>
            <a href="<?php echo site_url('admin/blog/manage'); ?>" class="btn btn-secondary btn-sm"><i class="fa fa-plus-square" aria-hidden="true"></i>  <?php echo lang('global_add_new_record') ?></a>
        </div>
    </div>
    <div class="panel-body">

        <table class="table table-bordered table-striped" id="datatable_">
            <thead>
                <tr>
                    <th> <?php echo lang('global_title') ?></th>
                    <th> <?php echo lang('global_visits') ?></th>
                    <th> <?php echo lang('global_created') ?></th>
                    <th><?php echo lang('global_operations') ?></th>
                </tr>
            </thead>

            <tbody class="middle-align">
                <?php foreach ($items as $item): ?>
                    <?php 
                        $slug = function_exists('sanitize') ? sanitize($item->title) : url_title($item->title, '-', TRUE);
                        $post_url = site_url('post/' . $item->blog_id . '-' . $slug) . '?preview=1';
                    ?>
                    <tr id="<?php echo $item->blog_id ?>">
                        <td>
                            <a href="<?php echo $post_url ?>" target="_blank" style="font-weight: 600; color: #2c2e2f;" title="Buka artikel di tab baru">
                                <?php echo $item->title ?> <i class="fa fa-external-link" style="font-size: 11px; color: #888; margin-left: 3px;"></i>
                            </a>
                            <?php if (isset($item->display) && $item->display === '0'): ?>
                                <span class="badge badge-warning" style="font-size: 10px; margin-left: 5px; padding: 2px 6px;">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $item->visits ?></td>
                        <td><?php echo $item->datetime ?></td>

                        <td>
                            <a href="<?php echo $post_url; ?>" target="_blank" class="btn btn-info btn-sm" title="Preview / Lihat Artikel" style="background-color: #00b19d; border-color: #00b19d; color: #fff;">
                                <i class="fa fa-eye" aria-hidden="true"></i> Preview
                            </a>

                            <a href="<?php echo site_url('admin/blog/manage/' . $item->blog_id); ?>" class="btn btn-orange btn-sm">
                                <i class="fa fa-pencil" aria-hidden="true"></i>
                                <?php echo lang('global_edit') ?>
                            </a>

                            <a class="btn btn-danger btn-sm remove">
                                <i class="fa fa-trash" aria-hidden="true"></i>
                                <?php echo lang('global_delete') ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <?php echo $pagination ?>
    </div>
</div>

<!-- Modal AI Generator -->
<div class="modal fade" id="modal-ai-generator" tabindex="-1" role="dialog" aria-labelledby="aiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 4px 4px 0 0;">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true" style="color: white; opacity: 0.9;">&times;</button>
                <h4 class="modal-title" id="aiModalLabel" style="color: white; font-weight: 700;">
                    <i class="fa fa-magic"></i> AI Blog Auto-Generator (Powered by Gemini)
                </h4>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <div class="alert alert-info" style="background-color: #e8f4fd; border-color: #b8daf7; color: #1e5799;">
                    <i class="fa fa-info-circle"></i> <strong>Format Acuan Otomatis:</strong> AI akan otomatis meniru gaya dan format artikel acuan terbaik Anda (seperti post #538), lengkap dengan <strong>Heading H2/H3, Tabel Perbandingan, Bullet Points, dan Cover Gambar</strong>.
                </div>

                <form id="form-ai-generator">
                    <div class="form-group">
                        <label for="ai_topic"><strong>Topik atau Ide Artikel (Opsional):</strong></label>
                        <input type="text" class="form-control" id="ai_topic" name="topic" placeholder="Contoh: Strategi Mengatasi Iklan Meta yang Boncos / Scale Up Budget (Kosongkan jika ingin AI memilih topik tren otomatis)...">
                        <span class="help-block" style="color: #888; font-size: 12px;">Kosongkan jika Anda ingin Gemini menganalisis database dan memilih topik baru yang belum pernah Anda tulis.</span>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ai_category"><strong>Kategori:</strong></label>
                                <select class="form-control" id="ai_category" name="category_id">
                                    <option value="0">-- Otomatis Dipilihkan AI --</option>
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat->blog_category_id ?>"><?php echo $cat->title ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ai_display"><strong>Status Penerbitan:</strong></label>
                                <select class="form-control" id="ai_display" name="display">
                                    <option value="1">🚀 Langsung Terbitkan (Live Publik)</option>
                                    <option value="0">📝 Simpan Sebagai Draft</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Box -->
                    <div id="ai-loading-box" style="display: none; margin-top: 20px; text-align: center; padding: 25px; background: #f9f9f9; border-radius: 6px; border: 1px dashed #764ba2;">
                        <i class="fa fa-spinner fa-spin fa-3x fa-fw" style="color: #764ba2;"></i>
                        <h4 id="ai-loading-status" style="margin-top: 15px; font-weight: 600; color: #333;">Gemini sedang meriset topik & menyusun artikel...</h4>
                        <p style="color: #777; font-size: 13px;">Membutuhkan waktu sekitar 15-30 detik untuk menghasilkan artikel berkualitas tinggi.</p>
                    </div>

                    <!-- Success Result Box -->
                    <div id="ai-result-box" style="display: none; margin-top: 20px; padding: 20px; background: #eafaf1; border: 1px solid #2ecc71; border-radius: 6px;">
                        <h4 style="color: #27ae60; font-weight: 700; margin-top: 0;"><i class="fa fa-check-circle"></i> Artikel Berhasil Dibuat!</h4>
                        <p id="ai-result-title" style="font-size: 16px; font-weight: 600; color: #2c3e50;"></p>
                        <div style="margin-top: 15px;">
                            <a id="btn-view-post" href="#" target="_blank" class="btn btn-success btn-sm"><i class="fa fa-external-link"></i> Lihat Artikel Live</a>
                            <a id="btn-edit-post" href="#" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i> Edit di Admin</a>
                            <button type="button" class="btn btn-default btn-sm" onclick="resetAIGenerator()"><i class="fa fa-plus"></i> Buat Artikel Lain</button>
                        </div>
                    </div>

                    <!-- Error Alert Box -->
                    <div id="ai-error-box" class="alert alert-danger" style="display: none; margin-top: 20px;">
                        <i class="fa fa-exclamation-triangle"></i> <span id="ai-error-message"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" id="btn-ai-close">Tutup</button>
                <button type="button" class="btn btn-purple" id="btn-start-ai" onclick="submitAIGenerator()" style="background-color: #7c38bc; border-color: #7c38bc; color: #fff; font-weight: bold;">
                    <i class="fa fa-bolt"></i> Mulai Generate Artikel
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(".remove").click(function () {
        var id = $(this).parents("tr").attr("id");
        if (confirm('<?php echo lang('global_Are_you_sure_to_remove_this_record') ?>'))
        {
            $.ajax({
                url: '<?php echo site_url() ?>admin/blog/delete/' + id,
                type: 'DELETE',
                error: function () {
                    alert('<?php echo lang('global_Something_is_wrong') ?>');
                },
                success: function (data) {
                    $("#" + id).remove();
                    alert("<?php echo lang('global_Record_removed_successfully') ?>");
                }
            });
        }
    });

    function submitAIGenerator() {
        var topic = $('#ai_topic').val();
        var category_id = $('#ai_category').val();
        var display = $('#ai_display').val();

        $('#btn-start-ai').prop('disabled', true);
        $('#btn-ai-close').prop('disabled', true);
        $('#ai-result-box').hide();
        $('#ai-error-box').hide();
        $('#ai-loading-box').show();
        $('#ai-loading-status').text('Gemini sedang meriset topik & menyusun artikel lengkap...');

        setTimeout(function() {
            if ($('#ai-loading-box').is(':visible')) {
                $('#ai-loading-status').text('Menulis konten dengan format acuan (Heading, Tabel & SEO)...');
            }
        }, 6000);

        setTimeout(function() {
            if ($('#ai-loading-box').is(':visible')) {
                $('#ai-loading-status').text('Mengunduh foto cover berkualitas tinggi & mempublikasikan...');
            }
        }, 14000);

        $.ajax({
            url: '<?php echo site_url("admin/blog/generate_ai"); ?>',
            type: 'POST',
            data: {
                topic: topic,
                category_id: category_id,
                display: display
            },
            dataType: 'json',
            success: function(res) {
                $('#ai-loading-box').hide();
                $('#btn-start-ai').prop('disabled', false);
                $('#btn-ai-close').prop('disabled', false);

                if (res && res.success) {
                    $('#ai-result-title').text(res.data.title);
                    $('#btn-view-post').attr('href', res.data.post_url);
                    $('#btn-edit-post').attr('href', res.data.edit_url);
                    $('#ai-result-box').fadeIn();
                    $('#btn-start-ai').hide();
                } else {
                    $('#ai-error-message').text((res && res.message) ? res.message : 'Terjadi kesalahan saat membuat artikel.');
                    $('#ai-error-box').fadeIn();
                }
            },
            error: function(xhr, status, error) {
                $('#ai-loading-box').hide();
                $('#btn-start-ai').prop('disabled', false);
                $('#btn-ai-close').prop('disabled', false);
                $('#ai-error-message').text('Gagal menghubungi server. Status: ' + status + ' (' + error + ')');
                $('#ai-error-box').fadeIn();
            }
        });
    }

    function resetAIGenerator() {
        $('#ai_topic').val('');
        $('#ai-result-box').hide();
        $('#ai-error-box').hide();
        $('#btn-start-ai').show().prop('disabled', false);
    }

    function toggleCronStatus() {
        var btn = $('#btn-toggle-cron');
        btn.prop('disabled', true);

        $.ajax({
            url: '<?php echo site_url("admin/blog/toggle_cron"); ?>',
            type: 'POST',
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false);
                if (res && res.success) {
                    if (res.status === '1') {
                        $('#cron-status-badge')
                            .css('background-color', '#27ae60')
                            .html('<i class="fa fa-check-circle"></i> <span id="cron-status-text">AKTIF (ON)</span>');
                        btn.removeClass('btn-success').addClass('btn-danger');
                        $('#btn-toggle-icon').removeClass('fa-play').addClass('fa-power-off');
                        $('#btn-toggle-text').text('Matikan Auto-Publish');
                        showCronAlert('success', '<i class="fa fa-check"></i> ' + res.message);
                    } else {
                        $('#cron-status-badge')
                            .css('background-color', '#c0392b')
                            .html('<i class="fa fa-power-off"></i> <span id="cron-status-text">NONAKTIF (OFF)</span>');
                        btn.removeClass('btn-danger').addClass('btn-success');
                        $('#btn-toggle-icon').removeClass('fa-power-off').addClass('fa-play');
                        $('#btn-toggle-text').text('Nyalakan Auto-Publish');
                        showCronAlert('warning', '<i class="fa fa-info-circle"></i> ' + res.message);
                    }
                    if (res.queue_count !== undefined) {
                        $('#queue-count-badge').text(res.queue_count.toLocaleString());
                    }
                } else {
                    showCronAlert('danger', 'Gagal mengubah status cron.');
                }
            },
            error: function() {
                btn.prop('disabled', false);
                showCronAlert('danger', 'Gagal menghubungi server untuk mengubah status cron.');
            }
        });
    }

    function processQueueManual() {
        var btn = $('#btn-manual-queue');
        btn.prop('disabled', true);
        $('#btn-manual-icon').removeClass('fa-bolt').addClass('fa-spin fa-spinner');
        $('#btn-manual-text').text('Memproses AI...');
        showCronAlert('info', '<i class="fa fa-spinner fa-spin"></i> Sedang mengambil 1 antrean dari database dan menghasilkan artikel via Gemini AI... Mohon tunggu ~15-30 detik.');

        $.ajax({
            url: '<?php echo site_url("admin/blog/process_queue_manual"); ?>',
            type: 'POST',
            dataType: 'json',
            timeout: 150000,
            success: function(res) {
                btn.prop('disabled', false);
                $('#btn-manual-icon').removeClass('fa-spin fa-spinner').addClass('fa-bolt');
                $('#btn-manual-text').text('⚡ Proses 1 Sekarang');

                if (res && res.success) {
                    $('#queue-count-badge').text(res.data.remaining_queue.toLocaleString());
                    $('#cron-last-run-val').text(res.data.last_run);
                    $('#cron-last-title-val').text(res.data.title.substring(0, 50) + (res.data.title.length > 50 ? '...' : ''));
                    $('#cron-last-title-container').show();

                    var alertHtml = '<div class="alert alert-success" style="margin-bottom: 0;">' +
                        '<strong><i class="fa fa-check-circle"></i> ' + res.message + '</strong><br>' +
                        'Judul: <em>"' + res.data.title + '"</em><br>' +
                        '<div style="margin-top: 8px;">' +
                        '<a href="' + res.data.post_url + '" target="_blank" class="btn btn-xs btn-white"><i class="fa fa-external-link"></i> Lihat Artikel</a> ' +
                        '<a href="javascript:location.reload();" class="btn btn-xs btn-white"><i class="fa fa-refresh"></i> Refresh Tabel</a>' +
                        '</div></div>';
                    $('#cron-alert-box').html(alertHtml).slideDown();

                    if (res.data.remaining_queue === 0) {
                        btn.prop('disabled', true);
                    }
                } else {
                    showCronAlert('danger', '<i class="fa fa-exclamation-triangle"></i> ' + (res.message || 'Gagal memproses antrean.'));
                }
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false);
                $('#btn-manual-icon').removeClass('fa-spin fa-spinner').addClass('fa-bolt');
                $('#btn-manual-text').text('⚡ Proses 1 Sekarang');
                showCronAlert('danger', '<i class="fa fa-exclamation-triangle"></i> Terjadi kesalahan saat memproses antrean: ' + error);
            }
        });
    }

    function showCronAlert(type, message) {
        var alertClass = 'alert-' + type;
        var html = '<div class="alert ' + alertClass + '" style="margin-bottom: 0;">' + message + '</div>';
        $('#cron-alert-box').html(html).slideDown();
        if (type !== 'info') {
            setTimeout(function() {
                $('#cron-alert-box').slideUp();
            }, 8000);
        }
    }
</script>