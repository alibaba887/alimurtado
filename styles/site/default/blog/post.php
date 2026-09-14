<!-- JSON-LD Structured Data for AI & Search Engines -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": <?php echo json_encode($item->title, JSON_UNESCAPED_UNICODE); ?>,
  "description": <?php echo json_encode($item->meta_description ?: $item->short_description, JSON_UNESCAPED_UNICODE); ?>,
  "image": "<?php echo base_url() ?>cdn/blog/<?php echo $item->image ?>",
  "datePublished": "<?php echo date('c', strtotime($item->datetime)) ?>",
  "dateModified": "<?php echo date('c', strtotime($item->datetime)) ?>",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "<?php echo current_url() ?>"
  },
  "author": {
    "@type": "Person",
    "name": "<?php echo !empty($item->author) ? addslashes($item->author) : config('name') ?>",
    "jobTitle": "Digital Advertiser",
    "url": "<?php echo base_url() ?>"
  },
  "publisher": {
    "@type": "Person",
    "name": "<?php echo config('name') ?>",
    "url": "<?php echo base_url() ?>"
  },
  "articleSection": "<?php echo addslashes($item->category) ?>",
  "keywords": "<?php echo addslashes($item->meta_keywords) ?>"
}
</script>

<div class="content-pages">
    <!-- Subpages -->
    <div class="sub-home-pages">
        <!-- Titlebar -->
        <div id="titlebar">
            <div class="container">
                <div class="special-block-bg">
                    <h2><?php echo lang('global_Blog') ?></h2>
                    <!-- Breadcrumbs -->
                    <nav id="breadcrumbs">
                        <ul>
                            <li><a href="<?php echo site_url() ?>"><i class="pe-7s-home"></i> <?php echo lang('global_home') ?></a></li>
                            <li><a href="<?php echo site_url('blog') ?>"><?php echo lang('global_Blog') ?></a></li>
                            <li><a href="<?php echo site_url('blog/category/' . $item->blog_category_id . '-' . sanitize($item->category)) ?>"><?php echo $item->category ?></a></li>
                            <li><?php echo $item->title ?></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
        <!-- /Titlebar -->

        <!-- Content -->
        <div class="container">
            <!-- Blog Posts -->
            <div class="blog-page">
                <div class="row">
                    <!-- Post Content -->
                    <div class="col-lg-8 col-md-8">
                        <!-- Blog Post -->
                        <div class="blog-post single-post">
                            <!-- Img -->
                            <img class="post-img img-fluid" src="<?php echo base_url() ?>cdn/blog/<?php echo $item->image ?>" alt="<?php echo $item->title ?>"loading="lazy">
                            <!-- Content -->
                            <div class="post-content">
                                <h3><?php echo $item->title ?></h3>
                                <ul class="post-meta">
                                    <li><a href="#"><i class="lnr lnr-user"></i> <?php echo $item->author ?></a></li>
                                    <li><i class="lnr lnr-history"></i> <?php echo date('M', strtotime($item->datetime)) ?> <?php echo date('d, Y', strtotime($item->datetime)) ?></li>
                                    <li><a href="#"><i class="lnr lnr-book"></i> <?php echo $item->category ?></a></li>
                                    <li><a href="#"><i class="lnr lnr-eye"></i> <?php echo $item->visits ?></a></li>
                                </ul>
                                <div class="post-description">
                                    <?php echo $item->description ?>
                                </div>
                                <!-- Share Buttons -->
                                <ul class="share-buttons pt-30">
                                    <li><a class="fb-share" href="http://www.facebook.com/share.php?u=<?php echo urlencode(current_url()) ?>&title=<?php echo urlencode(config('title')) ?>" target="_blank"><i class="fab fa-facebook-f"></i> Share</a></li>
                                    <li><a class="twitter-share" href="http://twitter.com/intent/tweet?status=<?php echo urlencode(config('title')) ?>+<?php echo urlencode(current_url()) ?>" target="_blank"><i class="fab fa-twitter"></i> Tweet</a></li>
                                    <li><a class="pinterest-share" href="https://pinterest.com/pin/create/bookmarklet/?media=<?php echo urlencode($item->image) ?>&url=<?php echo urlencode(current_url()) ?>&description=<?php echo urlencode(config('title')) ?>" target="_blank"><i class="fab fa-pinterest-p"></i> Pin</a></li>
                                </ul>
                                <?php if (config('display_disqus') == 1): ?>
                                    <?php if (config('disqus_username')): ?>
                                        <?php if (config('blog_comments_widget') == 1): ?>
                                            <div id="add-review" class="add-review-box">
                                                <!-- Add Review -->
                                                <h3 class="listing-desc-headline margin-bottom-35"><?php echo lang('global_Add_comment') ?></h3>
                                                <div id="disqus_thread"></div>
                                                <script>
                                                    (function () {
                                                        var d = document, s = d.createElement('script');
                                                        s.src = 'https://<?php echo config('disqus_username') ?>.disqus.com/embed.js';
                                                        s.setAttribute('data-timestamp', +new Date());
                                                        (d.head || d.body).appendChild(s);
                                                    })();
                                                </script>
                                            </div>
                                        <?php endif ?>
                                    <?php endif ?>
                                <?php endif ?>
                            </div>
                        </div>

                        <!-- Blog Post / End -->
