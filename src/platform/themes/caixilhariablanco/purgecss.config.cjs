const { PurgeCSS } = require('purgecss');
const fs = require('fs');
const path = require('path');

const themeDir = __dirname;
const cssDir = path.join(themeDir, 'public', 'css');

// Safelist extensa para Bootstrap + clases dinámicas del tema
const bootstrapSafelist = [
  // Bootstrap grid
  /^col-/, /^row/, /^container/, /^container-fluid/,
  // Display
  /^d-/, /^d-none/, /^d-block/, /^d-flex/, /^d-inline/, /^d-grid/,
  /^d-sm-/, /^d-md-/, /^d-lg-/, /^d-xl/, /^d-xxl/,
  // Flex
  /^flex-/, /^justify-content-/, /^align-items-/, /^align-self-/,
  /^order-/, /^gap-/, /^row-cols-/,
  // Spacing
  /^m-/, /^mt-/, /^mb-/, /^ms-/, /^me-/, /^mx-/, /^my-/, /^p-/, /^pt-/, /^pb-/, /^ps-/, /^pe-/, /^px-/, /^py-/,
  /^m-sm-/, /^m-md-/, /^m-lg-/, /^p-sm-/, /^p-md-/, /^p-lg-/,
  // Text
  /^text-/, /^text-start/, /^text-center/, /^text-end/, /^fw-/, /^fs-/, /^fst-/, /^lh-/, /^text-wrap/, /^text-break/,
  // Background & Colors
  /^bg-/, /^text-bg-/, /^bg-opacity-/, /^text-opacity-/,
  // Border
  /^border/, /^rounded/,
  // Position
  /^position-/, /^top-/, /^bottom-/, /^start-/, /^end-/, /^translate-middle/,
  // Sizing
  /^w-/, /^h-/, /^min-vw-/, /^min-vh-/, /^vw-/, /^vh-/, /^max-w-/, /^max-h-/,
  // Overflow
  /^overflow-/, /^visible/, /^invisible/,
  // Shadow
  /^shadow/,
  // Opacity
  /^opacity-/,
  // Z-index
  /^z-/,
  // Float
  /^float-/, /^clearfix/,
  // Visibility
  /^visible/, /^invisible/,
  // Print
  /^d-print-/,
  // Tables
  /^table/, /^caption-/,
  // Forms
  /^form-/, /^form-control/, /^form-select/, /^form-check/, /^form-range/, /^input-group/, /^btn/, /^btn-group/,
  // Alerts
  /^alert/, /^alert-/, /^alert-link/,
  // Badges
  /^badge/, /^badge-/,
  // Breadcrumb
  /^breadcrumb/, /^breadcrumb-item/,
  // Cards
  /^card/, /^card-/, /^list-group/, /^list-group-item/,
  // Dropdowns
  /^dropdown/, /^dropdown-/, /^dropup/, /^dropend/, /^dropstart/,
  // Modal
  /^modal/, /^modal-/, /^modal-backdrop/, /^modal-dialog/,
  // Nav / Navbar
  /^nav/, /^nav-/, /^navbar/, /^navbar-/, /^nav-tabs/, /^nav-pills/,
  // Pagination
  /^page-/, /^pagination/,
  // Progress
  /^progress/, /^progress-bar/,
  // Spinners
  /^spinner/, /^spinner-/,
  // Toast / Tooltip / Popover
  /^toast/, /^tooltip/, /^popover/,
  // Carousel
  /^carousel/, /^carousel-/, /^carousel-item/,
  // Offcanvas
  /^offcanvas/, /^offcanvas-/,
  // Accordion
  /^accordion/, /^accordion-/, /^accordion-button/, /^accordion-collapse/,
  // Placeholder
  /^placeholder/, /^placeholder-/,
  // Ratio
  /^ratio/, /^ratio-/,
  // Stacks
  /^hstack/, /^vstack/,
  // Sticky
  /^sticky-top/,
];

