@extends('user.layout.app')
@section('title', $folder->name)
@push('style')
<style>
#smViewerBackdrop{position:fixed;inset:0;background:rgba(0,4,53,.55);display:none;align-items:center;justify-content:center;z-index:5000;padding:16px;}
#smViewerBackdrop.open{display:flex;}
#smViewerModal{background:#fff;width:min(1100px,100%);max-height:92vh;display:flex;flex-direction:column;border-radius:4px;overflow:hidden;}
#smViewerHeader{background:#000435;color:#fff;padding:12px 16px;display:flex;justify-content:space-between;align-items:center;gap:12px;}
#smViewerHeader strong{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
#smViewerActions{display:flex;align-items:center;gap:10px;flex-shrink:0;}
#smViewerDownload{background:#f8961f;color:#1e1e1e;text-decoration:none;font-weight:700;padding:6px 12px;border-radius:4px;}
#smViewerBody{background:#fff;min-height:420px;flex:1;position:relative;}
#smViewerBody.video{background:#000;}
#smViewerBody iframe,#smViewerBody video,#smViewerBody img,#smViewerBody audio{width:100%;height:72vh;border:0;background:#000;}
#smViewerBody img{object-fit:contain;background:#111;}
#smViewerClose{background:transparent;border:0;color:#fff;font-size:24px;cursor:pointer;line-height:1;}
#smViewerLoading{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.55);color:#fff;font-weight:600;z-index:2;}
#smViewerDownload.is-busy{opacity:.7;pointer-events:none;}

.sm-tree-folder{margin:0 0 14px;}
.sm-tree-folder-head{
    padding:10px 12px;
    background:#f7f8fa;
    border:1px solid #e8eaed;
    border-radius:6px;
    margin-bottom:10px;
}
.sm-tree-folder-head .fa{margin-right:6px;}
.sm-files-grid{
    display:grid;
    grid-template-columns:1fr;
    gap:10px;
    margin-bottom:14px;
}
@media (min-width: 768px){
    .sm-files-grid{grid-template-columns:1fr 1fr;}
}
.sm-file-card{
    border:1px solid #e8eaed;
    border-radius:6px;
    background:#fff;
    min-height:52px;
}
.sm-file-link{
    display:flex;
    align-items:flex-start;
    gap:10px;
    padding:12px 14px;
    color:#000435;
    font-weight:600;
    text-decoration:none;
    height:100%;
}
.sm-file-link:hover{
    background:#f8fafc;
    color:#000435;
    text-decoration:none;
}
.sm-file-icon{
    font-size:18px;
    line-height:1.3;
    flex-shrink:0;
    margin-top:1px;
}
.sm-file-name{
    word-break:break-word;
    line-height:1.35;
}
</style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>{{ $folder->name }}</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('user.study-materials.index') }}">Study Materials</a></li>
            <li class="active"><strong>{{ $folder->name }}</strong></li>
        </ol>
        <p class="text-muted" style="margin-bottom:4px;">
            Course:
            @if($folder->course)
                <a href="{{ route('course.details', ['course' => $folder->course->slug ?? $folder->course_id]) }}" target="_blank" rel="noopener"><strong>{{ $folder->course->title }}</strong></a>
            @else
                —
            @endif
        </p>
        @php
            $instructors = $folder->displayInstructors();
            $courseInstructorIds = course_instructor_ids($folder->course ?? null);
            $headOfFaculty = $instructors->firstWhere('id', $courseInstructorIds[0] ?? null) ?: ($instructors->count() > 1 ? $instructors->get(0) : null);
            $primaryInstructor = $instructors->firstWhere('id', $courseInstructorIds[1] ?? null)
                ?: ($instructors->count() > 1 ? $instructors->get(1) : $instructors->first());
        @endphp
        <p class="text-muted" style="margin-bottom:4px;">
            Head of Faculty:
            @if($headOfFaculty)
                <a href="{{ url('/instructor/' . $headOfFaculty->id) }}" target="_blank" rel="noopener"><strong>{{ $headOfFaculty->name }}</strong></a>
            @else
                —
            @endif
        </p>
        <p class="text-muted" style="margin-bottom:4px;">
            Instructor:
            @if($primaryInstructor)
                <a href="{{ url('/instructor/' . $primaryInstructor->id) }}" target="_blank" rel="noopener"><strong>{{ $primaryInstructor->name }}</strong></a>
            @else
                —
            @endif
        </p>
        <p class="text-muted" style="margin-bottom:4px;">Access Start: {{ optional($access->issued_at)->format('d M Y') ?: '—' }}</p>
        <p class="text-muted" style="margin-bottom:0;">Access Expire: {{ $access->access_till ? $access->access_till->format('d M Y') : 'No expiry' }}</p>
    </div>
