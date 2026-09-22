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
            <li>
                <a href="<?php echo site_url('admin/blog') ?>"><?php echo lang('global_blog') ?></a>
            </li>
            <li class="active">
                <strong>Status Indexing Blog</strong>
            </li>
        </ol>
    </div>
</div>

<!-- STATS SUMMARY CARDS -->
<div class="row" style="margin-bottom: 20px;">
    <div class="col-sm-3 col-xs-6">
        <div class="xe-widget xe-counter xe-counter-blue" style="margin-bottom: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
            <div class="xe-icon">
                <i class="fa-file-text-o"></i>
            </div>
            <div class="xe-label">
                <strong class="num" id="stat-total-posts"><?php echo number_format($total_posts); ?></strong>
                <span>Total Artikel Terbit</span>
            </div>
        </div>
    </div>

    <div class="col-sm-3 col-xs-6">
        <div class="xe-widget xe-counter xe-counter-success" style="margin-bottom: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
            <div class="xe-icon">
                <i class="fa-check-circle"></i>
            </div>
            <div class="xe-label">
                <strong class="num" id="stat-indexed-count"><?php echo number_format($indexed_count); ?></strong>
                <span>Sudah Di-index</span>
            </div>
        </div>
    </div>

    <div class="col-sm-3 col-xs-6">
        <div class="xe-widget xe-counter xe-counter-warning" style="margin-bottom: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
            <div class="xe-icon">
                <i class="fa-clock-o"></i>
            </div>
            <div class="xe-label">
                <strong class="num" id="stat-pending-count"><?php echo number_format($pending_count); ?></strong>
                <span>Belum Lengkap / Pending</span>
            </div>
        </div>
    </div>

    <div class="col-sm-3 col-xs-6">
        <div class="xe-widget xe-counter xe-counter-purple" style="margin-bottom: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
            <div class="xe-icon">
                <i class="fa-cloud-upload"></i>
            </div>
            <div class="xe-label">
                <strong class="num" style="font-size: 14px; line-height: 28px;" id="stat-last-indexed"><?php echo $last_indexed_at !== '-' ? date('d M Y H:i', strtotime($last_indexed_at)) : '-'; ?></strong>
                <span>Terakhir Di-index</span>
            </div>
        </div>
    </div>
</div>

<!-- ACTION & ENGINE STATUS PANEL -->
<div class="panel panel-default" style="border-left: 4px solid #00b19d; box-shadow: 0 1px 4px rgba(0,0,0,0.06); margin-bottom: 25px;">
    <div class="panel-body" style="padding: 20px;">
        <div class="row" style="display: flex; align-items: center; flex-wrap: wrap;">
            <div class="col-md-7 col-sm-12">
                <h4 style="margin: 0 0 10px 0; font-size: 16px; font-weight: 700; color: #2c2e2f;">
                    <i class="fa fa-tachometer" style="color: #00b19d; margin-right: 6px;"></i> Mesin Auto-Indexing Aktif
                </h4>
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
                    <span class="badge badge-success" style="font-size: 12px; padding: 5px 12px; border-radius: 12px; background-color: #27ae60;">
                        <i class="fa fa-check"></i> IndexNow (Bing / Yandex / Seznam)
                    </span>
                    <span class="badge badge-success" style="font-size: 12px; padding: 5px 12px; border-radius: 12px; background-color: #2980b9;">
                        <i class="fa fa-google"></i> Google Search Console Sitemap Refresh
                    </span>
                    <span class="badge badge-success" style="font-size: 12px; padding: 5px 12px; border-radius: 12px; background-color: #8e44ad;">
                        <i class="fa fa-bolt"></i> Google Indexing API (Service Account)
                    </span>
                </div>
                <p style="margin: 0; font-size: 12px; color: #666; line-height: 1.6;">
                    Setiap artikel baru otomatis terkirim ke 3 search engine. Anda juga dapat memfilter artikel berdasarkan status engine tertentu di bawah ini dan menjalankannya secara manual per-artikel atau batch otomatis.
                </p>
            </div>
            <div class="col-md-5 col-sm-12 text-right" style="margin-top: 10px;">
                <button type="button" class="btn btn-success btn-icon btn-icon-standalone" onclick="openBatchModal()" style="font-weight: 700; box-shadow: 0 2px 5px rgba(0,0,0,0.15); margin-bottom: 5px;" <?php echo $total_filtered == 0 ? 'disabled' : ''; ?>>
                    <i class="fa-bolt"></i>
                    <span>
                        <?php if ($has_active_filter): ?>
                            ⚡ Index Hasil Filter Ini (<span id="btn-pending-counter"><?php echo $total_filtered; ?></span>)
                        <?php else: ?>
                            ⚡ Index Semua yang Belum (<span id="btn-pending-counter"><?php echo $pending_count; ?></span>)
                        <?php endif; ?>
                    </span>
                </button>
                <a href="<?php echo site_url('admin/blog'); ?>" class="btn btn-white btn-icon btn-icon-standalone" style="margin-bottom: 5px;">
                    <i class="fa-arrow-left"></i>
                    <span>Kembali ke Blog</span>
                </a>
            </div>
        </div>

        <div id="indexing-global-alert" style="display: none; margin-top: 15px;"></div>
    </div>
