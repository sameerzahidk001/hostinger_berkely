@php
    $inputId = $id ?? ('image_alt_' . md5($name));
@endphp
<div class="m-t-sm m-b-sm">
    <label for="{{ $inputId }}" class="control-label">Image alt text (SEO)</label>
    <input type="text" id="{{ $inputId }}" name="{{ $name }}" value="{{ $value ?? '' }}"
        class="form-control" maxlength="125" placeholder="Describe this image for accessibility and SEO">
</div>