</div>
<div class="wrapper wrapper-content">
    <div class="alert alert-info">Files open inside this page. A <strong>Download</strong> button appears only when download is allowed for that file.</div>
    <div class="ibox">
        <div class="ibox-content">
            @include('user.study-materials._tree', ['items' => $folder->rootItems])
        </div>
    </div>
</div>

<div id="smViewerBackdrop">
    <div id="smViewerModal">
        <div id="smViewerHeader">
            <strong id="smViewerTitle">File</strong>
            <div id="smViewerActions">
                <a id="smViewerDownload" href="#">Download</a>
                <button type="button" id="smViewerClose" aria-label="Close">&times;</button>
            </div>
        </div>
        <div id="smViewerBody"></div>
    </div>
</div>
@endsection
@push('script')
<script>
(function () {
    const backdrop = document.getElementById('smViewerBackdrop');
    const title = document.getElementById('smViewerTitle');
    const body = document.getElementById('smViewerBody');
    const closeBtn = document.getElementById('smViewerClose');
    const downloadBtn = document.getElementById('smViewerDownload');

    function closeViewer() {
        backdrop.classList.remove('open');
        body.classList.remove('video');
        body.innerHTML = '';
    }
    closeBtn.addEventListener('click', closeViewer);
    backdrop.addEventListener('click', function (e) {
        if (e.target === backdrop) closeViewer();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeViewer();
    });

    document.querySelectorAll('[data-sm-file]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            const url = el.getAttribute('href');
            const name = el.getAttribute('data-sm-name') || 'File';
            const kind = el.getAttribute('data-sm-kind') || 'file';
            const poster = el.getAttribute('data-sm-poster') || '';
            const download = el.getAttribute('data-sm-download') || '';
            title.textContent = name;
            if (download) {
                downloadBtn.href = download;
                downloadBtn.setAttribute('download', name);
                downloadBtn.style.display = '';
            } else {
                downloadBtn.removeAttribute('href');
                downloadBtn.removeAttribute('download');
                downloadBtn.style.display = 'none';
            }
            body.classList.toggle('video', kind === 'video');
            const safeUrl = url.replace(/"/g, '&quot;');
            const safeName = name.replace(/"/g, '');
            const isWorkdrive = /workdrive\.zoho/i.test(url);
            let media = '';
            if (kind === 'video' && !isWorkdrive) {
                media = '<video controls playsinline preload="metadata" src="' + safeUrl + '"' + (poster ? ' poster="' + poster.replace(/"/g, '&quot;') + '"' : '') + ' title="' + safeName + '"></video>';
            } else if (kind === 'video') {
                media = '<iframe src="' + safeUrl + '" title="' + safeName + '" allow="autoplay; fullscreen; encrypted-media; picture-in-picture" allowfullscreen></iframe>';
            } else if (kind === 'image') {
                media = '<img src="' + safeUrl + '" alt="' + safeName + '">';
            } else if (kind === 'audio') {
                media = '<audio controls src="' + safeUrl + '"></audio>';
            } else {
                media = '<iframe src="' + safeUrl + '" title="' + safeName + '" allow="fullscreen"></iframe>';
            }
            body.innerHTML = '<div id="smViewerLoading">' + (kind === 'video' ? 'Loading video…' : 'Opening file…') + '</div>' + media;
            const player = body.querySelector('video, audio, iframe, img');
            function hideLoading() {
                const loading = document.getElementById('smViewerLoading');
                if (loading) loading.remove();
            }
            if (player) {
                player.addEventListener('loadeddata', hideLoading);
                player.addEventListener('canplay', hideLoading);
                player.addEventListener('loadedmetadata', hideLoading);
                player.addEventListener('load', hideLoading);
                player.addEventListener('error', hideLoading);
            }
            setTimeout(hideLoading, 20000);
            backdrop.classList.add('open');
        });
    });

    downloadBtn.addEventListener('click', function (e) {
        const href = downloadBtn.getAttribute('href');
        if (!href || href === '#') {
            e.preventDefault();
            return;
        }
        e.preventDefault();
        const frame = document.createElement('iframe');
        frame.style.display = 'none';
        frame.setAttribute('aria-hidden', 'true');
        frame.src = href;
        document.body.appendChild(frame);
        setTimeout(function () { frame.remove(); }, 120000);
    });
})();
</script>
@endpush
