<?php echo '<?xml version="1.0" encoding="UTF-8" ?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?php echo base_url(); ?></loc>
        <lastmod><?php echo date('Y-m-d'); ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo base_url('appointments'); ?></loc>
        <priority>0.8</priority>
    </url>
    <?php foreach ($projects as $item) { ?>
        <url>
            <loc><?php echo site_url('project/' . $item->project_id . '-' . sanitize($item->title)); ?></loc>
            <priority>0.8</priority>
        </url>
    <?php } ?>
    <?php foreach ($posts as $item) { 
        if (isset($item->display) && $item->display === '0') continue;
        $post_date = !empty($item->datetime) ? date('c', strtotime($item->datetime)) : date('c');
    ?>
        <url>
            <loc><?php echo site_url('post/' . $item->blog_id . '-' . sanitize($item->title)); ?></loc>
            <lastmod><?php echo $post_date; ?></lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.8</priority>
        </url>
    <?php } ?>
</urlset>
