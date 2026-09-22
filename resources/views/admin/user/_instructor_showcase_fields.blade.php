@php
    \App\Models\User::ensureInstructorExtraColumns();
    $showcase = $user->instructorShowcaseData();
    $conferenceItems = old('conference.items', $showcase['conference_speaking']['items'] ?: [['name' => '', 'venue' => '', 'date' => '', 'link' => '']]);
    if ($conferenceItems === []) {
        $conferenceItems = [['name' => '', 'venue' => '', 'date' => '', 'link' => '']];
    }
    $awardsRows = old('awards', $showcase['awards'] ?: [['image' => '', 'name' => '', 'venue' => '', 'date' => '', 'link' => '']]);
    if ($awardsRows === []) {
        $awardsRows = [['image' => '', 'name' => '', 'venue' => '', 'date' => '', 'link' => '']];
    }
    $booksRows = old('books', $showcase['books'] ?: [['image' => '', 'name' => '', 'description' => '', 'link' => '']]);
    if ($booksRows === []) {
        $booksRows = [['image' => '', 'name' => '', 'description' => '', 'link' => '']];
    }
    $articleTopics = old('articles.topics', $showcase['articles_writing']['topics'] ?: [['title' => '', 'link' => '']]);
    if ($articleTopics === []) {
        $articleTopics = [['title' => '', 'link' => '']];
    }
    $podcastRows = old('podcasts', $showcase['podcasts'] ?: [['image' => '', 'name' => '', 'date' => '', 'link' => '']]);
    if ($podcastRows === []) {
        $podcastRows = [['image' => '', 'name' => '', 'date' => '', 'link' => '']];
    }
    $conferenceImage = old('conference.existing_image', $showcase['conference_speaking']['image'] ?? '');
    $articlesDescription = old('articles.description', $showcase['articles_writing']['description'] ?? '');
@endphp

<style>
    .showcase-card {
        border: 1px solid #e8e8e8;
        border-radius: 8px;
        padding: 14px;
        margin-bottom: 12px;
        background: #fafafa;
    }
    .showcase-card .remove-row-btn {
        margin-top: 8px;
    }
    .showcase-thumb {
        max-width: 120px;
        max-height: 90px;
        border-radius: 6px;
        margin-top: 6px;
        object-fit: cover;
    }
</style>

{{-- CONFERENCE SPEAKING --}}
<h3 class="profile-section-heading">Conference Speaking</h3>
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="conference_image">Left-side image</label>
        <input type="file" name="conference_image" id="conference_image" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp">
        <input type="hidden" name="conference[existing_image]" value="{{ $conferenceImage }}">
        @if($conferenceImage)
            <div class="mt-2">
                <img src="{{ asset($conferenceImage) }}" alt="Conference" class="showcase-thumb">
                <label class="d-block mt-1" style="font-weight:normal;">
                    <input type="checkbox" name="conference_remove_image" value="1"> Remove image
                </label>
            </div>
        @endif
    </div>
    <div class="col-md-8 mb-3">
        <label>Conferences (name, venue, date, optional link)</label>
        <div id="conference-items-wrapper">
            @foreach($conferenceItems as $i => $item)
                <div class="showcase-card conference-item-row">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <input type="text" name="conference[items][{{ $i }}][name]" class="form-control"
                                placeholder="Conference name" value="{{ $item['name'] ?? '' }}" maxlength="255">
                        </div>
                        <div class="col-md-6 mb-2">
                            <input type="text" name="conference[items][{{ $i }}][venue]" class="form-control"
                                placeholder="Venue" value="{{ $item['venue'] ?? '' }}" maxlength="255">
                        </div>
                        <div class="col-md-6 mb-2">
                            <input type="date" name="conference[items][{{ $i }}][date]" class="form-control"
                                value="{{ $item['date'] ?? '' }}">
                        </div>
                        <div class="col-md-6 mb-2">
                            <input type="url" name="conference[items][{{ $i }}][link]" class="form-control"
                                placeholder="Event link (optional)" value="{{ $item['link'] ?? '' }}">
                        </div>
                    </div>
                    @if($i > 0)
                        <button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="this.closest('.conference-item-row').remove()">Remove</button>
                    @endif
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addConferenceItem()">Add Conference</button>
    </div>
</div>

