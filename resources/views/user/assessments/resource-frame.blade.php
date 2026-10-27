<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $name ?? 'File' }}</title>
    <style>
        html, body { margin: 0; height: 100%; background: #111; overflow: hidden; }
        .wrap { width: 100%; height: 100%; overflow: auto; -webkit-user-select: none; user-select: none; }
        img { width: 100%; height: 100%; object-fit: contain; background: #000; display: block; pointer-events: none; }
        #pdf-pages { padding: 8px 0 24px; text-align: center; }
        #pdf-pages canvas { display: block; margin: 0 auto 10px; max-width: 100%; height: auto; background: #fff; pointer-events: none; }
        .pdf-fail { color: #ddd; font-family: sans-serif; padding: 24px; text-align: center; }
    </style>
</head>
<body oncontextmenu="return false">
    <div class="wrap">
        @if(($kind ?? '') === 'image')
            <img src="{{ $rawUrl }}" alt="" draggable="false">
        @else
            <div id="pdf-pages"></div>
            <script src="{{ asset('student/js/plugins/pdfjs/pdf.js') }}"></script>
            <script>
            (function () {
                document.addEventListener('keydown', function (e) {
                    var k = (e.key || '').toLowerCase();
                    if ((e.ctrlKey || e.metaKey) && (k === 's' || k === 'p' || k === 'u')) {
                        e.preventDefault();
                    }
                    if (k === 'f12' || (e.ctrlKey && e.shiftKey && (k === 'i' || k === 'j' || k === 'c'))) {
                        e.preventDefault();
                    }
                }, true);
                var url = @json($rawUrl);
                var host = document.getElementById('pdf-pages');
                var PDFJS = window.PDFJS || (window.pdfjsDistBuildPdf && window.pdfjsDistBuildPdf.PDFJS);
                function fail() {
                    host.innerHTML = '<p class="pdf-fail">Unable to open this file.</p>';
                }
                if (!PDFJS || !host) {
                    fail();
                    return;
                }
                PDFJS.workerSrc = @json(asset('student/js/plugins/pdfjs/pdf.worker.js'));
                fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/octet-stream' } })
                    .then(function (res) { return res.arrayBuffer(); })
                    .then(function (data) {
                        return PDFJS.getDocument({ data: data });
                    })
                    .then(function (pdf) {
                        var chain = Promise.resolve();
                        var renderPage = function (n) {
                            return pdf.getPage(n).then(function (page) {
                                var viewport = page.getViewport(1.35);
                                var canvas = document.createElement('canvas');
                                var ctx = canvas.getContext('2d');
                                canvas.width = viewport.width;
                                canvas.height = viewport.height;
                                host.appendChild(canvas);
                                var task = page.render({ canvasContext: ctx, viewport: viewport });
                                return task && task.promise ? task.promise : task;
                            });
                        };
                        for (var i = 1; i <= pdf.numPages; i++) {
                            (function (n) { chain = chain.then(function () { return renderPage(n); }); })(i);
                        }
                    })
                    .then(null, fail);
            })();
            </script>
        @endif
    </div>
</body>
</html>
