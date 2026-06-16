{{-- Academies Section --}}
                        <h3>Academies</h3>
                        @foreach(['cfo_academy', 'ceo_academy', 'entrepreneurs_academy'] as $academy)
                        <div class="form-group">
                            <h4>{{ ucfirst(str_replace('_', ' ', $academy)) }}</h4>
                            <label>Title:</label>
                            <input type="text" name="sections[{{ $academy }}][title]" class="form-control" value="{{ $sections[$academy]->title ?? '' }}" required>

                            <label>Description:</label>
                            <textarea name="sections[{{ $academy }}][description]" class="form-control">{{ $sections[$academy]->description ?? '' }}</textarea>

                            <label>Link:</label>
                            <input type="text" name="sections[{{ $academy }}][link]" class="form-control" value="{{ $sections[$academy]->link ?? '' }}">

                            <label>Image:</label>
                            <input type="file" name="sections[{{ $academy }}][image]" class="form-control">
                            @if(isset($sections[$academy]->image))
                                <img src="{{ asset($sections[$academy]->image) }}" width="200">
                            @endif
                        </div>
                        <hr>
                        @endforeach