{{-- About Us --}}
                        <div class="form-group">
                            <h3>About Us
                            </h3>
                            <label>Title:</label>
                            <input type="text" name="sections[about_us][title]" class="form-control" value="{{ $sections['centre_for_education']->title ?? '' }}" required>

                            <label>Description:</label>
                            <textarea name="sections[about_us][description]" class="form-control">{{ $sections['centre_for_education']->description ?? '' }}</textarea>

                            <label>Link:</label>
                            <input type="text" name="sections[about_us][link]" class="form-control" value="{{ $sections['centre_for_education']->link ?? '' }}">

                            <label>Image:</label>
                            <input type="file" name="sections[about_us][image]" class="form-control">
                            @if(isset($sections['about_us']->image))
                                <img src="{{ asset($sections['about_us']->image) }}" width="200">
                            @endif
                        </div>