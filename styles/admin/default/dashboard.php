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
                <a href="<?php echo site_url('admin/dashboard') ?>"><i class="fa-home"></i> <?php echo lang('global_home') ?></a>
            </li>
            <li class="active">
                <strong><?php echo lang('global_dashboard') ?></strong>
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
        <h3 class="panel-title"><?php echo lang('global_statics') ?></h3>
        <div class="panel-options">
        </div>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-3">
                <div class="xe-widget xe-progress-counter xe-progress-counter-primary"  data-count=".num" data-from="0" data-to="<?php echo $visitors ?>" data-suffix="" data-duration="3">
                    <div class="xe-background">
                        <i class="fa fa-eye"></i>
                    </div>
                    <div class="xe-upper">
                        <div class="xe-icon">
                            <i class="fa fa-eye"></i>
                        </div>
                        <div class="xe-label">
                            <span> <?php echo lang('global_views') ?></span>
                            <strong class="num"><?php echo $visitors ?></strong>
                        </div>
                    </div>
                    <div class="xe-progress">
                        <span class="xe-progress-fill"  data-fill-from="0" data-fill-to="82" data-fill-unit="%" data-fill-property="width" data-fill-duration="3" data-fill-easing="true"></span>
                    </div>
                    <div class="xe-lower">
                        <span><?php echo lang('global_total') ?> <?php echo lang('global_views') ?> </span>
                    </div>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="xe-widget xe-progress-counter xe-progress-counter-pink" data-count=".num" data-from="0" data-to="<?php echo $services ?>" data-duration="2">
                    <div class="xe-background">
                        <i class="fa fa-magic"></i>
                    </div>
                    <div class="xe-upper">
                        <div class="xe-icon">
                            <i class="fa fa-magic"></i>
                        </div>
                        <div class="xe-label">
                            <span><?php echo lang('global_services') ?></span>
                            <strong class="num"><?php echo $services ?></strong>
                        </div>
                    </div>
                    <div class="xe-progress">
                        <span class="xe-progress-fill"  data-fill-from="0" data-fill-to="56" data-fill-unit="%" data-fill-property="width" data-fill-duration="2" data-fill-easing="true"></span>
                    </div>
                    <div class="xe-lower">
                        <span><?php echo lang('global_total') ?> <?php echo lang('global_services') ?></span>
                    </div>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="xe-widget xe-progress-counter xe-progress-counter-turquoise"  data-count=".num" data-from="0" data-to="<?php echo $clients ?>" data-suffix="" data-duration="3">
                    <div class="xe-background">
                        <i class="fa fa-briefcase" aria-hidden="true"></i>
                    </div>
                    <div class="xe-upper">
                        <div class="xe-icon">
                            <i class="fa fa-briefcase" aria-hidden="true"></i>
                        </div>
                        <div class="xe-label">
                            <span> <?php echo lang('global_clients') ?></span>
                            <strong class="num"><?php echo $clients ?></strong>
                        </div>
                    </div>
                    <div class="xe-progress">
                        <span class="xe-progress-fill"  data-fill-from="0" data-fill-to="82" data-fill-unit="%" data-fill-property="width" data-fill-duration="3" data-fill-easing="true"></span>
                    </div>

                    <div class="xe-lower">
                        <span><?php echo lang('global_total') ?> <?php echo lang('global_clients') ?></span>
                    </div>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="xe-widget xe-progress-counter xe-counter-block-success"  data-count=".num" data-from="0" data-to="<?php echo $projects ?>" data-suffix="" data-duration="3">
                    <div class="xe-background">
                        <i class="fa fa-tags" aria-hidden="true"></i>
                    </div>
                    <div class="xe-upper">
                        <div class="xe-icon">
                            <i class="fa fa-tags" aria-hidden="true"></i>
                        </div>
                        <div class="xe-label">
                            <span> <?php echo lang('global_projects') ?></span>
                            <strong class="num"><?php echo $projects ?></strong>
                        </div>
                    </div>

                    <div class="xe-progress">
                        <span class="xe-progress-fill"  data-fill-from="0" data-fill-to="82" data-fill-unit="%" data-fill-property="width" data-fill-duration="3" data-fill-easing="true"></span>
                    </div>

                    <div class="xe-lower">
                        <span><?php echo lang('global_total') ?> <?php echo lang('global_projects') ?></span>
                    </div>

                </div>
            </div>

            <div class="col-sm-3">
                <div class="xe-widget xe-progress-counter xe-counter-block-purple"  data-count=".num" data-from="0" data-to="<?php echo $blog ?>" data-suffix="" data-duration="3">
                    <div class="xe-background">
                        <i class="fa fa-newspaper-o" aria-hidden="true"></i>
                    </div>
                    <div class="xe-upper">
                        <div class="xe-icon">
                            <i class="fa fa-newspaper-o" aria-hidden="true"></i>
                        </div>
                        <div class="xe-label">
                            <span> <?php echo lang('global_blog_posts') ?></span>
                            <strong class="num"><?php echo $blog ?></strong>
                        </div>
                    </div>
                    <div class="xe-progress">
                        <span class="xe-progress-fill"  data-fill-from="0" data-fill-to="82" data-fill-unit="%" data-fill-property="width" data-fill-duration="3" data-fill-easing="true"></span>
                    </div>
                    <div class="xe-lower">
                        <span><?php echo lang('global_total') ?> <?php echo lang('global_blog_posts') ?></span>
                    </div>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="xe-widget xe-progress-counter xe-counter-block-blue"  data-count=".num" data-from="0" data-to="<?php echo $testimonials ?>" data-suffix="" data-duration="3">
                    <div class="xe-background">
                        <i class="fa fa-comments-o" aria-hidden="true"></i>
                    </div>
                    <div class="xe-upper">
                        <div class="xe-icon">
                            <i class="fa fa-comments-o" aria-hidden="true"></i>
                        </div>
                        <div class="xe-label">
                            <span> <?php echo lang('global_testimonials') ?></span>
                            <strong class="num"><?php echo $testimonials ?></strong>
                        </div>
                    </div>
                    <div class="xe-progress">
                        <span class="xe-progress-fill"  data-fill-from="0" data-fill-to="82" data-fill-unit="%" data-fill-property="width" data-fill-duration="3" data-fill-easing="true"></span>
                    </div>
                    <div class="xe-lower">
                        <span><?php echo lang('global_total') ?> <?php echo lang('global_testimonials') ?></span>
                    </div>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="xe-widget xe-progress-counter xe-counter-block-red"  data-count=".num" data-from="0" data-to="<?php echo $skills ?>" data-suffix="" data-duration="3">
                    <div class="xe-background">
                        <i class="fa fa-lightbulb-o" aria-hidden="true"></i>
                    </div>
                    <div class="xe-upper">
                        <div class="xe-icon">
                            <i class="fa fa-lightbulb-o" aria-hidden="true"></i>
                        </div>
                        <div class="xe-label">
                            <span> <?php echo lang('global_skills') ?></span>
                            <strong class="num"><?php echo $skills ?></strong>
                        </div>
                    </div>
                    <div class="xe-progress">
                        <span class="xe-progress-fill"  data-fill-from="0" data-fill-to="82" data-fill-unit="%" data-fill-property="width" data-fill-duration="3" data-fill-easing="true"></span>
                    </div>
                    <div class="xe-lower">
                        <span><?php echo lang('global_total') ?> <?php echo lang('global_skills') ?></span>
                    </div>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="xe-widget xe-progress-counter xe-counter-block-orange"  data-count=".num" data-from="0" data-to="<?php echo $messages ?>" data-suffix="" data-duration="3">
                    <div class="xe-background">
                        <i class="fa fa-envelope" aria-hidden="true"></i>
                    </div>
                    <div class="xe-upper">
                        <div class="xe-icon">
                            <i class="fa fa-envelope" aria-hidden="true"></i>
                        </div>
                        <div class="xe-label">
                            <span> <?php echo lang('global_messages') ?></span>
                            <strong class="num"><?php echo $messages ?></strong>
                        </div>
                    </div>
                    <div class="xe-progress">
                        <span class="xe-progress-fill"  data-fill-from="0" data-fill-to="82" data-fill-unit="%" data-fill-property="width" data-fill-duration="3" data-fill-easing="true"></span>
                    </div>
                    <div class="xe-lower">
                        <span><?php echo lang('global_total') ?> <?php echo lang('global_messages') ?></span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="<?php echo STYLE_JS ?>/widgets.js"></script>
<script type="text/javascript">
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
                        '<a href="javascript:location.reload();" class="btn btn-xs btn-white"><i class="fa fa-refresh"></i> Refresh Halaman</a>' +
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