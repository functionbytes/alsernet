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
}), window.alsernetview = {},
    function(t) {
        var e, i, a, n, o, s, r;
        alsernetview.$window = t(window), alsernetview.$body = t(document.body), alsernetview.status = "", alsernetview.isIE = navigator.userAgent.indexOf("Trident") >= 0, alsernetview.isEdge = navigator.userAgent.indexOf("Edge") >= 0, alsernetview.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent), alsernetview.call = function(t, e) {
            setTimeout(t, e)
        }, alsernetview.parseOptions = function(t) {
            return "string" == typeof t ? JSON.parse(t.replace(/'/g, '"').replace(";", "")) : {}
        }, alsernetview.parseTemplate = function(t, e) {
            return t.replace(/\{\{(\w+)\}\}/g, (function() {
                return e[arguments[1]]
            }))
        }, alsernetview.byId = function(t) {
            return document.getElementById(t)
        }, alsernetview.byTag = function(t, e) {
            return e ? e.getElementsByTagName(t) : document.getElementsByTagName(t)
        }, alsernetview.byClass = function(t, e) {
            return e ? e.getElementsByClassName(t) : document.getElementsByClassName(t)
        }, alsernetview.setCookie = function(t, e, i) {
            var a = new Date;
            a.setTime(a.getTime() + 24 * i * 60 * 60 * 1e3), document.cookie = t + "=" + e + ";expires=" + a.toUTCString() + ";path=/"
        }, alsernetview.getCookie = function(t) {
            for (var e = t + "=", i = document.cookie.split(";"), a = 0; a < i.length; ++a) {
                for (var n = i[a];
                     " " == n.charAt(0);) n = n.substring(1);
                if (0 == n.indexOf(e)) return n.substring(e.length, n.length)
            }
            return ""
        }, alsernetview.$ = function(e) {
            return e instanceof jQuery ? e : t(e)
        }, alsernetview.isOnScreen = function(t) {
            var e = window.pageXOffset,
                i = window.pageYOffset,
                a = t.getBoundingClientRect(),
                n = a.left + e,
                o = a.top + i;
            return o + a.height >= i && o <= i + window.innerHeight && n + a.width >= e && n <= e + window.innerWidth
        }, alsernetview.appear = function(e, i, a) {
            return a && Object.keys(a).length && t.extend(intersectionObserverOptions, a), new IntersectionObserver((function(e) {
                for (var a = 0; a < e.length; a++) {
                    var n = e[a];
                    if (n.intersectionRatio > 0)
                        if ("string" == typeof i) Function("return " + functionName)();
                        else i.call(t(n.target))
                }
            }), {
                rootMargin: "0px 0px 200px 0px",
                threshold: 0,
                alwaysObserve: !0
            }).observe(e), this
        }, alsernetview.requestTimeout = function(t, e) {
            var i = window.requestAnimationFrame || window.webkitRequestAnimationFrame || window.mozRequestAnimationFrame;
            if (!i) return setTimeout(t, e);
            var a, n = new Object;
            return n.val = i((function o(s) {
                a || (a = s), s - a >= e ? t() : n.val = i(o)
            })), n
        }, alsernetview.requestInterval = function(t, e, i) {
            var a = window.requestAnimationFrame || window.webkitRequestAnimationFrame || window.mozRequestAnimationFrame;
            if (!a) return i ? (console.log("settimeout"), setInterval(t, e)) : (console.log("settimeout"), setTimeout(t, i));
            var n, o, s = new Object;
            return s.val = a((function r(l) {
                n || (n = o = l), !i || l - n < i ? l - o > e ? (t(), s.val = a(r), o = l) : s.val = a(r) : t()
            })), console.log(s), s
        }, alsernetview.deleteTimeout = function(t) {
            if (t) {
                var e = window.cancelAnimationFrame || window.webkitCancelAnimationFrame || window.mozCancelAnimationFrame;
                return e ? t.val ? e(t.val) : void 0 : clearTimeout(t)
            }
        }, alsernetview.setTab = function(e) {
            alsernetview.$body.on("click", ".tab .nav-link", (function(e) {
                var i = t(this);
                if (e.preventDefault(), !i.hasClass("active")) {
                    var a = t(i.attr("href"));
                    a.siblings(".active").removeClass("in active"), a.addClass("active in"), i.parent().parent().find(".active").removeClass("active"), i.addClass("active")
                }
            })).on("click", ".link-to-tab", (function(e) {
                var i = t(e.currentTarget).attr("href"),
                    a = t(i),
                    n = a.parent().siblings(".nav");
                e.preventDefault(), a.siblings().removeClass("active in"), a.addClass("active in"), n.find(".nav-link").removeClass("active"), n.find('[href="' + i + '"]').addClass("active"), t("html").animate({
                    scrollTop: a.offset().top - 150
                })
            }))
        }, alsernetview.initCartAction = function(e) {
            alsernetview.$body.on("click", e, (function(e) {
                t(".cart-dropdown").addClass("opened"), e.preventDefault()
            })).on("click", ".cart-offcanvas .cart-overlay", (function(e) {
                t(".cart-dropdown").removeClass("opened"), e.preventDefault()
            })).on("click", ".cart-offcanvas .cart-header, .cart-close", (function(e) {
                t(".cart-dropdown").removeClass("opened"), e.preventDefault()
            }))
        }, alsernetview.stickyDefaultOptions = {
            minWidth: 992,
            maxWidth: 2e4,
            top: !1,
            hide: !1,
            scrollMode: !0
        }, alsernetview.stickyToolboxOptions = {
            minWidth: 0,
            maxWidth: 767,
            top: !1,
            scrollMode: !0
        }, alsernetview.stickyProductOptions = {
            minWidth: 0,
            maxWidth: 2e4,
            scrollMode: !0,
            top: !1,
            hide: !1
        }, alsernetview.windowResized = function(e) {
            return e == alsernetview.resizeTimeStamp || (void 0 === window.innerHeight && (window.innerWidth = t(window).width() + alsernetview.getScrollbarWidth()), alsernetview.resizeChanged = alsernetview.canvasWidth != window.innerWidth, alsernetview.canvasWidth = window.innerWidth, alsernetview.resizeTimeStamp = e), alsernetview.resizeChanged
        }, alsernetview.getScrollbarWidth = function() {
            if (void 0 === alsernetview.scrollbarSize) {
                var t = document.createElement("div");
                t.style.cssText = "width: 99px; height: 99px; overflow: scroll; position: absolute; top: -9999px;", document.body.appendChild(t), alsernetview.scrollbarSize = t.offsetWidth - t.clientWidth, document.body.removeChild(t)
            }
            return alsernetview.scrollbarSize
        }, alsernetview.stickyContent = function() {
            function e(t, e) {
                return this.init(t, e)
            }

            function i() {
                alsernetview.$window.trigger("sticky_refresh.alsernetview", {
                    index: 0,
                    offsetTop: 0
                })
            }

            function a(t) {
                t && !alsernetview.windowResized(t.timeStamp) || (alsernetview.$window.trigger("sticky_refresh_size.alsernetview"), i())
            }
            return e.prototype.init = function(e, i) {
                this.$el = e, this.options = t.extend(!0, {}, alsernetview.stickyDefaultOptions, i, alsernetview.parseOptions(e.attr("data-sticky-options"))), alsernetview.$window.on("sticky_refresh.alsernetview", this.refresh.bind(this)).on("sticky_refresh_size.alsernetview", this.refreshSize.bind(this))
            }, e.prototype.refreshSize = function(t) {
                var e = window.innerWidth >= this.options.minWidth && window.innerWidth <= this.options.maxWidth;
                if (this.scrollPos = window.pageYOffset, void 0 === this.top && (this.top = this.options.top), window.innerWidth >= 768 && this.getTop) this.top = this.getTop();
                else if (!this.options.top && (this.top = this.isWrap ? this.$el.parent().offset().top : this.$el.offset().top + this.$el[0].offsetHeight, this.$el.hasClass("has-dropdown"))) {
                    var i = this.$el.find(".category-dropdown .dropdown-box");
                    i.length && (this.top += i[0].offsetHeight)
                }
                this.isWrap ? e || this.unwrap() : e && this.wrap(), alsernetview.sticky_top_height = 0, t && setTimeout(this.refreshSize.bind(this), 50)
            }, e.prototype.wrap = function() {
                this.$el.wrap('<div class="sticky-content-wrapper"></div>'), this.isWrap = !0
            }, e.prototype.unwrap = function() {
                this.$el.unwrap(".sticky-content-wrapper"), this.isWrap = !1
            }, e.prototype.refresh = function(t, e) {
                var i = window.pageYOffset + e.offsetTop,
                    a = this.$el;
                i > this.top && this.isWrap ? (this.height = a[0].offsetHeight, a.hasClass("fixed") || a.parent().css("height", this.height + "px"), a.hasClass("fix-top") ? (a.css("margin-top", e.offsetTop + "px"), this.zIndex = this.options.max_index - e.index) : a.hasClass("fix-bottom") ? (a.css("margin-bottom", e.offsetBottom + "px"), this.zIndex = this.options.max_index - e.index) : a.css({
                    transition: "opacity .5s",
                    "z-index": this.zIndex
                }), this.options.scrollMode ? (this.scrollPos >= i && a.hasClass("fix-top") || this.scrollPos <= i && a.hasClass("fix-bottom") ? (a.addClass("fixed"), this.onFixed && this.onFixed(), a.hasClass("product-sticky-content") && alsernetview.$body.addClass("addtocart-fixed")) : (a.removeClass("fixed").css("margin-top", "").css("margin-bottom", ""), this.onUnfixed && this.onUnfixed(), a.hasClass("product-sticky-content") && alsernetview.$body.removeClass("addtocart-fixed")), this.scrollPos = i) : (a.addClass("fixed"), this.onFixed && this.onFixed()), a.is(".fixed.fix-top") ? (e.offsetTop += a[0].offsetHeight, alsernetview.sticky_top_height = e.offsetTop) : a.is(".fixed.fix-bottom") && (e.offsetBottom += a[0].offsetHeight)) : (a.parent().css("height", ""), a.removeClass("fixed").css({
                    "margin-top": "",
                    "margin-bottom": "",
                    "z-index": ""
                }), this.onUnfixed && this.onUnfixed(), a.hasClass("product-sticky-content") && alsernetview.$body.removeClass("addtocart-fixed"))
            }, alsernetview.$window.on("alsernetview_complete", (function() {
                window.addEventListener("scroll", i, {
                    passive: !0
                }), alsernetview.$window.on("resize", a), setTimeout((function() {
                    a()
                }), 300)
            })),
                function(i, a) {
                    alsernetview.$(i).each((function() {
                        var i = t(this);
                        i.data("sticky-content") || i.data("sticky-content", new e(i, a))
                    }))
                }
        }(), alsernetview.parallax = function(e, i) {
            t.fn.themePluginParallax && alsernetview.$(e).each((function() {
                var e = t(this);
                e.themePluginParallax(t.extend(!0, alsernetview.parseOptions(e.attr("data-parallax-options")), i))
            }))
        },alsernetview.initFloatingParallax = function() {
            t.fn.parallax && alsernetview.$(".floating-item").each((function(e) {
                var i = t(this);
                i.data("parallax") && (i.parallax("disable"), i.removeData("parallax"), i.removeData("options")), i.children().addClass("layer").attr("data-depth", i.attr("data-child-depth")), i.parallax(alsernetview.parseOptions(i.data("options")))
            }))
        }, alsernetview.isotopeOptions = {
            itemsSelector: ".grid-item",
            layoutMode: "masonry",
            percentPosition: !0,
            masonry: {
                columnWidth: ".grid-space"
            }
        }, alsernetview.isotopes = function(e, i) {
            if ("function" == typeof imagesLoaded && t.fn.isotope) {
                var a = this;
                alsernetview.$(e).each((function() {
                    var e = t(this),
                        n = t.extend(!0, {}, a.isotopeOptions, alsernetview.parseOptions(e.attr("data-grid-options")), i || {});
                    alsernetview.lazyLoad(e),
                        e.imagesLoaded((function() {
                        n.customInitHeight && e.height(e.height()), n.customDelay && alsernetview.call((function() {
                            e.isotope(n)
                        }), parseInt(n.customDelay)), e.isotope(n)
                    }))
                }))
            }
        }, alsernetview.setProgressBar = function(e) {
            alsernetview.$(e).each((function() {
                var e = t(this),
                    i = e.parent().find("mark")[0].innerHTML,
                    a = ""; - 1 != i.indexOf("%") ? a = i : -1 != i.indexOf("/") && (a = (a = parseInt(i.split("/")[0]) / parseInt(i.split("/")[1]) * 100).toFixed(2).toString() + "%"), e.find("span").css("width", a)
            }))
        }, alsernetview.alert = function(e) {
            alsernetview.$body.on("click", e + " .btn-close", (function(i) {
                i.preventDefault(), t(this).closest(e).fadeOut((function() {
                    t(this).remove()
                }))
            }))
        }, alsernetview.closeTopNotice = function(e) {
            alsernetview.$body.on("click", e, (function(e) {
                e.preventDefault(), t(".top-banner").slideUp()
            }))
        },  alsernetview.animationOptions = {
            name: "fadeIn",
            duration: "1.2s",
            delay: ".2s"
        }, alsernetview.appearAnimate = function(e) {
            alsernetview.$(e).each((function() {
                var e = this;
                alsernetview.appear(e, (function() {
                    if (e.classList.contains("appear-animate")) {
                        var i = t.extend({}, alsernetview.animationOptions, alsernetview.parseOptions(e.getAttribute("data-animation-options")));
                        setTimeout((function() {
                            e.style["animation-duration"] = i.duration, e.classList.add(i.name), e.classList.add("appear-animation-visible")
                        }), i.delay ? 1e3 * Number(i.delay.slice(0, -1)) : 0)
                    }
                }))
            }))
        }, alsernetview.stickySidebarOptions = {
            autoInit: !0,
            minWidth: 991,
            containerSelector: ".sticky-sidebar-wrapper",
            autoFit: !0,
            activeClass: "sticky-sidebar-fixed",
            top: 0,
            bottom: 0
        }, alsernetview.stickySidebar = function(e) {
            if (t.fn.themeSticky) {
                var i = 0;

                function a() {
                    alsernetview.$(e).trigger("recalc.pin"), t(window).trigger("appear.check")
                }!t(".sticky-sidebar > .filter-actions").length && t(window).width() >= 992 && t(".sticky-content.fix-top").each((function(e) {
                    if (!t(this).hasClass("sticky-toolbox")) {
                        var a = t(this).hasClass("fixed");
                        i += t(this).addClass("fixed").outerHeight(), a || t(this).removeClass("fixed")
                    }
                })), alsernetview.$(e).each((function() {
                    var e = t(this);
                    e.themeSticky(t.extend({}, alsernetview.stickySidebarOptions, {
                        padding: {
                            top: i
                        }
                    }, alsernetview.parseOptions(e.attr("data-sticky-options"))))
                })), setTimeout(a, 300), alsernetview.$window.on("click", ".tab .nav-link", (function() {
                    setTimeout(a)
                }))
            }
        }, alsernetview.zoomImageOptions = {
            responsive: !0,
            borderSize: 0,
            zoomType: "inner",
            onZoomIn: !0,
            magnify: 1.1
        }, alsernetview.zoomImageObjects = [], alsernetview.zoomImage = function(e) {
            t.fn.zoom && e && ("string" == typeof e ? t(e) : e).find("img").each((function() {
                var e = t(this);
                alsernetview.zoomImageOptions.target = e.parent(), alsernetview.zoomImageOptions.url = e.attr("data-zoom-image"), e.zoom(alsernetview.zoomImageOptions), alsernetview.zoomImageObjects.push(e)
            }))
        }, alsernetview.zoomImageOnResize = function() {
            alsernetview.zoomImageObjects.forEach((function(e) {
                e.each((function() {
                    var e = t(this).data("zoom");
                    e && e.refresh()
                }))
            }))
        }, alsernetview.lazyLoad = function(t, e) {
            function i() {
                this.setAttribute("src", this.getAttribute("data-src")), this.addEventListener("load", (function() {
                    this.style["padding-top"] = "", this.classList.remove("lazy-img")
                }))
            }
            alsernetview.$(t).find(".lazy-img").each((function() {
                void 0 !== e && e ? i.call(this) : alsernetview.appear(this, i)
            }))
        }, alsernetview.initNotificationAlert = function() {
            alsernetview.$body.hasClass("has-notification") && setTimeout((function() {
                alsernetview.$body.addClass("show-notification")
            }), 5e3)
        }, alsernetview.countTo = function(e) {
            t.fn.countTo && alsernetview.$(e).each((function() {
                alsernetview.appear(this, (function() {
                    var e = t(this);
                    setTimeout((function() {
                        e.countTo({
                            onComplete: function() {
                                e.addClass("complete")
                            }
                        })
                    }), 300)
                }))
            }))
        }, alsernetview.minipopupOption = {
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
            template: '<div class="minipopup-box"><div class="product product-list-sm {{productClass}}"><figure class="product-media"><a href="{{imageLink}}"><img src="{{imageSrc}}" alt="Product" width="80" height="90" /></a></figure><div class="product-details"><h4 class="product-name"><a href="{{nameLink}}">{{name}}</a></h4>{{message}}</div></div><div class="product-action">{{actionTemplate}}</div></div>'
        }, alsernetview.Minipopup = (i = 0, a = [], n = !1, o = [], s = !1, r = function() {
            if (!n)
                for (var t = 0; t < o.length; ++t)(o[t] -= 200) <= 0 && this.close(t--)
        }, {
            init: function() {
                var i = document.createElement("div");
                i.className = "minipopup-area", alsernetview.byClass("page-wrapper")[0].appendChild(i), e = t(i), this.close = this.close.bind(this), r = r.bind(this)
            },
            open: function(n, l) {
                var c, d = this,
                    p = t.extend(!0, {}, alsernetview.minipopupOption, n);
                c = t(alsernetview.parseTemplate(p.template, p)), d.space = p.space;
                var u = c.appendTo(e).css("top", -i).find("img");
                u.length && u.on("load", (function() {
                    i += c[0].offsetHeight + d.space, c.addClass("show"), c.offset().top - window.pageYOffset < 0 && (d.close(), c.css("top", -i + c[0].offsetHeight + d.space)), c.on("mouseenter", (function() {
                        d.pause()
                    })).on("mouseleave", (function() {
                        d.resume()
                    })).on("touchstart", (function(t) {
                        d.pause(), t.stopPropagation()
                    })).on("mousedown", (function() {
                        t(this).addClass("focus")
                    })).on("mouseup", (function() {
                        d.close(t(this).index())
                    })), alsernetview.$body.on("touchstart", (function() {
                        d.resume()
                    })), a.push(c), o.length || (s = setInterval(r, 200)), o.push(p.delay), l && l(c)
                }))
            },
            close: function(t) {
                var e = void 0 === t ? 0 : t,
                    n = a.splice(e, 1)[0];
                o.splice(e, 1)[0];
                var r = n[0].offsetHeight;
                i -= r + this.space, n.removeClass("show"), setTimeout((function() {
                    n.remove()
                }), 300), a.forEach((function(t, i) {
                    i >= e && t.hasClass("show") && t.stop(!0, !0).animate({
                        top: parseInt(t.css("top")) + r + 20
                    }, 600, "easeOutQuint")
                })), a.length || clearTimeout(s)
            },
            pause: function() {
                n = !0
            },
            resume: function() {
                n = !1
            }
        }), alsernetview.headerToggleSearch = function(t) {
            var e = alsernetview.$(t);
            alsernetview.$body.on("click", ".hs-toggle .search-toggle", (function(t) {
                t.preventDefault()
            })), "ontouchstart" in document ? (e.find(".search-toggle").on("click", (function(t) {
                e.toggleClass("show")
            })), alsernetview.$body.on("click", (function(t) {
                e.removeClass("show")
            })), e.on("click", (function(t) {
                t.preventDefault(), t.stopPropagation()
            }))) : e.find(".form-control").on("focusin", (function(t) {
                e.addClass("show")
            })).on("focusout", (function(t) {
                e.removeClass("show")
            }))
        }, alsernetview.scrollTo = function(e, i) {
            var a = void 0 === i ? 0 : i;
            if ("number" == typeof e) o = e;
            else {
                var n = alsernetview.$(e);
                if (!n.length || "none" == n.css("display")) return;
                var o = n.offset().top,
                    s = t("#wp-toolbar");
                window.innerWidth > 600 && s.length && (o -= s.parent().outerHeight()), t(".sticky-content.fix-top.fixed").each((function() {
                    o -= this.offsetHeight
                }))
            }
            t("html,body").stop().animate({
                scrollTop: o
            }, a)
        }
    }(jQuery),
    function(t) {
        function e(t, e) {
            return this.init(t, e)
        }
        var i = function(t) {
                var e = this.wrapperEl,
                    i = e.getAttribute("class");
                if (i.match(/row|gutter\-\w\w|cols\-\d|cols\-\w\w-\d/g) && e.setAttribute("class", i.replace(/row|gutter\-\w\w|cols\-\d|cols\-\w\w-\d/g, "").replace(/\s+/, " ")), e.classList.contains("animation-slider"))
                    for (var a = e.children, n = a.length, o = 0; o < n; ++o) a[o].setAttribute("data-index", o + 1)
            },
            a = function(t) {
                var e, i = this.firstElementChild.firstElementChild.children,
                    a = i.length;
                for (e = 0; e < a; ++e)
                    if (!i[e].classList.contains("active")) {
                        var n, o = alsernetview.byClass("appear-animate", i[e]);
                        for (n = o.length - 1; n >= 0; --n) o[n].classList.remove("appear-animate")
                    }
            },
            n = function(e) {
                t(window).trigger("appear.check");
                var i = t(e.currentTarget),
                    a = i.find(".swiper-slide.active video");
                i.find(".swiper-slide:not(.swiper-slide-active) video").each((function() {
                    this.paused || i.trigger("autoplayStart"), this.pause(), this.currentTime = 0
                })), a.length && (!0 === i.data("slider").options.autoplay && i.trigger("autoplayStop"), a.each((function() {
                    this.paused && this.play()
                })))
            },
            o = function() {
                var e = this;
                t(this.wrapperEl).find(".swiper-slide-active .slide-animate").each((function() {
                    var i = t(this),
                        a = t.extend(!0, {}, alsernetview.animationOptions, alsernetview.parseOptions(i.data("animation-options"))),
                        n = a.duration,
                        o = a.delay,
                        s = a.name;
                    setTimeout((function() {
                        if (i.css("animation-duration", n), i.css("animation-delay", o), i.addClass(s), i.hasClass("maskLeft")) {
                            i.css("width", "fit-content");
                            var t = i.width();
                            i.css("width", 0).css("transition", "width " + (n || "0.75s") + " linear " + (o || "0s")), i.css("width", t)
                        }
                        n = n || "0.75s";
                        var a = alsernetview.requestTimeout((function() {
                            i.addClass("show-content")
                        }), o ? 1e3 * Number(o.slice(0, -1)) + 200 : 200);
                        e.timers.push(a)
                    }), 300)
                }))
            },
            s = function(e) {
                t(this.wrapperEl).find(".swiper-slide-active .slide-animate").each((function() {
                    var e = t(this);
                    e.addClass("show-content"), e.attr("style", "")
                }))
            },
            r = function(e) {
                var i = this,
                    a = t(this.wrapperEl);
                i.translateFlag = 1, i.prev = i.next, a.find(".swiper-slide .slide-animate").each((function() {
                    var e = t(this),
                        i = t.extend(!0, {}, alsernetview.animationOptions, alsernetview.parseOptions(e.data("animation-options")));
                    e.removeClass(i.name)
                }))
            },
            l = function(e) {
                var i = this,
                    a = t(this.wrapperEl);
                if (1 == i.translateFlag) {
                    if (i.next = this.slider.activeIndex, a.find(".show-content").removeClass("show-content"), i.prev != i.next) {
                        if (a.find(".show-content").removeClass("show-content"), a.hasClass("animation-slider")) {
                            for (var n = 0; n < i.timers.length; n++) alsernetview.deleteTimeout(i.timers[n]);
                            i.timers = []
                        }
                        a.find(".swiper-slide-active .slide-animate").each((function() {
                            var e = t(this),
                                a = t.extend(!0, {}, alsernetview.animationOptions, alsernetview.parseOptions(e.data("animation-options"))),
                                n = a.duration,
                                o = a.delay,
                                s = a.name;
                            e.css("animation-duration", n), e.css("animation-delay", o), e.css("transition-property", "visibility, opacity"), e.css("transition-delay", o), e.css("transition-duration", n), e.addClass(s), n = n || "0.75s";
                            var r = alsernetview.requestTimeout((function() {
                                e.css("transition-property", ""), e.css("transition-delay", ""), e.css("transition-duration", ""), e.addClass("show-content"), i.timers.splice(i.timers.indexOf(r), 1)
                            }), o ? 1e3 * Number(o.slice(0, -1)) + 500 * Number(n.slice(0, -1)) : 500 * Number(n.slice(0, -1)));
                            i.timers.push(r)
                        }))
                    } else a.find(".swiper-slide").eq(this.slider.activeIndex).find(".slide-animate").addClass("show-content");
                    i.translateFlag = 0
                }
            };
        e.defaults = {
            slidesPerView: 1,
            speed: 300
        }, e.presets = {
            "product-thumbs-wrap": {
                slidesPerView: 4,
                spaceBetween: 10,
                freeMode: !0,
                watchSlidesVisibility: !0,
                watchSlidesProgress: !0,
                freeModeSticky: !0
            }
        }, e.prototype.init = function(c, d) {
            this.timers = [], this.translateFlag = 0, this.prev = 0, this.next = 0, this.container = c[0], this.wrapperEl = c.children()[0];
            var p = c.children(".swiper-button-next"),
                u = c.children(".swiper-button-prev"),
                m = c.children(".swiper-pagination"),
                h = c.children(".custom-dots");
            if (!c.data("slider")) {
                alsernetview.lazyLoad(c, !0);
                var f = c.attr("class").split(" "),
                    g = t.extend(!0, {}, e.defaults);
                f.forEach((function(i) {
                    var a = e.presets[i];
                    a && t.extend(!0, g, a)
                })), p.length && t.extend(!0, g, {
                    navigation: {
                        nextEl: p[0]
                    }
                }), u.length && t.extend(!0, g, {
                    navigation: {
                        prevEl: u[0]
                    }
                }), m.length && t.extend(!0, g, {
                    pagination: {
                        el: m[0],
                        clickable: !0
                    }
                }), c.find("video").each((function() {
                    this.loop = !1
                })), t.extend(!0, g, alsernetview.parseOptions(c.attr("data-swiper-options")), d), i.call(this), this.slider = new Swiper(this.container, g), c.data("slider", this.slider), c.trigger("initialized.slider", this.slider), this.slider.on("afterInit", a).on("transitionEnd", n), c.hasClass("animation-slider") && o.call(this), c.hasClass("animation-slider") && this.slider.on("resize", s).on("transitionStart", r.bind(this)).on("transitionEnd", l.bind(this)), h.length && (this.slider.on("transitionEnd", (function() {
                    var t = this.activeIndex;
                    h.children("a:nth-child(" + ++t + ")").addClass("active").siblings().removeClass("active")
                })), h.children("a").on("click", (function(e) {
                    e.preventDefault();
                    var i = t(this);
                    if (!i.hasClass("active")) {
                        var a = i.index();
                        i.closest(".swiper-container").data("slider").slideTo(a), i.addClass("active").siblings().removeClass("active")
                    }
                })))
            }
        }, alsernetview.slider = function(i, a = {}, n = !1) {
            alsernetview.$(i).each((function() {
                var i = t(this);
                n ? new e(i, a) : alsernetview.call((function() {
                    new e(i, a)
                }))
            }))
        }, alsernetview.slider.pgToggle = function() {
            t(".swiper-container:not([class*='pg-']) .swiper-pagination").each((function() {
                var e = t(this);
                e.find("*").length <= 1 ? e.css("display", "none") : e.css("display", "block")
            }))
        }
    }(jQuery),
    function(t) {
        function e(t) {
            return this.init(t)
        }
        var i = function() {
            window.innerWidth < 992 && (this.$sidebar.find(".sidebar-content").removeAttr("style"), this.$sidebar.find(".sidebar-content").attr("style", ""), this.$sidebar.find(".toolbox").children(":not(:first-child)").removeAttr("style"))
        };
        e.prototype.init = function(e) {
            var a = this;
            return a.name = e, a.$sidebar = t("." + e), a.isNavigation = !1, a.$sidebar.length && (a.isNavigation = a.$sidebar.hasClass("sidebar-fixed") && a.$sidebar.parent().hasClass("toolbox-wrap"), a.isNavigation && (i = i.bind(this), alsernetview.$window.on("resize", i)), alsernetview.$window.on("resize", (function(t) {
                alsernetview.windowResized(t.timeStamp) && alsernetview.$body.removeClass(e + "-active")
            })), a.$sidebar.find(".sidebar-toggle, .sidebar-toggle-btn").add("sidebar" === e ? ".left-sidebar-toggle" : "." + e + "-toggle").on("click", (function(e) {
                a.toggle(), t(this).blur(), e.preventDefault()
            })), a.$sidebar.find(".sidebar-overlay, .sidebar-close").on("click", (function(t) {
                alsernetview.$body.removeClass(e + "-active"), t.preventDefault()
            }))), !1
        }, e.prototype.toggle = function() {
            var e = this,
                i = 992;
            if (e.$sidebar.hasClass("sidebar-switch-xl") && (i = 1200), window.innerWidth >= i && e.$sidebar.hasClass("sidebar-fixed")) {
                var a = e.$sidebar.hasClass("closed");
                if (e.isNavigation && (a || e.$sidebar.find(".filter-clean").hide(), e.$sidebar.siblings(".toolbox").children(":not(:first-child)").fadeToggle("fast"), e.$sidebar.find(".sidebar-content").stop().animate({
                    height: "toggle",
                    "margin-bottom": a ? "toggle" : -6
                }, (function() {
                    t(this).css("margin-bottom", ""), a && e.$sidebar.find(".filter-clean").fadeIn("fast")
                }))), e.$sidebar.hasClass("shop-sidebar")) {
                    var n = t(".main-content .product-wrapper");
                    n.length && n.hasClass("product-lists") && n.toggleClass("row cols-xl-2", !a)
                }
            } else e.$sidebar.find(".sidebar-overlay .sidebar-close").css("margin-left", -(window.innerWidth - document.body.clientWidth)), alsernetview.$body.toggleClass(e.name + "-active").removeClass("closed");
            setTimeout((function() {
                t(window).trigger("appear.check")
            }), 400)
        }, alsernetview.sidebar = function(t) {
            return (new e).init(t)
        }
    }(jQuery),
    function(t) {
        function e(t) {
            return this.init(t)
        }
        var i = function() {
                this.$wrapper.find(".product-details").css("height", window.innerWidth > 767 ? this.$wrapper.find(".product-gallery")[0].clientHeight : "")
            },
            a = function(e) {
                var i = t(this);
                i.hasClass("added") || (e.preventDefault(), i.addClass("load-more-overlay loading"), setTimeout((function() {
                    i.removeClass("load-more-overlay loading").toggleClass("w-icon-heart").toggleClass("w-icon-heart-full").addClass("added").attr("href", "wishlist.html")
                }), 500))
            },
            n = function(e) {
                e.preventDefault(), alsernetview.scrollTo(t('.product-tabs > .nav a[href="' + this.getAttribute("href") + '"]').trigger("click"))
            };
        e.prototype.init = function(e) {
            var o = this,
                s = e.find(".product-single-swiper");
            o.$wrapper = e, o.isQuickView = !!e.closest(".mfp-content").length, o._isPgVertical = !1, o.isQuickView && (i = i.bind(this), alsernetview.ratingTooltip()),
                function(t) {
                    t.$thumbs = t.$wrapper.find(".product-thumbs"), t.$thumbsWrap = t.$thumbs.parent(), t.$thumbUp = t.$thumbsWrap.find(".thumb-up"), t.$thumbDown = t.$thumbsWrap.find(".thumb-down"), t.$thumbsDots = t.$thumbs.children(), t.thumbsCount = t.$thumbsDots.length, t.$productThumb = t.$thumbsDots.eq(0), t._isPgVertical = t.$thumbsWrap.parent().hasClass("product-gallery-vertical"), t.thumbsIsVertical = t._isPgVertical && window.innerWidth >= 992, alsernetview.slider(t.$thumbsWrap, {}, !0)
                }(o), document.body.classList.contains("home") || s.parent().hasClass("product-gallery-video") && (o.isQuickView || s.append('<a href="#" class="product-gallery-btn product-degree-viewer" title="Product 360 Degree Gallery"><i class="w-icon-rotate-3d"></i></a>'), o.isQuickView || s.append('<a href="#" class="product-gallery-btn product-video-viewer" title="Product Video Thumbnail"><i class="w-icon-movie"></i></a>')), o.$wrapper.on("click", ".btn-wishlist", a), o.$wrapper.on("click", ".rating-reviews", n), "complete" === alsernetview.status && (alsernetview.slider(s, {
                thumbs: {
                    swiper: o.$thumbsWrap.data("slider")
                }
            })), s.length && window.addEventListener("resize", (function() {
                alsernetview.requestTimeout((function() {
                    null != s.data("slider") && (s.data("slider").update(), o.$thumbsWrap.data("slider").update())
                }), 100)
            }), {
                passive: !0
            }), o.$wrapper.find(".product-single-swiper").on("initialized.slider", (function(e) {
                t(e.target).find(".product-image").zoom(alsernetview.zoomImageOptions)
            })), o.$wrapper.find(".product-thumbs-sticky").length && (o.isStickyScrolling = !1, o.$wrapper.on("click", ".product-thumb:not(.active)", o.clickStickyThumbnail.bind(this)), window.addEventListener("scroll", o.scrollStickyThumbnail.bind(this), {
                passive: !0
            })),
                function(e) {
                    e.$selects = e.$wrapper.find(".product-variations select"), e.$items = e.$wrapper.find(".product-variations"), e.$priceWrap = e.$wrapper.find(".product-variation-price"), e.$clean = e.$wrapper.find(".product-variation-clean"), e.$btnCart = e.$wrapper.find(".btn-cart"), e.variationCheck(), e.$selects.on("change", (function(t) {
                        e.variationCheck()
                    })), e.$items.children("a").on("click", (function(i) {
                        t(this).toggleClass("active").siblings().removeClass("active"), i.preventDefault(), e.variationCheck(), e.$items.parent(".product-image-swatch") && e.swatchImage()
                    })), e.$clean.on("click", (function(t) {
                        t.preventDefault(), e.variationClean(!0)
                    }))
                }(this)
        }, e.prototype.variationCheck = function() {
            var e = this,
                i = !0;
            e.$selects.each((function() {
                return this.value || (i = !1)
            })), e.$items.each((function() {
                var e = t(this);
                if (e.children("a:not(.size-guide)").length) return e.children(".active").length || (i = !1)
            })), i ? e.variationMatch() : e.variationClean()
        }, e.prototype.variationMatch = function() {
            var t = this;
            t.$priceWrap.find("span").text("$" + (Math.round(50 * Math.random()) + 200) + ".00"), t.$priceWrap.slideDown(), t.$clean.slideDown(), t.$btnCart.removeClass("disabled")
        }, e.prototype.variationClean = function(t) {
            t && this.$selects.val(""), t && this.$items.children(".active").removeClass("active"), this.$priceWrap.slideUp(), this.$clean.css("display", "none"), this.$btnCart.addClass("disabled")
        }, e.prototype.clickStickyThumbnail = function(e) {
            var i = this,
                a = t(e.currentTarget),
                n = (a.parent().children(".active").index(), a.index() + 1);
            a.addClass("active").siblings(".active").removeClass("active"), this.isStickyScrolling = !0;
            var o = a.closest(".product-thumbs-sticky").find(".product-image-wrapper > :nth-child(" + n + ")");
            o.length && (o = o.offset().top + 10, alsernetview.scrollTo(o, 500)), setTimeout((function() {
                i.isStickyScrolling = !1
            }), 300)
        }, e.prototype.scrollStickyThumbnail = function() {
            var e = this;
            this.isStickyScrolling || e.$wrapper.find(".product-image-wrapper .product-image").each((function() {
                if (alsernetview.isOnScreen(this)) return e.$wrapper.find(".product-thumbs > :nth-child(" + (t(this).index() + 1) + ")").addClass("active").siblings().removeClass("active"), !1
            }))
        }, e.prototype.swatchImage = function() {
            var t = this.$items.find(".active img").attr("src"),
                e = this.$wrapper.find(".swiper-slide:first-child .product-image img"),
                i = this.$wrapper.find(".swiper-slide:first-child .product-thumb img");
            e.attr("src", t), i.attr("src", t)
        }, alsernetview.productSingle = function(i) {
            return alsernetview.$(i).each((function() {
                var i = t(this);
                i.is("body > *") || i.data("product-single", new e(i))
            })), null
        }
    }(jQuery),
    function(t) {
        function e(e) {
            e.preventDefault();
            var i, a, n = t(e.currentTarget),
                o = n.closest(".product-single"),
                s = n.closest(".review-image");
            if ((i = n.closest(".review-image").length ? n.closest(".review-image").find("img") : o.find(".product-single-swiper").length ? o.find(".product-single-swiper .swiper-slide:not(.cloned) img:first-child") : o.find(".product-gallery-carousel").length ? o.find(".product-gallery-carousel .swiper-slide:not(.cloned) img") : o.find(".product-image img:first-child")).length) {
                a = i.map((function() {
                    var e = t(this);
                    return {
                        src: e.attr("data-zoom-image"),
                        w: 800,
                        h: 900,
                        title: e.attr("alt")
                    }
                })).get();
                var r = o.find(".product-single-swiper").data("slider"),
                    l = r ? r.activeIndex : o.find(".product-gallery .product-gallery-btn").index(n);
                if (1 == s.length) l = s.find("img").index(n);
                if ("undefined" != typeof PhotoSwipe) {
                    var c = t(".pswp")[0];
                    if ("rtl" == alsernetview.$body.attr("dir")) var d = new PhotoSwipe(c, PhotoSwipeUI_Default, a, {
                        index: l,
                        closeOnScroll: !1,
                        showAnimationDuration: 0,
                        rtl: !0
                    });
                    else d = new PhotoSwipe(c, PhotoSwipeUI_Default, a, {
                        index: l,
                        closeOnScroll: !1,
                        showAnimationDuration: 0
                    });
                    d.init(), alsernetview.photoSwipe = d
                }
            }
        }

        function i(t) {
            t.preventDefault(), alsernetview.popup({
                items: {
                    src: '<video src="assets/video/memory-of-a-woman.mp4" autoplay loop controls>',
                    type: "inline"
                },
                mainClass: "mfp-video-popup"
            }, "video")
        }

        function a(e) {
            var i = t(this);
            i.addClass("active").siblings().removeClass("active"), i.parent().addClass("selected"), i.closest(".rating-form").find("select").val(i.text()), e.preventDefault()
        }

        function n(e) {
            var i = t(this),
                a = t(".main-content > .alert, .container > .alert");
            if (i.hasClass("disabled")) alert("Please select some product options before adding this product to your cart.");
            else {
                if (a.length) a.fadeOut((function() {
                    a.fadeIn()
                }));
                else {
                    var n = '<div class="alert alert-success alert-cart-product mb-2">                            <a href="cart.html" class="btn btn-success btn-rounded">View Cart</a>                            <p class="mb-0 ls-normal">“' + i.closest(".product-single").find(".product-title").text() + '” has been added to your cart.</p>                            <a href="#" class="btn btn-link btn-close" aria-label="button"><i class="close-icon"></i></a>                            </div>';
                    i.closest(".product-single").before(n)
                }
                t(".product-sticky-content").trigger("recalc.pin")
            }
        }
        alsernetview.initProductSinglePage = function() {
            console.log("initProductSinglePage");
            t(".product-gallery").each((function() {
                var e = t(this),
                    i = e.find(".product-image");
                i.length && 0 == e.find(".swiper-container").length && i.zoom(alsernetview.zoomImageOptions)
            })),
                function(e) {
                    var i = t(e),
                        a = i.closest(".product-single"),
                        n = '<div class="product product-list-sm mr-auto">                                        <figure class="product-media">                                        <img src="' + a.find(".product-image img").eq(0).attr("src") + '" alt="Product" width="85" height="85" />                                        </figure>                                        <div class="product-details pt-0 pl-2 pr-2">                                        <h4 class="product-name font-weight-normal mb-1">' + a.find(".product-details .product-title").text() + '</h4>                                        <div class="product-price mb-0">                                        <ins class="new-price">' + a.find(".new-price").text() + '</ins><del class="old-price">' + a.find(".old-price").text() + "</del></div>                                        </div></div>";

                    function o() {
                        i.hasClass("fix-top") && window.innerWidth > 767 && i.removeClass("fix-top").addClass("fix-bottom"), i.hasClass("fix-bottom") && window.innerWidth > 767 || (i.hasClass("fix-bottom") && window.innerWidth < 768 && i.removeClass("fix-bottom").addClass("fix-top"), i.hasClass("fix-top") && window.innerWidth)
                    }
                    i.find(".product-qty-form").before(n), window.addEventListener("resize", o, {
                        passive: !0
                    }), o()
                }(".product-sticky-content"), document.body.classList.contains("home") || alsernetview.$body.on("click", ".product-image-full", e).on("click", ".review-image img", e).on("click", ".product-video-viewer", i).on("click", ".product-degree-viewer", (function(e) {
                e.preventDefault(e), t.fn.ThreeSixty && function(t) {
                    t.preventDefault(), alsernetview.popup({
                        type: "inline",
                        mainClass: "product-popupbox wm-fade product-360-popup",
                        preloader: !1,
                        items: {
                            src: '<div class="product-gallery-degree">\t\t\t\t\t\t<div class="w-loading"><i></i></div>\t\t\t\t\t\t<ul class="product-degree-images"></ul>\t\t\t\t\t</div>'
                        },
                        callbacks: {
                            open: function() {
                                this.container.find(".product-gallery-degree").ThreeSixty({
                                    imagePath: "assets/images/products/video/",
                                    filePrefix: "360-",
                                    ext: ".jpg",
                                    totalFrames: 18,
                                    endFrame: 18,
                                    currentFrame: 1,
                                    imgList: this.container.find(".product-degree-images"),
                                    progress: ".w-loading",
                                    height: 500,
                                    width: 830,
                                    navigation: !0
                                })
                            },
                            beforeClose: function() {
                                this.container.empty()
                            }
                        }
                    })
                }(e)
            })).on("click", ".rating-form .rating-stars > a", a).on("click", ".product-single:not(.product-popup) .btn-cart", n)
        }
    }(jQuery),
    jQuery,
    alsernetview.initLayout = function() {
        alsernetview.isotopes(".grid:not(.grid-float)"),
        alsernetview.stickySidebar(".sticky-sidebar")
    },
    alsernetview.init = function() {
        alsernetview.stickyContent(".sticky-toolbox", alsernetview.stickyToolboxOptions),
        alsernetview.stickyContent(".product-sticky-content", alsernetview.stickyProductOptions),
        alsernetview.parallax(".parallax"),
        alsernetview.initFloatingParallax(),
        alsernetview.alert(".alert"),
        alsernetview.productSingle(".product-single"), 
        alsernetview.initProductSinglePage(),
        alsernetview.slider(".swiper-container"),
        alsernetview.Minipopup.init(),
        alsernetview.initPopup(),
        alsernetview.call(alsernetview.slider.pgToggle),
        alsernetview.$window.on("resize", (function() {
            alsernetview.call(alsernetview.slider.pgToggle)
        }))
    },
    jQuery,
    window.onload = function() {
            alsernetview.canvasWidth = window.innerWidth,
            alsernetview.resizeTimeStamp = 0,
            alsernetview.resizeChanged = !1,
            alsernetview.status = "loaded",
            document.body.classList.add("loaded"),
            alsernetview.call(alsernetview.initLayout),
            alsernetview.call(alsernetview.init),
            alsernetview.status = "complete",
            alsernetview.$window.trigger("alsernetview_complete")
    };