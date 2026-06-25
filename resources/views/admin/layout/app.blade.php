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
         /* Prevent invisible overlays from blocking admin clicks */
         body:not(.modal-open) .modal-backdrop,
         body:not(.modal-open) .sweet-overlay {
            display: none !important;
            pointer-events: none !important;
         }
      </style>
      @stack('style')
   </head>
   <body class="no-skin-config">
      <div id="wrapper">
         <nav class="navbar-default navbar-static-side" role="navigation" style="position: fixed;">
            <div class="sidebar-collapse">
               <ul class="nav metismenu" id="side-menu">
                  {{--<li class="nav-header">
                     <div class="dropdown profile-element">
                        <span>
                           <a href="{{route('welcome')}}"><img alt="image" class="img-circle" src="{{ asset('admin/images/logo.png') }}" /></a>
                        </span>
                        
                        <a data-toggle="dropdown" class="dropdown-toggle" href="#" class="text-left">
                        <span class="clear"> <span class="block m-t-xs"> <strong class="font-bold">{{ auth('admin')->user()->username }}</strong>
                        </span> <span class="text-muted text-xs block">Admin <b class="caret"></b></span> </span> </a>
                        <ul class="dropdown-menu animated fadeInRight m-t-xs">
                           <li><a href="{{ route('admin.profile') }}">Profile</a></li>
                           
                           <li class="divider"></li>
                           <li><a href="{{ route('admin.logout') }}">Log out</a></li>
                        </ul>
                     </div>
                     <div class="logo-element">
                        BM+
                     </div>
                  </li>--}}
                  <li>
                    <a href="{{route('welcome')}}" style="padding:0px;"> <img src="{{ asset('frontend/images/pngs/header-logo-white.png') }}" alt="" style="width: 220px; padding: 8px 12px;"></a>
                  </li>
                  @if(panel_profile_user())
                  <li style="padding: 16px 12px;">
                     <a href="{{ route('admin.profile') }}" style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:0;color:#fff;text-align:center;">
                        <img src="{{ user_avatar_url() }}" alt="{{ panel_profile_name() }}"
                           style="width:225px;height:225px;border-radius:999px;object-fit:cover;border:3px solid rgba(255,255,255,0.25);" />
                        <span style="line-height:1.3;">
                           <span style="display:block;font-weight:600;font-size:14px;">{{ panel_profile_name() }}</span>
                           <span style="display:block;font-size:12px;opacity:0.85;">Profile</span>
                        </span>
                     </a>
                  </li>
                  @endif
                  @if(admin_menu_allowed('dashboard'))
                  <li class="{{ request()->is('admin/home') ? 'active' : '' }}">
                     <a href="{{ route('admin.home') }}"><i class="fa fa-th-large"></i> <span class="nav-label">Dashboard</span></a>
                  </li>
                  @endif
                  @if(admin_menu_allowed('school'))
                  <li class="{{ request()->is('school/*') ? 'active' : '' }}">
                     <a href="{{ route('school.index') }}"><i class="fa fa-cogs"></i> <span class="nav-label">Schools</span></a>
                  </li>
                  @endif
                  @if(admin_menu_allowed('categories'))
                  <li class="{{ request()->is('admin/category*') ? 'active' : '' }}">
                     <a href="{{ route('category.index') }}"><i class="fa fa-cogs"></i> <span class="nav-label">Categories</span></a>
                  </li>
                  @endif
                  @if(admin_menu_allowed('courses'))
                  <li class="{{ request()->is('admin/course') || request()->is('admin/course/*') ? 'active' : '' }}">
                     <a href="javascript:void(0)"><i class="fa fa-files-o"></i> <span class="nav-label">Courses</span> <span class="fa arrow"></span></a>
                     <ul class="nav nav-second-level">
                        <li><a href="{{ route('admin.courses') }}">All Courses List</a></li>
                        <li><a href="{{ route('admin.course.disabled') }}">Disabled Courses</a></li>
                        <li><a href="{{ route('course.create') }}">Add Course</a></li>
                     </ul>
                  </li>
                  @endif
                  @if(admin_menu_allowed('pages'))
                  <li class="{{ request()->is('admin/pages*') || request()->is('admin/faq*') ? 'active' : '' }}">
                     <a href="javascript:void(0)"><i class="fa fa-sitemap"></i> <span class="nav-label">Pages</span> <span class="fa arrow"></span></a>
                     <ul class="nav nav-second-level">
                        <li><a href="{{ route('pages.index') }}">Pages List</a></li>
                        <li><a href="{{ route('admin.pages.disabled') }}">Disabled Pages</a></li>
                        @if(admin_menu_allowed('faq'))
                        <li><a href="{{ route('faq.index') }}">FAQs</a></li>
                        @endif
                     </ul>
                  </li>
                  @endif
                  @if(admin_menu_allowed('seo'))
                  <li class="{{ request()->is('admin/pages-seo*') ? 'active' : '' }}">
                     <a href="{{ route('pages-seo.index') }}"><i class="fa fa-laptop"></i> <span class="nav-label">SEO</span></a>
                  </li>
                  @endif
                  @if(admin_menu_allowed('training-calendar'))
                  <li class="{{ request()->is('admin/training-calendar*') || request()->is('admin/course-agendas*') ? 'active' : '' }}">
                     <a href="{{ route('admin.course-agendas.index') }}"><i class="fa fa-th-large"></i> <span class="nav-label">Training Calendar</span></a>
                  </li>
                  @endif
                  @if(admin_menu_allowed('stories'))
                  <li class="{{ request()->is('admin/testimonial*') ? 'active' : '' }}">
                     <a href="{{ route('testimonial.index') }}"><i class="fa fa-star"></i> <span class="nav-label">Testimonials</span></a>
                  </li>
                  @endif
                  @if(admin_menu_allowed('clients'))
                  <li class="{{ request()->is('admin/clients*') ? 'active' : '' }}">
                     <a href="{{ route('admin.clients.index') }}"><i class="fa fa-cog"></i> <span class="nav-label">Clients</span></a>
                  </li>
                  @endif
                  @if(admin_menu_allowed('analytics'))
                  <li class="{{ request()->is('admin/analytics*') ? 'active' : '' }}">
                     <a href="{{ route('admin.analytics') }}"><i class="fa fa-star"></i> <span class="nav-label">Analytics</span></a>
                  </li>
                  @endif
                  @if(admin_menu_allowed('users'))
                  <li class="{{ request()->is('admin/user*') ? 'active show' : '' }}">
                     <a href="javascript:void(0)"><i class="fa fa-address-card"></i> <span class="nav-label">Users</span> <span class="fa arrow"></span></a>
                     <ul class="nav nav-second-level">
                        <li><a href="{{ route('users', ['type' => 'student']) }}">Student List</a></li>
                        <li><a href="{{ route('users', ['type' => 'instructor']) }}">Instructors</a></li>
                        <li><a href="{{ route('users', ['type' => 'accountant']) }}">Accountants</a></li>
                        <li><a href="{{ route('users', ['type' => 'content-writer']) }}">Content Writers</a></li>
                        <li><a href="{{ route('users.create') }}">Add User</a></li>
                     </ul>
                  </li>
                  @endif
                  @if(admin_menu_allowed('payments'))
                  <li class="{{ request()->is('admin/payments*') || request()->is('admin/currency-rate-setup') || request()->is('admin/payment-gateways*') ? 'active show' : '' }}">
                     <a href="javascript:void(0)"><i class="fa fa-address-card"></i> <span class="nav-label">Payments</span> <span class="fa arrow"></span></a>
                     <ul class="nav nav-second-level">
                        <li class="{{ request()->routeIs('admin.payments.index') && request('status') === null ? 'active' : '' }}"><a href="{{ route('admin.payments.index') }}">Invoice List</a></li>
                        <li class="{{ request()->routeIs('admin.payments.index') && request('status') === 'pending' ? 'active' : '' }}"><a href="{{ route('admin.payments.index', ['status' => 'pending']) }}">Pending Invoices</a></li>
                        <li class="{{ request()->routeIs('admin.payments.index') && request('status') === 'partial' ? 'active' : '' }}"><a href="{{ route('admin.payments.index', ['status' => 'partial']) }}">Partial Paid Invoices</a></li>
                        <li><a href="{{ route('admin.payments.create') }}">Create Invoice</a></li>
                        <li><a href="{{ route('admin.payments.receipts') }}">Receipts</a></li>
                        @if(admin_menu_allowed('currency-rate-setup'))
                        <li class="{{ request()->is('admin/currency-rate-setup') ? 'active' : '' }}"><a href="{{ route('currency-rates.index') }}">Currency Rate Setup</a></li>
                        @endif
                        @if(admin_menu_allowed('payment-gateway'))
                        <li class="{{ request()->is('admin/payment-gateways*') ? 'active' : '' }}"><a href="{{ url('admin/payment-gateways') }}">Payment Gateway</a></li>
                        @endif
                     </ul>
                  </li>
                  @endif
                  @if(admin_menu_allowed('profile'))
                  <li class="{{ request()->routeIs('admin.profile') ? 'active' : '' }}">
                     <a href="{{ route('admin.profile') }}"><i class="fa fa-cog"></i> <span class="nav-label">Profile</span></a>
                  </li>
                  @endif
                  @if(admin_menu_allowed('settings'))
                  <li class="{{ request()->is('admin/homepage/edit') || request()->is('admin/menu*') || request()->is('admin/site-settings*') || request()->is('admin/widget*') || request()->is('admin/header-setting*') || request()->is('admin/footer-setting*') || request()->is('admin/smtp-settings*') || request()->is('admin/email-templates*') ? 'active show' : '' }}">
                     <a href="#"><i class="fa fa-address-card"></i> <span class="nav-label">Settings</span> <span class="fa arrow"></span></a>
                     <ul class="nav nav-second-level">
                        <li><a href="{{ route('menu.index') }}"><i class="fa fa-cog"></i> <span class="nav-label">Menu</span></a></li>
                        <li><a href="{{ route('site-settings.index') }}"><i class="fa fa-cog"></i> <span class="nav-label">Site Settings</span></a></li>
                        <li><a href="{{ route('widget.index') }}"><i class="fa fa-cog"></i> <span class="nav-label">Widgets</span></a></li>
                        <li><a href="{{ route('header.setting.index') }}"><i class="fa fa-cog"></i> <span class="nav-label">Header Settings</span></a></li>
                        <li><a href="{{ route('footer.setting.index') }}"><i class="fa fa-cog"></i> <span class="nav-label">Footer Settings</span></a></li>
                        <li><a href="{{ route('admin.smtpSettings.index') }}"><i class="fa fa-cog"></i> <span class="nav-label">Email SMTP Setting</span></a></li>
                        @if(admin_menu_allowed('emails'))
                        <li class="{{ request()->is('admin/email-templates*') ? 'active' : '' }}"><a href="{{ route('admin.emails.index') }}"><i class="fa fa-envelope"></i> <span class="nav-label">Email Templates</span></a></li>
                        @endif
                     </ul>
                  </li>
                  @endif
                  @if(admin_menu_allowed('logout'))
                  <li>
                     <a href="{{ route('admin.logout') }}"><i class="fa fa-sign-out"></i> <span class="nav-label">Log out</span></a>
                  </li>
                  @endif
                 
               </ul>
            </div>
         </nav>
         <div id="page-wrapper" class="gray-bg dashbard-1">
            <div class="row border-bottom">
               <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0">
                  <div class="navbar-header">
                     <a class="navbar-minimalize minimalize-styl-2 btn btn-primary " href="#"><i class="fa fa-bars"></i> </a>
                     <!-- <form role="search" class="navbar-form-custom" action="search_results.html">
                        <div class="form-group">
                           <input type="text" placeholder="Search for something..." class="form-control" name="top-search" id="top-search">
                        </div>
                     </form> -->
                  </div>
                  <ul class="nav navbar-top-links navbar-right">
                     <li>
                        <span class="m-r-sm text-muted welcome-message">Welcome, {{ panel_profile_name() ?: 'User' }}.</span>
                     </li>
                     <li>
                        <a href="{{ route('admin.logout') }}">
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
      <script src="{{ asset('/admin/js/jquery-3.1.1.min.js') }}"></script>
      <script src="{{ asset('/admin/js/bootstrap.min.js') }}"></script>
      <script src="{{ asset('/admin/js/plugins/metisMenu/jquery.metisMenu.js') }}"></script>
      <script src="{{ asset('/admin/js/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
      <!-- Flot -->
      <script src="{{ asset('/admin/js/plugins/flot/jquery.flot.js') }}"></script>
      <script src="{{ asset('/admin/js/plugins/flot/jquery.flot.tooltip.min.js') }}"></script>
      <script src="{{ asset('/admin/js/plugins/flot/jquery.flot.spline.js') }}"></script>
      <script src="{{ asset('/admin/js/plugins/flot/jquery.flot.resize.js') }}"></script>
      <script src="{{ asset('/admin/js/plugins/flot/jquery.flot.pie.js') }}"></script>
      <!-- Peity -->
      <script src="{{ asset('/admin/js/plugins/peity/jquery.peity.min.js') }}"></script>
      <script src="{{ asset('/admin/js/demo/peity-demo.js') }}"></script>
      <!-- Custom and plugin javascript -->
      <script src="{{ asset('/admin/js/inspinia.js') }}"></script>
      <script src="{{ asset('/admin/js/plugins/pace/pace.min.js') }}"></script>
      <!-- jQuery UI -->
      <script src="{{ asset('/admin/js/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
      <!-- GITTER -->
      <script src="{{ asset('/admin/js/plugins/gritter/jquery.gritter.min.js') }}"></script>
      <!-- Sparkline -->
      <script src="{{ asset('/admin/js/plugins/sparkline/jquery.sparkline.min.js') }}"></script>
      <!-- Sparkline demo data  -->
      <script src="{{ asset('/admin/js/demo/sparkline-demo.js') }}"></script>
      <!-- ChartJS-->
      <script src="{{ asset('/admin/js/plugins/chartJs/Chart.min.js') }}"></script>
      <!-- Toastr -->
      <script src="{{ asset('/admin/js/plugins/toastr/toastr.min.js') }}"></script>

      <script src="{{ asset('/admin/js/plugins/sweetalert/sweetalert.min.js') }}"></script>
      @include('admin.layout.partials.flash_messages')
      <script>
         function confirmDelete(id) {
            swal({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete it!",
                closeOnConfirm: true
            }, function (isConfirm) {
                if (isConfirm) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
         }

         function confirmDeleteLink(event, url, label) {
            if (event) {
                event.preventDefault();
            }

            swal({
                title: "Are you sure?",
                text: "You won't be able to revert " + (label || "this item") + "!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete it!",
                closeOnConfirm: true
            }, function (isConfirm) {
                if (isConfirm) {
                    window.location.href = url;
                }
            });

            return false;
         }

         function clearAdminUiBlockers() {
            $('.modal-backdrop, .sweet-overlay').remove();
            $('body').removeClass('modal-open stop-scrolling pace-running mobile-sidebar-open');
            $('body').addClass('pace-done');
            $('.modal').removeClass('in').attr('aria-hidden', 'true').hide();
         }

         $(document).ready(function () {
            clearAdminUiBlockers();
         });

         $(window).on('load', function () {
            clearAdminUiBlockers();
         });

         $(document).ready(function() {
            @if(session('success'))
               toastr.success("{{ session('success') }}", "Success");
            @endif
            @if(session('fail'))
               toastr.error("{{ session('fail') }}", "Error");
            @endif
            @if(session('warning'))
               toastr.warning("{{ session('warning') }}", "Warning");
            @endif
            //  setTimeout(function() {
            //      toastr.options = {
            //          closeButton: true,
            //          progressBar: true,
            //          showMethod: 'slideDown',
            //          timeOut: 4000
            //      };
            //      toastr.success('Responsive Admin Theme', 'Welcome to INSPINIA');
         
            //  }, 1300);
         });
      </script>
      <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>
      <script>
         document.addEventListener('DOMContentLoaded', (event) => {
            document.querySelectorAll('.text-editor').forEach((editorElement) => {
                  ClassicEditor.create(editorElement, {
                     link: {
                        addTargetToExternalLinks: true,
                        decorators: {
                              openInNewTab: {
                                 mode: 'manual',
                                 label: 'Open in a new tab',
                                 attributes: {
                                    target: '_blank',
                                    rel: 'noopener noreferrer'
                                 }
                              }
                        }
                     },
                     // Add the upload adapter configuration
                     simpleUpload: {
                        // The URL that the images are uploaded to.
                        uploadUrl: 'ckeditor-image-upload', // Replace with your upload URL

                        // Headers sent along with the XMLHttpRequest to the upload URL.
                        headers: {
                              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                     }
                  })
                  .catch(error => {
                     console.error(error);
                  });
            });
         });
      </script>
      
      @include('admin.layout.partials.char-count')
      @include('admin.layout.partials.restrict-delete')
      @stack('script')
   </body>
</html>