{{-- AWARDS --}}
<h3 class="profile-section-heading">Awards</h3>
<p class="text-muted" style="margin-top:-8px;">Picture, event name, venue, date, optional link. Shown 4 per row on the front page.</p>
<div id="awards-wrapper">
    @foreach($awardsRows as $i => $item)
        <div class="showcase-card award-row">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <label>Picture</label>
                    <input type="file" name="awards[{{ $i }}][image]" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp">
                    <input type="hidden" name="awards[{{ $i }}][existing_image]" value="{{ $item['image'] ?? ($item['existing_image'] ?? '') }}">
                    @if(!empty($item['image'] ?? $item['existing_image'] ?? ''))
                        <img src="{{ asset($item['image'] ?? $item['existing_image']) }}" alt="" class="showcase-thumb">
                        <label class="d-block mt-1" style="font-weight:normal;">
                            <input type="checkbox" name="awards[{{ $i }}][remove_image]" value="1"> Remove image
                        </label>
                    @endif
                </div>
                <div class="col-md-3 mb-2">
                    <label>Event name</label>
                    <input type="text" name="awards[{{ $i }}][name]" class="form-control" placeholder="Event name"
                        value="{{ $item['name'] ?? '' }}" maxlength="255">
                </div>
                <div class="col-md-3 mb-2">
                    <label>Venue</label>
                    <input type="text" name="awards[{{ $i }}][venue]" class="form-control" placeholder="Venue"
                        value="{{ $item['venue'] ?? '' }}" maxlength="255">
                </div>
                <div class="col-md-3 mb-2">
                    <label>Date</label>
                    <input type="date" name="awards[{{ $i }}][date]" class="form-control" value="{{ $item['date'] ?? '' }}">
                </div>
                <div class="col-md-12 mb-2">
                    <label>Event link (optional)</label>
                    <input type="url" name="awards[{{ $i }}][link]" class="form-control" placeholder="https://…"
                        value="{{ $item['link'] ?? '' }}">
                </div>
            </div>
            @if($i > 0)
                <button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="this.closest('.award-row').remove()">Remove</button>
            @endif
        </div>
    @endforeach
</div>
<button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="addAwardRow()">Add Award</button>

{{-- BOOK AUTHORING --}}
<h3 class="profile-section-heading">Book Authoring</h3>
<div id="books-wrapper">
    @foreach($booksRows as $i => $item)
        <div class="showcase-card book-row">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <label>Picture</label>
                    <input type="file" name="books[{{ $i }}][image]" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp">
                    <input type="hidden" name="books[{{ $i }}][existing_image]" value="{{ $item['image'] ?? ($item['existing_image'] ?? '') }}">
                    @if(!empty($item['image'] ?? $item['existing_image'] ?? ''))
                        <img src="{{ asset($item['image'] ?? $item['existing_image']) }}" alt="" class="showcase-thumb">
                        <label class="d-block mt-1" style="font-weight:normal;">
                            <input type="checkbox" name="books[{{ $i }}][remove_image]" value="1"> Remove image
                        </label>
                    @endif
                </div>
                <div class="col-md-9 mb-2">
                    <label>Book name</label>
                    <input type="text" name="books[{{ $i }}][name]" class="form-control" placeholder="Book name"
                        value="{{ $item['name'] ?? '' }}" maxlength="255">
                    <label class="mt-2">Description <span class="text-muted book-desc-count">(0 / 500)</span></label>
                    <textarea name="books[{{ $i }}][description]" class="form-control book-description" rows="3"
                        maxlength="500" placeholder="Up to 500 characters">{{ $item['description'] ?? '' }}</textarea>
                    <label class="mt-2">Link (optional)</label>
                    <input type="url" name="books[{{ $i }}][link]" class="form-control" placeholder="https://…"
                        value="{{ $item['link'] ?? '' }}">
                </div>
            </div>
            @if($i > 0)
                <button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="this.closest('.book-row').remove()">Remove</button>
            @endif
        </div>
    @endforeach
</div>
<button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="addBookRow()">Add Book</button>

{{-- ARTICLES WRITING --}}
<h3 class="profile-section-heading">Articles Writing</h3>
<div class="form-group mb-3">
    <label for="articles_description">Description</label>
    <textarea name="articles[description]" id="articles_description" class="form-control" rows="3"
        placeholder="Brief description of your articles writing…">{{ $articlesDescription }}</textarea>
