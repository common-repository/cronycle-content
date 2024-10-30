var pluginUrl = "";
if (WPURLS != null) {
    pluginUrl = WPURLS["plugin_url"];
} else {
    throw new Error("Plugin URL not found.");
}

jQuery(function ($) {

    var CronycleCarousel = function (element) {
        // jQuery main carousel element
        this.$cronycleCarousel = $(element);

        // jQuery parent banner element of carousel
        this.$cronycleBanner = this.$cronycleCarousel.parents('.cronycle-banner');

        // main slick initialization arguments
        this.slickArgs = {
            infinite: false,
            slidesToShow: 1,
            slidesToScroll: 1,
            adaptiveHeight: false,
            variableWidth: true,
        };

        // breakpoint of responsiveness
        this.responsiveBreakpoint = 770;

        // boolean to flag usage of max item height
        this.useMaxItemHeight = false;

        // boolean to flag usage of fixed item width
        this.useFixedItemWidth = true;

        // fixed item width to use if corresponding flag is set
        this.fixedItemWidth = 348;

        // boolean to flag existence of more articles to fetch
        this.haveMoreArticles = true;

        // Function to check and fetch more articles
        this.getAndAppendArticles = function () {
            // return if there are no more articles
            if (!this.haveMoreArticles) return;

            var _ = this;
            var slick = _.$cronycleCarousel.slick('getSlick');

            // fetch more articles using AJAX
            jQuery.ajax({
                url: WPURLS['ajax_url'],
                data: {
                    action: 'getMoreBoardTiles',
                    cronContentSettings: function () {
                        var settings = {};
                        settings['boardId'] = slick.$slider.data('board-id');
                        settings['includeImage'] = slick.$slider.data('include-image');
                        return JSON.stringify(settings);
                    },
                    lastTileId: function () {
                        if (slick.$slider.find('.cronycle-carousel-item-convo').length == 0)
                            return slick.$slider.find('.cronycle-carousel-item').last().data('cronycle-wp-tile-id');
                        else if (slick.$slider.find('.cronycle-carousel-item').length == 0)
                            return slick.$slider.find('.cronycle-carousel-item-convo').last().data('cronycle-wp-tile-id');
                        else
                            return Math.max(slick.$slider.find('.cronycle-carousel-item').last().data('cronycle-wp-tile-id'),
                                slick.$slider.find('.cronycle-carousel-item-convo').last().data('cronycle-wp-tile-id'));
                    },
                    lastGroupId: function () {
                        return slick.$slider.find('.cronycle-carousel-item-group').last().data('cronycle-wp-group-id');
                    }
                },
                dataType: "html",
                async: false,
                cache: false,
                timeout: 10000,
                success: function (response) {
                    // return if response is null and set more article flag to false
                    if (response == null || response == "") {
                        _.haveMoreArticles = false;
                        return;
                    }

                    // debugging statements
                    // console.log("new boards tiles = ", $(response));

                    // format published date from epoch in the response before adding it to document
                    var $response = $(response);
                    $response.find('.cronycle-carousel-item-subtitle span:nth-child(2), \
                    .cronycle-carousel-item-convo-subtitle span:nth-child(2)').formatPublishedDate();
                    response = $response.wrapAll('<div>').parent().html();

                    // add slides to slick
                    if (!slick.unslicked) {
                        _.$cronycleCarousel.slick('slickAdd', response);
                    } else {
                        _.$cronycleCarousel.append(response);
                    }

                    // apply hiding on new story arc items on small resolution
                    _.hideOrShowItemGroupText(_.$cronycleCarousel.slick('getSlick'));
                },
                error: function (e) {
                    console.log("Error: ", e);
                }
            });
        }

        // Function to find the maximum height of item
        this.getMaxItemHeight = function () {
            var maxItemHeight = 0;
            this.slick.$slider.find('.cronycle-carousel-item').each(function () {
                maxItemHeight = Math.max($(this).outerHeight(true), maxItemHeight);
            });
            this.slick.$slider.find('.cronycle-carousel-item-convo').each(function () {
                maxItemHeight = Math.max($(this).outerHeight(true), maxItemHeight);
            });
            return maxItemHeight;
        }

        /* 
         * Boolean conditions for checking mode
         * horizontal mode - !unslicked
         * vertical mode - unslicked
         */

        // Function to hide the summary (item-group-text) on small screen
        // in story arc item-group except for first item-group
        this.hideOrShowItemGroupText = function (slick) {
            if (!slick.unslicked) {
                /* horizontal mode */
                jQuery('.cronycle-carousel-item-group .cronycle-carousel-item-group-text').show();
            } else {
                /* vertical mode */
                jQuery('.cronycle-carousel-item-group').each(function () {
                    var groupId = jQuery(this).data("cronycle-wp-group-id");
                    jQuery(`.cronycle-carousel-item-group[data-cronycle-wp-group-id='${groupId}'] .cronycle-carousel-item-group-text`).hide();
                    jQuery(`.cronycle-carousel-item-group[data-cronycle-wp-group-id='${groupId}'] .cronycle-carousel-item-group-text`).eq(0).show();
                });
            }
        }

        // Function to set width and height of item, item-group and item-convo
        this.setItemsDimensions = function (slick) {
            if (!slick.unslicked) {
                /* horizontal mode */
                // setting slider height to auto
                slick.$slider.height("auto");

                // setting width of items
                var sliderWidth = slick.$slider.width();
                var itemWidth = sliderWidth / 2;
                if (this.useFixedItemWidth)
                    itemWidth = this.fixedItemWidth;
                slick.$slider.find('.cronycle-carousel-item').outerWidth(itemWidth, true);
                slick.$slider.find('.cronycle-carousel-item-convo').outerWidth(itemWidth, true);
                slick.$slider.find('.cronycle-carousel-item-summary').outerWidth(itemWidth, true);

                // setting width of item-groups
                slick.$slider.find('.cronycle-carousel-item-group').each(function () {
                    // set width of item group on the basis of items inside it
                    var itemsCount = $(this).find('.cronycle-carousel-item').length +
                        $(this).find('.cronycle-carousel-item-convo').length;
                    if (itemsCount == 2)
                        $(this).outerWidth(itemWidth * 2, true);
                    else
                        $(this).outerWidth(itemWidth, true);

                    // for fixed height of items inside item group
                    var itemHeightInGroup = $(this).height() - $(this).find('.cronycle-carousel-item-group-text p').outerHeight(true);
                    $(this).find('.cronycle-carousel-item-group-tiles').outerHeight(itemHeightInGroup, true);
                });

                // setting height of article inside item-summary
                slick.$slider.find('.cronycle-carousel-item-summary').each(function () {
                    // for fixed height of items inside item group
                    var itemHeight = $(this).height() - $(this).find('.cronycle-carousel-item-summary-text p').outerHeight(true);
                    $(this).find('.cronycle-carousel-item-summary-tiles').outerHeight(itemHeight, true);
                });

                // for convo item specific padding between title and content
                $('.cronycle-carousel-item-convo .cronycle-carousel-item-convo-empty').show();
                $('.cronycle-carousel-item-group .cronycle-carousel-item-convo .cronycle-carousel-item-convo-empty').hide();
            } else {
                /* vertical mode */
                // setting widths and heights of items and item-groups first to default
                slick.$slider.find('.cronycle-carousel-item').outerWidth("100%");
                slick.$slider.find('.cronycle-carousel-item-convo').outerWidth("100%");
                slick.$slider.find('.cronycle-carousel-item-group').outerWidth("100%");
                slick.$slider.find('.cronycle-carousel-item-summary').outerWidth("100%");
                slick.$slider.find('.cronycle-carousel-item-group-tiles').outerHeight("auto");
                slick.$slider.find('.cronycle-carousel-item-summary-tiles').outerHeight("auto");

                // get max item height
                var maxItemHeight = this.getMaxItemHeight();

                // finding item height to use on the basis of settings
                var itemHeight = 0;
                if (this.useMaxItemHeight) {
                    itemHeight = maxItemHeight;
                } else {
                    itemHeight = "auto";

                    // for convo item specific padding between title and content
                    $('.cronycle-carousel-item-convo .cronycle-carousel-item-convo-empty').hide();
                }

                // now setting item height
                slick.$slider.find('.cronycle-carousel-item').height(itemHeight);
                slick.$slider.find('.cronycle-carousel-item-convo').height(itemHeight);

                // items inside item-group and item-summary should always be auto
                slick.$slider.find('.cronycle-carousel-item-group .cronycle-carousel-item').height("auto");
                slick.$slider.find('.cronycle-carousel-item-group .cronycle-carousel-item-convo').height("auto");
                slick.$slider.find('.cronycle-carousel-item-summary .cronycle-carousel-item').height("auto");

                // setting slider height
                slick.$slider.height(maxItemHeight * 2);
            }
        }

        // Function to register event handlers
        this.registerEvents = function () {

            var _ = this;

            // Event handler after each initialization/re-initialization of carousel
            this.$cronycleCarousel.on('init reInit', function (event, slick) {
                _.hideOrShowItemGroupText(slick);
                _.setItemsDimensions(slick);
            });

            // Event handler after slide change event
            this.$cronycleCarousel.on('afterChange', function (event, slick, currentSlide) {
                // fetch more items if remaining items are less than 5
                if (slick.$slider.find('.cronycle-carousel-item').length - currentSlide < 9) {
                    _.getAndAppendArticles();
                }
            });

            // Event handler for mouse scroll
            this.$cronycleCarousel.on('wheel', function (e) {
                var slick = _.$cronycleCarousel.slick('getSlick');
                if (!slick.unslicked) {
                    e.preventDefault();
                    /* horizontal mode */
                    if (e.originalEvent.deltaX > 0)
                        $(this).slick('slickNext');
                    else
                        $(this).slick('slickPrev');
                }
            });

            // Event handler to check scrolling in vertical mode
            this.$cronycleCarousel.scroll(function () {
                var slick = _.$cronycleCarousel.slick('getSlick');
                var maxItemHeight = _.getMaxItemHeight();
                if (slick.$slider.find('.cronycle-carousel-item').length != 0)
                    maxItemHeight = slick.$slider.find('.cronycle-carousel-item').outerHeight();
                else
                    maxItemHeight = slick.$slider.find('.cronycle-carousel-item-convo').outerHeight();

                // fetch more articles if less than 5 items remaining to scroll
                if ($(this).scrollTop() >= slick.$slider.get(0).scrollHeight - maxItemHeight * 9) {
                    _.getAndAppendArticles();
                }
            });

            // window load & resize handler
            $(window).on('resize', function () {
                var slick = _.$cronycleCarousel.slick('getSlick');

                // return if slick is not initialized
                if (slick.length == 0) return;

                _.updateOrientation();
            });
        }

        // Function to update orientation on the banner as per available width
        this.updateOrientation = function () {
            var bannerWidth = this.$cronycleBanner.outerWidth();
            if (bannerWidth >= this.responsiveBreakpoint) {
                /* horizontal mode */
                this.$cronycleCarousel.parents('.cronycle-banner-container').removeClass('vertical-view');
                this.$cronycleCarousel.slick('unslick');
                this.$cronycleCarousel.slick(this.slickArgs);
            } else {
                /* vertical mode */
                var slick = this.$cronycleCarousel.slick('getSlick');
                this.$cronycleCarousel.slick('unslick');
                this.$cronycleCarousel.parents('.cronycle-banner-container').addClass('vertical-view');
                this.hideOrShowItemGroupText(slick);
                this.setItemsDimensions(slick);
            }
        }

        // Function to initialize carousel
        this.init = function () {
            if (this.$cronycleCarousel.hasClass('slick-initialized'))
                return;

            // register all slick, window or other related event handlers
            this.registerEvents();

            // initialize slick
            this.$cronycleCarousel.slick(this.slickArgs);
            this.slick = this.$cronycleCarousel.slick('getSlick');
            this.updateOrientation();
        }
    }

    // jQuery plugin for Cronycle's carousel
    $.fn.cronycleCarousel = function () {
        var _ = this;
        for (var i = 0; i < _.length; i++) {
            new CronycleCarousel(_[i]).init();
        }
    }
});