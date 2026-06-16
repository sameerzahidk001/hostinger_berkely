@extends('admin.layout.app')
@section('title', 'Subjects')
@push('style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery-tags-input@1.3.5/dist/jquery.tagsinput.min.css">
    <style>
        .page-heading {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }

        .col {
            flex: 1;
        }

        .instruction {
            font-size: 14px;
            color: #555;
            margin-top: 5px;
        }
        #cc_tagsinput, #bcc_tagsinput{
            width: 100% !important;
            height: auto !important;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            min-height: auto !important;
        }
        #cc_addTag, #bcc_addTag{
            flex: 0 0 auto;
        }
        .tag, #cc_tag, #bcc_tag{
            margin: 0px !important;
            width: auto !important;
        }
        .tags_clear{
            display: none !important;
        }
    </style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col">
        <h2>Email Templates</h2>
        <ol class="breadcrumb">
            <li>
                <a href="{{ route('admin.home') }}">Home</a>
            </li>
            <li>
                <a href="{{ route('admin.emails.index') }}">Email Templates</a>
            </li>
            <li class="active">
                <strong>Create</strong>
            </li>
        </ol>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                <div class="ibox-title">
                    <h5>Create</h5>
                </div>

                <div class="ibox-content">

                    <form role="form" action="{{ route('admin.emails.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-lg-3" style="margin-bottom: 15px;">
                                <label for="name">Name</label>
                                <select name="name" id="name" class="form-control">
                                    <option value="">Select Email</option>
                                    <option value="user-registration" {{ old('name') == 'user-registration' ? 'selected' : '' }}>User Registration</option>
                                    <option value="email-verification" {{ old('name') == 'email-verification' ? 'selected' : '' }}>Email Verification</option>
                                    <option value="user-suspend" {{ old('name') == 'user-suspend' ? 'selected' : '' }}>User Suspend</option>
                                    <option value="user-active" {{ old('name') == 'user-active' ? 'selected' : '' }}>User Active</option>
                                    <option value="user-update" {{ old('name') == 'user-update' ? 'selected' : '' }}>User Update</option>
                                    <option value="password-reset" {{ old('name') == 'password-reset' ? 'selected' : '' }}>Password Reset</option>
                                    <option value="password-changed" {{ old('name') == 'password-changed' ? 'selected' : '' }}>Password Changed</option>
                                    <option value="fees-paid" {{ old('name') == 'fees-paid' ? 'selected' : '' }}>Fees Paid</option>
                                    <option value="send-invoice" {{ old('name') == 'send-invoice' ? 'selected' : '' }}>Send Invoice</option>
                                    <option value="send-receipt" {{ old('name') == 'send-receipt' ? 'selected' : '' }}>Send Receipt</option>
                                    <option value="admin-user-update" {{ old('name') == 'admin-user-update' ? 'selected' : '' }}>Admin User Update</option>
                                    <option value="admin-user-password-changed" {{ old('name') == 'admin-user-password-changed' ? 'selected' : '' }}>Admin User Password Changed</option>
                                </select>
                                @error('name')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-lg-9" style="margin-bottom: 15px;">
                                <label for="subject">Subject</label>
                                <input type="text" name="subject" id="subject" class="form-control" value="{{ old('subject') }}">
                                @error('subject')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-lg-12" style="margin-bottom: 15px;">
                                <label for="">CC Emails</label>
                                <input name="cc" id="cc" value="{{ old('cc') }}" class="form-control" />
                                @error('cc')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-lg-12" style="margin-bottom: 15px;">
                                <label for="">BCC Emails</label>
                                <input name="bcc" id="bcc" value="{{ old('bcc') }}" class="form-control" />
                                @error('bcc')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-lg-12 mb">
                                <label for="body">Description</label>
                                <p class="instruction" id="email-instruction"></p>
                                <textarea class="form-control" id="description" placeholder="Write something here..."
                                    name="body">{!! old('body') !!}</textarea>
                                @error('body')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 16px;text-align:right;">
                                <button class="btn btn-primary " type="submit"><i
                                        class="fa fa-check"></i>&nbsp;Submit</button>
                                <a href="{{ route('admin.emails.index') }}" class="btn btn-secondary"><i
                                        class="fa fa-arrow-left"></i>&nbsp;Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/jquery-tags-input@1.3.5/dist/jquery.tagsinput.min.js"></script>
    <script>
        $('#cc').tagsInput({
            'defaultText': 'Add an email',
            'unique': true,
            'onAddTag': function (email) {
                if (!validateEmail(email)) {
                    alert('Invalid email format: ' + email);
                    $('#cc').removeTag(email); // Remove invalid email
                }
            }
        });

        $('#bcc').tagsInput({
            'defaultText': 'Add an email',
            'unique': true,
            'onAddTag': function (email) {
                if (!validateEmail(email)) {
                    alert('Invalid email format: ' + email);
                    $('#cc').removeTag(email); // Remove invalid email
                }
            }
        });

        function validateEmail(email) {
            var emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            return emailPattern.test(email);
        }
        document.addEventListener('DOMContentLoaded', (event) => {
            let editorElement = document.getElementById('description');
            let instructionText = document.getElementById('email-instruction');
            let emailSelect = document.getElementById('name');

            function updateInstruction() {
                if (emailSelect.value === 'user-registration') {
                    instructionText.innerHTML = "You can use the following placeholders in the email body:<br> <strong>{name}</strong> - User's Name <br> <strong>{email}</strong> - User's Email <br> <strong>{password}</strong> - User's Password <br> <strong>{role}</strong> - User's Role";
                } else if (emailSelect.value === 'email-verification') {
                    instructionText.innerHTML = "You can use the following placeholders in the email body:<br> <strong>{name}</strong> - User's Name <br> <strong>{email}</strong> - User's Email <br> <strong>{email-verification}</strong> - Email Verification link";
                } else if (emailSelect.value === 'user-suspend') {
                    instructionText.innerHTML = "You can use the following placeholders in the email body:<br> <strong>{name}</strong> - User's Name <br> <strong>{email}</strong> - User's Email";
                } else if (emailSelect.value === 'user-active') {
                    instructionText.innerHTML = "You can use the following placeholders in the email body:<br> <strong>{name}</strong> - User's Name <br> <strong>{email}</strong> - User's Email";
                } else if (emailSelect.value === 'user-update') {
                    instructionText.innerHTML = "You can use the following placeholders in the email body:<br> <strong>{name}</strong> - User's Name <br> <strong>{email}</strong> - User's Email <br> <strong>{password}</strong> - User's Password";
                } else if (emailSelect.value === 'fees-paid') {
                    instructionText.innerHTML = "You can use the following placeholders in the email body:<br> <strong>{name}</strong> - User's Name <br> <strong>{email}</strong> - User's Email <br> <strong>{fees-paid}</strong> - Fees Paid <br> <strong>{fees-installment}</strong> - Fees Installment";
                } else if(emailSelect.value == 'password-reset') {
                    instructionText.innerHTML = "You can use the following placeholders in the email body:<br> <strong>{name}</strong> - User's Name <br> <strong>{email}</strong> - User's Email <br> <strong>{password-reset}</strong> - Password Reset";
                } else if (emailSelect.value === 'fees-paid') {
                    instructionText.innerHTML = "You can use the following placeholders in the email body:<br> <strong>{name}</strong> - User's Name <br> <strong>{email}</strong> - User's Email <br> <strong>{fees-paid}</strong> - Fees Paid <br> <strong>{fees-installment}</strong> - Fees Installment";
                } else if (emailSelect.value === 'send-invoice') {
                    instructionText.innerHTML = "You can use the following placeholders in the email body:<br> <strong>{name}</strong> - User's Name <br> <strong>{email}</strong> - User's Email";
                } else if (emailSelect.value === 'send-receipt') {
                    instructionText.innerHTML = "You can use the following placeholders in the email body:<br> <strong>{name}</strong> - User's Name <br> <strong>{email}</strong> - User's Email";
                } else if (emailSelect.value === 'admin-user-update') {
                    instructionText.innerHTML = "You can use the following placeholders in the email body:<br> <strong>{name}</strong> - User's Name <br> <strong>{email}</strong> - User's Email";
                } else if (emailSelect.value === 'admin-user-password-changed') {
                    instructionText.innerHTML = "You can use the following placeholders in the email body:<br> <strong>{name}</strong> - User's Name <br> <strong>{email}</strong> - User's Email <br> <strong>{password}</strong> - User's Password";
                } else if (emailSelect.value === 'password-changed') {
                    instructionText.innerHTML = "You can use the following placeholders in the email body:<br> <strong>{name}</strong> - User's Name <br> <strong>{email}</strong> - User's Email <br> <strong>{password}</strong> - User's Password";
                } else {
                    instructionText.innerHTML = "";
                }
            }

            // Initialize instruction on page load
            updateInstruction();

            // Update instruction when dropdown changes
            emailSelect.addEventListener('change', updateInstruction);

            if (editorElement) {
                ClassicEditor.create(editorElement, {
                    toolbar: [
                        'heading', '|', 'bold', 'italic', '|',
                        'alignment', 'bulletedList', 'numberedList', '|',
                        'link', 'blockQuote', '|',
                        'insertTable', 'tableColumn', 'tableRow', 'mergeTableCells', '|',
                        'undo', 'redo', '|',
                        'indent', 'outdent', '|'
                    ],
                })
                .then(editor => {
                    window.editor = editor;
                })
                .catch(error => {
                    console.error('CKEditor initialization error:', error);
                });
            }
        });
    </script>
@endpush