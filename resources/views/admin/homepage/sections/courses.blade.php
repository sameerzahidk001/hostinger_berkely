{{-- Courses Section --}}
                        <h3>Courses</h3>
                        @foreach(['diplomas', 'graduate_courses', 'master_courses'] as $course)
                        <div class="form-group">
                            <h4>{{ ucfirst(str_replace('_', ' ', $course)) }}</h4>
                            <label>Title:</label>
                            <input type="text" name="sections[{{ $course }}][title]" class="form-control" value="{{ $sections[$course]->title ?? '' }}" required>

                            <label>Description:</label>
                            <textarea name="sections[{{ $course }}][description]" class="form-control">{{ $sections[$course]->description ?? '' }}</textarea>

                            <label>Link:</label>
                            <input type="text" name="sections[{{ $course }}][link]" class="form-control" value="{{ $sections[$course]->link ?? '' }}">

                            <label>Image:</label>
                            <input type="file" name="sections[{{ $course }}][image]" class="form-control">
                            @if(isset($sections[$course]->image))
                                <img src="{{ asset($sections[$course]->image) }}" width="200">
                            @endif
                        </div>
                        <hr>
                        @endforeach