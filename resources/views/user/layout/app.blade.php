<!DOCTYPE html>
<html>

<head>
   <meta charset="utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="csrf-token" content="{{ csrf_token() }}">
   <title>{{ config('app.name') }} - Dashboard</title>
   <link href="{{ asset('/admin/css/bootstrap.min.css') }}" rel="stylesheet">
   <link href="{{ asset('/admin/font-awesome/css/font-awesome.css') }}" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
   <!-- Toastr style -->
   <link href="{{ asset('/admin/css/plugins/toastr/toastr.min.css') }}" rel="stylesheet">
   <!-- Gritter -->
   <link href="{{ asset('/admin/js/plugins/gritter/jquery.gritter.css') }}" rel="stylesheet">
   <link href="{{ asset('/admin/css/animate.css') }}" rel="stylesheet">
   <link href="{{ asset('/admin/css/style.css') }}" rel="stylesheet">
   <link href="{{ asset('/admin/css/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet">
   <style>
      @media (max-width: 768px) {
         .table-responsive,
         .ibox-content,
         .wrapper-content {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
         }
         .table-responsive > table,
         .dataTables_wrapper .dataTables_scroll,
         table.dataTables-example {
            min-width: 720px;
         }
      }
   </style>
   @stack('style')
</head>

<body>
   <style>
      /* Mobile off-canvas sidebar for user panel */
      @media (max-width: 768px) {
         /* Inspinia hides sidebar on mobile; override it */
         #wrapper .navbar-static-side {
            display: block !important;
            position: fixed !important;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            width: 220px !important;
            background: #2f4050;
         }

         #wrapper .navbar-static-side {
            width: 220px;
            transform: translateX(-100%);
            transition: transform 200ms ease;
            z-index: 2001;
         }

         body.mobile-sidebar-open #wrapper .navbar-static-side {
            transform: translateX(0);
         }

         .mobile-sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            z-index: 2000;
            display: none;
         }

         body.mobile-sidebar-open .mobile-sidebar-overlay {
            display: block;
         }

         /* Give page content full width on mobile */
         #page-wrapper {
            margin: 0 !important;
         }

         /* Keep top actions in one line on mobile */
         .navbar.navbar-static-top {
            display: flex;
            align-items: center;
         }
         .navbar.navbar-static-top .navbar-header {
            flex: 0 0 auto;
         }
         .navbar-top-links.navbar-right {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: flex-end;
            width: 100%;
            margin: 0;
            flex: 1 1 auto;
         }
         .navbar-top-links.navbar-right > li {
            float: none !important;
            flex: 0 0 auto;
         }
         .navbar-top-links.navbar-right > li > a {
            padding: 15px 8px;
            white-space: nowrap;
         }
         /* Inspinia adds right margin on last item */
         .navbar-top-links li:last-child {
            margin-right: 0 !important;
         }
         .navbar-top-links.navbar-right .welcome-message {
            display: none;
         }
      }
   </style>
   <div id="wrapper">
      <div class="mobile-sidebar-overlay" id="mobileSidebarOverlay"
         onclick="document.body.classList.remove('mobile-sidebar-open')"></div>
      <nav class="navbar-default navbar-static-side" role="navigation" style="position: fixed;">
         <div class="sidebar-collapse">
            <ul class="nav metismenu" id="side-menu">

               <li>
                  <a href="{{route('welcome')}}" style="padding:0px;"><img
                        src="{{ asset('frontend/images/pngs/header-logo-white.png') }}" alt=""
                        style="width: 220px; padding: 8px 12px;"></a>
               </li>
               <li style="padding: 16px 12px;">
                  <div style="display:flex;flex-direction:column;align-items:center;gap:8px;text-align:center;">
                     <img src="{{ user_avatar_url(auth()->user()) }}" alt="{{ auth()->user()->name ?? 'User' }}"
                        style="width:225px;height:225px;border-radius:999px;object-fit:cover;border:3px solid rgba(255,255,255,0.25);" />
                     <div style="color:#fff;line-height:1.3;">
                        <div style="font-weight:600;font-size:14px;">{{ auth()->user()->name ?? 'User' }}</div>
                        <div style="font-size:12px;opacity:0.85;">Student Portal</div>
                     </div>
                  </div>
               </li>
               @if(auth()->user()->hasPermission('dashboard-read'))
                  <li class="{{ request()->routeIs('user.home') ? 'active' : '' }}">
                     <a href="{{ route('user.home') }}"><i class="fa fa-th-large"></i> <span
                           class="nav-label">Dashboard</span></a>
                  </li>
               @endif
               {{-- @if(auth()->user()->hasPermission('installment-list'))
               <li class="{{ request()->routeIs('user.installments.index') ? 'active' : '' }}">
                  <a href="{{ route('user.installments.index') }}"><i class="fa fa-th-large"></i> <span
                        class="nav-label">Installments</span></a>
               </li>
               @endif --}}
               <li class="{{ request()->routeIs('user.profile') ? 'active' : '' }}">
                  <a href="{{ route('user.profile') }}"><i class="fa fa-th-large"></i> <span
                        class="nav-label">Profile</span></a>
               </li>
               <li>
                  <a href="https://elearning.eduberkeley.com" target="_blank" rel="noopener noreferrer">
                     <i class="fa fa-book"></i>
                     <span class="nav-label">Study Material</span>
                  </a>
               </li>
               @if(auth()->user()->hasPermission('testimonial-list'))
                  <li class="{{ request()->routeIs('user.testimonial.index') ? 'active' : '' }}">
                     <a href="{{ route('user.testimonial.index') }}"><i class="fa fa-th-large"></i> <span
                           class="nav-label">Testimonials</span></a>
                  </li>
               @endif
               @if(auth()->user()->hasPermission('cart-list') || auth()->user()->roles()->whereIn('name', ['student'])->exists())
                  <li class="{{ request()->routeIs('cart.index', 'user.cart.index') ? 'active' : '' }}">
                     <a href="{{ route('cart.index') }}"><i class="fa fa-shopping-cart"></i> <span
                           class="nav-label">Cart @if(cart_item_count() > 0)({{ cart_item_count() }})@endif</span></a>
                  </li>
               @endif
               <li>
                  <a href="{{ route('user.logout') }}"><i class="fa fa-sign-out"></i> <span
                        class="nav-label">Log out</span></a>
               </li>

            </ul>
         </div>
      </nav>
      <div id="page-wrapper" class="gray-bg dashbard-1">
         <div class="row border-bottom">
            <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0">
               <div class="navbar-header" style="display:flex;align-items:center;">
                  <button type="button" class="btn btn-primary visible-xs" id="mobileSidebarToggle"
                     style="margin: 10px 0 10px 10px;"
                     onclick="document.body.classList.toggle('mobile-sidebar-open'); return false;">
                     <i class="fa fa-bars"></i>
                  </button>
               </div>

               <ul class="nav navbar-top-links navbar-right">
                  <li>
                     <span class="m-r-sm text-muted welcome-message">
                        {{ auth()->user()->name }}
                     </span>
                  </li>
                  <li>
                     <a href="{{ route('cart.index') }}" title="Cart">
                        <i class="fa fa-shopping-cart"></i>
                        @if(cart_item_count() > 0)
                           <span class="label label-warning">{{ cart_item_count() }}</span>
                        @endif
                     </a>
                  </li>
                  <li>
                     <a href="{{ url('/courses') }}" target="_blank">Explore more courses</a>
                  </li>
                  <li>
                     <a href="{{ route('user.logout') }}">
                        <i class="fa fa-sign-out"></i> Log out
                     </a>
                  </li>

               </ul>
            </nav>
         </div>

         @yield('content')

      </div>

   </div>

   <!-- Mainly scripts -->
   <script src="{{ asset('admin/js/jquery-3.1.1.min.js') }}"></script>
   <script src="{{ asset('admin/js/bootstrap.min.js') }}"></script>
   <script src="{{ asset('admin/js/plugins/metisMenu/jquery.metisMenu.js') }}"></script>
   <script src="{{ asset('admin/js/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
   <!-- Flot -->
   <script src="{{ asset('admin/js/plugins/flot/jquery.flot.js') }}"></script>
   <script src="{{ asset('admin/js/plugins/flot/jquery.flot.tooltip.min.js') }}"></script>
   <script src="{{ asset('admin/js/plugins/flot/jquery.flot.spline.js') }}"></script>
   <script src="{{ asset('admin/js/plugins/flot/jquery.flot.resize.js') }}"></script>
   <script src="{{ asset('admin/js/plugins/flot/jquery.flot.pie.js') }}"></script>
   <!-- Peity -->
   <script src="{{ asset('admin/js/plugins/peity/jquery.peity.min.js') }}"></script>
   <script src="{{ asset('admin/js/demo/peity-demo.js') }}"></script>
   <!-- Custom and plugin javascript -->
   <script src="{{ asset('admin/js/inspinia.js') }}"></script>
   <script src="{{ asset('admin/js/plugins/pace/pace.min.js') }}"></script>
   <!-- jQuery UI -->
   <script src="{{ asset('admin/js/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
   <!-- GITTER -->
   <script src="{{ asset('admin/js/plugins/gritter/jquery.gritter.min.js') }}"></script>
   <!-- Sparkline -->
   <script src="{{ asset('admin/js/plugins/sparkline/jquery.sparkline.min.js') }}"></script>
   <!-- Sparkline demo data  -->
   <script src="{{ asset('admin/js/demo/sparkline-demo.js') }}"></script>
   <!-- ChartJS-->
   <script src="{{ asset('admin/js/plugins/chartJs/Chart.min.js') }}"></script>
   <!-- Toastr -->
   <script src="{{ asset('admin/js/plugins/toastr/toastr.min.js') }}"></script>
   <!-- Sweet Alert -->
   <script src="{{ asset('admin/js/plugins/sweetalert/sweetalert.min.js') }}"></script>
   <!-- CK Editor -->
   <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>

   <script>
      (function () {
         // Close after clicking a sidebar link on mobile
         var sideMenu = document.getElementById('side-menu');
         if (!sideMenu) return;
         sideMenu.addEventListener('click', function (e) {
            var t = e.target;
            if (!t) return;
            var link = t.closest ? t.closest('a') : null;
            if (link && window.innerWidth <= 768) {
               document.body.classList.remove('mobile-sidebar-open');
            }
         });
      })();
   </script>

   @include('admin.layout.partials.flash_messages')

   @stack('script')
</body>

</html>