</div>

<!-- FILTER PANEL DENGAN MULTI DROPDOWN -->
<div class="panel panel-default" style="box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 20px;">
    <div class="panel-body" style="padding: 16px 20px; background-color: #fbfbfd; border-radius: 4px;">
        <form method="GET" action="<?php echo site_url('admin/blog_indexing'); ?>" id="filter-indexing-form">
            <!-- Tabs Status Global -->
            <div style="margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div class="btn-group">
                    <a href="<?php echo site_url('admin/blog_indexing?status=all'); ?>" 
                       class="btn btn-xs <?php echo ($filter_status === 'all' && empty($filter_indexnow) && empty($filter_gsc) && empty($filter_google_api)) ? 'btn-primary active' : 'btn-white'; ?>">
                        Semua Artikel (<?php echo $total_posts; ?>)
                    </a>
                    <a href="<?php echo site_url('admin/blog_indexing?status=pending'); ?>" 
                       class="btn btn-xs <?php echo $filter_status === 'pending' ? 'btn-warning active' : 'btn-white'; ?>">
                        <i class="fa fa-clock-o"></i> Belum Lengkap / Pending (<?php echo $pending_count; ?>)
                    </a>
                    <a href="<?php echo site_url('admin/blog_indexing?status=indexed'); ?>" 
                       class="btn btn-xs <?php echo $filter_status === 'indexed' ? 'btn-success active' : 'btn-white'; ?>">
                        <i class="fa fa-check"></i> Sudah Di-index (<?php echo $indexed_count; ?>)
                    </a>
                </div>

                <?php if ($has_active_filter): ?>
                    <div>
                        <span class="badge badge-info" style="font-size: 11px; padding: 4px 8px; margin-right: 5px;">
                            <i class="fa fa-filter"></i> Filter Aktif (<?php echo $total_filtered; ?> artikel cocok)
                        </span>
                        <a href="<?php echo site_url('admin/blog_indexing'); ?>" class="btn btn-xs btn-white" style="font-weight: 600; color: #c0392b;">
                            <i class="fa fa-times"></i> Reset Semua Filter
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">

            <!-- Baris Dropdown Filter Spesifik Engine -->
            <div class="row">
                <!-- Dropdown 1: IndexNow -->
                <div class="col-md-3 col-sm-6 col-xs-12" style="margin-bottom: 10px;">
                    <label style="font-size: 11px; font-weight: 700; color: #444; text-transform: uppercase; margin-bottom: 4px; display: block;">
                        <i class="fa fa-bolt" style="color: #27ae60;"></i> Filter IndexNow:
                    </label>
                    <select name="indexnow" class="form-control input-sm" onchange="this.form.submit()" style="border-radius: 4px; border-color: #dce1e4;">
                        <option value="all" <?php echo ($filter_indexnow === null || $filter_indexnow === '' || $filter_indexnow === 'all') ? 'selected' : ''; ?>>
                            Semua Status (<?php echo $total_posts; ?>)
                        </option>
                        <option value="1" <?php echo $filter_indexnow === '1' ? 'selected' : ''; ?>>
                            ✓ Sukses (<?php echo (int)$stats_breakdown->in_success; ?>)
                        </option>
                        <option value="0" <?php echo $filter_indexnow === '0' ? 'selected' : ''; ?>>
                            ⏳ Pending (<?php echo (int)$stats_breakdown->in_pending; ?>)
                        </option>
                        <option value="2" <?php echo $filter_indexnow === '2' ? 'selected' : ''; ?>>
                            ✗ Gagal (<?php echo (int)$stats_breakdown->in_failed; ?>)
                        </option>
                    </select>
                </div>

                <!-- Dropdown 2: GSC Sitemap -->
                <div class="col-md-3 col-sm-6 col-xs-12" style="margin-bottom: 10px;">
                    <label style="font-size: 11px; font-weight: 700; color: #444; text-transform: uppercase; margin-bottom: 4px; display: block;">
                        <i class="fa fa-google" style="color: #2980b9;"></i> Filter GSC Sitemap:
                    </label>
                    <select name="gsc" class="form-control input-sm" onchange="this.form.submit()" style="border-radius: 4px; border-color: #dce1e4;">
                        <option value="all" <?php echo ($filter_gsc === null || $filter_gsc === '' || $filter_gsc === 'all') ? 'selected' : ''; ?>>
                            Semua Status (<?php echo $total_posts; ?>)
                        </option>
                        <option value="1" <?php echo $filter_gsc === '1' ? 'selected' : ''; ?>>
                            ✓ Sukses (<?php echo (int)$stats_breakdown->gsc_success; ?>)
                        </option>
                        <option value="0" <?php echo $filter_gsc === '0' ? 'selected' : ''; ?>>
                            ⏳ Pending (<?php echo (int)$stats_breakdown->gsc_pending; ?>)
                        </option>
                        <option value="2" <?php echo $filter_gsc === '2' ? 'selected' : ''; ?>>
                            ✗ Gagal (<?php echo (int)$stats_breakdown->gsc_failed; ?>)
                        </option>
                    </select>
                </div>

                <!-- Dropdown 3: Google Indexing API -->
                <div class="col-md-3 col-sm-6 col-xs-12" style="margin-bottom: 10px;">
                    <label style="font-size: 11px; font-weight: 700; color: #444; text-transform: uppercase; margin-bottom: 4px; display: block;">
                        <i class="fa fa-search" style="color: #8e44ad;"></i> Filter Google API:
                    </label>
                    <select name="google_api" class="form-control input-sm" onchange="this.form.submit()" style="border-radius: 4px; border-color: #dce1e4;">
                        <option value="all" <?php echo ($filter_google_api === null || $filter_google_api === '' || $filter_google_api === 'all') ? 'selected' : ''; ?>>
                            Semua Status (<?php echo $total_posts; ?>)
                        </option>
                        <option value="1" <?php echo $filter_google_api === '1' ? 'selected' : ''; ?>>
                            ✓ Terkirim (<?php echo (int)$stats_breakdown->gapi_success; ?>)
                        </option>
                        <option value="0" <?php echo $filter_google_api === '0' ? 'selected' : ''; ?>>
                            ⏳ Belum Di-index (<?php echo (int)$stats_breakdown->gapi_pending; ?>)
                        </option>
                        <option value="2" <?php echo $filter_google_api === '2' ? 'selected' : ''; ?>>
                            ⚠ Gagal / Butuh Akses (<?php echo (int)$stats_breakdown->gapi_failed; ?>)
                        </option>
                    </select>
                </div>

                <!-- Search box -->
                <div class="col-md-3 col-sm-6 col-xs-12" style="margin-bottom: 10px;">
                    <label style="font-size: 11px; font-weight: 700; color: #444; text-transform: uppercase; margin-bottom: 4px; display: block;">
                        <i class="fa fa-font"></i> Cari Judul Artikel:
                    </label>
                    <div class="input-group">
                        <input type="text" name="q" class="form-control input-sm" placeholder="Ketik kata kunci..." value="<?php echo htmlspecialchars($search_query); ?>" style="border-radius: 4px 0 0 4px; border-color: #dce1e4;">
                        <span class="input-group-btn">
                            <button type="submit" class="btn btn-sm btn-primary" style="border-radius: 0 4px 4px 0;"><i class="fa-search"></i> Cari</button>
                        </span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MAIN TABLE -->
