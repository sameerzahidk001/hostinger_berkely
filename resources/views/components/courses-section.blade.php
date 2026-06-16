<section class="courses-section">
    <div class="container">
        <!-- Card Structure for the Courses Section -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title" contenteditable="true">{{ $courses->title ?? 'The World’s Best Collection of Courses' }}</h2>
            </div>
            <div class="card-body">
                <p class="lead" contenteditable="true">{{ $courses->description ?? 'Explore our world-class courses' }}</p>
            </div>
            <div class="card-footer">
                <a href="{{ $courses->link ?? route('courses') }}" contenteditable="true" class="btn btn-primary">
                    Search courses by index
                </a>
            </div>
        </div>
        <!-- End of Card -->
    </div>
</section>
