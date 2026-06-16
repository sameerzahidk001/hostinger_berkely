@foreach($courses as $data)
    <div class="col-12 col-md-6 col-lg-4">
        <div class="grid-course-card">
            <div class="thumnail-area">
                <img src="{{ asset($data->thumbnail) }}" class="img-fluid" alt="Course">
            </div>
            <div class="g-c-card-content">
                <a href="{{ route('course.details', ['course' => $data->slug]) }}" class="grid-course-card-title">{{ $data->title }}</a>
                
                <!-- <p>{!! $data->short_description !!}</p> -->
                <div class="d-flex gap-3" id="g-c-card-content-reviews-sec">
                    <div class="review-stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <div>
                        <small>( 4322 REVIEWS )</small>
                    </div>
                </div>
                <div class="grid-sch">
                    <div>
                        <img src="{{ asset('frontend/images/gc-logo.jpg') }}" alt="">
                    </div>
                    <div>
                        <h6 style="font-weight:300;">Berkeleyme School</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-start pt-2 g-c-card-content-info-btn-area">
                    <a href="" >Info</a>
                </div>
            </div>
        </div>
    </div>
@endforeach