<!--Banner-->
<style>
    .ban_sec {
  width: 100%;
  display:block;
}
.ban_img {
  width: 100%;
  position: relative;
}
.ban_img img {
  width: 100%;
}
.ban_text {
  position: absolute;
  top: 50%;
  left: 6%;
  -ms-transform: translateY(-50%);
  -webkit-transform: translateY(-50%);
  -moz-transform: translateY(-50%);
  -o-transform: translateY(-50%);
  transform: translateY(-50%);
}
.ban_text strong {
  font: 800 62.22px/70px "Montserrat", sans-serif;
  color: #fff;
  text-transform: uppercase;
}
.ban_text strong span {
  font: 400 44.44px/52px "Montserrat", sans-serif;
  letter-spacing: 3px;
}
.ban_text p {
  font: 400 25px/30px "Montserrat", sans-serif;
  color: #fff;
  margin: 7px 0 25px;
}
.ban_text a {
  display: inline-block;
  font: 800 19.39px/24px "Montserrat", sans-serif;
  background: #282828;
  border-radius: 26px;
  color: #fff;
  padding: 12px 28px;
  -moz-transition: all 0.3s ease-in-out;
  -o-transition: all 0.3s ease-in-out;
  -webkit-transition: all 0.3s ease-in-out;
  -ms-transition: all 0.3s ease-in-out;
  transition: all 0.3s ease-in-out;
  text-decoration:none;
}
.ban_text a:hover {
  background: #50af47;
}

@media (min-width: 1200px) and (max-width: 1399px) {
  .ban_text p {
    font-size: 21px;
  }
}

@media (min-width: 992px) and (max-width: 1199px) {
  .ban_text p {
    font-size: 17px;
  }
  .ban_text strong {
    font-size: 50px;
    line-height: 60px;
  }
  .ban_text strong span {
    font-size: 37px;
  }
  .ban_text a {
    font-size: 16px;
    line-height: 19px;
  }
}

@media only screen and (max-width: 991px) {
  .ban_text strong {
    font-size: 35px;
    line-height: 40px;
  }
  .ban_text strong span {
    font-size: 28px;
    line-height: 35px;
    letter-spacing: 2px;
  }
  .ban_text p {
    font-size: 14px;
    line-height: 20px;
  }
  .ban_text a {
    font-size: 13.39px;
    line-height: 15px;
  }
}
@media only screen and (max-width: 767px) {
  .ban_img img {
    min-height: 290px;
    object-fit: cover;
  }
}
@media only screen and (max-width: 575px) {
  .ban_text strong {
    background: rgba(0, 0, 0, 0.8);
    padding: 10px;
    width: 100%;
    display: block;
  }
}
@media only screen and (max-width: 480px) {
  .ban_text strong span {
    font-size: 22px;
    line-height: 31px;
    letter-spacing: 1px;
  }
  .ban_text {
    left: 2%;
  }
}

</style>
<section class="ban_sec">
		<div class="container">
			<div class="ban_img">
	<img src="http://alimurtado.com/cdn/blog/ctablog.webp" alt="banner" border="0">
				<div class="ban_text">
					<strong>
					</strong>
					<p></p>
					<a href="https://impacta.id/?utm_source=personal-brand&utm_medium=mas-ali&utm_campaign=web-alimurtado.com&utm_term=blog-alimurtado.com">Selengkapnya</a>
				</div>
			</div>
		</div>
	</section>

