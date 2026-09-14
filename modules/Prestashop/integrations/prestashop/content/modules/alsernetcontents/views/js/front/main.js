"use strict";
var $ = jQuery.noConflict();
$.extend($.easing, {
    def: "easeOutQuad",
    swing: function(t, e, i, a, n) {
        return $.easing[$.easing.def](t, e, i, a, n)
    },
    easeOutQuad: function(t, e, i, a, n) {
        return -a * (e /= n) * (e - 2) + i
    },
    easeOutQuint: function(t, e, i, a, n) {
        return a * ((e = e / n - 1) * e * e * e * e + 1) + i
    }
}),
    window.alsernet = {},
    function(t) {
        var e, i, a, n, o, s, r;
        alsernet.$window = t(window),
            alsernet.$body = t(document.body),
            alsernet.status = "",
            alsernet.isIE = navigator.userAgent.indexOf("Trident") >= 0,
            alsernet.isEdge = navigator.userAgent.indexOf("Edge") >= 0,
            alsernet.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
            alsernet.call = function(t, e) {
                setTimeout(t, e)
            }
            ,
            alsernet.parseOptions = function(t) {
                return "string" == typeof t ? JSON.parse(t.replace(/'/g, '"').replace(";", "")) : {}
            }
            ,
            alsernet.parseTemplate = function(t, e) {
                return t.replace(/\{\{(\w+)\}\}/g, (function() {
                        return e[arguments[1]]
                    }
                ))
            }
            ,
            alsernet.byId = function(t) {
                return document.getElementById(t)
            }
            ,
            alsernet.byTag = function(t, e) {
                return e ? e.getElementsByTagName(t) : document.getElementsByTagName(t)
            }
            ,
            alsernet.byClass = function(t, e) {
                return e ? e.getElementsByClassName(t) : document.getElementsByClassName(t)
            }
            ,
            alsernet.setCookie = function(t, e, i) {
                var a = new Date;
                a.setTime(a.getTime() + 24 * i * 60 * 60 * 1e3),
                    document.cookie = t + "=" + e + ";expires=" + a.toUTCString() + ";path=/"
            }
            ,
            alsernet.getCookie = function(t) {
                for (var e = t + "=", i = document.cookie.split(";"), a = 0; a < i.length; ++a) {
                    for (var n = i[a]; " " == n.charAt(0); )
                        n = n.substring(1);
                    if (0 == n.indexOf(e))
                        return n.substring(e.length, n.length)
                }
                return ""
            }
            ,
            alsernet.$ = function(e) {
                return e instanceof jQuery ? e : t(e)
            }
            ,
            alsernet.isOnScreen = function(t) {
                var e = window.pageXOffset
                    , i = window.pageYOffset
                    , a = t.getBoundingClientRect()
                    , n = a.left + e
                    , o = a.top + i;
                return o + a.height >= i && o <= i + window.innerHeight && n + a.width >= e && n <= e + window.innerWidth
            }
            ,
            alsernet.appear = function(e, i, a) {
                return a && Object.keys(a).length && t.extend(intersectionObserverOptions, a),
                    new IntersectionObserver((function(e) {
                            for (var a = 0; a < e.length; a++) {
                                var n = e[a];
                                if (n.intersectionRatio > 0)
                                    if ("string" == typeof i)
                                        Function("return " + functionName)();
                                    else
                                        i.call(t(n.target))
                            }
                        }
                    ),{
                        rootMargin: "0px 0px 200px 0px",
                        threshold: 0,
                        alwaysObserve: !0
                    }).observe(e),
                    this
            }
            ,
            alsernet.requestTimeout = function(t, e) {
                var i = window.requestAnimationFrame || window.webkitRequestAnimationFrame || window.mozRequestAnimationFrame;
                if (!i)
                    return setTimeout(t, e);
                var a, n = new Object;
                return n.val = i((function o(s) {
                        a || (a = s),
                            s - a >= e ? t() : n.val = i(o)
                    }
                )),
                    n
            }
            ,
            alsernet.requestInterval = function(t, e, i) {
                var a = window.requestAnimationFrame || window.webkitRequestAnimationFrame || window.mozRequestAnimationFrame;
                if (!a)
                    return i ? (console.log("settimeout"),
                        setInterval(t, e)) : (console.log("settimeout"),
                        setTimeout(t, i));
                var n, o, s = new Object;
                return s.val = a((function r(l) {
                        n || (n = o = l),
                            !i || l - n < i ? l - o > e ? (t(),
                                s.val = a(r),
                                o = l) : s.val = a(r) : t()
                    }
                )),
                    console.log(s),
                    s
            }
            ,
            alsernet.deleteTimeout = function(t) {
                if (t) {
                    var e = window.cancelAnimationFrame || window.webkitCancelAnimationFrame || window.mozCancelAnimationFrame;
                    return e ? t.val ? e(t.val) : void 0 : clearTimeout(t)
                }
            }
            ,
            alsernet.setTab = function(e) {
                alsernet.$body.on("click", ".tab .nav-link", (function(e) {
                        var i = t(this);
                        if (e.preventDefault(),
                            !i.hasClass("active")) {
                            var a = t(i.attr("href"));
                            a.siblings(".active").removeClass("in active"),
                                a.addClass("active in"),
                                i.parent().parent().find(".active").removeClass("active"),
                                i.addClass("active")
                        }
                    }
                )).on("click", ".link-to-tab", (function(e) {
                        var i = t(e.currentTarget).attr("href")
                            , a = t(i)
                            , n = a.parent().siblings(".nav");
                        e.preventDefault(),
                            a.siblings().removeClass("active in"),
                            a.addClass("active in"),
                            n.find(".nav-link").removeClass("active"),
                            n.find('[href="' + i + '"]').addClass("active"),
                            t("html").animate({
                                scrollTop: a.offset().top - 150
                            })
                    }
                ))
            }
            ,
            alsernet.initCartAction = function(e) {
                alsernet.$body.on("click", e, (function(e) {
                        t(".cart-dropdown").addClass("opened"),
                            e.preventDefault()
                    }
                )).on("click", ".cart-offcanvas .cart-overlay", (function(e) {
                        t(".cart-dropdown").removeClass("opened"),
                            e.preventDefault()
                    }
                )).on("click", ".cart-offcanvas .cart-header, .cart-close", (function(e) {
                        t(".cart-dropdown").removeClass("opened"),
                            e.preventDefault()
                    }
                ))
            }
            ,
            alsernet.stickyDefaultOptions = {
                minWidth: 992,
                maxWidth: 2e4,
                top: !1,
                hide: !1,
                scrollMode: !0
            },
            alsernet.stickyToolboxOptions = {
                minWidth: 0,
                maxWidth: 767,
                top: !1,
                scrollMode: !0
            },
            alsernet.stickyProductOptions = {
                minWidth: 0,
                maxWidth: 2e4,
                scrollMode: !0,
                top: !1,
                hide: !1
            },
            alsernet.windowResized = function(e) {
                return e == alsernet.resizeTimeStamp || (void 0 === window.innerHeight && (window.innerWidth = t(window).width() + alsernet.getScrollbarWidth()),
                    alsernet.resizeChanged = alsernet.canvasWidth != window.innerWidth,
                    alsernet.canvasWidth = window.innerWidth,
                    alsernet.resizeTimeStamp = e),
                    alsernet.resizeChanged
            }
            ,
            alsernet.getScrollbarWidth = function() {
                if (void 0 === alsernet.scrollbarSize) {
                    var t = document.createElement("div");
                    t.style.cssText = "width: 99px; height: 99px; overflow: scroll; position: absolute; top: -9999px;",
                        document.body.appendChild(t),
                        alsernet.scrollbarSize = t.offsetWidth - t.clientWidth,
                        document.body.removeChild(t)
                }
                return alsernet.scrollbarSize
            }
            ,
            alsernet.stickyContent = function() {
                function e(t, e) {
                    return this.init(t, e)
                }
                function i() {
                    alsernet.$window.trigger("sticky_refresh.wolmart", {
                        index: 0,
                        offsetTop: 0
                    })
                }
                function a(t) {
                    t && !alsernet.windowResized(t.timeStamp) || (alsernet.$window.trigger("sticky_refresh_size.wolmart"),
                        i())
                }
                return e.prototype.init = function(e, i) {
                    this.$el = e,
                        this.options = t.extend(!0, {}, alsernet.stickyDefaultOptions, i, alsernet.parseOptions(e.attr("data-sticky-options"))),
                        alsernet.$window.on("sticky_refresh.wolmart", this.refresh.bind(this)).on("sticky_refresh_size.wolmart", this.refreshSize.bind(this))
                }
                    ,
                    e.prototype.refreshSize = function(t) {
                        var e = window.innerWidth >= this.options.minWidth && window.innerWidth <= this.options.maxWidth;
                        if (this.scrollPos = window.pageYOffset,
                        void 0 === this.top && (this.top = this.options.top),
                        window.innerWidth >= 768 && this.getTop)
                            this.top = this.getTop();
                        else if (!this.options.top && (this.top = this.isWrap ? this.$el.parent().offset().top : this.$el.offset().top + this.$el[0].offsetHeight,
                            this.$el.hasClass("has-dropdown"))) {
                            var i = this.$el.find(".category-dropdown .dropdown-box");
                            i.length && (this.top += i[0].offsetHeight)
                        }
                        this.isWrap ? e || this.unwrap() : e && this.wrap(),
                            alsernet.sticky_top_height = 0,
                        t && setTimeout(this.refreshSize.bind(this), 50)
                    }
                    ,
                    e.prototype.wrap = function() {
                        this.$el.wrap('<div class="sticky-content-wrapper"></div>'),
                            this.isWrap = !0
                    }
                    ,
                    e.prototype.unwrap = function() {
                        this.$el.unwrap(".sticky-content-wrapper"),
                            this.isWrap = !1
                    }
                    ,
                    e.prototype.refresh = function(t, e) {

                        var i = window.pageYOffset + e.offsetTop,
                            a = this.$el,
                            addElement = $(".page-product-default .product-sticky"); // Seleccionamos el otro elemento

                        if (i > this.top && this.isWrap) {
                            this.height = a[0].offsetHeight;

                            if (!a.hasClass("fixed")) {
                                a.parent().css("height", this.height + "px");
                            }

                            if (a.hasClass("fix-top")) {
                                a.css("margin-top", e.offsetTop + "px");
                                this.zIndex = this.options.max_index - e.index;
                            } else if (a.hasClass("fix-bottom")) {
                                a.css("margin-bottom", e.offsetBottom + "px");
                                this.zIndex = this.options.max_index - e.index;
                            } else {
                                a.css({
                                    transition: "opacity .5s",
                                    "z-index": this.zIndex
                                });
                            }

                            if (this.options.scrollMode) {
                                if ((this.scrollPos >= i && a.hasClass("fix-top")) || (this.scrollPos <= i && a.hasClass("fix-bottom"))) {
                                    a.addClass("fixed");
                                    addElement.addClass("fixed"); // Agrega la clase fixed al nuevo elemento
                                    this.onFixed && this.onFixed();
                                    if (a.hasClass("product-sticky-content")) {
                                        alsernet.$body.addClass("addtocart-fixed");
                                    }
                                } else {
                                    a.removeClass("fixed").css("margin-top", "").css("margin-bottom", "");
                                    addElement.removeClass("fixed"); // Quita la clase fixed del nuevo elemento
                                    this.onUnfixed && this.onUnfixed();
                                    if (a.hasClass("product-sticky-content")) {
                                        alsernet.$body.removeClass("addtocart-fixed");
                                    }
                                }
                                this.scrollPos = i;
                            } else {
                                a.addClass("fixed");
                                addElement.addClass("fixed"); // Agrega la clase fixed al nuevo elemento
                                this.onFixed && this.onFixed();
                            }

                            if (a.is(".fixed.fix-top")) {
                                e.offsetTop += a[0].offsetHeight;
                                alsernet.sticky_top_height = e.offsetTop;
                            } else if (a.is(".fixed.fix-bottom")) {
                                e.offsetBottom += a[0].offsetHeight;
                            }
                        } else {
                            a.parent().css("height", "");
                            a.removeClass("fixed").css({
                                "margin-top": "",
                                "margin-bottom": "",
                                "z-index": ""
                            });
                            addElement.removeClass("fixed"); // Quita la clase fixed del nuevo elemento
                            this.onUnfixed && this.onUnfixed();
                            if (a.hasClass("product-sticky-content")) {
                                alsernet.$body.removeClass("addtocart-fixed");
                            }
                        }


                    }
                    ,
                    alsernet.$window.on("wolmart_complete", (function() {
                            window.addEventListener("scroll", i, {
                                passive: !0
                            }),
                                alsernet.$window.on("resize", a),
                                setTimeout((function() {
                                        a()
                                    }
                                ), 300)
                        }
                    )),
                    function(i, a) {
                        alsernet.$(i).each((function() {
                                var i = t(this);
                                i.data("sticky-content") || i.data("sticky-content", new e(i,a))
                            }
                        ))
                    }
            }(),
            alsernet.parallax = function(e, i) {
                t.fn.themePluginParallax && alsernet.$(e).each((function() {
                        var e = t(this);
                        e.themePluginParallax(t.extend(!0, alsernet.parseOptions(e.attr("data-parallax-options")), i))
                    }
                ))
            }
            ,
            alsernet.skrollrParallax = function() {
                alsernet.isMobile || "undefined" != typeof skrollr && alsernet.$(".skrollable").length && skrollr.init({
                    forceHeight: !1
                })
            }
            ,
            alsernet.initFloatingParallax = function() {
                t.fn.parallax && alsernet.$(".floating-item").each((function(e) {
                        var i = t(this);
                        i.data("parallax") && (i.parallax("disable"),
                            i.removeData("parallax"),
                            i.removeData("options")),
                            i.children().addClass("layer").attr("data-depth", i.attr("data-child-depth")),
                            i.parallax(alsernet.parseOptions(i.data("options")))
                    }
                ))
            }
            ,
            alsernet.isotopeOptions = {
                itemsSelector: ".grid-item",
                layoutMode: "masonry",
                percentPosition: !0,
                masonry: {
                    columnWidth: ".grid-space"
                }
            },
            alsernet.isotopes = function(e, i) {
                if ("function" == typeof imagesLoaded && t.fn.isotope) {
                    var a = this;
                    alsernet.$(e).each((function() {
                            var e = t(this)
                                , n = t.extend(!0, {}, a.isotopeOptions, alsernet.parseOptions(e.attr("data-grid-options")), i || {});
                            alsernet.lazyLoad(e),
                                e.imagesLoaded((function() {
                                        n.customInitHeight && e.height(e.height()),
                                        n.customDelay && alsernet.call((function() {
                                                e.isotope(n)
                                            }
                                        ), parseInt(n.customDelay)),
                                            e.isotope(n)
                                    }
                                ))
                        }
                    ))
                }
            }
            ,
            alsernet.initNavFilter = function(e) {
                t.fn.isotope && alsernet.$(e).on("click", (function(e) {
                        var i = t(this)
                            , a = i.attr("data-filter")
                            , n = i.parent().parent().attr("data-target");
                        t(n || ".grid").isotope({
                            filter: a
                        }).isotope("on", "arrangeComplete", (function() {
                                alsernet.$window.trigger("appear.check")
                            }
                        )),
                            i.parent().siblings().children().removeClass("active"),
                            i.addClass("active"),
                            e.preventDefault()
                    }
                ))
            }
            ,
            alsernet.ratingTooltip = function(t) {
                for (var e = alsernet.byClass("ratings-full", t || document.body), i = e.length, a = function() {
                    var t = parseInt(this.firstElementChild.style.width.slice(0, -1)) / 20;
                    this.lastElementChild.innerText = t ? t.toFixed(2) : t
                }, n = 0; n < i; ++n)
                    e[n].addEventListener("mouseover", a),
                        e[n].addEventListener("touchstart", a, {
                            passive: !0
                        })
            }
            ,
            alsernet.setProgressBar = function(e) {
                alsernet.$(e).each((function() {
                        var e = t(this)
                            , i = e.parent().find("mark")[0].innerHTML
                            , a = "";
                        -1 != i.indexOf("%") ? a = i : -1 != i.indexOf("/") && (a = (a = parseInt(i.split("/")[0]) / parseInt(i.split("/")[1]) * 100).toFixed(2).toString() + "%"),
                            e.find("span").css("width", a)
                    }
                ))
            }
            ,
            alsernet.alert = function(e) {
                alsernet.$body.on("click", e + " .btn-close", (function(i) {
                        i.preventDefault(),
                            t(this).closest(e).fadeOut((function() {
                                    t(this).remove()
                                }
                            ))
                    }
                ))
            }
            ,
            alsernet.closeTopNotice = function(e) {
                alsernet.$body.on("click", e, (function(e) {
                        e.preventDefault(),
                            t(".top-banner").slideUp()
                    }
                ))
            }
            ,
            alsernet.accordion = function(e) {
                alsernet.$body.on("click", e, (function(e) {
                        var a = t(this)
                            , n = a.closest(".card").find(a.attr("href"))
                            , o = a.closest(".accordion");
                        e.preventDefault(),
                        0 === o.find(".collapsing").length && 0 === o.find(".expanding").length && (n.hasClass("expanded") ? o.hasClass("radio-type") || i(n) : n.hasClass("collapsed") && (o.find(".expanded").length > 0 ? alsernet.isIE ? i(o.find(".expanded"), (function() {
                                i(n)
                            }
                        )) : (i(o.find(".expanded")),
                            i(n)) : i(n)))
                    }
                ));
                var i = function(t, i) {
                    var a = t.closest(".card").find(e);
                    t.hasClass("expanded") ? (a.removeClass("collapse").addClass("expand"),
                        t.addClass("collapsing").slideUp(300, (function() {
                                t.removeClass("expanded collapsing").addClass("collapsed"),
                                i && i()
                            }
                        ))) : t.hasClass("collapsed") && (a.removeClass("expand").addClass("collapse"),
                        t.addClass("expanding").slideDown(300, (function() {
                                t.removeClass("collapsed expanding").addClass("expanded"),
                                i && i()
                            }
                        )))
                }
            }
            ,
            alsernet.animationOptions = {
                name: "fadeIn",
                duration: "1.2s",
                delay: ".2s"
            },
            alsernet.appearAnimate = function(e) {
                alsernet.$(e).each((function() {
                        var e = this;
                        alsernet.appear(e, (function() {
                                if (e.classList.contains("appear-animate")) {
                                    var i = t.extend({}, alsernet.animationOptions, alsernet.parseOptions(e.getAttribute("data-animation-options")));
                                    setTimeout((function() {
                                            e.style["animation-duration"] = i.duration,
                                                e.classList.add(i.name),
                                                e.classList.add("appear-animation-visible")
                                        }
                                    ), i.delay ? 1e3 * Number(i.delay.slice(0, -1)) : 0)
                                }
                            }
                        ))
                    }
                ))
            }
            ,
            alsernet.countDown = function(e) {
                t.fn.countdown && alsernet.$(e).each((function() {
                        var e = t(this)
                            , i = e.data("until")
                            , a = e.data("compact")
                            , n = e.data("format") ? e.data("format") : "DHMS"
                            , o = e.data("labels-short") ? ["Years", "Months", "Weeks", "Days", "Hrs", "Mins", "Secs"] : ["Years", "Months", "Weeks", "Days", "Hours", "Minutes", "Seconds"]
                            , s = e.data("labels-short") ? ["Year", "Month", "Week", "Day", "Hour", "Min", "Sec"] : ["Year", "Month", "Week", "Day", "Hour", "Minute", "Second"];
                        if (e.data("relative"))
                            l = i;
                        else
                            var r = i.split(", ")
                                , l = new Date(r[0],r[1] - 1,r[2]);
                        e.countdown({
                            until: l,
                            format: n,
                            padZeroes: !0,
                            compact: a,
                            compactLabels: [" y", " m", " w", " days, "],
                            timeSeparator: " : ",
                            labels: o,
                            labels1: s
                        })
                    }
                ))
            }
            ,
            alsernet.priceSlider = function(e, i) {
                "object" == typeof noUiSlider && alsernet.$(e).each((function() {
                        var e = this;
                        noUiSlider.create(e, t.extend(!0, {
                            start: [0, 400],
                            connect: !0,
                            step: 1,
                            range: {
                                min: 0,
                                max: 635
                            }
                        }, i)),
                            e.noUiSlider.on("update", (function(i, a) {
                                    i = i.map((function(t) {
                                            return "$" + parseInt(t)
                                        }
                                    ));
                                    t(e).parent().find(".filter-price-range").text(i.join(" - "))
                                }
                            ))
                    }
                ))
            }
            ,
            alsernet.stickySidebarOptions = {
                autoInit: !0,
                minWidth: 991,
                containerSelector: ".sticky-sidebar-wrapper",
                autoFit: !0,
                activeClass: "sticky-sidebar-fixed",
                top: 0,
                bottom: 0
            },
            alsernet.stickySidebar = function(e) {
                if (t.fn.themeSticky) {
                    var i = 0;

                    function a() {
                        alsernet.$(e).trigger("recalc.pin");
                        t(window).trigger("appear.check");
                    }

                    // Detecta elementos sticky y aplica la clase fixed si corresponde
                    if (!t(".sticky-sidebar > .filter-actions").length && t(window).width() >= 992) {
                        t(".sticky-content.fix-top").each(function(e) {
                            if (!t(this).hasClass("sticky-toolbox")) {
                                var isFixed = t(this).hasClass("fixed");
                                i += t(this).addClass("fixed").outerHeight();

                                // Si no estaba fijado previamente, lo removemos
                                if (!isFixed) {
                                    t(this).removeClass("fixed");
                                }

                                // Aplica la misma clase fixed al elemento adicional
                                var addElement = $(".product-detail .product-actions .product-quantity .add");
                                if (isFixed) {
                                    addElement.addClass("fixed");
                                } else {
                                    addElement.removeClass("fixed");
                                }
                            }
                        });
                    }

                    // Configura la funcionalidad sticky en los elementos encontrados
                    alsernet.$(e).each(function() {
                        var e = t(this);
                        e.themeSticky(t.extend({}, alsernet.stickySidebarOptions, {
                            padding: {
                                top: i
                            }
                        }, alsernet.parseOptions(e.attr("data-sticky-options"))));
                    });

                    // Ajusta la posición de los elementos sticky después de un tiempo
                    setTimeout(a, 300);

                    // Recalcula la posición cuando se hace clic en un enlace de la pestaña
                    alsernet.$window.on("click", ".tab .nav-link", function() {
                        setTimeout(a);
                    });
                }
            }
            ,
            alsernet.zoomImageOptions = {
                responsive: !0,
                borderSize: 0,
                zoomType: "inner",
                onZoomIn: !0,
                magnify: 1.1
            },
            alsernet.zoomImageObjects = [],
            alsernet.zoomImage = function(e) {
                t.fn.zoom && e && ("string" == typeof e ? t(e) : e).find("img").each((function() {
                        var e = t(this);
                        alsernet.zoomImageOptions.target = e.parent(),
                            alsernet.zoomImageOptions.url = e.attr("data-zoom-image"),
                            e.zoom(alsernet.zoomImageOptions),
                            alsernet.zoomImageObjects.push(e)
                    }
                ))
            }
            ,
            alsernet.zoomImageOnResize = function() {
                alsernet.zoomImageObjects.forEach((function(e) {
                        e.each((function() {
                                var e = t(this).data("zoom");
                                e && e.refresh()
                            }
                        ))
                    }
                ))
            }
            ,
            alsernet.lazyLoad = function(t, e) {
                function i() {
                    this.setAttribute("src", this.getAttribute("data-src")),
                        this.addEventListener("load", (function() {
                                this.style["padding-top"] = "",
                                    this.classList.remove("lazy-img")
                            }
                        ))
                }
                alsernet.$(t).find(".lazy-img").each((function() {
                        void 0 !== e && e ? i.call(this) : alsernet.appear(this, i)
                    }
                ))
            }
            ,
            alsernet.initPopup = function(e, i) {
                alsernet.$body.hasClass("home") && "true" !== alsernet.getCookie("hideNewsletterPopup") && setTimeout((function() {
                        alsernet.popup({
                            items: {
                                src: ".newsletter-popup"
                            },
                            type: "inline",
                            tLoading: "",
                            mainClass: "mfp-newsletter mfp-fadein-popup",
                            callbacks: {
                                beforeClose: function() {
                                    t("#hide-newsletter-popup")[0].checked && alsernet.setCookie("hideNewsletterPopup", !0, 7)
                                }
                            }
                        })
                    }
                ), 7500),
                    alsernet.$body.on("click", ".btn-iframe", (function(e) {
                            e.preventDefault(),
                                alsernet.popup({
                                    items: {
                                        src: '<video src="' + t(e.currentTarget).attr("href") + '" autoplay loop controls>',
                                        type: "inline"
                                    },
                                    mainClass: "mfp-video-popup"
                                }, "video")
                        }
                    )),
                    alsernet.$body.on("click", ".sign-in", (function(e) {
                            e.preventDefault(),
                                alsernet.popup({
                                    items: {
                                        src: t(e.currentTarget).attr("href")
                                    }
                                }, "login")
                        }
                    )).on("click", ".register", (function(e) {
                            e.preventDefault(),
                                alsernet.popup({
                                    items: {
                                        src: t(e.currentTarget).attr("href")
                                    },
                                    callbacks: {
                                        ajaxContentAdded: function() {
                                            this.wrap.find('[href="#sign-up"]').click()
                                        }
                                    }
                                }, "login")
                        }
                    ))
            }
            ,
            alsernet.initNotificationAlert = function() {
                alsernet.$body.hasClass("has-notification") && setTimeout((function() {
                        alsernet.$body.addClass("show-notification")
                    }
                ), 5e3)
            }
            ,
            alsernet.countTo = function(e) {
                t.fn.countTo && alsernet.$(e).each((function() {
                        alsernet.appear(this, (function() {
                                var e = t(this);
                                setTimeout((function() {
                                        e.countTo({
                                            onComplete: function() {
                                                e.addClass("complete")
                                            }
                                        })
                                    }
                                ), 300)
                            }
                        ))
                    }
                ))
            }
            ,
            alsernet.minipopupOption = {
                productClass: "",
                imageSrc: "",
                imageLink: "#",
                name: "",
                nameLink: "#",
                message: "",
                actionTemplate: "",
                isPurchased: !1,
                delay: 4e3,
                space: 20,
                wishlistLink: "#", // Nueva propiedad para el enlace de wishlist
                template: '<div class="minipopup-box">' +
                    '<div class="product product-list-sm {{productClass}}">' +
                    '<figure class="product-media">' +
                    '<a href="{{imageLink}}"><img src="{{imageSrc}}" alt="Product" width="80" height="90" /></a>' +
                    '</figure>' +
                    '<div class="product-details">' +
                    '<h4 class="product-name"><a href="{{nameLink}}">{{name}}</a></h4>' +
                    '{{message}}' +
                    '</div>' +
                    '</div>' +
                    '<div class="product-action">' +
                    '{{actionTemplate}}' +
                    '<a href="{{wishlistLink}}" class="wishlist-button">Add to Wishlist</a>' + // Botón de wishlist
                    '</div>' +
                    '</div>'
            };
        alsernet.Minipopup = (i = 0,
            a = [],
            n = !1,
            o = [],
            s = !1,
            r = function() {
                if (!n)
                    for (var t = 0; t < o.length; ++t)
                        (o[t] -= 200) <= 0 && this.close(t--)
            }
            ,
            {
                init: function() {
                    var i = document.createElement("div");
                    i.className = "minipopup-area",
                        e = t(i),
                        this.close = this.close.bind(this),
                        r = r.bind(this)
                },
                open: function(n, l) {
                    var c, d = this, p = t.extend(!0, {}, alsernet.minipopupOption, n);
                    c = t(alsernet.parseTemplate(p.template, p)),
                        d.space = p.space;
                    var u = c.appendTo(e).css("top", -i).find("img");
                    u.length && u.on("load", (function() {
                            i += c[0].offsetHeight + d.space,
                                c.addClass("show"),
                            c.offset().top - window.pageYOffset < 0 && (d.close(),
                                c.css("top", -i + c[0].offsetHeight + d.space)),
                                c.on("mouseenter", (function() {
                                        d.pause()
                                    }
                                )).on("mouseleave", (function() {
                                        d.resume()
                                    }
                                )).on("touchstart", (function(t) {
                                        d.pause(),
                                            t.stopPropagation()
                                    }
                                )).on("mousedown", (function() {
                                        t(this).addClass("focus")
                                    }
                                )).on("mouseup", (function() {
                                        d.close(t(this).index())
                                    }
                                )),
                                alsernet.$body.on("touchstart", (function() {
                                        d.resume()
                                    }
                                )),
                                a.push(c),
                            o.length || (s = setInterval(r, 200)),
                                o.push(p.delay),
                            l && l(c)
                        }
                    ))
                },
                close: function(t) {
                    var e = void 0 === t ? 0 : t
                        , n = a.splice(e, 1)[0];
                    o.splice(e, 1)[0];
                    var r = n[0].offsetHeight;
                    i -= r + this.space,
                        n.removeClass("show"),
                        setTimeout((function() {
                                n.remove()
                            }
                        ), 300),
                        a.forEach((function(t, i) {
                                i >= e && t.hasClass("show") && t.stop(!0, !0).animate({
                                    top: parseInt(t.css("top")) + r + 20
                                }, 600, "easeOutQuint")
                            }
                        )),
                    a.length || clearTimeout(s)
                },
                pause: function() {
                    n = !0
                },
                resume: function() {
                    n = !1
                }
            }),
            alsernet.headerToggleSearch = function(t) {
                var e = alsernet.$(t);
                alsernet.$body.on("click", ".hs-toggle .search-toggle", (function(t) {
                        t.preventDefault()
                    }
                )),
                    "ontouchstart"in document ? (e.find(".search-toggle").on("click", (function(t) {
                            e.toggleClass("show")
                        }
                    )),
                        alsernet.$body.on("click", (function(t) {
                                e.removeClass("show")
                            }
                        )),
                        e.on("click", (function(t) {
                                t.preventDefault(),
                                    t.stopPropagation()
                            }
                        ))) : e.find(".form-control").on("focusin", (function(t) {
                            e.addClass("show")
                        }
                    )).on("focusout", (function(t) {
                            e.removeClass("show")
                        }
                    ))
            }
            ,
            alsernet.scrollTo = function(e, i) {
                var a = void 0 === i ? 0 : i;
                if ("number" == typeof e)
                    o = e;
                else {
                    var n = alsernet.$(e);
                    if (!n.length || "none" == n.css("display"))
                        return;
                    var o = n.offset().top
                        , s = t("#wp-toolbar");
                    window.innerWidth > 600 && s.length && (o -= s.parent().outerHeight()),
                        t(".sticky-content.fix-top.fixed").each((function() {
                                o -= this.offsetHeight
                            }
                        ))
                }
                t("html,body").stop().animate({
                    scrollTop: o
                }, a)
            }
    }(jQuery),

    jQuery,
    alsernet.initLayout = function() {
        alsernet.isotopes(".grid:not(.grid-float)"),
            alsernet.stickySidebar(".sticky-sidebar")
    }
    ,
    alsernet.init = function() {
        alsernet.appearAnimate(".appear-animate"),
            alsernet.setTab(".nav-tabs"),
            alsernet.stickyContent(".sticky-header", {
                scrollMode: !1
            }),
            alsernet.stickyContent(".sticky-footer", {
                minWidth: 0,
                maxWidth: 767,
                top: 150,
                hide: !0,
                max_index: 2100,
                scrollMode: !0
            })
    }
    ,
    jQuery,
    window.onload = function() {
        alsernet.canvasWidth = window.innerWidth,
            alsernet.resizeTimeStamp = 0,
            alsernet.resizeChanged = !1,
            alsernet.status = "loaded",
            document.body.classList.add("loaded"),
            alsernet.call(alsernet.initLayout),
            alsernet.call(alsernet.init),
            alsernet.Minipopup.init(),
            alsernet.status = "complete",
            alsernet.$window.trigger("wolmart_complete")
    }
;


