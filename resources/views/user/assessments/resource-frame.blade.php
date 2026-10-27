<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $name ?? 'File' }}</title>
    <style>
        html, body { margin: 0; height: 100%; background: #111; }
        .wrap { width: 100%; height: 100%; overflow: auto; }
        iframe, embed, object, img { width: 100%; height: 100%; border: 0; display: block; background: #111; }
        img { object-fit: contain; background: #000; }
        #pdf-pages { padding: 12px 0 40px; text-align: center; }
        #pdf-pages canvas { display: block; margin: 0 auto 12px; max-width: 100%; height: auto; background: #fff; box-shadow: 0 1px 6px rgba(0,0,0,.4); }
        .pdf-fail { color: #fff; font-family: sans-serif; padding: 24px; text-align: center; }
    </style>
</head>
<body>
    <div class="wrap">
        @if(($kind ?? '') === 'image')
            <img src="{{ $rawUrl }}" alt="{{ $name ?? 'File' }}" draggable="false">
        @elseif(($kind ?? '') === 'office' && !empty($officeEmbed))
            <iframe src="{{ $officeEmbed }}" title="{{ $name ?? 'File' }}" allow="fullscreen" sandbox="allow-scripts allow-same-origin allow-popups"></iframe>
        @elseif(($kind ?? '') === 'pdf')
            <div id="pdf-pages"></div>
            <script src="{{ asset('student/js/plugins/pdfjs/pdf.js') }}"></script>
            <script>
            (function () {
                var url = @json($rawUrl);
                var host = document.getElementById('pdf-pages');
                var PDFJS = window.PDFJS || (window.pdfjsDistBuildPdf && window.pdfjsDistBuildPdf.PDFJS);
                if (!PDFJS || !host) {
                    host.innerHTML = '<p class="pdf-fail">Unable to open this file in the exam viewer.</p>';
                    return;
                }
                PDFJS.workerSrc = @json(asset('student/js/plugins/pdfjs/pdf.worker.js'));
                PDFJS.disableWorker = false;
                PDFJS.getDocument(url).then(function (pdf) {
                    var renderPage = function (n) {
                        return pdf.getPage(n).then(function (page) {
                            var scale = 1.35;
                            var viewport = page.getViewport(scale);
                            var canvas = document.createElement('canvas');
                            var ctx = canvas.getContext('2d');
                            canvas.width = viewport.width;
                            canvas.height = viewport.height;
                            host.appendChild(canvas);
                            var task = page.render({ canvasContext: ctx, viewport: viewport });
                            return task && task.promise ? task.promise : task;
                        });
                    };
                    var chain = Promise.resolve();
                    for (var i = 1; i <= pdf.numPages; i++) {
                        (function (n) { chain = chain.then(function () { return renderPage(n); }); })(i);
                    }
                }).then(null, function () {
                    host.innerHTML = '<p class="pdf-fail">Unable to open this file in the exam viewer.</p>';
                });
            })();
            </script>
        @else
            <p class="pdf-fail">This file type cannot be previewed during the test.</p>
        @endif
    </div>
</body>
</html>