<!--Akahir banner-->
                        <div class="margin-top-50"></div>
                    </div>
                    <!-- Content / End -->
                    <!-- Widgets -->
                    <div class="col-lg-4 col-md-4">
                        <div class="sidebar right">
                            <!-- Widget -->
                            <?php if (config('post_search_widget') == 1): ?>
                                <div class="widget search-widget">
                                    <h4><?php echo lang('global_Search_In_Blog') ?></h4>
                                    <div class="search-blog-input">
                                        <form action="<?php echo site_url('blog') ?>">
                                            <div class="input">
                                                <input class="search-field" type="search"name="q" value="<?php echo set_value('q') ?>" placeholder="<?php echo lang('global_search_posts') ?> .." />
                                                <button class="btn" type="submit"><i class="fa fa-search"></i></button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endif ?>
                            <!-- Widget / End -->

                            <!-- Widget -->
                            <?php if (config('post_related_widget') == 1): ?>
                                <?php if ($related_items): ?>
                                    <div class="widget">
                                        <h4 class="widget-title"><?php echo lang('global_Related_Posts') ?></h4>
                                        <ul class="widget-tabs">
                                            <?php foreach ($related_items as $pop): ?>
                                                <li>
                                                    <div class="widget-content">
                                                        <div class="widget-thumb">
                                                            <a href="<?php echo site_url('post/' . $pop->blog_id . '-' . sanitize($pop->title)) ?>"><img src="<?php echo base_url() ?>cdn/blog/<?php echo $pop->image ?>" alt="<?php echo $pop->title ?>"></a>
                                                        </div>
                                                        <div class="widget-text">
                                                            <h5>
                                                                <a href="<?php echo site_url('post/' . $pop->blog_id . '-' . sanitize($pop->title)) ?>">
                                                                    <?php $this->load->helper('text') ?>
                                                                    <?php echo character_limiter($pop->title, 20) ?> 
                                                                </a>
                                                            </h5>
                                                            <span><?php echo date('M', strtotime($pop->datetime)) ?></i> <?php echo date('d, Y', strtotime($pop->datetime)) ?></span>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach ?>
                                        </ul>
                                    </div>
                                <?php endif ?>
                            <?php endif ?>
                            <!-- Widget / End-->

                            <!-- Widget -->
                            <?php if (config('blog_categories_widget') == 1): ?>
                                <div class="widget categories">
                                    <h4 class="widget-title"><?php echo lang('global_All_Categories') ?></h4>
                                    <ul class="categories-list">
                                        <?php foreach ($categories as $cat): ?>
                                            <li>
                                                <a href="<?php echo site_url('blog/category/' . $cat->blog_category_id . '-' . sanitize($cat->title)) ?>">
                                                    <i class="lni-dinner"></i>
                                                    <?php echo $cat->title ?> <span class="category-counter">(<?php echo $cat->posts ?>)</span>
                                                </a>
                                            </li>
                                        <?php endforeach ?>
                                    </ul>
                                </div>
                            <?php endif ?>
                            <!-- Widget / End-->
                            <!-- Widget -->
                            <?php if (config('post_latest_widget') == 1): ?>
                                <?php if ($latest_added): ?>
                                    <div class="widget">
                                        <h4 class="widget-title"><?php echo lang('global_Latest_Posts') ?></h4>
                                        <ul class="widget-tabs">
                                            <?php foreach ($latest_added as $latest): ?>
                                                <li>
                                                    <div class="widget-content">
                                                        <div class="widget-thumb">
                                                            <a href="<?php echo site_url('post/' . $latest->blog_id . '-' . sanitize($latest->title)) ?>"><img src="<?php echo base_url() ?>cdn/blog/<?php echo $latest->image ?>" alt="<?php echo $latest->title ?>"></a>
                                                        </div>
                                                        <div class="widget-text">
                                                            <h5>
                                                                <a href="<?php echo site_url('post/' . $latest->blog_id . '-' . sanitize($latest->title)) ?>">
                                                                    <?php $this->load->helper('text') ?>
                                                                    <?php echo character_limiter($latest->title, 20) ?> 
                                                                </a>
                                                            </h5>
                                                            <span><?php echo date('M', strtotime($latest->datetime)) ?></i> <?php echo date('d, Y', strtotime($latest->datetime)) ?></span>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach ?>
                                        </ul>
                                    </div>
                                <?php endif ?>
                            <?php endif ?>
                            <!-- Widget / End-->
                            <!-- Widget -->
                            <?php if (config('post_tags_widget') == 1): ?>
                                <div class="widget tag">
                                    <h4 class="widget-title">Tag Cloud</h4>
                                    <div class="tagcloud">
                                        <?php foreach (explode(',', $item->meta_keywords) as $tag): ?>
                                            <a href="<?php echo site_url('blog?q=' . urlencode($tag)) ?>"><?php echo $tag ?></a>
                                        <?php endforeach ?>
                                    </div>
                                </div>
                            <?php endif ?>
                            <!-- Widget / End-->
                        </div>
                    </div>
                </div>
                <!-- Sidebar / End -->
            </div>
        </div>
    </div>
</div>