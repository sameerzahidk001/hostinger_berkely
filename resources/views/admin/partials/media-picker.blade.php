<input type="file" id="global_media_input" style="display:none" accept="image/*">

<div id="mediaPickSourceModal" class="modal fade" tabindex="-1" role="dialog"
    aria-labelledby="mediaPickSourceModalLabel">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="mediaPickSourceModalLabel">Select Media</h4>
            </div>
            <div class="modal-body text-center">
                <button type="button" class="btn btn-primary btn-block" onclick="MediaPicker.pickLocal()">Upload from
                    Computer</button>
                <button type="button" class="btn btn-success btn-block" onclick="MediaPicker.openFileManager()">Choose
                    from File Manager</button>
            </div>
        </div>
    </div>
</div>

<div id="mediaFileManagerModal" class="modal fade" tabindex="-1" role="dialog"
    aria-labelledby="mediaFileManagerModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button class="close" type="button" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="mediaFileManagerModalLabel">Choose Media</h4>
            </div>
            <div class="modal-body" style="max-height: 65vh; overflow-y:auto;" id="mediaFileManagerBody">
                <div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x"></i> Loading...</div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="MediaPicker.confirm()">Select</button>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
if (!window.MediaPicker) {
    window.MediaPicker = {
        active: null,
        selectedUrls: [],

        open(cfg) {
            this.active = Object.assign({ multiple: false, accept: 'image/*', fmType: 'image' }, cfg || {});
            this.selectedUrls = [];

            const g = document.getElementById('global_media_input');
            g.accept = this.active.accept;
            g.multiple = !!this.active.multiple;

            $('#mediaPickSourceModal').modal('show');
        },

        pickLocal() {
            $('#mediaPickSourceModal').modal('hide');
            const g = document.getElementById('global_media_input');

            const onChange = (e) => {
                const files = Array.from(e.target.files || []);
                if (!files.length) return;

                const fileField = document.getElementById(this.active.idBase + '_file');
                const pathField = document.getElementById(this.active.idBase + '_path');
                const display = document.getElementById(this.active.idBase + '_display');

                if (pathField) pathField.value = '';

                const dt = new DataTransfer();
                files.forEach(f => dt.items.add(f));
                if (fileField) fileField.files = dt.files;

                if (display) display.value = files.map(f => f.name).join(', ');

                this.renderPreview(files.map(f => URL.createObjectURL(f)));

                g.removeEventListener('change', onChange);
                g.value = '';
            };

            g.addEventListener('change', onChange, { once: true });
            g.click();
        },

        openFileManager() {
            $('#mediaPickSourceModal').modal('hide');

            $('#mediaFileManagerModal').modal('show');
            $('#mediaFileManagerBody').html('<div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x"></i> Loading...</div>');

            $('#mediaFileManagerBody').load("{{ route('file-manager.index') }}?type=" + encodeURIComponent(this.active.fmType), () => {
                const syncUI = () => {
                    $('#mediaFileManagerBody .file-thumbnail').each((_, el) => {
                        const url = $(el).data('url');
                        $(el).toggleClass('selected', this.selectedUrls.includes(url));
                    });
                    $('#mediaFileManagerBody .image-selector').each((_, cb) => {
                        const url = cb.value;
                        cb.checked = this.selectedUrls.includes(url);
                    });
                };

                $('#mediaFileManagerBody').off('click.thumb')
                    .on('click.thumb', '.file-thumbnail', (ev) => {
                        const url = $(ev.currentTarget).data('url');
                        if (this.active.multiple) {
                            const i = this.selectedUrls.indexOf(url);
                            if (i >= 0) this.selectedUrls.splice(i, 1); else this.selectedUrls.push(url);
                        } else {
                            this.selectedUrls = [url];
                        }
                        syncUI();
                    });

                $('#mediaFileManagerBody').off('change.cb')
                    .on('change.cb', '.image-selector', (ev) => {
                        const url = ev.currentTarget.value;
                        if (this.active.multiple) {
                            if (ev.currentTarget.checked) {
                                if (!this.selectedUrls.includes(url)) this.selectedUrls.push(url);
                            } else {
                                this.selectedUrls = this.selectedUrls.filter(u => u !== url);
                            }
                        } else {
                            this.selectedUrls = [url];
                            $('#mediaFileManagerBody .image-selector').not(ev.currentTarget).prop('checked', false);
                        }
                        syncUI();
                    });

                syncUI();
            });
        },

        confirm() {
            const pathField = document.getElementById(this.active.idBase + '_path');
            const fileField = document.getElementById(this.active.idBase + '_file');
            const display = document.getElementById(this.active.idBase + '_display');

            if (pathField) pathField.value = this.active.multiple ? this.selectedUrls.join(',') : (this.selectedUrls[0] || '');
            if (fileField) fileField.value = '';
            if (display) display.value = pathField ? pathField.value : '';

            this.renderPreview(this.selectedUrls);

            $('#mediaFileManagerModal').modal('hide');
        },

        renderPreview(urls) {
            const wrap = document.getElementById(this.active.idBase + '_preview');
            if (!wrap) return;
            wrap.innerHTML = '';
            (urls || []).forEach(u => {
                const img = document.createElement('img');
                img.src = u;
                img.className = 'img img-bordered picker-preview';
                img.style.maxHeight = '60px';
                img.style.marginRight = '6px';
                wrap.appendChild(img);
            });
        }
    };
}
</script>
@endpush
