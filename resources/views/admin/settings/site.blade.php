@extends('admin.layout.app')
@section('title', 'Site Settings')
@push('style')
<style>
    .nav-tab > li >a{
        color: #000;
    }
</style>
@endpush

@section('content')
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col-lg-10">
            <h2>Site Settings</h2>
            <ol class="breadcrumb">
                <li><a href="index.html">Home</a></li>
                <li class="active"><strong>Site Settings</strong></li>
            </ol>
        </div>
    </div>

    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox">
                    <div class="ibox-content">
                        <div class="row">
                            <!-- Sidebar with Tab Links -->
                            <div class="col-md-3">
                                <ul class="nav nav-pills nav-stacked nav-tab">
                                    <li class="active">
                                        <a href="#logos-settings" data-toggle="tab">Logos</a>
                                    </li>
                                    <li>
                                        <a href="#header-settings" data-toggle="tab">Header Settings</a>
                                    </li>
                                    <li>
                                        <a href="#footer-settings" data-toggle="tab">Footer Settings</a>
                                    </li>
                                    <li>
                                        <a href="#social-settings" data-toggle="tab">Social Media Links</a>
                                    </li>
                                    <li>
                                        <a href="#mobile-menu-settings" data-toggle="tab">Mobile Menu Settings</a>
                                    </li>
                                    <li>
                                        <a href="#page-settings" data-toggle="tab">Page Settings</a>
                                    </li>
                                    <li>
                                        <a href="#perma-settings" data-toggle="tab">PermaLinks Settings</a>
                                    </li>
                                </ul>
                            </div>
                    
                            <!-- Tab Content -->
                            <div class="col-md-9">
                                <div class="tab-content">
                                    <!-- Logos Settings -->
                                    <div class="tab-pane fade in active" id="logos-settings">
                                        <form action="{{ route('site-settings.update') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="active_tab" id="active_tab" value="logos-settings">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="logo">Color Logo</label>
                                                        <input type="file" name="logo" id="logo" class="form-control">
                                                        @if($settings->logo)
                                                            <div class="mt-2">
                                                                <small>Current Image: <a href="{{ asset('images/' . $settings->logo) }}" target="_blank">View</a></small>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="white_logo">White Logo</label>
                                                        <input type="file" name="white_logo" id="white_logo" class="form-control">
                                                        @if($settings->white_logo)
                                                            <div class="mt-2">
                                                                <small>Current Image: <a href="{{ asset('images/' . $settings->white_logo) }}" target="_blank">View</a></small>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="favicon">Favicon</label>
                                                        <input type="file" name="favicon" id="favicon" class="form-control">
                                                        @if($settings->favicon)
                                                            <div class="mt-2">
                                                                <small>Current Image: <a href="{{ asset('images/' . $settings->favicon) }}" target="_blank">View</a></small>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </form>
                                    </div>
                                    <!-- Header Settings -->
                                    <div class="tab-pane fade" id="header-settings">
                                        <form action="{{ route('site-settings.update') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="active_tab" id="active_tab" value="header-settings">
                                        
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h3>Header Menu</h3>
                                                    <div class="form-group">
                                                        <label for="header_menu">Select Logo</label>
                                                        <select name="header_menu" id="header_menu" class="form-control">
                                                    @php
                                                        $uniqueMenuGroups = $menus->pluck('menu_group')->unique();
                                                    @endphp
                                                    @foreach ($uniqueMenuGroups as $group)
                                                        <option value="{{ $group }}">
                                                            {{ ucfirst($group) }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h3>Logo</h3>
                                                    <div class="form-group">
                                                        <label for="header_logo">Select Logo</label>
                                                        <select name="header_logo" id="header_logo" class="form-control">
                                                            <option value="logo" {{ $settings->header_logo == 'logo' ? 'selected' : '' }}>Color Logo</option>
                                                            <option value="white_logo" {{ $settings->header_logo == 'white_logo' ? 'selected' : '' }}>White Logo</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h3>Button</h3>
                                                    <div class="form-group">
                                                        <input type="hidden" name="header_button" value="0">
                                                        <input type="checkbox" name="header_button" value="1" {{ $settings->header_button ? 'checked' : '' }}> Enable Button
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="header_button_text">Text</label>
                                                        <input type="text" name="header_button_text" id="header_button_text" value="{{ $settings->header_button_text }}" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="header_button_url">URL</label>
                                                        <input type="url" name="header_button_url" id="header_button_url" value="{{ $settings->header_button_url }}" class="form-control">
                                                    </div>
                                                </div>
                                            </div>
                                        
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <h3>Search</h3>
                                                    <div class="form-group">
                                                        <input type="hidden" name="header_search" value="0">
                                                        <input type="checkbox" name="header_search" value="1" {{ $settings->header_search ? 'checked' : '' }}> Enable Search
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="header_search_image">Image</label>
                                                        <input type="file" name="header_search_image" id="header_search_image" class="form-control">
                                                        @if(isset($settings->header_search_image))
                                                            <div class="mt-2">
                                                                <small>Current Image: <a href="{{ asset('images/' . $settings->header_search_image) }}" target="_blank">View</a></small>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                        
                                                <div class="col-md-4">
                                                    <h3>Login</h3>
                                                    <div class="form-group">
                                                        <input type="hidden" name="login" value="0">
                                                        <input type="checkbox" name="login" value="1" {{ $settings->login ? 'checked' : '' }}> Enable Login
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="login_text">Text</label>
                                                        <input type="text" name="login_text" id="login_text" value="{{ $settings->login_text }}" class="form-control">
                                                    </div>
                                                </div>
                                        
                                                <div class="col-md-4">
                                                    <h3>Register</h3>
                                                    <div class="form-group">
                                                        <input type="hidden" name="register" value="0">
                                                        <input type="checkbox" name="register" value="1" {{ $settings->register ? 'checked' : '' }}> Enable Register
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="register_text">Text</label>
                                                        <input type="text" name="register_text" id="register_text" value="{{ $settings->register_text }}" class="form-control">
                                                    </div>
                                                </div>
                                            </div>
                                        
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h3>Styling</h3>
                                                    <div class="row">
                                                        <div class="col-md-3 form-group">
                                                            <label for="header_bg">Background Color</label>
                                                            <input type="color" id="header_bg" name="header_bg" class="form-control" value="{{ $settings->header_bg ?? '#FFFFFF' }}">
                                                        </div>
                                                        <div class="col-md-3 form-group">
                                                            <label for="header_menu_color">Menu Color</label>
                                                            <input type="color" id="header_menu_color" name="header_menu_color" class="form-control" value="{{ $settings->header_menu_color ?? '#000000' }}">
                                                        </div>
                                                        <div class="col-md-3 form-group">
                                                            <label for="header_button_bg">Button Background</label>
                                                            <input type="color" id="header_button_bg" name="header_button_bg" class="form-control" value="{{ $settings->header_button_bg ?? '#000435' }}">
                                                        </div>
                                                        <div class="col-md-3 form-group">
                                                            <label for="header_button_color">Button Color</label>
                                                            <input type="color" id="header_button_color" name="header_button_color" class="form-control" value="{{ $settings->header_button_color ?? '#FFFFFF' }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </form>                                        
                                    </div>
                                    <!-- Footer Settings -->
                                    <div class="tab-pane fade" id="footer-settings">
                                        <form action="{{ route('site-settings.update') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="active_tab" id="active_tab" value="footer-settings">
                                            
                                            <div class="form-group">
                                                <label for="copyright_message">Copyright Message</label>
                                                <input type="text" name="copyright_message" id="copyright_message" value="{{ $settings->copyright_message }}" class="form-control">
                                            </div>

                                            <div class="form-group">
                                                <label for="footer_columns">Footer Columns</label>
                                                <select class="form-control" name="footer_columns">
                                                    <option>- Select Options -</option>
                                                    <option value="1" {{ $settings->footer_columns == '1' ? 'selected' : '' }}>1</option>
                                                    <option value="2" {{ $settings->footer_columns == '2' ? 'selected' : '' }}>2</option>
                                                    <option value="3" {{ $settings->footer_columns == '3' ? 'selected' : '' }}>3</option>
                                                    <option value="4" {{ $settings->footer_columns == '4' ? 'selected' : '' }}>4</option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="show_logo_settings">Show Logo Settings</label>
                                                <input type="checkbox" id="show_logo_settings" name="show_logo_settings" {{ $settings->show_logo_settings ? 'checked' : '' }}>
                                            </div>

                                            <!-- Hidden Logo Settings (Initially Hidden) -->
                                            <div id="logo-settings" class="row" style="{{ $settings->show_logo_settings ? 'display: block;' : 'display: none;' }}">
                                                <div class="col-md-12">
                                                    <h3>Logo</h3>
                                                    <div class="form-group">
                                                        <label for="footer_logo">Select Logo</label>
                                                        <select name="footer_logo" id="footer_logo" class="form-control">
                                                            <option value="logo" {{ $settings->footer_logo == 'logo' ? 'selected' : '' }}>Color Logo</option>
                                                            <option value="white_logo" {{ $settings->footer_logo == 'white_logo' ? 'selected' : '' }}>White Logo</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h3>Styling</h3>
                                                    <div class="row">
                                                        <div class="col-md-4 form-group">
                                                            <label for="footer_bg">Background Color</label>
                                                            <input type="color" id="footer_bg" name="footer_bg" class="form-control" value="{{ $settings->footer_bg ?? '#FFFFFF' }}">
                                                        </div>
                                                        <div class="col-md-4 form-group">
                                                            <label for="footer_text_color">Text Color</label>
                                                            <input type="color" id="footer_text_color" name="footer_text_color" class="form-control" value="{{ $settings->footer_text_color ?? '#000000' }}">
                                                        </div>
                                                        <div class="col-md-4 form-group">
                                                            <label for="footer_title_bg">Title Background</label>
                                                            <input type="color" id="footer_title_bg" name="footer_title_bg" class="form-control" value="{{ $settings->footer_title_bg ?? '#000435' }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-primary">Update Footer Settings</button>
                                        </form>
                                    </div>
                                    <!-- Social Media Settings -->
                                    <div class="tab-pane fade" id="social-settings">
                                        <form action="{{ route('site-settings.update') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="active_tab" id="active_tab" value="social-settings">
                                            @php
                                                $socialMedia = ['facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'tiktok', 'whatsapp'];
                                            @endphp
                                            @foreach($socialMedia as $key)
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="{{ $key }}_icon">{{ ucfirst($key) }} Icon</label>
                                                            <input type="file" name="{{ $key }}_icon" id="{{ $key }}_icon" class="form-control">
                                                            @if(isset($settings->{$key . '_icon'}))
                                                                <div class="mt-2">
                                                                    <small>Current Image: <a href="{{ asset('images/' . $settings->{$key . '_icon'}) }}" target="_blank">View</a></small>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">                                                            
                                                            <label for="{{ $key }}_url">{{ ucfirst($key) }} URL</label>
                                                            <input type="text" name="{{ $key }}_url" id="{{ $key }}_url" value="{{ $settings->{$key . '_url'} ?? '' }}" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </form>
                                    </div>
                                    <!-- Mobile Menu Settings -->
                                    <div class="tab-pane fade" id="mobile-menu-settings">
                                        <form action="{{ route('site-settings.update') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="active_tab" id="active_tab" value="mobile-menu-settings">

                                            <div class="form-group">
                                        <label for="show_footerlogo_settings">Show Logo Settings</label>
                                        <input type="checkbox" id="show_footerlogo_settings" name="show_footerlogo_settings" {{ $settings->show_footerlogo_settings ? 'checked' : '' }}>
                                            </div>
                                                    <!-- Mobile Logo -->
                                            <div id="logo-footer-settings" class="row" style="{{ $settings->show_logo_settings ? 'display: block;' : 'display: none;' }}">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="mobile_logo">Mobile Logo</label>
                                                        <input type="file" name="mobile_logo" id="mobile_logo" class="form-control">
                                                        @if($settings->mobile_logo)
                                                            <div class="mt-2">
                                                                <small>Current Image: <a href="{{ asset('images/' . $settings->mobile_logo) }}" target="_blank">View</a></small>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Background Color -->
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="mobile_menu_bg">Background Color</label>
                                                        <input type="color" name="mobile_menu_bg" id="mobile_menu_bg" class="form-control" value="{{ $settings->mobile_menu_bg ?? '#FFFFFF' }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="mobile_menu_color">Menu Color</label>
                                                        <input type="color" name="mobile_menu_color" id="mobile_menu_color" class="form-control" value="{{ $settings->mobile_menu_color ?? '#000000' }}">
                                                    </div>
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </form>
                                    </div>
                                    <!-- Page Settings -->
                                    <div class="tab-pane fade" id="page-settings">
                                        <form action="{{ route('site-settings.update') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="form-group">
                                                <label for="home">Home Page</label>
                                                <select class="form-control" id="home" name="home">
                                                    <option value="">{{ $settings->home ?? '- Select Page -' }}</option>
                                                    @foreach ($pages as $page)
                                                        <option value="{{ $page->id }}" {{ isset($settings->home) && $settings->home == $page->id ? 'selected' : '' }}>
                                                            {{ $page->page_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="school">School Page</label>
                                                <select class="form-control" id="school" name="school">
                                                    <option value="">{{ $settings->school ?? '- Select Page -' }}</option>
                                                    @foreach ($pages as $page)
                                                        <option value="{{ $page->id }}" {{ isset($settings->school) && $settings->school == $page->id ? 'selected' : '' }}>
                                                            {{ $page->page_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="categories">Category Page</label>
                                                <select class="form-control" id="categories" name="categories">
                                                    <option value="">{{ $settings->categories ?? '- Select Page -' }}</option>
                                                    @foreach ($pages as $page)
                                                        <option value="{{ $page->id }}" {{ isset($settings->categories) && $settings->categories == $page->id ? 'selected' : '' }}>
                                                            {{ $page->page_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="courses">Course Page</label>
                                                <select class="form-control" id="courses" name="courses">
                                                    <option value="">{{ $settings->courses ?? '- Select Page -' }}</option>
                                                    @foreach ($pages as $page)
                                                        <option value="{{ $page->id }}" {{ isset($settings->courses) && $settings->courses == $page->id ? 'selected' : '' }}>
                                                            {{ $page->page_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <button type="submit" class="btn btn-primary">Submit</button>
                                        </form>
                                    </div>
                                    <!-- PermaLinks Settings -->
                                    <div class="tab-pane fade" id="perma-settings">
                                        <form action="{{ route('site-settings.update') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                            <div class="form-group">
                                                <label for="school">School Permalink</label>
                                                <input type="text" class="form-control" id="school_perma" name="school_perma" value="{{ $settings->school_perma ?? '' }}">
                                            </div>

                                            <div class="form-group">
                                                <label for="categories">Category Permalink</label>
                                                <input type="text" class="form-control" id="category_perma" name="category_perma" value="{{ $settings->category_perma ?? '' }}">
                                            </div>

                                            <div class="form-group">
                                                <label for="courses">Course Permalink</label>
                                                <input type="text" class="form-control" id="course_perma" name="course_perma" value="{{ $settings->course_perma ?? '' }}">
                                            </div>

                                            <button type="submit" class="btn btn-primary">Submit</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkbox = document.getElementById('show_logo_settings');
        const logoSettings = document.getElementById('logo-settings');

        checkbox.addEventListener('change', function () {
            if (this.checked) {
                logoSettings.style.display = 'block';
            } else {
                logoSettings.style.display = 'none';
            }
        });
    });
    document.addEventListener('DOMContentLoaded', function () {
        const checkbox = document.getElementById('show_footerlogo_settings');
        const logoSettings = document.getElementById('logo-footer-settings');

        checkbox.addEventListener('change', function () {
            if (this.checked) {
                logoSettings.style.display = 'block';
            } else {
                logoSettings.style.display = 'none';
            }
        });
    });
</script>
@endpush