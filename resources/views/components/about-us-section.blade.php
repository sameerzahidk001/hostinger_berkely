<section class="about-us-section">
    <div class="container">
        <!-- iBox Layout for About Us Section -->
        <div class="ibox float-e-margins" style="margin-bottom: 0;">
            <div class="ibox-title">
                <h5>About Us</h5>
                <div class="ibox-tools">
                    <a class="collapse-link">
                        <i class="fa fa-chevron-up"></i>
                    </a>
                    <a class="close-link">
                        <i class="fa fa-times"></i>
                    </a>
                </div>
            </div>

            <div class="ibox-content">
                <div class="row">
                    <div class="col-md-6">
                        <!-- Image -->
                        <img src="{{ asset($aboutUs->image ?? 'frontend/images/jpg/Berkeley-Square-USA.jpg') }}" class="img-responsive" alt="About Us">
                    </div>
                    <div class="col-md-6">
                        <!-- Section Content -->
                        <h2 contenteditable="true">{{ $aboutUs->title ?? 'About Us' }}</h2>
                        <p contenteditable="true">{{ $aboutUs->description ?? 'We are committed to excellence in education.' }}</p>
                        <a href="{{ $aboutUs->link ?? route('about-us') }}" class="btn btn-secondary" contenteditable="true">
                            Learn More
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of iBox -->
    </div>
</section>
