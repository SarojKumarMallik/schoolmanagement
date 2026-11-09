(function($) {
  'use strict';
  $(function() {
    // Ensure mobile sidebar backdrop exists
    if (!$('#sidebarBackdrop').length) {
      $('body').append('<div class="sidebar-backdrop" id="sidebarBackdrop"></div>');
    }

    // Toggle sidebar on hamburger click
    $(document).on('click', '[data-toggle="offcanvas"]', function(e) {
      e.preventDefault();
      e.stopPropagation();
      $('.sidebar-offcanvas').toggleClass('active');
      $('#sidebarBackdrop').toggleClass('active', $('.sidebar-offcanvas').hasClass('active'));
      $('body').toggleClass('sidebar-open', $('.sidebar-offcanvas').hasClass('active'));
    });

    // Close on backdrop click
    $(document).on('click', '#sidebarBackdrop', function() {
      $('.sidebar-offcanvas').removeClass('active');
      $('#sidebarBackdrop').removeClass('active');
      $('body').removeClass('sidebar-open');
    });

    // Close on clicking outside sidebar on mobile
    $(document).on('click', function(e) {
      if ($(window).width() < 992) {
        if (!$(e.target).closest('#sidebar, [data-toggle="offcanvas"]').length) {
          if ($('.sidebar-offcanvas').hasClass('active')) {
            $('.sidebar-offcanvas').removeClass('active');
            $('#sidebarBackdrop').removeClass('active');
            $('body').removeClass('sidebar-open');
          }
        }
      }
    });

    // Close on Escape key
    $(document).on('keydown', function(e) {
      if (e.key === 'Escape') {
        $('.sidebar-offcanvas').removeClass('active');
        $('#sidebarBackdrop').removeClass('active');
        $('body').removeClass('sidebar-open');
      }
    });
  });
})(jQuery);