const themeSafelist = [
  // OwlCarousel
  /^owl-/, /^owl-carousel/, /^owl-stage/, /^owl-item/, /^owl-nav/, /^owl-dots/, /^owl-dot/, /^active/,
  // Swiper
  /^swiper/, /^swiper-/, /^swiper-slide/, /^swiper-pagination/, /^swiper-button/,
  // AOS
  /^aos-/, /^data-aos/, /^fade-/, /^zoom-/, /^slide-/, /^flip-/, /^flip-x/, /^flip-y/,
  // Animate.css
  /^animated/, /^fadeIn/, /^fadeOut/, /^bounce/, /^slideIn/, /^slideOut/, /^zoomIn/, /^zoomOut/,
  /^pulse/, /^shake/, /^headShake/, /^swing/, /^tada/, /^wobble/, /^jello/, /^heartBeat/,
  // SlickNav
  /^slicknav/, /^slicknav-/, /^slicknav_menu/, /^slicknav_btn/, /^slicknav_icon/,
  // Slick slider
  /^slick-/, /^slick/, /^slick-list/, /^slick-track/, /^slick-slide/, /^slick-active/,
  // Magnific Popup
  /^mfp-/, /^mfp/, /^mfp-bg/, /^mfp-wrap/, /^mfp-container/, /^mfp-content/, /^mfp-close/,
  // Wow
  /^wow/, /^fadeIn/, /^fadeInUp/, /^fadeInDown/, /^fadeInLeft/, /^fadeInRight/,
  // FontAwesome
  /^fa-/, /^fas/, /^far/, /^fal/, /^fad/, /^fab/, /^fa-solid/, /^fa-regular/, /^fa-brands/,
  /^fa-arrow/, /^fa-angle/, /^fa-check/, /^fa-star/, /^fa-phone/, /^fa-envelope/, /^fa-clock/,
  /^fa-location/, /^fa-search/, /^fa-magnifying-glass/, /^fa-bars/, /^fa-times/, /^fa-xmark/,
  /^fa-user/, /^fa-calendar/, /^fa-eye/, /^fa-facebook/, /^fa-twitter/, /^fa-linkedin/,
  /^fa-quote/, /^fa-play/, /^fa-pause/, /^fa-chevron/, /^fa-bolt/, /^fa-message/,
  /^fa-circle/, /^fa-exclamation/, /^fa-check-circle/, /^fa-question/, /^fa-list/,
  /^fa-file/, /^fa-pen/, /^fa-copy/, /^fa-magnifying-glass/, /^fa-circle-info/,
  /^fa-question-circle/, /^fa-user-secret/, /^fa-clock/, /^fa-lightbulb/,
  /^fa-bolt/, /^fa-message/, /^fa-list-check/, /^fa-exclamation-triangle/,
  /^fa-file-pen/, /^fa-circle-check/, /^fa-clipboard/, /^fa-circle-exclamation/,
  /^fa-arrow-right/, /^fa-arrow-left/, /^fa-angle-up/, /^fa-angle-down/,
  /^fa-star/, /^fa-eye/, /^fa-user/, /^fa-calendar/, /^fa-clock/,
  // Theme classes
  /^header-/, /^hero/, /^footer/, /^slider-/, /^btn-/, /^theme-/, /^progress-/, /^preloader/,
  /^section-/, /^about-/, /^service-/, /^contact-/, /^testimonial-/, /^faq-/, /^blog-/, /^post-/,
  /^project-/, /^team-/, /^gallery-/, /^work-/, /^case-/, /^cta-/, /^estimate-/, /^pqrsf/,
  /^tracking-/, /^timeline-/, /^response-/, /^file-/, /^submit-/, /^success-/, /^radicado/,
  /^gi-/, /^review-/, /^lightbox-/, /^line-clamp/, /^text-brand/, /^text-main-color/,
  /^fs-/, /^rounded-/, /^h-/, /^z-/, /^bg-/, /^sticky-top-/, /^color-inherit/,
  /^tracking-/, /^pqrsf-/, /^success-/, /^next-step/, /^what-next/,
  // Select2
  /^select2/, /^select2-/, /^select2-container/,
  // Bootstrap JS components
  /^collapse/, /^collapsing/, /^show/, /^fade/, /^modal-backdrop/, /^modal-open/,
  /^dropdown-menu/, /^dropdown-toggle/, /^dropdown-item/,
  /^was-validated/, /^is-valid/, /^is-invalid/, /^invalid-feedback/, /^valid-feedback/,
  /^accordion-button/, /^accordion-collapse/, /^accordion-item/,
  /^tab-pane/, /^tab-content/, /^active/,
  // Progress bar
  /^progress/, /^progress-bar/, /^progress-bar-striped/, /^progress-bar-animated/,
  // Tooltip / Popover JS
  /^tooltip/, /^popover/, /^bs-tooltip/, /^bs-popover/,
  // Toast JS
  /^toast/, /^showing/, /^hide/,
  // General
  /^active$/, /^show$/, /^open$/, /^disabled$/, /^loaded$/, /^sticky$/,
  /^loading$/, /^loaded$/, /^lazy$/, /^lazyloaded$/, /^lazyloading$/,
  /^img-fluid$/, /^w-100$/, /^h-100$/, /^rounded$/, /^rounded-circle$/,
  /^d-block$/, /^d-flex$/, /^d-none$/, /^d-inline$/, /^d-inline-block$/,
  /^text-center$/, /^text-start$/, /^text-end$/, /^text-white$/, /^text-muted$/,
  /^text-danger$/, /^text-success$/, /^text-warning$/, /^text-info$/, /^text-primary$/, /^text-secondary$/,
  /^bg-white$/, /^bg-light$/, /^bg-dark$/, /^bg-primary$/, /^bg-secondary$/, /^bg-success$/, /^bg-danger$/, /^bg-warning$/, /^bg-info$/,
  /^p-0$/, /^m-0$/, /^px-0$/, /^py-0$/, /^mx-0$/, /^my-0$/, /^ms-0$/, /^me-0$/, /^mt-0$/, /^mb-0$/,
  /^p-1$/, /^p-2$/, /^p-3$/, /^p-4$/, /^p-5$/,
  /^m-1$/, /^m-2$/, /^m-3$/, /^m-4$/, /^m-5$/,
  /^gap-1$/, /^gap-2$/, /^gap-3$/, /^gap-4$/, /^gap-5$/,
  /^g-1$/, /^g-2$/, /^g-3$/, /^g-4$/, /^g-5$/,
  /^gx-1$/, /^gx-2$/, /^gx-3$/, /^gx-4$/, /^gx-5$/,
  /^gy-1$/, /^gy-2$/, /^gy-3$/, /^gy-4$/, /^gy-5$/,
  /^col$/, /^col-auto$/, /^col-sm$/, /^col-md$/, /^col-lg$/, /^col-xl$/, /^col-xxl$/,
  /^col-1$/, /^col-2$/, /^col-3$/, /^col-4$/, /^col-5$/, /^col-6$/, /^col-7$/, /^col-8$/, /^col-9$/, /^col-10$/, /^col-11$/, /^col-12$/,
  /^col-sm-1$/, /^col-sm-2$/, /^col-sm-3$/, /^col-sm-4$/, /^col-sm-5$/, /^col-sm-6$/, /^col-sm-7$/, /^col-sm-8$/, /^col-sm-9$/, /^col-sm-10$/, /^col-sm-11$/, /^col-sm-12$/,
  /^col-md-1$/, /^col-md-2$/, /^col-md-3$/, /^col-md-4$/, /^col-md-5$/, /^col-md-6$/, /^col-md-7$/, /^col-md-8$/, /^col-md-9$/, /^col-md-10$/, /^col-md-11$/, /^col-md-12$/,
  /^col-lg-1$/, /^col-lg-2$/, /^col-lg-3$/, /^col-lg-4$/, /^col-lg-5$/, /^col-lg-6$/, /^col-lg-7$/, /^col-lg-8$/, /^col-lg-9$/, /^col-lg-10$/, /^col-lg-11$/, /^col-lg-12$/,
  /^row$/, /^row-cols-1$/, /^row-cols-2$/, /^row-cols-3$/, /^row-cols-4$/, /^row-cols-5$/, /^row-cols-6$/,
  /^flex-row$/, /^flex-column$/, /^flex-wrap$/, /^flex-nowrap$/, /^flex-sm-row$/, /^flex-sm-column$/,
  /^justify-content-start$/, /^justify-content-end$/, /^justify-content-center$/, /^justify-content-between$/, /^justify-content-around$/, /^justify-content-evenly$/,
  /^align-items-start$/, /^align-items-end$/, /^align-items-center$/, /^align-items-baseline$/, /^align-items-stretch$/,
  /^align-self-start$/, /^align-self-end$/, /^align-self-center$/, /^align-self-baseline$/, /^align-self-stretch$/,
  /^position-static$/, /^position-relative$/, /^position-absolute$/, /^position-fixed$/, /^position-sticky$/,
  /^top-0$/, /^top-50$/, /^top-100$/, /^bottom-0$/, /^bottom-50$/, /^bottom-100$/, /^start-0$/, /^start-50$/, /^start-100$/, /^end-0$/, /^end-50$/, /^end-100$/,
  /^translate-middle$/, /^translate-middle-x$/, /^translate-middle-y$/,
  /^w-25$/, /^w-50$/, /^w-75$/, /^w-100$/, /^w-auto$/, /^mw-100$/, /^min-vw-100$/,
  /^h-25$/, /^h-50$/, /^h-75$/, /^h-100$/, /^h-auto$/, /^mh-100$/, /^min-vh-100$/,
  /^overflow-auto$/, /^overflow-hidden$/, /^overflow-visible$/, /^overflow-scroll$/,
  /^shadow-none$/, /^shadow-sm$/, /^shadow$/, /^shadow-lg$/,
  /^border$/, /^border-0$/, /^border-top$/, /^border-top-0$/, /^border-end$/, /^border-end-0$/, /^border-bottom$/, /^border-bottom-0$/, /^border-start$/, /^border-start-0$/,
  /^border-primary$/, /^border-secondary$/, /^border-success$/, /^border-danger$/, /^border-warning$/, /^border-info$/, /^border-light$/, /^border-dark$/, /^border-white$/,
  /^rounded$/, /^rounded-0$/, /^rounded-1$/, /^rounded-2$/, /^rounded-3$/, /^rounded-4$/, /^rounded-5$/, /^rounded-circle$/, /^rounded-pill$/,
  /^rounded-top$/, /^rounded-end$/, /^rounded-bottom$/, /^rounded-start$/,
  /^visible$/, /^invisible$/,
  /^z-0$/, /^z-1$/, /^z-2$/, /^z-3$/,
  /^stretched-link$/,
  /^vstack$/, /^hstack$/, /^vr$/,
  /^ratio$/, /^ratio-1x1$/, /^ratio-4x3$/, /^ratio-16x9$/, /^ratio-21x9$/,
  /^placeholder$/, /^placeholder-xs$/, /^placeholder-sm$/, /^placeholder-lg$/,
  /^placeholder-wave$/,
  /^clearfix$/,
  /^text-decoration-underline$/, /^text-decoration-line-through$/, /^text-decoration-none$/,
  /^text-lowercase$/, /^text-uppercase$/, /^text-capitalize$/,
  /^text-truncate$/, /^text-reset$/,
  /^font-monospace$/,
  /^user-select-all$/, /^user-select-auto$/, /^user-select-none$/,
  /^pe-none$/, /^pe-auto$/,
  /^pointer-events-none$/, /^pointer-events-auto$/,
  /^list-unstyled$/, /^list-inline$/, /^list-inline-item$/,
  /^initialism$/,
  /^figure$/, /^figure-img$/, /^figure-caption$/,
  /^img-thumbnail$/,
  /^caption-top$/,
  /^table-responsive$/, /^table-responsive-sm$/, /^table-responsive-md$/, /^table-responsive-lg$/, /^table-responsive-xl$/, /^table-responsive-xxl$/,
  /^form-label$/, /^col-form-label$/, /^col-form-label-lg$/, /^col-form-label-sm$/,
  /^form-text$/, /^form-control$/, /^form-control-plaintext$/, /^form-control-sm$/, /^form-control-lg$/,
  /^form-select$/, /^form-select-sm$/, /^form-select-lg$/,
  /^form-check$/, /^form-check-inline$/, /^form-check-input$/, /^form-check-label$/,
  /^form-switch$/, /^form-range$/, /^form-floating$/,
  /^input-group$/, /^input-group-text$/, /^input-group-sm$/, /^input-group-lg$/,
  /^valid-feedback$/, /^invalid-feedback$/, /^valid-tooltip$/, /^invalid-tooltip$/,
  /^is-valid$/, /^is-invalid$/,
  /^was-validated$/,
  /^btn$/, /^btn-sm$/, /^btn-lg$/,
  /^btn-primary$/, /^btn-secondary$/, /^btn-success$/, /^btn-danger$/, /^btn-warning$/, /^btn-info$/, /^btn-light$/, /^btn-dark$/,
  /^btn-outline-primary$/, /^btn-outline-secondary$/, /^btn-outline-success$/, /^btn-outline-danger$/, /^btn-outline-warning$/, /^btn-outline-info$/, /^btn-outline-light$/, /^btn-outline-dark$/,
  /^btn-link$/, /^btn-group$/, /^btn-group-sm$/, /^btn-group-lg$/, /^btn-toolbar$/, /^btn-group-vertical$/,
  /^dropdown-toggle$/, /^dropdown-toggle-split$/, /^dropdown-menu$/, /^dropdown-menu-start$/, /^dropdown-menu-end$/,
  /^dropdown-menu-sm-start$/, /^dropdown-menu-sm-end$/, /^dropdown-menu-md-start$/, /^dropdown-menu-md-end$/, /^dropdown-menu-lg-start$/, /^dropdown-menu-lg-end$/,
  /^dropdown-item$/, /^dropdown-item-text$/, /^dropdown-header$/, /^dropdown-divider$/,
  /^dropup$/, /^dropend$/, /^dropstart$/,
  /^dropdown-menu-dark$/,
  /^nav$/, /^nav-link$/, /^nav-tabs$/, /^nav-pills$/, /^nav-fill$/, /^nav-justified$/,
  /^tab-content$/, /^tab-pane$/,
  /^navbar$/, /^navbar-brand$/, /^navbar-nav$/, /^navbar-text$/, /^navbar-collapse$/, /^navbar-expand$/, /^navbar-expand-sm$/, /^navbar-expand-md$/, /^navbar-expand-lg$/, /^navbar-expand-xl$/, /^navbar-expand-xxl$/,
  /^navbar-toggler$/, /^navbar-toggler-icon$/, /^navbar-dark$/, /^navbar-light$/,
  /^card$/, /^card-body$/, /^card-title$/, /^card-subtitle$/, /^card-text$/, /^card-link$/, /^card-header$/, /^card-footer$/, /^card-img-overlay$/, /^card-img$/, /^card-img-top$/, /^card-img-bottom$/,
  /^card-group$/, /^card-deck$/, /^card-columns$/, /^card-border$/,
  /^accordion$/, /^accordion-item$/, /^accordion-header$/, /^accordion-button$/, /^accordion-body$/, /^accordion-collapse$/,
  /^breadcrumb$/, /^breadcrumb-item$/, /^breadcrumb-item-active$/,
  /^pagination$/, /^page-item$/, /^page-link$/, /^pagination-lg$/, /^pagination-sm$/,
  /^badge$/, /^rounded-pill$/,
  /^alert$/, /^alert-link$/, /^alert-heading$/, /^alert-dismissible$/, /^alert-dismissible fade show$/,
  /^progress$/, /^progress-bar$/, /^progress-bar-striped$/, /^progress-bar-animated$/,
  /^list-group$/, /^list-group-item$/, /^list-group-item-action$/, /^list-group-horizontal$/, /^list-group-horizontal-sm$/, /^list-group-horizontal-md$/, /^list-group-horizontal-lg$/, /^list-group-horizontal-xl$/, /^list-group-horizontal-xxl$/,
  /^list-group-item-primary$/, /^list-group-item-secondary$/, /^list-group-item-success$/, /^list-group-item-danger$/, /^list-group-item-warning$/, /^list-group-item-info$/, /^list-group-item-light$/, /^list-group-item-dark$/,
  /^list-group-flush$/, /^list-group-numbered$/,
  /^toast$/, /^toast-container$/, /^toast-header$/, /^toast-body$/,
  /^modal$/, /^modal-dialog$/, /^modal-dialog-centered$/, /^modal-dialog-scrollable$/,
  /^modal-content$/, /^modal-header$/, /^modal-title$/, /^modal-body$/, /^modal-footer$/, /^modal-backdrop$/, /^modal-static$/,
  /^modal-fullscreen$/, /^modal-fullscreen-sm-down$/, /^modal-fullscreen-md-down$/, /^modal-fullscreen-lg-down$/, /^modal-fullscreen-xl-down$/, /^modal-fullscreen-xxl-down$/,
  /^modal-sm$/, /^modal-lg$/, /^modal-xl$/,
  /^tooltip$/, /^tooltip-inner$/, /^bs-tooltip-auto$/,
  /^popover$/, /^popover-header$/, /^popover-body$/,
  /^carousel$/, /^carousel-inner$/, /^carousel-item$/, /^carousel-item-next$/, /^carousel-item-prev$/, /^carousel-item-start$/, /^carousel-item-end$/,
  /^carousel-control-prev$/, /^carousel-control-next$/, /^carousel-control-prev-icon$/, /^carousel-control-next-icon$/,
  /^carousel-indicators$/, /^carousel-caption$/, /^carousel-dark$/,
  /^spinner-border$/, /^spinner-border-sm$/, /^spinner-grow$/, /^spinner-grow-sm$/,
  /^offcanvas$/, /^offcanvas-header$/, /^offcanvas-title$/, /^offcanvas-body$/,
  /^offcanvas-start$/, /^offcanvas-end$/, /^offcanvas-top$/, /^offcanvas-bottom$/,
  /^offcanvas-lg$/, /^offcanvas-md$/, /^offcanvas-sm$/, /^offcanvas-xl$/, /^offcanvas-xxl$/,
  /^offcanvas-backdrop$/,
  /^placeholder$/, /^placeholder-xs$/, /^placeholder-sm$/, /^placeholder-lg$/,
  /^placeholder-glow$/, /^placeholder-wave$/,
  /^sticky-lg-top$/, /^sticky-md-top$/, /^sticky-sm-top$/, /^sticky-xl-top$/, /^sticky-xxl-top$/,
  /^fixed-top$/, /^fixed-bottom$/, /^sticky-top$/,
];