</div>
<label>Article topics (shown in 2 columns; link optional)</label>
<div id="article-topics-wrapper">
    @foreach($articleTopics as $i => $item)
        <div class="showcase-card article-topic-row">
            <div class="row">
                <div class="col-md-6 mb-2">
                    <input type="text" name="articles[topics][{{ $i }}][title]" class="form-control"
                        placeholder="Article topic" value="{{ $item['title'] ?? '' }}" maxlength="255">
                </div>
                <div class="col-md-6 mb-2">
                    <input type="url" name="articles[topics][{{ $i }}][link]" class="form-control"
                        placeholder="External link (optional)" value="{{ $item['link'] ?? '' }}">
                </div>
            </div>
            @if($i > 0)
                <button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="this.closest('.article-topic-row').remove()">Remove</button>
            @endif
        </div>
    @endforeach
</div>
<button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="addArticleTopic()">Add Topic</button>

{{-- PODCASTS --}}
<h3 class="profile-section-heading">Podcasts</h3>
<p class="text-muted" style="margin-top:-8px;">Picture, podcast name, date, optional link. Shown 4 per row on the front page.</p>
<div id="podcasts-wrapper">
    @foreach($podcastRows as $i => $item)
        <div class="showcase-card podcast-row">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <label>Picture</label>
                    <input type="file" name="podcasts[{{ $i }}][image]" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp">
                    <input type="hidden" name="podcasts[{{ $i }}][existing_image]" value="{{ $item['image'] ?? ($item['existing_image'] ?? '') }}">
                    @if(!empty($item['image'] ?? $item['existing_image'] ?? ''))
                        <img src="{{ asset($item['image'] ?? $item['existing_image']) }}" alt="" class="showcase-thumb">
                        <label class="d-block mt-1" style="font-weight:normal;">
                            <input type="checkbox" name="podcasts[{{ $i }}][remove_image]" value="1"> Remove image
                        </label>
                    @endif
                </div>
                <div class="col-md-3 mb-2">
                    <label>Podcast name</label>
                    <input type="text" name="podcasts[{{ $i }}][name]" class="form-control" placeholder="Podcast name"
                        value="{{ $item['name'] ?? '' }}" maxlength="255">
                </div>
                <div class="col-md-3 mb-2">
                    <label>Date</label>
                    <input type="date" name="podcasts[{{ $i }}][date]" class="form-control" value="{{ $item['date'] ?? '' }}">
                </div>
                <div class="col-md-3 mb-2">
                    <label>Link (optional)</label>
                    <input type="url" name="podcasts[{{ $i }}][link]" class="form-control" placeholder="https://…"
                        value="{{ $item['link'] ?? '' }}">
                </div>
            </div>
            @if($i > 0)
                <button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="this.closest('.podcast-row').remove()">Remove</button>
            @endif
        </div>
    @endforeach
</div>
<button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="addPodcastRow()">Add Podcast</button>