<div class="panel panel-default">
    <div class="panel-heading" style="padding: 12px 20px;">
        <h3 class="panel-title" style="font-weight: 700;">
            <i class="fa fa-list"></i> Daftar Artikel
            <span class="text-muted" style="font-size: 13px; font-weight: normal; margin-left: 8px;">
                (Menampilkan <strong><?php echo count($items); ?></strong> dari <strong><?php echo number_format($total_filtered); ?></strong> artikel cocok)
            </span>
        </h3>
        <div class="panel-options">
            <a href="javascript:location.reload();" class="btn btn-white btn-xs" title="Refresh Halaman">
                <i class="fa fa-refresh"></i> Refresh
            </a>
        </div>
    </div>
    <div class="panel-body" style="padding: 0;">

        <div class="table-responsive">
            <table class="table table-bordered table-hover" style="margin-bottom: 0;">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th style="width: 55px; text-align: center;">ID</th>
                        <th>Judul Artikel</th>
                        <th style="width: 140px; text-align: center;">IndexNow</th>
                        <th style="width: 140px; text-align: center;">GSC Sitemap</th>
                        <th style="width: 150px; text-align: center;">Google API</th>
                        <th style="width: 150px;">Waktu Indexing</th>
                        <th style="width: 130px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="middle-align">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 35px 20px; color: #888;">
                                <i class="fa fa-filter" style="font-size: 32px; display: block; margin-bottom: 10px; color: #bdc3c7;"></i>
                                <strong>Tidak ada artikel yang cocok dengan filter yang dipilih.</strong>
                                <div style="margin-top: 10px;">
                                    <a href="<?php echo site_url('admin/blog_indexing'); ?>" class="btn btn-xs btn-primary">
                                        <i class="fa fa-refresh"></i> Reset Filter
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <?php 
                                $slug = function_exists('sanitize') ? sanitize($item->title) : url_title($item->title, '-', TRUE);
                                $post_url = site_url('post/' . $item->blog_id . '-' . $slug);
                            ?>
                            <tr id="row-blog-<?php echo $item->blog_id; ?>">
                                <td class="text-center" style="font-weight: 600; color: #777;">
                                    #<?php echo $item->blog_id; ?>
                                </td>
                                <td>
                                    <a href="<?php echo $post_url; ?>" target="_blank" style="font-weight: 600; color: #2c2e2f;" title="Buka artikel di tab baru">
                                        <?php echo htmlspecialchars($item->title); ?> 
                                        <i class="fa fa-external-link" style="font-size: 11px; color: #888; margin-left: 3px;"></i>
                                    </a>
                                    <div style="font-size: 11px; color: #888; margin-top: 3px;">
                                        <i class="fa fa-calendar"></i> <?php echo date('d M Y H:i', strtotime($item->datetime)); ?>
                                        <?php if (!empty($item->indexing_log)): ?>
                                            | <span id="log-blog-<?php echo $item->blog_id; ?>" style="color: #666;"><?php echo htmlspecialchars($item->indexing_log); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <!-- IndexNow Status -->
                                <td class="text-center" id="cell-indexnow-<?php echo $item->blog_id; ?>">
                                    <?php if ($item->indexnow_status == 1): ?>
                                        <span class="badge badge-success" style="padding: 4px 8px; background-color: #27ae60;"><i class="fa fa-check"></i> Sukses (200)</span>
                                    <?php elseif ($item->indexnow_status == 2): ?>
                                        <span class="badge badge-danger" style="padding: 4px 8px; background-color: #c0392b;"><i class="fa fa-times"></i> Gagal</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning" style="padding: 4px 8px; background-color: #f39c12;"><i class="fa fa-clock-o"></i> Pending</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Google Sitemap Status -->
                                <td class="text-center" id="cell-gsc-<?php echo $item->blog_id; ?>">
                                    <?php if ($item->gsc_status == 1): ?>
                                        <span class="badge badge-success" style="padding: 4px 8px; background-color: #27ae60;"><i class="fa fa-check"></i> Sukses (204)</span>
                                    <?php elseif ($item->gsc_status == 2): ?>
                                        <span class="badge badge-danger" style="padding: 4px 8px; background-color: #c0392b;"><i class="fa fa-times"></i> Gagal</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning" style="padding: 4px 8px; background-color: #f39c12;"><i class="fa fa-clock-o"></i> Pending</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Google Indexing API Status -->
                                <td class="text-center" id="cell-api-<?php echo $item->blog_id; ?>">
                                    <?php if ($item->google_indexing_status == 1): ?>
                                        <span class="badge badge-success" style="padding: 4px 8px; background-color: #27ae60;"><i class="fa fa-check"></i> Terkirim (200)</span>
                                    <?php elseif ($item->google_indexing_status == 2): ?>
                                        <span class="badge badge-danger" style="padding: 4px 8px; background-color: #c0392b;" title="Gagal / Perlu kirim ulang"><i class="fa fa-times-circle"></i> Gagal / 403</span>
                                    <?php else: ?>
                                        <span class="badge badge-default" style="padding: 4px 8px; background-color: #bdc3c7; color: #555;"><i class="fa fa-minus"></i> Belum</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Last Indexed At -->
                                <td id="cell-time-<?php echo $item->blog_id; ?>" style="font-size: 12px;">
                                    <?php if (!empty($item->last_indexed_at)): ?>
                                        <i class="fa fa-check-circle text-success"></i> <?php echo date('d M Y H:i', strtotime($item->last_indexed_at)); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="text-center">
                                    <button type="button" 
                                            id="btn-index-<?php echo $item->blog_id; ?>" 
                                            class="btn btn-xs <?php echo ($item->gsc_status == 1 && $item->indexnow_status == 1 && $item->google_indexing_status == 1) ? 'btn-white' : 'btn-primary'; ?>" 
                                            onclick="indexSingle(<?php echo $item->blog_id; ?>)" 
                                            style="font-weight: 600;">
                                        <i class="fa-bolt" id="icon-index-<?php echo $item->blog_id; ?>"></i> 
                                        <span id="text-index-<?php echo $item->blog_id; ?>"><?php echo ($item->gsc_status == 1 && $item->indexnow_status == 1 && $item->google_indexing_status == 1) ? 'Re-Index' : 'Index'; ?></span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($pagination)): ?>
            <div style="padding: 15px 20px; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div style="font-size: 13px; color: #777;">
                    Menampilkan total <strong><?php echo number_format($total_filtered); ?></strong> artikel
                </div>
                <div>
                    <?php echo $pagination; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- MODAL BATCH INDEXING OTOMATIS -->