async function purgeFile(inputFile, outputFile, safelist) {
  console.log(`Purging: ${path.basename(inputFile)}...`);

  const result = await new PurgeCSS().purge({
    content: [
      path.join(themeDir, 'views', '**', '*.blade.php'),
      path.join(themeDir, 'partials', '**', '*.blade.php'),
      path.join(themeDir, 'public', 'js', '*.js'),
      path.join(themeDir, 'public', 'js', 'plugins', '*.js'),
    ],
    css: [inputFile],
    safelist: {
      standard: safelist,
      deep: [/^owl-/, /^swiper-/, /^aos-/, /^mfp-/, /^slick-/, /^gi-/, /^tracking-/, /^timeline-/, /^response-/, /^success-/],
      greedy: [/^data-/, /^aria-/, /^role$/],
    },
    fontFace: true,
    keyframes: true,
    variables: true,
  });

  if (result.length > 0) {
    fs.writeFileSync(outputFile, result[0].css, 'utf8');
    const originalSize = fs.statSync(inputFile).size;
    const newSize = fs.statSync(outputFile).size;
    const reduction = ((1 - newSize / originalSize) * 100).toFixed(1);
    console.log(`  Original: ${(originalSize / 1024).toFixed(1)} KB`);
    console.log(`  Purged:   ${(newSize / 1024).toFixed(1)} KB`);
    console.log(`  Saved:    ${reduction}%`);
  } else {
    console.log('  No output generated');
  }
}

(async () => {
  try {
    // Backup original files
    const files = [
      { input: 'bootstrap.min.css', output: 'bootstrap.purged.css', safelist: bootstrapSafelist },
      { input: 'main.min.css', output: 'main.purged.css', safelist: [...bootstrapSafelist, ...themeSafelist] },
      { input: 'custom.min.css', output: 'custom.purged.css', safelist: [...bootstrapSafelist, ...themeSafelist] },
    ];

    for (const file of files) {
      const inputPath = path.join(cssDir, file.input);
      const outputPath = path.join(cssDir, file.output);
      if (fs.existsSync(inputPath)) {
        await purgeFile(inputPath, outputPath, file.safelist);
      } else {
        console.log(`Not found: ${file.input}`);
      }
    }

    console.log('\nDone! Check the .purged.css files.');
  } catch (err) {
    console.error('Error:', err.message);
    process.exit(1);
  }
})();