<script>
(function () {
    function nextIndex(wrapper, rowClass) {
        return wrapper.querySelectorAll('.' + rowClass).length;
    }

    function bindBookCounters(root) {
        (root || document).querySelectorAll('.book-row').forEach(function (row) {
            var ta = row.querySelector('.book-description');
            var count = row.querySelector('.book-desc-count');
            if (!ta || !count || ta._bound) return;
            ta._bound = true;
            var update = function () { count.textContent = '(' + (ta.value || '').length + ' / 500)'; };
            ta.addEventListener('input', update);
            update();
        });
    }
    bindBookCounters();

    window.addConferenceItem = function () {
        var wrap = document.getElementById('conference-items-wrapper');
        var i = nextIndex(wrap, 'conference-item-row');
        var div = document.createElement('div');
        div.className = 'showcase-card conference-item-row';
        div.innerHTML = '<div class="row">' +
            '<div class="col-md-6 mb-2"><input type="text" name="conference[items][' + i + '][name]" class="form-control" placeholder="Conference name" maxlength="255"></div>' +
            '<div class="col-md-6 mb-2"><input type="text" name="conference[items][' + i + '][venue]" class="form-control" placeholder="Venue" maxlength="255"></div>' +
            '<div class="col-md-6 mb-2"><input type="date" name="conference[items][' + i + '][date]" class="form-control"></div>' +
            '<div class="col-md-6 mb-2"><input type="url" name="conference[items][' + i + '][link]" class="form-control" placeholder="Event link (optional)"></div>' +
            '</div><button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="this.closest(\'.conference-item-row\').remove()">Remove</button>';
        wrap.appendChild(div);
    };

    window.addAwardRow = function () {
        var wrap = document.getElementById('awards-wrapper');
        var i = nextIndex(wrap, 'award-row');
        var div = document.createElement('div');
        div.className = 'showcase-card award-row';
        div.innerHTML = '<div class="row">' +
            '<div class="col-md-3 mb-2"><label>Picture</label><input type="file" name="awards[' + i + '][image]" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp"><input type="hidden" name="awards[' + i + '][existing_image]" value=""></div>' +
            '<div class="col-md-3 mb-2"><label>Event name</label><input type="text" name="awards[' + i + '][name]" class="form-control" placeholder="Event name" maxlength="255"></div>' +
            '<div class="col-md-3 mb-2"><label>Venue</label><input type="text" name="awards[' + i + '][venue]" class="form-control" placeholder="Venue" maxlength="255"></div>' +
            '<div class="col-md-3 mb-2"><label>Date</label><input type="date" name="awards[' + i + '][date]" class="form-control"></div>' +
            '<div class="col-md-12 mb-2"><label>Event link (optional)</label><input type="url" name="awards[' + i + '][link]" class="form-control" placeholder="https://…"></div>' +
            '</div><button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="this.closest(\'.award-row\').remove()">Remove</button>';
        wrap.appendChild(div);
    };

    window.addBookRow = function () {
        var wrap = document.getElementById('books-wrapper');
        var i = nextIndex(wrap, 'book-row');
        var div = document.createElement('div');
        div.className = 'showcase-card book-row';
        div.innerHTML = '<div class="row">' +
            '<div class="col-md-3 mb-2"><label>Picture</label><input type="file" name="books[' + i + '][image]" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp"><input type="hidden" name="books[' + i + '][existing_image]" value=""></div>' +
            '<div class="col-md-9 mb-2"><label>Book name</label><input type="text" name="books[' + i + '][name]" class="form-control" placeholder="Book name" maxlength="255">' +
            '<label class="mt-2">Description <span class="text-muted book-desc-count">(0 / 500)</span></label>' +
            '<textarea name="books[' + i + '][description]" class="form-control book-description" rows="3" maxlength="500" placeholder="Up to 500 characters"></textarea>' +
            '<label class="mt-2">Link (optional)</label><input type="url" name="books[' + i + '][link]" class="form-control" placeholder="https://…"></div>' +
            '</div><button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="this.closest(\'.book-row\').remove()">Remove</button>';
        wrap.appendChild(div);
        bindBookCounters(div);
    };

    window.addArticleTopic = function () {
        var wrap = document.getElementById('article-topics-wrapper');
        var i = nextIndex(wrap, 'article-topic-row');
        var div = document.createElement('div');
        div.className = 'showcase-card article-topic-row';
        div.innerHTML = '<div class="row">' +
            '<div class="col-md-6 mb-2"><input type="text" name="articles[topics][' + i + '][title]" class="form-control" placeholder="Article topic" maxlength="255"></div>' +
            '<div class="col-md-6 mb-2"><input type="url" name="articles[topics][' + i + '][link]" class="form-control" placeholder="External link (optional)"></div>' +
            '</div><button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="this.closest(\'.article-topic-row\').remove()">Remove</button>';
        wrap.appendChild(div);
    };

    window.addPodcastRow = function () {
        var wrap = document.getElementById('podcasts-wrapper');
        var i = nextIndex(wrap, 'podcast-row');
        var div = document.createElement('div');
        div.className = 'showcase-card podcast-row';
        div.innerHTML = '<div class="row">' +
            '<div class="col-md-3 mb-2"><label>Picture</label><input type="file" name="podcasts[' + i + '][image]" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp"><input type="hidden" name="podcasts[' + i + '][existing_image]" value=""></div>' +
            '<div class="col-md-3 mb-2"><label>Podcast name</label><input type="text" name="podcasts[' + i + '][name]" class="form-control" placeholder="Podcast name" maxlength="255"></div>' +
            '<div class="col-md-3 mb-2"><label>Date</label><input type="date" name="podcasts[' + i + '][date]" class="form-control"></div>' +
            '<div class="col-md-3 mb-2"><label>Link (optional)</label><input type="url" name="podcasts[' + i + '][link]" class="form-control" placeholder="https://…"></div>' +
            '</div><button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="this.closest(\'.podcast-row\').remove()">Remove</button>';
        wrap.appendChild(div);
    };
})();
</script>