<div class="modal fade" id="modal-batch-indexing" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #00b19d 0%, #009688 100%); color: #fff; border-top-left-radius: 5px; border-top-right-radius: 5px;">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true" style="color: #fff; opacity: 0.8;">&times;</button>
                <h4 class="modal-title" style="font-weight: 700; color: #fff;">
                    <i class="fa fa-bolt"></i> Batch Auto-Indexing
                </h4>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <div id="batch-intro-box">
                    <p style="font-size: 14px; line-height: 1.6; color: #333;">
                        <?php if ($has_active_filter): ?>
                            Fitur ini akan memproses seluruh artikel yang <strong>sesuai dengan filter aktif saat ini</strong> secara bertahap (10 artikel per-batch) ke:
                        <?php else: ?>
                            Fitur ini akan secara otomatis memproses seluruh artikel yang berstatus <strong>Pending / Belum Lengkap</strong> ke:
                        <?php endif; ?>
                    </p>
                    <ul style="color: #555; line-height: 1.8; margin-bottom: 20px;">
                        <li><i class="fa fa-check text-success"></i> <strong>IndexNow API</strong> (Bing, Yandex, Seznam, Naver)</li>
                        <li><i class="fa fa-check text-success"></i> <strong>Google Search Console Sitemaps API</strong> (Refresh Sitemap)</li>
                        <li><i class="fa fa-check text-success"></i> <strong>Google Web Search Indexing API</strong> (Instant URL Submission)</li>
                    </ul>
                    <div class="alert alert-info" style="font-size: 13px;">
                        <i class="fa fa-info-circle"></i> Target yang akan diproses: 
                        <strong id="modal-pending-count"><?php echo $has_active_filter ? $total_filtered : $pending_count; ?></strong> artikel.
                    </div>
                </div>

                <!-- Progress Box (Hidden at start) -->
                <div id="batch-progress-box" style="display: none;">
                    <div style="margin-bottom: 10px; display: flex; justify-content: space-between; font-weight: 600;">
                        <span id="batch-status-text">Memproses batch...</span>
                        <span id="batch-percentage-text">0%</span>
                    </div>
                    <div class="progress progress-striped active" style="height: 22px; border-radius: 11px; margin-bottom: 15px;">
                        <div class="progress-bar progress-bar-success" id="batch-progress-bar" role="progressbar" style="width: 0%; font-size: 12px; line-height: 22px; font-weight: bold;">
                            0%
                        </div>
                    </div>
                    <div id="batch-log-box" style="max-height: 150px; overflow-y: auto; background: #f8f9fa; border: 1px solid #eee; border-radius: 6px; padding: 10px; font-size: 12px; font-family: monospace; color: #444;">
                        Menunggu proses dimulai...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-white" data-dismiss="modal" id="btn-batch-close">Tutup</button>
                <button type="button" class="btn btn-success" id="btn-start-batch" onclick="startBatchIndexing()" style="font-weight: 700;">
                    <i class="fa fa-play"></i> Mulai Auto-Indexing Sekarang
                </button>
                <button type="button" class="btn btn-primary" id="btn-batch-reload" onclick="location.reload()" style="display: none; font-weight: 700;">
                    <i class="fa fa-refresh"></i> Selesai & Refresh Halaman
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var totalToProcess = <?php echo (int)($has_active_filter ? $total_filtered : $pending_count); ?>;
    var totalProcessed = 0;
    var isBatchRunning = false;

    // Filter states
    var filterParams = {
        status: '<?php echo addslashes($filter_status); ?>',
        indexnow: '<?php echo addslashes($filter_indexnow !== null ? $filter_indexnow : ''); ?>',
        gsc: '<?php echo addslashes($filter_gsc !== null ? $filter_gsc : ''); ?>',
        google_api: '<?php echo addslashes($filter_google_api !== null ? $filter_google_api : ''); ?>',
        q: '<?php echo addslashes($search_query); ?>'
    };

    // Trigger Single Indexing via AJAX
    function indexSingle(blogId) {
        var btn = $('#btn-index-' + blogId);
        var icon = $('#icon-index-' + blogId);
        var text = $('#text-index-' + blogId);

        btn.prop('disabled', true);
        icon.removeClass('fa-bolt').addClass('fa-spinner fa-spin');
        text.text('Mengirim...');

        $.ajax({
            url: '<?php echo site_url("admin/blog_indexing/ajax_index_single"); ?>',
            type: 'POST',
            dataType: 'json',
            data: { blog_id: blogId },
            success: function(res) {
                btn.prop('disabled', false);
                icon.removeClass('fa-spinner fa-spin').addClass('fa-bolt');
                text.text('Re-Index');
                btn.removeClass('btn-primary').addClass('btn-white');

                if (res && res.success) {
                    var d = res.data;
                    // Update IndexNow Cell
                    if (d.indexnow_status === 1) {
                        $('#cell-indexnow-' + blogId).html('<span class="badge badge-success" style="padding: 4px 8px; background-color: #27ae60;"><i class="fa fa-check"></i> Sukses (200)</span>');
                    } else if (d.indexnow_status === 2) {
                        $('#cell-indexnow-' + blogId).html('<span class="badge badge-danger" style="padding: 4px 8px; background-color: #c0392b;"><i class="fa fa-times"></i> Gagal</span>');
                    }

                    // Update GSC Cell
                    if (d.gsc_status === 1) {
                        $('#cell-gsc-' + blogId).html('<span class="badge badge-success" style="padding: 4px 8px; background-color: #27ae60;"><i class="fa fa-check"></i> Sukses (204)</span>');
                    } else if (d.gsc_status === 2) {
                        $('#cell-gsc-' + blogId).html('<span class="badge badge-danger" style="padding: 4px 8px; background-color: #c0392b;"><i class="fa fa-times"></i> Gagal</span>');
                    }

                    // Update Google API Cell
                    if (d.google_indexing_status === 1) {
                        $('#cell-api-' + blogId).html('<span class="badge badge-success" style="padding: 4px 8px; background-color: #27ae60;"><i class="fa fa-check"></i> Terkirim (200)</span>');
                    } else if (d.google_indexing_status === 2) {
                        $('#cell-api-' + blogId).html('<span class="badge badge-danger" style="padding: 4px 8px; background-color: #c0392b;"><i class="fa fa-times-circle"></i> Gagal / 403</span>');
                    }

                    // Update Time Cell
                    $('#cell-time-' + blogId).html('<i class="fa fa-check-circle text-success"></i> Baru saja');

                    // Update Log
                    if (d.indexing_log) {
                        $('#log-blog-' + blogId).text(d.indexing_log);
                    }

                    showGlobalAlert('success', '<i class="fa fa-check-circle"></i> ' + res.message);
                } else {
                    showGlobalAlert('danger', '<i class="fa fa-exclamation-circle"></i> ' + (res.message || 'Gagal melakukan indexing artikel.'));
                }
            },
            error: function(xhr, status, err) {
                btn.prop('disabled', false);
                icon.removeClass('fa-spinner fa-spin').addClass('fa-bolt');
                text.text('Index');
                showGlobalAlert('danger', '<i class="fa fa-exclamation-circle"></i> Terjadi kesalahan koneksi server: ' + err);
            }
        });
    }

    function openBatchModal() {
        $('#modal-batch-indexing').modal('show');
    }

    function startBatchIndexing() {
        if (isBatchRunning) return;
        isBatchRunning = true;

        $('#btn-start-batch').prop('disabled', true).hide();
        $('#btn-batch-close').prop('disabled', true);
        $('#batch-intro-box').slideUp();
        $('#batch-progress-box').slideDown();

        totalProcessed = 0;
        processNextBatch();
    }

    function processNextBatch() {
        var postData = $.extend({ limit: 10 }, filterParams);

        $.ajax({
            url: '<?php echo site_url("admin/blog_indexing/ajax_index_batch"); ?>',
            type: 'POST',
            dataType: 'json',
            data: postData,
            success: function(res) {
                if (res && res.success) {
                    if (res.processed > 0) {
                        totalProcessed += res.processed;
                        var pct = totalToProcess > 0 ? Math.min(100, Math.round((totalProcessed / totalToProcess) * 100)) : 100;

                        $('#batch-progress-bar').css('width', pct + '%').text(pct + '%');
                        $('#batch-percentage-text').text(pct + '%');
                        $('#batch-status-text').text('Memproses... (' + totalProcessed + ' selesai)');

                        $('#batch-log-box').append('<div><span class="text-success"><i class="fa fa-check"></i></span> Sukses batch ' + res.processed + ' artikel. Sisa antrean: ' + res.remaining_count + '</div>');
                        $('#batch-log-box').scrollTop($('#batch-log-box')[0].scrollHeight);

                        if (res.remaining_count > 0) {
                            // Lanjut batch berikutnya
                            setTimeout(processNextBatch, 600);
                        } else {
                            // Selesai seluruhnya
                            finishBatch(true, 'Semua artikel yang dipilih berhasil di-index!');
                        }
                    } else {
                        // Tidak ada yang diproses lagi
                        finishBatch(true, 'Tidak ada antrean pending lagi.');
                    }
                } else {
                    finishBatch(false, res.message || 'Gagal memproses batch.');
                }
            },
            error: function(xhr, status, err) {
                finishBatch(false, 'Terjadi error koneksi: ' + err);
            }
        });
    }

    function finishBatch(success, msg) {
        isBatchRunning = false;
        $('#batch-progress-bar').removeClass('progress-bar-striped active');
        if (success) {
            $('#batch-progress-bar').css('width', '100%').text('100% Selesai');
            $('#batch-status-text').html('<strong class="text-success"><i class="fa fa-check-circle"></i> Selesai!</strong>');
            $('#batch-log-box').append('<div style="font-weight: bold; color: #27ae60; margin-top: 5px;">🎉 ' + msg + '</div>');
        } else {
            $('#batch-status-text').html('<strong class="text-danger"><i class="fa fa-times-circle"></i> ' + msg + '</strong>');
            $('#batch-log-box').append('<div style="font-weight: bold; color: #c0392b; margin-top: 5px;">⚠️ ' + msg + '</div>');
        }
        $('#batch-log-box').scrollTop($('#batch-log-box')[0].scrollHeight);

        $('#btn-batch-close').prop('disabled', false);
        $('#btn-batch-reload').show();
    }

    function showGlobalAlert(type, html) {
        var alertClass = 'alert-' + type;
        $('#indexing-global-alert')
            .removeClass('alert-success alert-danger alert-warning alert-info')
            .addClass('alert ' + alertClass)
            .html(html)
            .slideDown();

        setTimeout(function() {
            $('#indexing-global-alert').slideUp();
        }, 6000);
    }
</script>
